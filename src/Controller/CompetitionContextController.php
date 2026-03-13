<?php

namespace App\Controller;

use App\Service\CompetitionContextProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CompetitionContextController extends AbstractController
{
    public function __construct(private readonly CompetitionContextProvider $competitionContextProvider)
    {
    }
    #[Route('/competition-context/switch', name: 'competition_context_switch', methods: ['GET'])]
    public function switch(Request $request): RedirectResponse
    {
        $competitionId = $request->query->getInt('competition_id');
        $this->competitionContextProvider->switchCompetition($competitionId > 0 ? $competitionId : null);

        $referer = $request->headers->get('referer');

        if (is_string($referer) && $referer !== '') {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('clubs_list');
    }
}
