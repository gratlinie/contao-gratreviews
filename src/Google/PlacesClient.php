<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\Google;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Zugriff auf die Google Places API (New) mit dem API-Schlüssel aus dem Cloud-Projekt des Kunden.
 *
 * Place Details liefert Gesamtnote, Anzahl und höchstens 5 Bewertungen (von Google nach Relevanz
 * ausgewählt, eine Sortierung nach Datum gibt es in der neuen API nicht).
 */
class PlacesClient
{
    private const BASE = 'https://places.googleapis.com/v1/';

    private const DETAILS_FIELDS = 'id,displayName,formattedAddress,rating,userRatingCount,reviews,googleMapsUri,googleMapsLinks';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * Textsuche für die Auswahl des Profils im Backend.
     *
     * @return list<array{id: string, name: string, address: string}>
     */
    public function search(string $apiKey, string $query, string $language): array
    {
        $data = $this->request('POST', 'places:searchText', $apiKey, 'places.id,places.displayName,places.formattedAddress', [
            'json' => ['textQuery' => $query, 'languageCode' => $language ?: 'de', 'pageSize' => 10],
        ]);

        return array_map(static fn (array $place): array => [
            'id' => (string) $place['id'],
            'name' => (string) ($place['displayName']['text'] ?? ''),
            'address' => (string) ($place['formattedAddress'] ?? ''),
        ], $data['places'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function details(string $apiKey, string $placeId, string $language): array
    {
        return $this->request('GET', 'places/'.rawurlencode($placeId), $apiKey, self::DETAILS_FIELDS, [
            'query' => ['languageCode' => $language ?: 'de'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, string $apiKey, string $fieldMask, array $options): array
    {
        if ('' === trim($apiKey)) {
            throw new PlacesException('Kein API-Schlüssel hinterlegt.');
        }

        $options['headers'] = ['X-Goog-Api-Key' => $apiKey, 'X-Goog-FieldMask' => $fieldMask];

        try {
            $response = $this->httpClient->request($method, self::BASE.$path, $options);
            $data = $response->toArray(false);
            $status = $response->getStatusCode();
        } catch (ExceptionInterface $exception) {
            throw new PlacesException('Google nicht erreichbar: '.$exception->getMessage(), 0, $exception);
        }

        if ($status >= 400) {
            // Fehlermeldung von Google durchreichen, z. B. API nicht aktiviert oder Schlüssel eingeschränkt
            throw new PlacesException(\sprintf('Google meldet %d: %s', $status, $data['error']['message'] ?? 'unbekannter Fehler'));
        }

        return $data;
    }
}
