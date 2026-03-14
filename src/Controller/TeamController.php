<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Form\TeamType;
use App\Repository\CompetitionRepository;
use App\Repository\TeamRepository;
use App\Service\CompetitionContextProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TeamController extends AbstractController
{
    public function __construct(
        private readonly TeamRepository $teamRepository,
        private readonly CompetitionContextProvider $competitionContextProvider,
        private readonly CompetitionRepository $competitionRepository,
    ) {
    }

    #[Route('/teams', name: 'teams_list', methods: ['GET'])]
    public function listTeams(): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null) {
            $this->addFlash('error', 'Bitte zuerst einen Wettkampf auswählen.');

            return $this->redirectToRoute('competitions_list');
        }

        return $this->render('team/list.html.twig', [
            'competition' => $competition,
            'teams' => $this->teamRepository->findBy(['Competition' => $competition], ['Name' => 'ASC']),
        ]);
    }

    #[Route('/teams/new', name: 'teams_new', methods: ['GET', 'POST'])]
    public function newTeam(Request $request, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null) {
            $this->addFlash('error', 'Bitte zuerst einen Wettkampf auswählen.');

            return $this->redirectToRoute('competitions_list');
        }

        $team = new Team();
        $team->setCompetition($competition);
        $team->addTeamMember(new TeamMember());

        $form = $this->createForm(TeamType::class, $team, [
            'disciplines' => $competition->getDisciplines()->toArray(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isTeamSizeValid($competition->getMaxTeamSize(), $team->getTeamMembers()->count())) {
                $this->addFlash('error', 'Maximale Teamgröße für diesen Wettkampf überschritten.');
            } else {
                $entityManager->persist($team);
                $entityManager->flush();

                return $this->redirectToRoute('teams_list');
            }
        }

        return $this->render('team/new.html.twig', [
            'competition' => $competition,
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/teams/{id}/edit', name: 'teams_edit', methods: ['GET', 'POST'])]
    public function editTeam(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null || $team->getCompetition()?->getId() !== $competition->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(TeamType::class, $team, [
            'disciplines' => $competition->getDisciplines()->toArray(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isTeamSizeValid($competition->getMaxTeamSize(), $team->getTeamMembers()->count())) {
                $this->addFlash('error', 'Maximale Teamgröße für diesen Wettkampf überschritten.');
            } else {
                $entityManager->flush();

                return $this->redirectToRoute('teams_list');
            }
        }

        return $this->render('team/edit.html.twig', [
            'competition' => $competition,
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/teams/{id}/delete', name: 'teams_delete', methods: ['POST'])]
    public function deleteTeam(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition !== null && $team->getCompetition()?->getId() === $competition->getId() && $this->isCsrfTokenValid('delete_team_' . $team->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($team);
            $entityManager->flush();
        }

        return $this->redirectToRoute('teams_list');
    }

    private function getSelectedCompetition(): ?\App\Entity\Competition
    {
        $selectedCompetitionId = $this->competitionContextProvider->getSelectedCompetitionId();

        if ($selectedCompetitionId === null) {
            return null;
        }

        return $this->competitionRepository->find($selectedCompetitionId);
    }

    private function isTeamSizeValid(?int $maxTeamSize, int $currentMembers): bool
    {
        if ($maxTeamSize === null) {
            return true;
        }

        return $currentMembers <= $maxTeamSize;
    }
}
