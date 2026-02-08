<?php

namespace App\Controller;

use App\Entity\Competition;
use App\Entity\Person;
use App\Entity\Team;
use App\Enum\CompetitionType;
use App\Enum\TeamType;
use App\Repository\CompetitionRepository;
use App\Repository\PersonRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ManagementController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): RedirectResponse
    {
        return $this->redirectToRoute('app_management');
    }

    #[Route('/verwaltung', name: 'app_management', methods: ['GET'])]
    public function index(
        CompetitionRepository $competitionRepository,
        PersonRepository $personRepository,
        TeamRepository $teamRepository,
    ): Response {
        return $this->render('management/index.html.twig', [
            'competitions' => $competitionRepository->findBy([], ['StartTime' => 'ASC']),
            'people' => $personRepository->findBy([], ['LastName' => 'ASC', 'FristName' => 'ASC']),
            'teams' => $teamRepository->findBy([], ['Name' => 'ASC']),
            'competitionTypes' => CompetitionType::cases(),
            'teamTypes' => TeamType::cases(),
        ]);
    }

    #[Route('/competitions', name: 'app_competition_create', methods: ['POST'])]
    public function createCompetition(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $competition = new Competition();

        if (!$this->applyCompetitionInput($competition, $request, true)) {
            return $this->redirectToRoute('app_management');
        }

        $entityManager->persist($competition);
        $entityManager->flush();

        $this->addFlash('success', 'Wettkampf wurde angelegt.');

        return $this->redirectToRoute('app_management');
    }

    #[Route('/competitions/{id}/edit', name: 'app_competition_edit', methods: ['POST'])]
    public function editCompetition(Competition $competition, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->applyCompetitionInput($competition, $request, false)) {
            return $this->redirectToRoute('app_management');
        }

        $entityManager->flush();
        $this->addFlash('success', 'Wettkampf wurde aktualisiert.');

        return $this->redirectToRoute('app_management');
    }

    #[Route('/competitions/{id}/delete', name: 'app_competition_delete', methods: ['POST'])]
    public function deleteCompetition(Competition $competition, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($competition);
        $entityManager->flush();

        $this->addFlash('success', 'Wettkampf wurde gelöscht.');

        return $this->redirectToRoute('app_management');
    }

    #[Route('/shooters', name: 'app_person_create', methods: ['POST'])]
    public function createPerson(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $person = new Person();

        if (!$this->applyPersonInput($person, $request, true)) {
            return $this->redirectToRoute('app_management');
        }

        $entityManager->persist($person);
        $entityManager->flush();

        $this->addFlash('success', 'Schütze wurde angelegt.');

        return $this->redirectToRoute('app_management');
    }

    #[Route('/shooters/{id}/edit', name: 'app_person_edit', methods: ['POST'])]
    public function editPerson(Person $person, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->applyPersonInput($person, $request, false)) {
            return $this->redirectToRoute('app_management');
        }

        $entityManager->flush();
        $this->addFlash('success', 'Schütze wurde aktualisiert.');

        return $this->redirectToRoute('app_management');
    }

    #[Route('/shooters/{id}/delete', name: 'app_person_delete', methods: ['POST'])]
    public function deletePerson(Person $person, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($person);
        $entityManager->flush();

        $this->addFlash('success', 'Schütze wurde gelöscht.');

        return $this->redirectToRoute('app_management');
    }

    #[Route('/teams', name: 'app_team_create', methods: ['POST'])]
    public function createTeam(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $team = new Team();

        if (!$this->applyTeamInput($team, $request, true)) {
            return $this->redirectToRoute('app_management');
        }

        $entityManager->persist($team);
        $entityManager->flush();

        $this->addFlash('success', 'Team wurde angelegt.');

        return $this->redirectToRoute('app_management');
    }

    #[Route('/teams/{id}/edit', name: 'app_team_edit', methods: ['POST'])]
    public function editTeam(Team $team, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->applyTeamInput($team, $request, false)) {
            return $this->redirectToRoute('app_management');
        }

        $entityManager->flush();
        $this->addFlash('success', 'Team wurde aktualisiert.');

        return $this->redirectToRoute('app_management');
    }

    #[Route('/teams/{id}/delete', name: 'app_team_delete', methods: ['POST'])]
    public function deleteTeam(Team $team, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($team);
        $entityManager->flush();

        $this->addFlash('success', 'Team wurde gelöscht.');

        return $this->redirectToRoute('app_management');
    }

    private function applyCompetitionInput(Competition $competition, Request $request, bool $isCreate): bool
    {
        $name = trim((string) $request->request->get('name', ''));
        $type = (string) $request->request->get('type', '');
        $start = (string) $request->request->get('start_time', '');
        $end = (string) $request->request->get('end_time', '');

        if ($name === '' || $start === '' || $end === '' || CompetitionType::tryFrom($type) === null) {
            $this->addFlash('error', $isCreate
                ? 'Bitte alle Felder für den Wettkampf ausfüllen.'
                : 'Bitte alle Felder für die Wettkampf-Bearbeitung ausfüllen.');

            return false;
        }

        $startTime = \DateTime::createFromFormat('Y-m-d\\TH:i', $start);
        $endTime = \DateTime::createFromFormat('Y-m-d\\TH:i', $end);

        if (!$startTime instanceof \DateTime || !$endTime instanceof \DateTime) {
            $this->addFlash('error', 'Ungültiges Datumsformat für den Wettkampf.');

            return false;
        }

        if ($endTime < $startTime) {
            $this->addFlash('error', 'Das Enddatum darf nicht vor dem Startdatum liegen.');

            return false;
        }

        $competition
            ->setName($name)
            ->setType(CompetitionType::from($type))
            ->setStartTime($startTime)
            ->setEndTime($endTime);

        return true;
    }

    private function applyPersonInput(Person $person, Request $request, bool $isCreate): bool
    {
        $firstName = trim((string) $request->request->get('first_name', ''));
        $lastName = trim((string) $request->request->get('last_name', ''));
        $birthdateInput = (string) $request->request->get('birthdate', '');
        $professional = $request->request->getBoolean('professional', false);

        if ($firstName === '' || $lastName === '') {
            $this->addFlash('error', $isCreate
                ? 'Vor- und Nachname für Schützen sind Pflichtfelder.'
                : 'Vor- und Nachname für die Schützen-Bearbeitung sind Pflichtfelder.');

            return false;
        }

        $birthdate = null;
        if ($birthdateInput !== '') {
            $birthdate = \DateTime::createFromFormat('Y-m-d', $birthdateInput);
            if (!$birthdate instanceof \DateTime) {
                $this->addFlash('error', 'Ungültiges Geburtsdatum.');

                return false;
            }
        }

        $person
            ->setFristName($firstName)
            ->setLastName($lastName)
            ->setBirthdate($birthdate)
            ->setProfessional($professional);

        return true;
    }

    private function applyTeamInput(Team $team, Request $request, bool $isCreate): bool
    {
        $name = trim((string) $request->request->get('name', ''));
        $type = (string) $request->request->get('type', '');

        if ($name === '' || TeamType::tryFrom($type) === null) {
            $this->addFlash('error', $isCreate
                ? 'Bitte Teamname und Teamtyp angeben.'
                : 'Bitte Teamname und Teamtyp für die Bearbeitung angeben.');

            return false;
        }

        $team
            ->setName($name)
            ->setType(TeamType::from($type));

        return true;
    }
}
