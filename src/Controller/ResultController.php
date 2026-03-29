<?php

namespace App\Controller;

use App\Entity\Competition;
use App\Entity\TeamMember;
use App\Enum\CompetitionType;
use App\Repository\CompetitionRepository;
use App\Repository\SeriesRepository;
use App\Repository\TeamMemberRepository;
use App\Service\CompetitionContextProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ResultController extends AbstractController
{
    public function __construct(
        private readonly CompetitionContextProvider $competitionContextProvider,
        private readonly CompetitionRepository $competitionRepository,
        private readonly SeriesRepository $seriesRepository,
        private readonly TeamMemberRepository $teamMemberRepository,
    ) {
    }

    #[Route('/results', name: 'results_index', methods: ['GET'])]
    public function index(): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null) {
            $this->addFlash('error', 'Bitte zuerst einen Wettkampf auswählen.');

            return $this->redirectToRoute('competitions_list');
        }

        $seriesResults = $this->seriesRepository->findSeriesTotalsForCompetition($competition);
        $teamAssignments = $this->teamMemberRepository->createQueryBuilder('tm')
            ->innerJoin('tm.Team', 't')
            ->addSelect('t')
            ->innerJoin('tm.Person', 'p')
            ->addSelect('p')
            ->innerJoin('tm.Discipline', 'd')
            ->addSelect('d')
            ->andWhere('t.Competition = :competition')
            ->setParameter('competition', $competition)
            ->orderBy('t.Name', 'ASC')
            ->addOrderBy('d.Name', 'ASC')
            ->addOrderBy('p.LastName', 'ASC')
            ->addOrderBy('p.FristName', 'ASC')
            ->getQuery()
            ->getResult();

        $individualByDiscipline = $this->buildIndividualRanking($seriesResults);
        $teamRanking = $this->buildTeamRanking($seriesResults, $teamAssignments);

        return $this->render('result/index.html.twig', [
            'competition' => $competition,
            'isFireOrCompanyCompetition' => in_array($competition->getType(), [CompetitionType::FIRE, CompetitionType::COMPANY], true),
            'individualByDiscipline' => $individualByDiscipline,
            'teamRanking' => $teamRanking,
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

    /**
     * @param array<int, array<string, mixed>> $seriesResults
     *
     * @return array<int, array{disciplineName: string, entries: array<int, array<string, mixed>>}>
     */
    private function buildIndividualRanking(array $seriesResults): array
    {
        $bestResults = [];

        foreach ($seriesResults as $seriesResult) {
            $disciplineId = (int) $seriesResult['disciplineId'];
            $personId = (int) $seriesResult['personId'];
            $totalScore = (float) $seriesResult['totalScore'];
            $shotCount = (int) $seriesResult['shotCount'];

            $key = sprintf('%d-%d', $disciplineId, $personId);

            if (!isset($bestResults[$key]) || $totalScore > $bestResults[$key]['totalScore']) {
                $bestResults[$key] = [
                    'disciplineId' => $disciplineId,
                    'disciplineName' => (string) $seriesResult['disciplineName'],
                    'personName' => trim(sprintf('%s %s', (string) $seriesResult['personFirstName'], (string) $seriesResult['personLastName'])),
                    'teamName' => (string) $seriesResult['teamName'],
                    'isProfessional' => (bool) $seriesResult['isProfessional'],
                    'totalScore' => $totalScore,
                    'shotCount' => $shotCount,
                    'targetShotCount' => (int) $seriesResult['targetShotCount'],
                ];
            }
        }

        $grouped = [];

        foreach ($bestResults as $result) {
            $disciplineId = (int) $result['disciplineId'];

            if (!isset($grouped[$disciplineId])) {
                $grouped[$disciplineId] = [
                    'disciplineName' => (string) $result['disciplineName'],
                    'entries' => [],
                ];
            }

            $grouped[$disciplineId]['entries'][] = $result;
        }

        foreach ($grouped as &$disciplineData) {
            usort($disciplineData['entries'], static function (array $left, array $right): int {
                $scoreSort = $right['totalScore'] <=> $left['totalScore'];
                if ($scoreSort !== 0) {
                    return $scoreSort;
                }

                $shotCountSort = $right['shotCount'] <=> $left['shotCount'];
                if ($shotCountSort !== 0) {
                    return $shotCountSort;
                }

                return strcmp((string) $left['personName'], (string) $right['personName']);
            });

            $disciplineData['entries'] = $this->attachRanks($disciplineData['entries']);
        }
        unset($disciplineData);

        usort($grouped, static fn (array $left, array $right): int => strcmp((string) $left['disciplineName'], (string) $right['disciplineName']));

        return array_values($grouped);
    }

    /**
     * @param array<int, array<string, mixed>> $seriesResults
     * @param array<int, TeamMember> $teamAssignments
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildTeamRanking(array $seriesResults, array $teamAssignments): array
    {
        $bestAssignmentScores = [];

        foreach ($seriesResults as $seriesResult) {
            $teamId = (int) $seriesResult['teamId'];
            $personId = (int) $seriesResult['personId'];
            $disciplineId = (int) $seriesResult['disciplineId'];
            $totalScore = (float) $seriesResult['totalScore'];

            $assignmentKey = sprintf('%d-%d-%d', $teamId, $personId, $disciplineId);

            if (!isset($bestAssignmentScores[$assignmentKey]) || $totalScore > $bestAssignmentScores[$assignmentKey]) {
                $bestAssignmentScores[$assignmentKey] = $totalScore;
            }
        }

        $teams = [];

        foreach ($teamAssignments as $assignment) {
            if (!$assignment instanceof TeamMember) {
                continue;
            }

            $team = $assignment->getTeam();
            $person = $assignment->getPerson();
            $discipline = $assignment->getDiscipline();
            $teamId = $team?->getId();
            $personId = $person?->getId();
            $disciplineId = $discipline?->getId();

            if ($teamId === null || $personId === null || $disciplineId === null) {
                continue;
            }

            if (!isset($teams[$teamId])) {
                $teams[$teamId] = [
                    'teamName' => (string) $team->getName(),
                    'teamType' => $team->getType()?->getLabel() ?? '—',
                    'expectedAssignments' => 0,
                    'scoredAssignments' => 0,
                    'totalScore' => 0.0,
                ];
            }

            $teams[$teamId]['expectedAssignments']++;

            $assignmentKey = sprintf('%d-%d-%d', $teamId, $personId, $disciplineId);

            if (isset($bestAssignmentScores[$assignmentKey])) {
                $teams[$teamId]['scoredAssignments']++;
                $teams[$teamId]['totalScore'] += $bestAssignmentScores[$assignmentKey];
            }
        }

        $teams = array_values($teams);

        usort($teams, static function (array $left, array $right): int {
            $scoreSort = $right['totalScore'] <=> $left['totalScore'];
            if ($scoreSort !== 0) {
                return $scoreSort;
            }

            $completionSort = $right['scoredAssignments'] <=> $left['scoredAssignments'];
            if ($completionSort !== 0) {
                return $completionSort;
            }

            return strcmp((string) $left['teamName'], (string) $right['teamName']);
        });

        return $this->attachRanks($teams);
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     *
     * @return array<int, array<string, mixed>>
     */
    private function attachRanks(array $entries): array
    {
        $rank = 0;
        $position = 0;
        $previousScore = null;

        foreach ($entries as $index => $entry) {
            ++$position;
            $currentScore = (float) $entry['totalScore'];

            if ($previousScore === null || $currentScore !== $previousScore) {
                $rank = $position;
                $previousScore = $currentScore;
            }

            $entries[$index]['rank'] = $rank;
        }

        return $entries;
    }
}
