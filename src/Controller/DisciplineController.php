<?php

namespace App\Controller;

use App\Entity\Discipline;
use App\Form\DisciplineType;
use App\Repository\DisciplineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DisciplineController extends AbstractController
{
    public function __construct(private readonly DisciplineRepository $disciplineRepository)
    {
    }

    #[Route('/disciplines', name: 'disciplines_list', methods: ['GET'])]
    public function listDisciplines(): Response
    {
        return $this->render('discipline/list.html.twig', [
            'disciplines' => $this->disciplineRepository->findBy([], ['Name' => 'ASC']),
        ]);
    }

    #[Route('/disciplines/new', name: 'disciplines_new', methods: ['GET', 'POST'])]
    public function newDiscipline(Request $request, EntityManagerInterface $entityManager): Response
    {
        $discipline = new Discipline();
        $form = $this->createForm(DisciplineType::class, $discipline);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($discipline);
            $entityManager->flush();

            return $this->redirectToRoute('disciplines_list');
        }

        return $this->render('discipline/new.html.twig', [
            'discipline' => $discipline,
            'form' => $form,
        ]);
    }

    #[Route('/disciplines/{id}', name: 'disciplines_show', methods: ['GET'])]
    public function showDiscipline(Discipline $discipline): Response
    {
        return $this->render('discipline/show.html.twig', [
            'discipline' => $discipline,
        ]);
    }

    #[Route('/disciplines/{id}/edit', name: 'disciplines_edit', methods: ['GET', 'POST'])]
    public function editDiscipline(Request $request, Discipline $discipline, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DisciplineType::class, $discipline);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('disciplines_list');
        }

        return $this->render('discipline/edit.html.twig', [
            'discipline' => $discipline,
            'form' => $form,
        ]);
    }

    #[Route('/disciplines/{id}/delete', name: 'disciplines_delete', methods: ['POST'])]
    public function deleteDiscipline(Request $request, Discipline $discipline, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_discipline_' . $discipline->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($discipline);
            $entityManager->flush();
        }

        return $this->redirectToRoute('disciplines_list');
    }
}
