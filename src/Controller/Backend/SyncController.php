<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\Controller\Backend;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Message;
use Gratlinie\GratReviewsBundle\Sync\ReviewSynchronizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Operation „Jetzt abrufen“ in der Liste der Quellen.
 */
#[Route('/%contao.backend.route_prefix%/gratreviews/sync', name: 'gratreviews_sync', defaults: ['_scope' => 'backend'])]
#[IsGranted('contao_user.modules', 'gr_sources')]
class SyncController extends AbstractController
{
    public function __construct(
        private readonly ReviewSynchronizer $synchronizer,
        private readonly ContaoFramework $framework,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $this->framework->initialize();
        $result = $this->synchronizer->sync($request->query->getInt('id'));
        $message = $this->framework->getAdapter(Message::class);

        $result->success ? $message->addConfirmation($result->message()) : $message->addError($result->message());

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('contao_backend', ['do' => 'gr_sources']));
    }
}
