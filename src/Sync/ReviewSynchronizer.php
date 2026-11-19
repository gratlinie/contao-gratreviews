<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\Sync;

use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Gratlinie\GratReviewsBundle\Google\PlacesClient;
use Gratlinie\GratReviewsBundle\Google\PlacesException;
use Gratlinie\GratReviewsBundle\ProvenExpert\ProvenExpertClient;
use Gratlinie\GratReviewsBundle\ProvenExpert\ProvenExpertException;
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
 *
 * ProvenExpert-Quellen: Gesamtnote und Anzahl aus rating/summary/get (jedes Paket), die Bewertungen selbst
 * vollständig aus rating/get (ab PREMIUM). Bewertungen, die ProvenExpert nicht mehr oder als unveröffentlicht
 * liefert, werden gelöscht (erscheinen sie wieder, legt der nächste Abruf sie neu an). E-Mail-Adressen der
 * Bewertenden werden nicht gespeichert.
 */
class ReviewSynchronizer
{
    private const DEFAULT_AVATAR_FOLDER = 'bewertungen';

    private const MAX_PAGES = 50;

    public function __construct(
        private readonly Connection $db,
        private readonly PlacesClient $places,
        private readonly ProvenExpertClient $provenExpert,
        private readonly HttpClientInterface $httpClient,
        #[Autowire(service: 'contao.filesystem.virtual.files')]
        private readonly VirtualFilesystemInterface $files,
        private readonly CacheTagManager $cacheTagManager,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    /**
     * @return list<int> IDs der abrufbaren Quellen (Google, ProvenExpert), deren Abruf fällig ist
     */
    public function dueSources(int $now): array
    {
        return array_map('intval', $this->db->fetchFirstColumn(
            "SELECT id FROM tl_gr_source WHERE published = 1 AND lastSync + syncInterval <= ? AND ((type = 'google' AND googlePlaceId != '') OR (type = 'provenexpert' AND peApiId != '' AND peApiKey != ''))",
            [$now],
        ));
    }

    public function sync(int $sourceId): SyncResult
    {
        $source = $this->db->fetchAssociative('SELECT * FROM tl_gr_source WHERE id = ?', [$sourceId]);

        return match ($source['type'] ?? null) {
            'google' => $this->syncGoogle($source),
            'provenexpert' => $this->syncProvenExpert($source),
            default => SyncResult::failed('Keine abrufbare Quelle mit dieser ID.'),
        };
    }

    private function syncGoogle(array $source): SyncResult
    {
        $sourceId = (int) $source['id'];

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

    private function syncProvenExpert(array $source): SyncResult
    {
        $sourceId = (int) $source['id'];

        try {
            $summary = $this->provenExpert->summary($source['peApiId'], $source['peApiKey']);
        } catch (ProvenExpertException $exception) {
            $this->logger?->error('GratReviews: Abruf von Quelle '.$sourceId.' fehlgeschlagen – '.$exception->getMessage());

            return $this->finish($sourceId, SyncResult::failed($exception->getMessage()));
        }

        $this->db->update('tl_gr_source', [
            'ratingAverage' => round($summary['ratingValue'], 2),
            'ratingCount' => $summary['reviewCount'],
        ], ['id' => $sourceId]);

        $ok = static fn (int $created, int $updated, string $note = ''): SyncResult => SyncResult::ok($created, $updated, $summary['ratingValue'], $summary['reviewCount'], 'ProvenExpert', $note);

        // Einzelne Bewertungen gibt es erst ab Paket PREMIUM – ohne sie bleibt es bei Note und Anzahl
        $ratings = [];
        $complete = false;

        try {
            for ($page = 0; $page < self::MAX_PAGES; ++$page) {
                $batch = $this->provenExpert->ratings($source['peApiId'], $source['peApiKey'], $page * ProvenExpertClient::PAGE_SIZE);
                $ratings += $batch;

                if (\count($batch) < ProvenExpertClient::PAGE_SIZE) {
                    $complete = true;
                    break;
                }
            }
        } catch (ProvenExpertException $exception) {
            $this->logger?->warning('GratReviews: Bewertungen von Quelle '.$sourceId.' nicht abrufbar – '.$exception->getMessage());

            return $this->finish($sourceId, $ok(0, 0, 'Einzelne Bewertungen nicht abrufbar (erst ab ProvenExpert-Paket PREMIUM) – '.$exception->getMessage()));
        }

        $created = 0;
        $updated = 0;
        $seen = [];

        foreach ($ratings as $id => $rating) {
            $externalId = (string) $id;

            if ('' === $externalId || !\is_array($rating)) {
                continue;
            }

            // Auf ProvenExpert unveröffentlicht: wie gelöscht behandeln
            if ('published' !== ($rating['status'] ?? 'published')) {
                continue;
            }

            $seen[] = $externalId;
            $existing = $this->db->fetchAssociative('SELECT id FROM tl_gr_review WHERE pid = ? AND externalId = ?', [$sourceId, $externalId]);

            $row = [
                'tstamp' => time(),
                'author' => mb_substr($this->shortName((string) ($rating['user']['name'] ?? '')), 0, 128),
                'rating' => max(1, min(5, (int) round((float) ($rating['ratingValue'] ?? 0)))),
                'date' => (int) ($rating['created'] ?? time()),
                'text' => trim((string) ($rating['feedback'] ?? '')),
            ];

            if (false === $existing) {
                $this->db->insert('tl_gr_review', $row + ['pid' => $sourceId, 'externalId' => $externalId, 'published' => 1]);
                ++$created;
            } else {
                // „Veröffentlicht“ und „Hervorheben“ bleiben, wie im Backend gesetzt
                $this->db->update('tl_gr_review', $row, ['id' => $existing['id']]);
                ++$updated;
            }
        }

        // Nur nach vollständigem Abruf und nie bei leerer Antwort trotz Bewertungen: Bewertungen, die ProvenExpert
        // nicht mehr (öffentlich) führt, löschen. Von Hand angelegte Einträge (ohne externalId) bleiben.
        $deleted = 0;

        if ($complete && ([] !== $seen || 0 === $summary['reviewCount'])) {
            $deleted = $this->db->executeStatement(
                "DELETE FROM tl_gr_review WHERE pid = ? AND externalId != ''".($seen ? ' AND externalId NOT IN (?)' : ''),
                $seen ? [$sourceId, $seen] : [$sourceId],
                $seen ? [ParameterType::INTEGER, ArrayParameterType::STRING] : [],
            );
        }

        $notes = array_filter([
            $deleted ? \sprintf('%d entfernt (auf ProvenExpert gelöscht oder unveröffentlicht).', $deleted) : '',
            $complete ? '' : \sprintf('Nur die neuesten %d Bewertungen abgerufen.', \count($ratings)),
        ]);

        return $this->finish($sourceId, $ok($created, $updated, implode(' ', $notes)));
    }

    /**
     * Nachname abkürzen („Max Mustermann“ → „Max M.“): ProvenExpert liefert über die API den vollständigen
     * Namen, auf dem Profil erscheint er je nach Einstellung gekürzt oder anonym.
     */
    private function shortName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (\count($parts) < 2) {
            return implode(' ', $parts);
        }

        return $parts[0].' '.mb_strtoupper(mb_substr((string) end($parts), 0, 1)).'.';
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
