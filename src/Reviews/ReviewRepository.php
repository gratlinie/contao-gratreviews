<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\Reviews;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Liest Quellen und Bewertungen für die Inhaltselemente. Nur veröffentlichte Quellen und Bewertungen.
 */
class ReviewRepository
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @param list<int> $ids
     *
     * @return list<array<string, mixed>> Quellen in der gewählten Reihenfolge
     */
    public function sources(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $rows = $this->db->fetchAllAssociativeIndexed(
            'SELECT id, title, type, placeName, ratingAverage, ratingCount, profileUrl, reviewsUrl, writeReviewUrl FROM tl_gr_source WHERE published = 1 AND id IN (?)',
            [$ids],
            [ArrayParameterType::INTEGER],
        );

        // Eigene Bewertungen: Note und Anzahl aus den veröffentlichten Einträgen
        foreach ($rows as $id => $row) {
            $rows[$id]['id'] = $id;

            if ('manual' === $row['type']) {
                $stats = $this->db->fetchAssociative('SELECT AVG(rating) AS average, COUNT(*) AS count FROM tl_gr_review WHERE pid = ? AND published = 1', [$id]);
                $rows[$id]['ratingAverage'] = (float) $stats['average'];
                $rows[$id]['ratingCount'] = (int) $stats['count'];
            }
        }

        return array_values(array_filter(array_map(static fn (int $id) => $rows[$id] ?? null, $ids)));
    }

    /**
     * Gesamtnote über alle Quellen, gewichtet nach Anzahl.
     *
     * @param list<array<string, mixed>> $sources
     *
     * @return array{average: float, count: int}
     */
    public function summary(array $sources): array
    {
        $count = array_sum(array_map(static fn (array $s): int => (int) $s['ratingCount'], $sources));
        $sum = array_sum(array_map(static fn (array $s): float => (float) $s['ratingAverage'] * (int) $s['ratingCount'], $sources));

        return ['average' => $count ? round($sum / $count, 1) : 0.0, 'count' => $count];
    }

    /**
     * @param list<int> $sourceIds
     *
     * @return list<array<string, mixed>>
     */
    public function reviews(array $sourceIds, int $limit, int $minRating, bool $onlyText, string $order): array
    {
        if ([] === $sourceIds) {
            return [];
        }

        $orderBy = match ($order) {
            'date' => 'r.date DESC',
            'rating' => 'r.rating DESC, r.date DESC',
            'random' => 'RAND()',
            default => 'r.featured DESC, r.date DESC',
        };

        $sql = 'SELECT r.*, s.type AS sourceType, s.title AS sourceTitle FROM tl_gr_review r JOIN tl_gr_source s ON s.id = r.pid'
            .' WHERE s.published = 1 AND r.published = 1 AND r.pid IN (?) AND r.rating >= ?'
            .($onlyText ? " AND TRIM(COALESCE(r.text, '')) != ''" : '')
            .' ORDER BY '.$orderBy
            .($limit > 0 ? ' LIMIT '.$limit : '');

        return $this->db->fetchAllAssociative($sql, [$sourceIds, $minRating], [ArrayParameterType::INTEGER, ParameterType::INTEGER]);
    }
}
