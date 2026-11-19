<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\ProvenExpert;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Zugriff auf die ProvenExpert-API (v1) mit API-ID und API-Key des Profils (Administration → Developer API).
 * Alle Aufrufe sind POST mit Basic Auth, Antworten JSON mit "status": "success" oder "error".
 *
 * rating/summary/get gibt es in jedem Paket, die einzelnen Bewertungen (rating/get) nur ab PREMIUM.
 *
 * GRATREVIEWS_PROVENEXPERT_URL ersetzt die Basis-URL (Tests gegen einen Mock-Server, Proxy).
 */
class ProvenExpertClient
{
    private const BASE = 'https://www.provenexpert.com/api/v1/';

    public const PAGE_SIZE = 100;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(default::GRATREVIEWS_PROVENEXPERT_URL)%')]
        private readonly string|null $baseUrl = null,
    ) {
    }

    /**
     * @return array{ratingValue: float, reviewCount: int}
     */
    public function summary(string $apiId, string $apiKey): array
    {
        $data = $this->request('rating/summary/get', $apiId, $apiKey);

        return [
            'ratingValue' => (float) ($data['ratingValue'] ?? 0),
            'reviewCount' => (int) ($data['reviewCount'] ?? 0),
        ];
    }

    /**
     * Eine Seite Bewertungen, neueste zuerst.
     *
     * @return array<string, array<string, mixed>> Bewertungen nach ID
     */
    public function ratings(string $apiId, string $apiKey, int $offset): array
    {
        $data = $this->request('rating/get', $apiId, $apiKey, ['limit' => self::PAGE_SIZE, 'offset' => $offset]);

        return \is_array($data['ratings'] ?? null) ? $data['ratings'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $path, string $apiId, string $apiKey, array $body = []): array
    {
        if ('' === trim($apiId) || '' === trim($apiKey)) {
            throw new ProvenExpertException('API-ID und API-Key fehlen.');
        }

        try {
            $response = $this->httpClient->request('POST', ($this->baseUrl ?: self::BASE).$path, [
                'auth_basic' => [$apiId, $apiKey],
                'body' => $body,
                'timeout' => 20,
            ]);
            $status = $response->getStatusCode();
            $data = json_decode($response->getContent(false), true);
        } catch (ExceptionInterface $exception) {
            throw new ProvenExpertException('ProvenExpert nicht erreichbar: '.$exception->getMessage(), 0, $exception);
        }

        if (!\is_array($data) || !isset($data['status'])) {
            throw new ProvenExpertException(\sprintf('ProvenExpert meldet %d ohne gültige Antwort.', $status));
        }

        if ('success' !== $data['status']) {
            $errors = array_filter(array_map('strval', (array) ($data['errors'] ?? [])));

            throw new ProvenExpertException('ProvenExpert meldet: '.($errors ? implode('; ', $errors) : 'unbekannter Fehler'));
        }

        return $data;
    }
}
