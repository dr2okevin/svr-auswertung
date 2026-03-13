<?php

namespace App\Controller;

use App\Entity\Club;
use App\Form\ClubType;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ClubController extends AbstractController
{
    public function __construct(private readonly ClubRepository $clubRepository)
    {
    }
    #[Route('/clubs', name: 'clubs_list', methods: ['GET'])]
    public function listClubs(): Response
    {
        return $this->render('club/list.html.twig', [
            'clubs' => $this->clubRepository->findAll(),
        ]);
    }
    #[Route('/clubs/new', name: 'clubs_new', methods: ['GET', 'POST'])]
    public function newClub(Request $request, EntityManagerInterface $entityManager): Response
    {
        $club = new Club();
        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($club);
            $entityManager->flush();

            return $this->redirectToRoute('clubs_list');
        }

        return $this->render('club/new.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }
    #[Route('/clubs/{id}', name: 'clubs_show', methods: ['GET'])]
    public function showClub(Club $club): Response
    {
        return $this->render('club/show.html.twig', [
            'club' => $club,
        ]);
    }
    #[Route('/clubs/{id}/edit', name: 'clubs_edit', methods: ['GET', 'POST'])]
    public function editClub(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('clubs_list');
        }

        return $this->render('club/edit.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }
    #[Route('/clubs/{id}/delete', name: 'clubs_delete', methods: ['POST'])]
    public function deleteClub(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_club_' . $club->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($club);
            $entityManager->flush();
        }

        return $this->redirectToRoute('clubs_list');
    }
}
