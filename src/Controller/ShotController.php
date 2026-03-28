<?php

namespace App\Controller;

use App\Entity\Competition;
use App\Entity\Round;
use App\Entity\Series;
use App\Entity\Shot;
use App\Entity\TeamMember;
use App\Form\ShotBatchType;
use App\Form\ShotType;
use App\Repository\CompetitionRepository;
use App\Repository\RoundRepository;
use App\Repository\SeriesRepository;
use App\Repository\ShotRepository;
use App\Repository\TeamMemberRepository;
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
        private readonly TeamMemberRepository $teamMemberRepository,
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
        $selectedRound = $this->resolveSelectedRound($rounds, $request->query->getInt('round', 0));

        $seriesByDiscipline = [];

        if ($selectedRound !== null) {
            $seriesByDiscipline = $this->buildSeriesEntriesByDiscipline($competition, $selectedRound);
        }

        return $this->render('shot/person_list.html.twig', [
            'competition' => $competition,
            'rounds' => $rounds,
            'selectedRound' => $selectedRound,
            'seriesByDiscipline' => $seriesByDiscipline,
        ]);
    }

    #[Route('/shots/open/{round}/{teamMember}', name: 'shots_series_open', methods: ['GET'])]
    public function openSeries(Round $round, TeamMember $teamMember, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null || $round->getCompetition()?->getId() !== $competition->getId() || $teamMember->getTeam()?->getCompetition()?->getId() !== $competition->getId()) {
            throw $this->createNotFoundException();
        }

        $series = $this->seriesRepository->findOneBy([
            'Round' => $round,
            'Team' => $teamMember->getTeam(),
            'Person' => $teamMember->getPerson(),
            'Discipline' => $teamMember->getDiscipline(),
        ]);

        if (!$series instanceof Series) {
            $series = new Series();
            $series->setRound($round);
            $series->setTeam($teamMember->getTeam());
            $series->setPerson($teamMember->getPerson());
            $series->setDiscipline($teamMember->getDiscipline());
            $series->setShotsCount(0);
            $series->setImportFile(null);

            $entityManager->persist($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('shots_series_edit', ['id' => $series->getId()]);
    }

    #[Route('/shots/series/{id}/edit', name: 'shots_series_edit', methods: ['GET', 'POST'])]
    public function editSeries(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null || !$this->seriesBelongsToCompetition($series, $competition)) {
            throw $this->createNotFoundException();
        }

        $targetShotCount = $this->getTargetShotCount($series);
        $existingShots = $this->shotRepository->findBy(['Series' => $series], ['ShotIndex' => 'ASC']);

        $rows = [];
        $existingShotsByIndex = [];

        foreach ($existingShots as $existingShot) {
            $existingShotsByIndex[$existingShot->getShotIndex() ?? 0] = $existingShot;
        }

        for ($shotIndex = 1; $shotIndex <= $targetShotCount; $shotIndex++) {
            $existingShot = $existingShotsByIndex[$shotIndex] ?? null;

            $rows[] = [
                'ShotIndex' => $shotIndex,
                'value' => $existingShot?->getValue(),
                'XPosition' => $existingShot?->getXPosition(),
                'YPosition' => $existingShot?->getYPosition(),
                'RecordTime' => $existingShot?->getRecordTime() ?? new \DateTime(),
            ];
        }

        $form = $this->createForm(ShotBatchType::class, ['shots' => $rows]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{shots: array<int, array{ShotIndex: int|string, value: float|string|null, XPosition: float|string|null, YPosition: float|string|null, RecordTime: \DateTimeInterface|string|null}>} $data */
            $data = $form->getData();
            $submittedRows = $data['shots'] ?? [];

            foreach ($submittedRows as $row) {
                $shotIndex = (int) ($row['ShotIndex'] ?? 0);

                if ($shotIndex <= 0) {
                    continue;
                }

                $rawValue = $row['value'] ?? null;
                $value = $rawValue === null || $rawValue === '' ? null : (float) $rawValue;
                $recordTime = $row['RecordTime'] ?? null;

                $existingShot = $existingShotsByIndex[$shotIndex] ?? null;

                if ($value === null) {
                    if ($existingShot instanceof Shot) {
                        $entityManager->remove($existingShot);
                    }

                    continue;
                }

                $shot = $existingShot;
                if (!$shot instanceof Shot) {
                    $shot = new Shot();
                    $shot->setSeries($series);
                }

                $shot->setShotIndex($shotIndex);
                $shot->setValue($value);
                $shot->setXPosition($this->toNullableFloat($row['XPosition'] ?? null));
                $shot->setYPosition($this->toNullableFloat($row['YPosition'] ?? null));
                $shot->setRecordTime($recordTime instanceof \DateTimeInterface ? \DateTime::createFromInterface($recordTime) : new \DateTime());

                $entityManager->persist($shot);
            }

            $entityManager->flush();
            $this->syncSeriesShotCount($series, $entityManager);

            $this->addFlash('success', 'Schüsse wurden gespeichert.');

            return $this->redirectToRoute('shots_series_edit', ['id' => $series->getId()]);
        }

        return $this->render('shot/edit_series.html.twig', [
            'competition' => $competition,
            'series' => $series,
            'form' => $form,
            'targetShotCount' => $targetShotCount,
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
        $shot->setShotIndex($this->shotRepository->count(['Series' => $series]) + 1);

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

    /**
     * @param array<int, Round> $rounds
     */
    private function resolveSelectedRound(array $rounds, int $selectedRoundId): ?Round
    {
        if ($selectedRoundId > 0) {
            foreach ($rounds as $round) {
                if ($round->getId() === $selectedRoundId) {
                    return $round;
                }
            }
        }

        return $rounds[0] ?? null;
    }

    /**
     * @return array<string, array<int, array{series: ?Series, teamMember: TeamMember, personName: string, disciplineName: string, teamName: string, shotCount: int, totalShots: int}>>
     */
    private function buildSeriesEntriesByDiscipline(Competition $competition, Round $selectedRound): array
    {
        $teamMembers = $this->teamMemberRepository->createQueryBuilder('tm')
            ->innerJoin('tm.Team', 't')
            ->addSelect('t')
            ->innerJoin('tm.Person', 'p')
            ->addSelect('p')
            ->innerJoin('tm.Discipline', 'd')
            ->addSelect('d')
            ->andWhere('t.Competition = :competition')
            ->setParameter('competition', $competition)
            ->orderBy('d.Name', 'ASC')
            ->addOrderBy('p.LastName', 'ASC')
            ->addOrderBy('p.FristName', 'ASC')
            ->getQuery()
            ->getResult();

        $seriesForRound = $this->seriesRepository->findBy(['Round' => $selectedRound]);
        $seriesMap = [];

        foreach ($seriesForRound as $series) {
            $seriesMap[$this->buildAssignmentKey($series->getTeam()?->getId(), $series->getPerson()?->getId(), $series->getDiscipline()?->getId())] = $series;
        }

        $seriesByDiscipline = [];

        foreach ($teamMembers as $teamMember) {
            if (!$teamMember instanceof TeamMember) {
                continue;
            }

            $discipline = $teamMember->getDiscipline();
            $disciplineName = (string) $discipline?->getName();
            if ($disciplineName === '') {
                continue;
            }

            $assignmentKey = $this->buildAssignmentKey($teamMember->getTeam()?->getId(), $teamMember->getPerson()?->getId(), $discipline?->getId());
            $series = $seriesMap[$assignmentKey] ?? null;
            $shotCount = $series instanceof Series ? $this->shotRepository->count(['Series' => $series]) : 0;

            $totalShots = ($discipline?->getShotsPerSeries() ?? 0) * ($discipline?->getMaxSeriesCount() ?? 0);
            if ($totalShots <= 0) {
                $totalShots = $discipline?->getShotsPerSeries() ?? 0;
            }

            $personName = trim(sprintf('%s %s', $teamMember->getPerson()?->getFristName() ?? '', $teamMember->getPerson()?->getLastName() ?? ''));

            if (!isset($seriesByDiscipline[$disciplineName])) {
                $seriesByDiscipline[$disciplineName] = [];
            }

            $seriesByDiscipline[$disciplineName][] = [
                'series' => $series,
                'teamMember' => $teamMember,
                'personName' => $personName,
                'disciplineName' => $disciplineName,
                'teamName' => $teamMember->getTeam()?->getName() ?? '-',
                'shotCount' => $shotCount,
                'totalShots' => $totalShots,
            ];
        }

        return $seriesByDiscipline;
    }

    private function getTargetShotCount(Series $series): int
    {
        $discipline = $series->getDiscipline();
        $shotsPerSeries = $discipline?->getShotsPerSeries() ?? 0;
        $maxSeriesCount = $discipline?->getMaxSeriesCount() ?? 0;

        $targetShotCount = $shotsPerSeries * $maxSeriesCount;
        if ($targetShotCount <= 0) {
            $targetShotCount = $shotsPerSeries;
        }

        return max($targetShotCount, 1);
    }

    private function buildAssignmentKey(?int $teamId, ?int $personId, ?int $disciplineId): string
    {
        return sprintf('%d-%d-%d', $teamId ?? 0, $personId ?? 0, $disciplineId ?? 0);
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
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
