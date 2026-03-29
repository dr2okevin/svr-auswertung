<?php

namespace App\Controller;

use App\Entity\Competition;
use App\Entity\Discipline;
use App\Entity\Person;
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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ShotController extends AbstractController
{
    private const DISAG_IMPORT_SESSION_KEY = 'disag_import_payload';

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

    #[Route('/shots/import/disag', name: 'shots_disag_import', methods: ['GET', 'POST'])]
    public function disagImport(Request $request, SessionInterface $session): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null) {
            $this->addFlash('error', 'Bitte zuerst einen Wettkampf auswählen.');

            return $this->redirectToRoute('competitions_list');
        }

        $rounds = $this->roundRepository->findBy(['Competition' => $competition], ['StartDate' => 'ASC', 'Name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $round = $this->resolveSelectedRound($rounds, (int) $request->request->get('round_id', 0));
            $file = $request->files->get('xml_file');

            if (!$round instanceof Round) {
                $this->addFlash('error', 'Bitte eine gültige Runde auswählen.');

                return $this->redirectToRoute('shots_disag_import');
            }

            if (!$file instanceof UploadedFile) {
                $this->addFlash('error', 'Bitte eine XML-Datei auswählen.');

                return $this->redirectToRoute('shots_disag_import');
            }

            $xmlData = $this->parseDisagXmlFile($file);

            if ($xmlData === null) {
                $this->addFlash('error', 'Die Datei konnte nicht gelesen werden. Bitte prüfen, ob es sich um einen gültigen DISAG-Export handelt.');

                return $this->redirectToRoute('shots_disag_import');
            }

            $teamMembers = $this->findTeamMembersForCompetition($competition);
            $previewRows = $this->buildImportPreviewRows($xmlData, $teamMembers, $competition);

            if ($previewRows === []) {
                $this->addFlash('error', 'Keine importierbaren Schützeneinträge in der XML-Datei gefunden.');

                return $this->redirectToRoute('shots_disag_import');
            }

            $importToken = bin2hex(random_bytes(16));
            $session->set(self::DISAG_IMPORT_SESSION_KEY, [
                'token' => $importToken,
                'roundId' => $round->getId(),
                'sourceFile' => $file->getClientOriginalName() ?: $file->getFilename(),
                'rows' => $previewRows,
            ]);

            return $this->render('shot/disag_preview.html.twig', [
                'competition' => $competition,
                'round' => $round,
                'previewRows' => $previewRows,
                'importToken' => $importToken,
            ]);
        }

        return $this->render('shot/disag_import.html.twig', [
            'competition' => $competition,
            'rounds' => $rounds,
        ]);
    }

    #[Route('/shots/import/disag/confirm', name: 'shots_disag_import_confirm', methods: ['POST'])]
    public function confirmDisagImport(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $competition = $this->getSelectedCompetition();

        if ($competition === null) {
            $this->addFlash('error', 'Bitte zuerst einen Wettkampf auswählen.');

            return $this->redirectToRoute('competitions_list');
        }

        /** @var array{token?: string, roundId?: int, sourceFile?: string, rows?: array<int, array<string, mixed>>}|null $importState */
        $importState = $session->get(self::DISAG_IMPORT_SESSION_KEY);
        $submittedToken = (string) $request->request->get('import_token', '');

        if (!is_array($importState) || ($importState['token'] ?? '') === '' || !hash_equals((string) $importState['token'], $submittedToken)) {
            $this->addFlash('error', 'Die Import-Vorschau ist abgelaufen. Bitte die XML-Datei erneut hochladen.');

            return $this->redirectToRoute('shots_disag_import');
        }

        $roundId = (int) ($importState['roundId'] ?? 0);
        $round = $this->roundRepository->find($roundId);

        if (!$round instanceof Round || $round->getCompetition()?->getId() !== $competition->getId()) {
            $this->addFlash('error', 'Die gewählte Runde ist nicht mehr gültig.');
            $session->remove(self::DISAG_IMPORT_SESSION_KEY);

            return $this->redirectToRoute('shots_disag_import');
        }

        $rows = is_array($importState['rows'] ?? null) ? $importState['rows'] : [];
        $sourceFile = (string) ($importState['sourceFile'] ?? 'DISAG XML');

        $selectedAssignments = $request->request->all('assignment');
        $importedSeriesCount = 0;
        $importedShotCount = 0;

        foreach ($rows as $row) {
            $rowKey = (string) ($row['key'] ?? '');
            if ($rowKey === '' || !isset($selectedAssignments[$rowKey])) {
                continue;
            }

            $teamMemberId = (int) $selectedAssignments[$rowKey];
            if ($teamMemberId <= 0) {
                continue;
            }

            $teamMember = $this->teamMemberRepository->find($teamMemberId);
            if (!$teamMember instanceof TeamMember || $teamMember->getTeam()?->getCompetition()?->getId() !== $competition->getId()) {
                continue;
            }

            $shots = is_array($row['shots'] ?? null) ? $row['shots'] : [];
            $series = new Series();
            $series->setRound($round);
            $series->setTeam($teamMember->getTeam());
            $series->setPerson($teamMember->getPerson());
            $series->setDiscipline($teamMember->getDiscipline());
            $series->setImportFile($sourceFile);
            $series->setShotsCount(count($shots));
            $entityManager->persist($series);

            foreach ($shots as $index => $shotData) {
                if (!is_array($shotData)) {
                    continue;
                }

                $value = $this->toNullableFloat($shotData['value'] ?? null);
                if ($value === null) {
                    continue;
                }

                $shot = new Shot();
                $shot->setSeries($series);
                $shot->setShotIndex($index + 1);
                $shot->setValue($value);
                $shot->setXPosition($this->toNullableFloat($shotData['x'] ?? null));
                $shot->setYPosition($this->toNullableFloat($shotData['y'] ?? null));

                $recordedAt = $this->parseDisagDateTime(is_string($shotData['recordedAt'] ?? null) ? $shotData['recordedAt'] : '');
                $shot->setRecordTime($recordedAt ?? new \DateTime());

                $entityManager->persist($shot);
                $importedShotCount++;
            }

            $importedSeriesCount++;
        }

        $entityManager->flush();
        $session->remove(self::DISAG_IMPORT_SESSION_KEY);

        $this->addFlash('success', sprintf('%d Serien mit insgesamt %d Schüssen importiert.', $importedSeriesCount, $importedShotCount));

        return $this->redirectToRoute('shots_person_list', ['round' => $round->getId()]);
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
     * @return array<int, TeamMember>
     */
    private function findTeamMembersForCompetition(Competition $competition): array
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
            ->orderBy('p.LastName', 'ASC')
            ->addOrderBy('p.FristName', 'ASC')
            ->getQuery()
            ->getResult();

        return array_values(array_filter($teamMembers, static fn (mixed $item): bool => $item instanceof TeamMember));
    }

    /**
     * @param array<string, mixed> $xmlData
     * @param array<int, TeamMember> $teamMembers
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildImportPreviewRows(array $xmlData, array $teamMembers, Competition $competition): array
    {
        $rows = [];
        $counter = 0;

        foreach ($xmlData['shooters'] ?? [] as $shooter) {
            if (!is_array($shooter)) {
                continue;
            }

            $shots = is_array($shooter['shots'] ?? null) ? $shooter['shots'] : [];
            if ($shots === []) {
                continue;
            }

            $firstName = trim((string) ($shooter['firstName'] ?? ''));
            $lastName = trim((string) ($shooter['lastName'] ?? ''));
            $birthYear = (int) ($shooter['birthYear'] ?? 0);
            $disciplineHint = trim((string) ($shooter['disciplineHint'] ?? ''));

            $match = $this->matchBestTeamMember($firstName, $lastName, $birthYear, $disciplineHint, $teamMembers, $competition);
            $assignmentOptions = $this->buildAssignmentOptions($teamMembers, $disciplineHint, $competition);

            $rows[] = [
                'key' => 'row_' . $counter,
                'displayName' => trim($firstName . ' ' . $lastName),
                'firstName' => $firstName,
                'lastName' => $lastName,
                'birthYear' => $birthYear > 0 ? $birthYear : null,
                'disciplineHint' => $disciplineHint,
                'shotCount' => count($shots),
                'totalScore' => (float) ($shooter['totalScore'] ?? 0.0),
                'shots' => $shots,
                'assignmentOptions' => $assignmentOptions,
                'suggestedAssignmentId' => $match['teamMemberId'] ?? null,
                'confidence' => $match['confidence'] ?? 'offen',
            ];

            $counter++;
        }

        return $rows;
    }

    /**
     * @param array<int, TeamMember> $teamMembers
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function buildAssignmentOptions(array $teamMembers, string $disciplineHint, Competition $competition): array
    {
        $disciplineIdFromHint = $this->extractDisciplineId($disciplineHint, $competition);
        $normalizedHint = $this->normalizeString($disciplineHint);

        $options = [];

        foreach ($teamMembers as $teamMember) {
            $discipline = $teamMember->getDiscipline();
            if (!$discipline instanceof Discipline) {
                continue;
            }

            $disciplineName = (string) $discipline->getName();
            $disciplineNameNormalized = $this->normalizeString($disciplineName);

            if ($disciplineIdFromHint !== null && $discipline->getId() !== $disciplineIdFromHint) {
                continue;
            }

            if ($disciplineIdFromHint === null && $normalizedHint !== '' && !str_contains($disciplineNameNormalized, $normalizedHint)) {
                continue;
            }

            $personName = trim(($teamMember->getPerson()?->getFristName() ?? '') . ' ' . ($teamMember->getPerson()?->getLastName() ?? ''));

            $options[] = [
                'id' => (int) $teamMember->getId(),
                'label' => sprintf('%s · %s · %s', $personName, $disciplineName, $teamMember->getTeam()?->getName() ?? '-'),
            ];
        }

        if ($options !== []) {
            return $options;
        }

        foreach ($teamMembers as $teamMember) {
            $personName = trim(($teamMember->getPerson()?->getFristName() ?? '') . ' ' . ($teamMember->getPerson()?->getLastName() ?? ''));
            $options[] = [
                'id' => (int) $teamMember->getId(),
                'label' => sprintf('%s · %s · %s', $personName, $teamMember->getDiscipline()?->getName() ?? '-', $teamMember->getTeam()?->getName() ?? '-'),
            ];
        }

        return $options;
    }

    /**
     * @param array<int, TeamMember> $teamMembers
     *
     * @return array{teamMemberId: int|null, confidence: string}
     */
    private function matchBestTeamMember(string $firstName, string $lastName, int $birthYear, string $disciplineHint, array $teamMembers, Competition $competition): array
    {
        $normalizedFirstName = $this->normalizeString($firstName);
        $normalizedLastName = $this->normalizeString($lastName);
        $disciplineIdFromHint = $this->extractDisciplineId($disciplineHint, $competition);
        $normalizedDisciplineHint = $this->normalizeString($disciplineHint);

        $bestScore = -INF;
        $bestTeamMemberId = null;

        foreach ($teamMembers as $teamMember) {
            $person = $teamMember->getPerson();
            $discipline = $teamMember->getDiscipline();

            if (!$person instanceof Person || !$discipline instanceof Discipline) {
                continue;
            }

            $score = 0.0;

            $candidateFirstName = $this->normalizeString((string) $person->getFristName());
            $candidateLastName = $this->normalizeString((string) $person->getLastName());

            $score += $this->nameSimilarityScore($normalizedFirstName, $candidateFirstName);
            $score += $this->nameSimilarityScore($normalizedLastName, $candidateLastName);

            $personBirthYear = (int) $person->getBirthdate()?->format('Y');
            if ($birthYear > 0 && $personBirthYear > 0) {
                $score += $birthYear === $personBirthYear ? 20.0 : -15.0;
            }

            $disciplineNameNormalized = $this->normalizeString((string) $discipline->getName());

            if ($disciplineIdFromHint !== null) {
                $score += $discipline->getId() === $disciplineIdFromHint ? 25.0 : -20.0;
            } elseif ($normalizedDisciplineHint !== '') {
                if (str_contains($disciplineNameNormalized, $normalizedDisciplineHint) || str_contains($normalizedDisciplineHint, $disciplineNameNormalized)) {
                    $score += 15.0;
                } else {
                    $score -= 5.0;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTeamMemberId = $teamMember->getId();
            }
        }

        $confidence = 'niedrig';
        if ($bestScore >= 70.0) {
            $confidence = 'hoch';
        } elseif ($bestScore >= 45.0) {
            $confidence = 'mittel';
        }

        return [
            'teamMemberId' => $bestTeamMemberId,
            'confidence' => $confidence,
        ];
    }

    private function nameSimilarityScore(string $needle, string $candidate): float
    {
        if ($needle === '' || $candidate === '') {
            return 0.0;
        }

        if ($needle === $candidate) {
            return 35.0;
        }

        if (str_contains($candidate, $needle) || str_contains($needle, $candidate)) {
            return 24.0;
        }

        $distance = levenshtein($needle, $candidate);
        $maxLength = max(strlen($needle), strlen($candidate));

        if ($maxLength === 0) {
            return 0.0;
        }

        $ratio = 1 - ($distance / $maxLength);

        return max($ratio, 0.0) * 22.0;
    }

    private function extractDisciplineId(string $disciplineHint, Competition $competition): ?int
    {
        if (preg_match('/\d+/', $disciplineHint, $matches) !== 1) {
            return null;
        }

        $id = (int) $matches[0];
        if ($id <= 0) {
            return null;
        }

        foreach ($competition->getDisciplines() as $discipline) {
            if ($discipline->getId() === $id) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseDisagXmlFile(UploadedFile $file): ?array
    {
        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            return null;
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if (!$xml instanceof \SimpleXMLElement) {
            return null;
        }

        $shooters = [];

        foreach ($xml->shooters->shooter ?? [] as $shooterElement) {
            $shots = [];

            foreach ($shooterElement->shots->series ?? [] as $seriesElement) {
                foreach ($seriesElement->shot ?? [] as $shotElement) {
                    $shots[] = [
                        'value' => (string) ($shotElement['dec'] ?? $shotElement),
                        'x' => (string) ($shotElement['x'] ?? ''),
                        'y' => (string) ($shotElement['y'] ?? ''),
                        'recordedAt' => (string) ($shotElement['datetime'] ?? ''),
                    ];
                }
            }

            $shooters[] = [
                'firstName' => (string) ($shooterElement['firstname'] ?? ''),
                'lastName' => (string) ($shooterElement['lastname'] ?? ''),
                'birthYear' => (string) ($shooterElement['birthyear'] ?? ''),
                'disciplineHint' => (string) ($shooterElement['fidRanges'] ?? ''),
                'totalScore' => (string) ($shooterElement['totalscore_t'] ?? $shooterElement['totalscore'] ?? 0),
                'shots' => $shots,
            ];
        }

        return ['shooters' => $shooters];
    }

    private function normalizeString(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $normalized = $ascii === false ? $value : $ascii;
        $normalized = strtolower($normalized);

        return trim((string) preg_replace('/[^a-z0-9]+/', '', $normalized));
    }

    private function parseDisagDateTime(string $value): ?\DateTime
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = \DateTime::createFromFormat('d.m.Y H:i:s', $value);

        return $date ?: null;
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

        return (float) str_replace(',', '.', (string) $value);
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
