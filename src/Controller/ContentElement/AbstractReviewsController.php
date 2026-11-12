<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Gratlinie\GratReviewsBundle\Reviews\ReviewRepository;

/**
 * Gemeinsames der Bewertungs-Elemente: gewählte Quellen, Hinweis zur Echtheit (§ 5b Abs. 3 UWG) und
 * Cache-Tags je Quelle, damit ein Abruf oder eine Änderung im Backend die Seite neu erzeugt.
 */
abstract class AbstractReviewsController extends AbstractContentElementController
{
    public function __construct(
        protected readonly ReviewRepository $reviews,
        private readonly CacheTagManager $cacheTagManager,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function sources(ContentModel $model): array
    {
        $ids = array_map('intval', StringUtil::deserialize($model->grSources, true));
        $this->cacheTagManager->tagWith(array_map(static fn (int $id): string => 'contao.db.tl_gr_source.'.$id, $ids));

        return $this->reviews->sources($ids);
    }

    protected function addNotice(FragmentTemplate $template, ContentModel $model, array $sources): void
    {
        if (!$model->grShowNotice) {
            $template->set('gr_notice', '');

            return;
        }

        $notice = trim((string) $model->grNotice);

        if ('' === $notice) {
            $types = array_unique(array_column($sources, 'type'));
            $notice = $GLOBALS['TL_LANG']['MSC']['gr_notice_'.(1 === \count($types) ? $types[0] : 'mixed')] ?? '';
        }

        $template->set('gr_notice', $notice);
    }
}
