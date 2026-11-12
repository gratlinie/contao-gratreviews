<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\Sync;

use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Doctrine\DBAL\Connection;
use Gratlinie\GratReviewsBundle\Google\PlacesClient;
use Gratlinie\GratReviewsBundle\Google\PlacesException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Gleicht eine Google-Quelle mit der Places API ab: Gesamtnote, Anzahl und Links an der Quelle,
 * neue Bewertungen anlegen, bekannte aktualisieren. Bewertungen, die Google nicht mehr unter den 5
 * relevantesten liefert, bleiben erhalten – so wächst der Bestand mit jedem Abruf.
 *
 * Profilbilder der Bewertenden werden einmal heruntergeladen und lokal ausgeliefert, damit Besucher keine
 * Anfrage an Google-Server schicken.
 */
class ReviewSynchronizer
{
    private const DEFAULT_AVATAR_FOLDER = 'bewertungen';

    public function __construct(
        private readonly Connection $db,
        private readonly PlacesClient $places,
        private readonly HttpClientInterface $httpClient,
        #[Autowire(service: 'contao.filesystem.virtual.files')]
        private readonly VirtualFilesystemInterface $files,
        private readonly CacheTagManager $cacheTagManager,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    /**
     * @return list<int> IDs der Google-Quellen, deren Abruf fällig ist
     */
    public function dueSources(int $now): array
    {
        return array_map('intval', $this->db->fetchFirstColumn(
            "SELECT id FROM tl_gr_source WHERE type = 'google' AND published = 1 AND googlePlaceId != '' AND lastSync + syncInterval <= ?",
            [$now],
        ));
    }

    public function sync(int $sourceId): SyncResult
    {
        $source = $this->db->fetchAssociative('SELECT * FROM tl_gr_source WHERE id = ?', [$sourceId]);

        if (false === $source || 'google' !== $source['type']) {
            return SyncResult::failed('Keine Google-Quelle mit dieser ID.');
        }

        if ('' === $source['googlePlaceId']) {
            return $this->finish($sourceId, SyncResult::failed('Noch kein Unternehmensprofil gewählt.'));
        }

        try {
            $place = $this->places->details($source['googleApiKey'], $source['googlePlaceId'], $source['language']);
        } catch (PlacesException $exception) {
            $this->logger?->error('GratReviews: Abruf von Quelle '.$sourceId.' fehlgeschlagen – '.$exception->getMessage());

            return $this->finish($sourceId, SyncResult::failed($exception->getMessage()));
        }

        $this->db->update('tl_gr_source', [
            'placeName' => (string) ($place['displayName']['text'] ?? ''),
            'placeAddress' => (string) ($place['formattedAddress'] ?? ''),
            'ratingAverage' => round((float) ($place['rating'] ?? 0), 2),
            'ratingCount' => (int) ($place['userRatingCount'] ?? 0),
            'profileUrl' => (string) ($place['googleMapsUri'] ?? ''),
            'reviewsUrl' => (string) ($place['googleMapsLinks']['reviewsUri'] ?? ''),
            'writeReviewUrl' => (string) ($place['googleMapsLinks']['writeAReviewUri'] ?? ''),
        ], ['id' => $sourceId]);

        $created = 0;
        $updated = 0;

        foreach ($place['reviews'] ?? [] as $review) {
            $externalId = (string) ($review['name'] ?? '');

            if ('' === $externalId) {
                continue;
            }

            // Originaltext statt der Übersetzung von Google
            $text = $review['originalText'] ?? $review['text'] ?? [];
            $author = $review['authorAttribution'] ?? [];

            $row = [
                'tstamp' => time(),
                'author' => mb_substr((string) ($author['displayName'] ?? ''), 0, 128),
                'authorUrl' => (string) ($author['uri'] ?? ''),
                'rating' => max(1, min(5, (int) round((float) ($review['rating'] ?? 0)))),
                'date' => (int) strtotime((string) ($review['publishTime'] ?? 'now')),
                'text' => trim((string) ($text['text'] ?? '')),
                'language' => (string) ($text['languageCode'] ?? ''),
                'reviewUrl' => (string) ($review['googleMapsUri'] ?? ''),
            ];

            $existing = $this->db->fetchAssociative('SELECT id, avatar FROM tl_gr_review WHERE pid = ? AND externalId = ?', [$sourceId, $externalId]);

            if (null === ($existing['avatar'] ?? null) && !empty($author['photoUri'])) {
                $row['avatar'] = $this->storeAvatar($source, (string) $author['photoUri'], $externalId);
            }

            if (false === $existing) {
                $this->db->insert('tl_gr_review', $row + ['pid' => $sourceId, 'externalId' => $externalId, 'published' => 1]);
                ++$created;
            } else {
                $this->db->update('tl_gr_review', $row, ['id' => $existing['id']]);
                ++$updated;
            }
        }

        return $this->finish($sourceId, SyncResult::ok($created, $updated, (float) ($place['rating'] ?? 0), (int) ($place['userRatingCount'] ?? 0)));
    }

    private function finish(int $sourceId, SyncResult $result): SyncResult
    {
        $this->db->update('tl_gr_source', ['lastSync' => time(), 'lastStatus' => $result->message()], ['id' => $sourceId]);
        $this->cacheTagManager->invalidateTags(['contao.db.tl_gr_source.'.$sourceId]);

        return $result;
    }

    /**
     * @return string|null UUID (binär) der lokalen Kopie
     */
    private function storeAvatar(array $source, string $url, string $externalId): string|null
    {
        try {
            $response = $this->httpClient->request('GET', $url, ['timeout' => 10]);

            if (200 !== $response->getStatusCode()) {
                return null;
            }

            $contents = $response->getContent();
            $extension = match (strtok($response->getHeaders()['content-type'][0] ?? '', ';')) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
        } catch (\Throwable $exception) {
            $this->logger?->warning('GratReviews: Profilbild nicht geladen – '.$exception->getMessage());

            return null;
        }

        $folder = $this->avatarFolder($source);
        $path = $folder.'/gr-'.substr(md5($externalId), 0, 16).'.'.$extension;
        $this->files->write($path, $contents);

        return $this->files->get($path)?->getUuid()?->toBinary();
    }

    private function avatarFolder(array $source): string
    {
        if (null !== $source['avatarFolder']) {
            try {
                return $this->files->resolveUuid(Uuid::fromBinary($source['avatarFolder']));
            } catch (\Throwable) {
            }
        }

        if (!$this->files->directoryExists(self::DEFAULT_AVATAR_FOLDER)) {
            $this->files->createDirectory(self::DEFAULT_AVATAR_FOLDER);
        }

        return self::DEFAULT_AVATAR_FOLDER;
    }
}
