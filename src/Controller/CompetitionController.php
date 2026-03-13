<?php

namespace App\Controller;

use App\Entity\Competition;
use App\Form\CompetitionType;
use App\Repository\CompetitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CompetitionController extends AbstractController
{
    public function __construct(private readonly CompetitionRepository $competitionRepository)
    {
    }
    #[Route('/competitions', name: 'competitions_list', methods: ['GET'])]
    public function listCompetitions(): Response
    {
        return $this->render('competition/list.html.twig', [
            'competitions' => $this->competitionRepository->findBy([], ['StartTime' => 'DESC']),
        ]);
    }
    #[Route('/competitions/new', name: 'competitions_new', methods: ['GET', 'POST'])]
    public function newCompetition(Request $request, EntityManagerInterface $entityManager): Response
    {
        $competition = new Competition();
        $form = $this->createForm(CompetitionType::class, $competition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($competition);
            $entityManager->flush();

            return $this->redirectToRoute('competitions_list');
        }

        return $this->render('competition/new.html.twig', [
            'competition' => $competition,
            'form' => $form,
        ]);
    }
    #[Route('/competitions/{id}/edit', name: 'competitions_edit', methods: ['GET', 'POST'])]
    public function editCompetition(Request $request, Competition $competition, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CompetitionType::class, $competition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('competitions_list');
        }

        return $this->render('competition/edit.html.twig', [
            'competition' => $competition,
            'form' => $form,
        ]);
    }
    #[Route('/competitions/{id}/delete', name: 'competitions_delete', methods: ['POST'])]
    public function deleteCompetition(Request $request, Competition $competition, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_competition_' . $competition->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($competition);
            $entityManager->flush();
        }

        return $this->redirectToRoute('competitions_list');
    }
}
