<?php

namespace App\Controller;

use App\Service\CompetitionContextProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CompetitionContextController extends AbstractController
{
    #[Route('/competition-context/switch', name: 'competition_context_switch', methods: ['GET'])]
    public function switch(Request $request, CompetitionContextProvider $competitionContextProvider): RedirectResponse
    {
        $competitionId = $request->query->getInt('competition_id');
        $competitionContextProvider->switchCompetition($competitionId > 0 ? $competitionId : null);

        $referer = $request->headers->get('referer');

        if (is_string($referer) && $referer !== '') {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('clubs_list');
    }
}
