<?php

namespace App\Controller;

use App\Entity\Competition;
use App\Repository\ClubRepository;
use App\Repository\CompetitionRepository;
use App\Repository\DisciplineRepository;
use App\Repository\PersonRepository;
use App\Repository\RoundRepository;
use App\Repository\SeriesRepository;
use App\Repository\ShotRepository;
use App\Repository\TeamRepository;
use App\Service\CompetitionContextProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly CompetitionRepository $competitionRepository,
        private readonly ClubRepository $clubRepository,
        private readonly PersonRepository $personRepository,
        private readonly DisciplineRepository $disciplineRepository,
        private readonly TeamRepository $teamRepository,
        private readonly RoundRepository $roundRepository,
        private readonly SeriesRepository $seriesRepository,
        private readonly ShotRepository $shotRepository,
        private readonly CompetitionContextProvider $competitionContextProvider,
    ) {
    }

    #[Route('', name: 'welcome', methods: ['GET'])]
    public function welcome(): Response
    {
        $selectedCompetition = $this->getSelectedCompetition();

        return $this->render('dashboard/welcome.html.twig', [
            'globalStats' => [
                'wettkaempfe' => $this->competitionRepository->count([]),
                'vereine' => $this->clubRepository->count([]),
                'personen' => $this->personRepository->count([]),
                'disziplinen' => $this->disciplineRepository->count([]),
                'teams' => $this->teamRepository->count([]),
                'runden' => $this->roundRepository->count([]),
                'serien' => $this->seriesRepository->count([]),
                'schuesse' => $this->shotRepository->count([]),
            ],
            'selectedCompetition' => $selectedCompetition,
            'competitionStats' => $selectedCompetition instanceof Competition ? [
                'teams' => $this->teamRepository->count(['Competition' => $selectedCompetition]),
                'runden' => $this->roundRepository->count(['Competition' => $selectedCompetition]),
                'serien' => $this->seriesCountForCompetition($selectedCompetition),
                'schuesse' => $this->shotCountForCompetition($selectedCompetition),
            ] : null,
        ]);
    }

    private function getSelectedCompetition(): ?Competition
    {
        $selectedCompetitionId = $this->competitionContextProvider->getSelectedCompetitionId();

        if ($selectedCompetitionId === null) {
            return null;
        }

        return $this->competitionRepository->find($selectedCompetitionId);
    }

    private function seriesCountForCompetition(Competition $competition): int
    {
        return (int) $this->seriesRepository->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->innerJoin('s.Round', 'r')
            ->where('r.Competition = :competition')
            ->setParameter('competition', $competition)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function shotCountForCompetition(Competition $competition): int
    {
        return (int) $this->shotRepository->createQueryBuilder('shot')
            ->select('COUNT(shot.id)')
            ->innerJoin('shot.Series', 's')
            ->innerJoin('s.Round', 'r')
            ->where('r.Competition = :competition')
            ->setParameter('competition', $competition)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
