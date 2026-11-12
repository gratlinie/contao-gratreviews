<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Contao\Message;
use Contao\StringUtil;
use Gratlinie\GratReviewsBundle\Google\PlacesClient;
use Gratlinie\GratReviewsBundle\Google\PlacesException;
use Gratlinie\GratReviewsBundle\Sync\ReviewSynchronizer;

/**
 * Profil auswählen: Mit API-Schlüssel und Suchbegriff (Firmenname, Ort) sucht die Textsuche von Google
 * passende Einträge; die Treffer stehen mit Adresse in der Auswahl. Nach dem Speichern eines neuen Profils
 * werden die Bewertungen sofort abgerufen.
 */
class SourceListener
{
    /** @var array<string, array<string, string>> */
    private array $searchCache = [];

    private bool $placeChanged = false;

    public function __construct(
        private readonly PlacesClient $places,
        private readonly ReviewSynchronizer $synchronizer,
    ) {
    }

    /**
     * @return array<string, string>
     */
    #[AsCallback(table: 'tl_gr_source', target: 'fields.googlePlaceId.options')]
    public function placeOptions(DataContainer|null $dc = null): array
    {
        $record = $dc?->activeRecord;

        if (null === $record) {
            return [];
        }

        $options = [];

        // Das gewählte Profil bleibt in der Auswahl, auch wenn der Suchbegriff inzwischen etwas anderes findet
        if ($record->googlePlaceId) {
            $options[$record->googlePlaceId] = $record->placeName ? $this->label($record->placeName, $record->placeAddress) : $record->googlePlaceId;
        }

        if (!$record->googleApiKey || !$record->googleQuery) {
            return $options;
        }

        $key = $record->googleQuery.'|'.$record->language;

        if (!isset($this->searchCache[$key])) {
            $this->searchCache[$key] = [];

            try {
                foreach ($this->places->search($record->googleApiKey, $record->googleQuery, $record->language) as $place) {
                    $this->searchCache[$key][$place['id']] = $this->label($place['name'], $place['address']);
                }

                if ([] === $this->searchCache[$key]) {
                    Message::addInfo($GLOBALS['TL_LANG']['tl_gr_source']['noResults'] ?? 'Keine Treffer.');
                }
            } catch (PlacesException $exception) {
                Message::addError($exception->getMessage());
            }
        }

        return $options + $this->searchCache[$key];
    }

    #[AsCallback(table: 'tl_gr_source', target: 'fields.googlePlaceId.save')]
    public function placeSaved(string $value, DataContainer $dc): string
    {
        $this->placeChanged = '' !== $value && $value !== $dc->activeRecord?->googlePlaceId;

        return $value;
    }

    #[AsCallback(table: 'tl_gr_source', target: 'config.onsubmit')]
    public function onSubmit(DataContainer $dc): void
    {
        if (!$this->placeChanged || 'google' !== $dc->activeRecord?->type) {
            return;
        }

        $result = $this->synchronizer->sync((int) $dc->id);
        $result->success ? Message::addConfirmation($result->message()) : Message::addError($result->message());
    }

    /**
     * @return list<string>
     */
    #[AsCallback(table: 'tl_gr_source', target: 'list.label.label')]
    public function listLabel(array $row, string $label, DataContainer $dc, array $columns): array
    {
        $isGoogle = 'google' === $row['type'];
        $columns[2] = $isGoogle && $row['ratingCount'] ? number_format((float) $row['ratingAverage'], 1, ',', '').' ★' : '–';
        $columns[3] = $isGoogle ? (string) $row['ratingCount'] : '–';
        $columns[4] = $row['lastSync'] ? Date::parse(Date::getNumericDatimFormat(), (int) $row['lastSync']).(str_starts_with((string) $row['lastStatus'], 'Fehler') ? ' ⚠' : '') : '–';

        return $columns;
    }

    #[AsCallback(table: 'tl_gr_review', target: 'list.sorting.child_record')]
    public function childRecord(array $row): string
    {
        $stars = str_repeat('★', (int) $row['rating']).'<span style="opacity:.3">'.str_repeat('★', 5 - (int) $row['rating']).'</span>';
        $text = StringUtil::substr(strip_tags((string) $row['text']), 180);

        return \sprintf(
            '<div><span style="color:#e8a317">%s</span> <strong>%s</strong> <span style="color:#999">– %s%s</span></div>%s',
            $stars,
            StringUtil::specialchars($row['author']),
            Date::parse(Date::getNumericDateFormat(), (int) $row['date']),
            $row['featured'] ? ' · ★ hervorgehoben' : '',
            '' !== $text ? '<div style="margin-top:4px;color:#666">'.StringUtil::specialchars($text).'</div>' : '',
        );
    }

    private function label(string $name, string $address): string
    {
        return $address ? $name.' – '.$address : $name;
    }
}
