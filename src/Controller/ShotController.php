<?php

namespace App\Controller;

use App\Entity\Competition;
use App\Entity\Series;
use App\Entity\Shot;
use App\Form\ShotType;
use App\Repository\CompetitionRepository;
use App\Repository\RoundRepository;
use App\Repository\SeriesRepository;
use App\Repository\ShotRepository;
use App\Service\CompetitionContextProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShotController extends AbstractController
{
    public function __construct(
        private readonly CompetitionContextProvider $competitionContextProvider,
        private readonly CompetitionRepository $competitionRepository,
        private readonly RoundRepository $roundRepository,
        private readonly SeriesRepository $seriesRepository,
        private readonly ShotRepository $shotRepository,
    ) {
    }

    #[Route('/shots', name: 'shots_person_list', methods: ['GET'])]
    public function listPersons(Request $request): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null) {
            $this->addFlash('error', 'Bitte zuerst einen Wettkampf auswählen.');

            return $this->redirectToRoute('competitions_list');
        }

        $rounds = $this->roundRepository->findBy(['Competition' => $competition], ['StartDate' => 'ASC', 'Name' => 'ASC']);
        $selectedRound = null;

        $selectedRoundId = $request->query->getInt('round', 0);
        if ($selectedRoundId > 0) {
            foreach ($rounds as $round) {
                if ($round->getId() === $selectedRoundId) {
                    $selectedRound = $round;
                    break;
                }
            }
        }

        if ($selectedRound === null && $rounds !== []) {
            $selectedRound = $rounds[0];
        }

        $seriesByDiscipline = [];

        if ($selectedRound !== null) {
            $series = $this->seriesRepository->findBy(['Round' => $selectedRound], ['id' => 'ASC']);

            foreach ($series as $entry) {
                $discipline = $entry->getDiscipline();
                if ($discipline === null) {
                    continue;
                }

                $disciplineName = (string) $discipline->getName();
                if (!isset($seriesByDiscipline[$disciplineName])) {
                    $seriesByDiscipline[$disciplineName] = [];
                }

                $shotCount = $this->shotRepository->count(['Series' => $entry]);
                $totalShots = ($discipline->getShotsPerSeries() ?? 0) * ($discipline->getMaxSeriesCount() ?? 0);
                if ($totalShots <= 0) {
                    $totalShots = $discipline->getShotsPerSeries() ?? 0;
                }

                $personName = trim(sprintf('%s %s', $entry->getPerson()?->getFristName() ?? '', $entry->getPerson()?->getLastName() ?? ''));

                $seriesByDiscipline[$disciplineName][] = [
                    'series' => $entry,
                    'personName' => $personName,
                    'disciplineName' => $disciplineName,
                    'teamName' => $entry->getTeam()?->getName() ?? '-',
                    'shotCount' => $shotCount,
                    'totalShots' => $totalShots,
                ];
            }

            ksort($seriesByDiscipline);
        }

        return $this->render('shot/person_list.html.twig', [
            'competition' => $competition,
            'rounds' => $rounds,
            'selectedRound' => $selectedRound,
            'seriesByDiscipline' => $seriesByDiscipline,
        ]);
    }

    #[Route('/shots/series/{id}/edit', name: 'shots_series_edit', methods: ['GET'])]
    public function editSeries(Series $series): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null || !$this->seriesBelongsToCompetition($series, $competition)) {
            throw $this->createNotFoundException();
        }

        return $this->render('shot/edit_series.html.twig', [
            'competition' => $competition,
            'series' => $series,
            'shots' => $this->shotRepository->findBy(['Series' => $series], ['ShotIndex' => 'ASC', 'RecordTime' => 'ASC']),
        ]);
    }

    #[Route('/shots/series/{id}/new', name: 'shots_new', methods: ['GET', 'POST'])]
    public function newShot(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null || !$this->seriesBelongsToCompetition($series, $competition)) {
            throw $this->createNotFoundException();
        }

        $shot = new Shot();
        $shot->setSeries($series);
        $shot->setRecordTime(new \DateTime());

        $form = $this->createForm(ShotType::class, $shot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($shot);
            $entityManager->flush();

            $this->syncSeriesShotCount($series, $entityManager);

            return $this->redirectToRoute('shots_series_edit', ['id' => $series->getId()]);
        }

        return $this->render('shot/new.html.twig', [
            'series' => $series,
            'shot' => $shot,
            'form' => $form,
        ]);
    }

    #[Route('/shots/{id}/edit', name: 'shots_edit', methods: ['GET', 'POST'])]
    public function editShot(Request $request, Shot $shot, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null || !$this->shotBelongsToCompetition($shot, $competition)) {
            throw $this->createNotFoundException();
        }

        $series = $shot->getSeries();
        if (!$series instanceof Series) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ShotType::class, $shot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('shots_series_edit', ['id' => $series->getId()]);
        }

        return $this->render('shot/edit.html.twig', [
            'series' => $series,
            'shot' => $shot,
            'form' => $form,
        ]);
    }

    #[Route('/shots/{id}/delete', name: 'shots_delete', methods: ['POST'])]
    public function deleteShot(Request $request, Shot $shot, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition !== null
            && $this->shotBelongsToCompetition($shot, $competition)
            && $this->isCsrfTokenValid('delete_shot_' . $shot->getId(), (string) $request->request->get('_token'))
        ) {
            $series = $shot->getSeries();

            $entityManager->remove($shot);
            $entityManager->flush();

            if ($series instanceof Series) {
                $this->syncSeriesShotCount($series, $entityManager);

                return $this->redirectToRoute('shots_series_edit', ['id' => $series->getId()]);
            }
        }

        return $this->redirectToRoute('shots_person_list');
    }

    private function getSelectedCompetition(): ?Competition
    {
        $selectedCompetitionId = $this->competitionContextProvider->getSelectedCompetitionId();

        if ($selectedCompetitionId === null) {
            return null;
        }

        return $this->competitionRepository->find($selectedCompetitionId);
    }

    private function seriesBelongsToCompetition(Series $series, Competition $competition): bool
    {
        return $series->getRound()?->getCompetition()?->getId() === $competition->getId();
    }

    private function shotBelongsToCompetition(Shot $shot, Competition $competition): bool
    {
        $series = $shot->getSeries();

        if (!$series instanceof Series) {
            return false;
        }

        return $this->seriesBelongsToCompetition($series, $competition);
    }

    private function syncSeriesShotCount(Series $series, EntityManagerInterface $entityManager): void
    {
        $series->setShotsCount($this->shotRepository->count(['Series' => $series]));
        $entityManager->flush();
    }
}
