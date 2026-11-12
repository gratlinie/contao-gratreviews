<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\Sync;

final class SyncResult
{
    private function __construct(
        public readonly bool $success,
        private readonly string $message,
    ) {
    }

    public static function ok(int $created, int $updated, float $rating, int $count): self
    {
        return new self(true, \sprintf('OK – %d neu, %d aktualisiert; Google: %s ★ aus %d Bewertungen', $created, $updated, number_format($rating, 1, ',', ''), $count));
    }

    public static function failed(string $reason): self
    {
        return new self(false, 'Fehler – '.$reason);
    }

    public function message(): string
    {
        return $this->message;
    }
}
