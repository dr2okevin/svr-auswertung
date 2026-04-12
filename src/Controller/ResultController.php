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

        $viewData = $this->getResultViewData($competition);

        return $this->render('result/index.html.twig', [
            'competition' => $competition,
            'isFireOrCompanyCompetition' => $viewData['isFireOrCompanyCompetition'],
            'individualByDiscipline' => $viewData['individualByDiscipline'],
            'teamRanking' => $viewData['teamRanking'],
        ]);
    }

    #[Route('/results/print/team', name: 'results_print_team', methods: ['GET'])]
    public function printTeam(): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null) {
            $this->addFlash('error', 'Bitte zuerst einen Wettkampf auswählen.');

            return $this->redirectToRoute('competitions_list');
        }

        $viewData = $this->getResultViewData($competition);

        return $this->render('result/print_team.html.twig', [
            'competition' => $competition,
            'teamRanking' => $viewData['teamRanking'],
        ]);
    }

    #[Route('/results/print/individual/{disciplineId}', name: 'results_print_individual', methods: ['GET'], requirements: ['disciplineId' => '\\d+'])]
    public function printIndividual(int $disciplineId): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null) {
            $this->addFlash('error', 'Bitte zuerst einen Wettkampf auswählen.');

            return $this->redirectToRoute('competitions_list');
        }

        $viewData = $this->getResultViewData($competition);
        $discipline = $this->findDisciplineResult($viewData['individualByDiscipline'], $disciplineId);

        if ($discipline === null) {
            throw $this->createNotFoundException('Die gewünschte Disziplin wurde in den Ergebnissen nicht gefunden.');
        }

        return $this->render('result/print_individual.html.twig', [
            'competition' => $competition,
            'discipline' => $discipline,
        ]);
    }

    #[Route('/results/export/team.csv', name: 'results_export_team_csv', methods: ['GET'])]
    public function exportTeamCsv(): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null) {
            $this->addFlash('error', 'Bitte zuerst einen Wettkampf auswählen.');

            return $this->redirectToRoute('competitions_list');
        }

        $viewData = $this->getResultViewData($competition);
        $rows = [];

        foreach ($viewData['teamRanking'] as $team) {
            $rows[] = [
                $team['rank'],
                $team['teamName'],
                $team['teamType'],
                number_format((float) $team['totalScore'], 1, ',', ''),
                sprintf('%d/%d', (int) $team['scoredAssignments'], (int) $team['expectedAssignments']),
            ];
        }

        return $this->createCsvResponse(
            $rows,
            ['Platz', 'Team', 'Typ', 'Ringe', 'Wertungen'],
            sprintf('teamwertung-%s.csv', $this->buildCsvSlug($competition->getName()))
        );
    }

    #[Route('/results/export/individual/{disciplineId}.csv', name: 'results_export_individual_csv', methods: ['GET'], requirements: ['disciplineId' => '\\d+'])]
    public function exportIndividualCsv(int $disciplineId): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null) {
            $this->addFlash('error', 'Bitte zuerst einen Wettkampf auswählen.');

            return $this->redirectToRoute('competitions_list');
        }

        $viewData = $this->getResultViewData($competition);
        $discipline = $this->findDisciplineResult($viewData['individualByDiscipline'], $disciplineId);

        if ($discipline === null) {
            throw $this->createNotFoundException('Die gewünschte Disziplin wurde in den Ergebnissen nicht gefunden.');
        }

        $rows = [];

        foreach ($discipline['entries'] as $entry) {
            $rows[] = [
                $entry['rank'],
                $entry['personName'],
                $entry['teamName'],
                $entry['isProfessional'] ? 'Profi' : 'Amateur',
                number_format((float) $entry['totalScore'], 1, ',', ''),
                sprintf('%d/%d', (int) $entry['shotCount'], (int) $entry['targetShotCount']),
            ];
        }

        return $this->createCsvResponse(
            $rows,
            ['Platz', 'Schütze', 'Team', 'Status', 'Ringe', 'Schüsse'],
            sprintf(
                'einzelwertung-%s-%s.csv',
                $this->buildCsvSlug($competition->getName()),
                $this->buildCsvSlug((string) $discipline['disciplineName'])
            )
        );
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
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $header
     */
    private function createCsvResponse(array $rows, array $header, string $fileName): Response
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw $this->createNotFoundException('CSV konnte nicht erstellt werden.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $header, ';');

        foreach ($rows as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        if ($csvContent === false) {
            throw $this->createNotFoundException('CSV konnte nicht erstellt werden.');
        }

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $fileName));

        return $response;
    }

    private function buildCsvSlug(string $value): string
    {
        $slug = preg_replace('/[^a-z0-9\-]+/i', '-', mb_strtolower($value));

        if ($slug === null || $slug === '') {
            return 'wettkampf';
        }

        return trim($slug, '-');
    }

    /**
     * @param array<int, array<string, mixed>> $individualByDiscipline
     *
     * @return array<string, mixed>|null
     */
    private function findDisciplineResult(array $individualByDiscipline, int $disciplineId): ?array
    {
        foreach ($individualByDiscipline as $discipline) {
            if ((int) ($discipline['disciplineId'] ?? 0) === $disciplineId) {
                return $discipline;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     isFireOrCompanyCompetition: bool,
     *     individualByDiscipline: array<int, array{disciplineId: int, disciplineName: string, entries: array<int, array<string, mixed>>}>,
     *     teamRanking: array<int, array<string, mixed>>
     * }
     */
    private function getResultViewData(Competition $competition): array
    {
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

        return [
            'isFireOrCompanyCompetition' => in_array($competition->getType(), [CompetitionType::FIRE, CompetitionType::COMPANY], true),
            'individualByDiscipline' => $this->buildIndividualRanking($seriesResults),
            'teamRanking' => $this->buildTeamRanking($seriesResults, $teamAssignments),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $seriesResults
     *
     * @return array<int, array{disciplineId: int, disciplineName: string, entries: array<int, array<string, mixed>>}>
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
                    'disciplineId' => $disciplineId,
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
