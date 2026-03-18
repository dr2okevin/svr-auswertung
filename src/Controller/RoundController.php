<?php

namespace App\Controller;

use App\Entity\Competition;
use App\Entity\Round;
use App\Enum\CompetitionType;
use App\Form\RoundType;
use App\Repository\CompetitionRepository;
use App\Repository\RoundRepository;
use App\Service\CompetitionContextProvider;
use App\Service\CompetitionRoundManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RoundController extends AbstractController
{
    public function __construct(
        private readonly RoundRepository $roundRepository,
        private readonly CompetitionContextProvider $competitionContextProvider,
        private readonly CompetitionRepository $competitionRepository,
        private readonly CompetitionRoundManager $competitionRoundManager,
    ) {
    }

    #[Route('/rounds', name: 'rounds_list', methods: ['GET'])]
    public function listRounds(): Response
    {
        $competition = $this->getSelectedRoundsCompetition();

        if ($competition instanceof Response) {
            return $competition;
        }

        return $this->render('round/list.html.twig', [
            'competition' => $competition,
            'rounds' => $this->roundRepository->findBy(['Competition' => $competition], ['StartDate' => 'ASC', 'Name' => 'ASC']),
        ]);
    }

    #[Route('/rounds/new', name: 'rounds_new', methods: ['GET', 'POST'])]
    public function newRound(Request $request, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedRoundsCompetition();

        if ($competition instanceof Response) {
            return $competition;
        }

        $round = $this->competitionRoundManager->createRoundFromCompetitionWindow($competition);
        $form = $this->createForm(RoundType::class, $round);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $competition->addRound($round);
            $entityManager->persist($round);
            $entityManager->flush();

            return $this->redirectToRoute('rounds_list');
        }

        return $this->render('round/new.html.twig', [
            'competition' => $competition,
            'round' => $round,
            'form' => $form,
        ]);
    }

    #[Route('/rounds/{id}/edit', name: 'rounds_edit', methods: ['GET', 'POST'])]
    public function editRound(Request $request, Round $round, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedRoundsCompetition();

        if ($competition instanceof Response || $round->getCompetition()?->getId() !== $competition->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(RoundType::class, $round);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('rounds_list');
        }

        return $this->render('round/edit.html.twig', [
            'competition' => $competition,
            'round' => $round,
            'form' => $form,
        ]);
    }

    #[Route('/rounds/{id}/delete', name: 'rounds_delete', methods: ['POST'])]
    public function deleteRound(Request $request, Round $round, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedRoundsCompetition();

        if ($competition instanceof Competition
            && $round->getCompetition()?->getId() === $competition->getId()
            && $this->isCsrfTokenValid('delete_round_' . $round->getId(), (string) $request->request->get('_token'))
        ) {
            if ($competition->getRounds()->count() <= 1) {
                $this->addFlash('error', 'Ein Rundenwettkampf muss mindestens eine Runde enthalten.');
            } else {
                $entityManager->remove($round);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('rounds_list');
    }

    private function getSelectedRoundsCompetition(): Competition|Response
    {
        $selectedCompetitionId = $this->competitionContextProvider->getSelectedCompetitionId();

        if ($selectedCompetitionId === null) {
            $this->addFlash('error', 'Bitte zuerst einen Wettkampf auswählen.');

            return $this->redirectToRoute('competitions_list');
        }

        $competition = $this->competitionRepository->find($selectedCompetitionId);

        if (!$competition instanceof Competition) {
            $this->addFlash('error', 'Der ausgewählte Wettkampf wurde nicht gefunden.');

            return $this->redirectToRoute('competitions_list');
        }

        if ($competition->getType() !== CompetitionType::ROUNDS) {
            $this->addFlash('error', 'Runden können nur für Rundenwettkämpfe gepflegt werden.');

            return $this->redirectToRoute('competitions_list');
        }

        return $competition;
    }
}
