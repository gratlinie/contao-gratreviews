<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Übersicht: Gesamtnote mit Sternen und Anzahl (bei Google die Werte des Profils, also über alle
 * Bewertungen), je Quelle eine Zeile und auf Wunsch Links zu Google („Alle Bewertungen“, „Bewertung schreiben“).
 */
#[AsContentElement('gr_summary', category: 'gratreviews')]
class ReviewsSummaryController extends AbstractReviewsController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $sources = $this->sources($model);

        if ([] === $sources) {
            return new Response();
        }

        $links = StringUtil::deserialize($model->grLinks, true);

        $template->set('gr_summary', $this->reviews->summary($sources));
        $template->set('gr_sources', $sources);
        $template->set('gr_show_reviews_link', \in_array('reviews', $links, true));
        $template->set('gr_show_write_link', \in_array('write', $links, true));
        $this->addNotice($template, $model, $sources);

        return $template->getResponse();
    }
}
