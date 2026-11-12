<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Gratlinie\GratReviewsBundle\Sync\ReviewSynchronizer;

/**
 * Prüft stündlich, welche Google-Quellen laut ihrem Intervall fällig sind, und ruft sie ab.
 */
#[AsCronJob('hourly')]
class SyncReviewsCron
{
    public function __construct(private readonly ReviewSynchronizer $synchronizer)
    {
    }

    public function __invoke(): void
    {
        foreach ($this->synchronizer->dueSources(time()) as $sourceId) {
            $this->synchronizer->sync($sourceId);
        }
    }
}
