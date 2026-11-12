<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Date;
use Gratlinie\GratReviewsBundle\Reviews\ReviewRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Liste der Bewertungen als Raster oder Slider: Sterne, Text (lange Texte aufklappbar), Name mit lokal
 * gespeichertem Profilbild, Datum und Herkunft.
 */
#[AsContentElement('gr_list', category: 'gratreviews')]
class ReviewsListController extends AbstractReviewsController
{
    public function __construct(ReviewRepository $reviews, CacheTagManager $cacheTagManager, private readonly Studio $studio)
    {
        parent::__construct($reviews, $cacheTagManager);
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $sources = $this->sources($model);
        $rows = $this->reviews->reviews(array_column($sources, 'id'), (int) $model->grLimit, max(1, (int) $model->grMinRating), (bool) $model->grOnlyText, (string) $model->grOrder);

        if ([] === $rows) {
            return new Response();
        }

        foreach ($rows as &$row) {
            $row['avatar_figure'] = $row['avatar'] ? $this->studio->createFigureBuilder()->fromUuid($row['avatar'])->setSize([96, 96, 'crop'])->buildIfResourceExists() : null;
            $row['date_formatted'] = Date::parse('F Y', (int) $row['date']);
            $row['initials'] = mb_strtoupper(implode('', array_map(static fn (string $part): string => mb_substr($part, 0, 1), \array_slice(preg_split('/\s+/', trim((string) $row['author'])) ?: [], 0, 2))));
        }

        $template->set('gr_reviews', $rows);
        $template->set('gr_layout', 'slider' === $model->grLayout ? 'slider' : 'grid');
        $this->addNotice($template, $model, $sources);

        return $template->getResponse();
    }
}
