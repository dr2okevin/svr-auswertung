<?php

namespace App\Controller;

use App\Entity\Person;
use App\Form\PersonType;
use App\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PersonController extends AbstractController
{
    public function __construct(private readonly PersonRepository $personRepository)
    {
    }
    #[Route('/persons', name: 'persons_list', methods: ['GET'])]
    public function listPersons(): Response
    {
        return $this->render('person/list.html.twig', [
            'persons' => $this->personRepository->findBy([], ['LastName' => 'ASC', 'FristName' => 'ASC']),
        ]);
    }
    #[Route('/persons/new', name: 'persons_new', methods: ['GET', 'POST'])]
    public function newPerson(Request $request, EntityManagerInterface $entityManager): Response
    {
        $person = new Person();
        $form = $this->createForm(PersonType::class, $person);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($person);
            $entityManager->flush();

            return $this->redirectToRoute('persons_list');
        }

        return $this->render('person/new.html.twig', [
            'person' => $person,
            'form' => $form,
        ]);
    }
    #[Route('/persons/{id}', name: 'persons_show', methods: ['GET'])]
    public function showPerson(Person $person): Response
    {
        return $this->render('person/show.html.twig', [
            'person' => $person,
        ]);
    }
    #[Route('/persons/{id}/edit', name: 'persons_edit', methods: ['GET', 'POST'])]
    public function editPerson(Request $request, Person $person, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PersonType::class, $person);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('persons_list');
        }

        return $this->render('person/edit.html.twig', [
            'person' => $person,
            'form' => $form,
        ]);
    }
    #[Route('/persons/{id}/delete', name: 'persons_delete', methods: ['POST'])]
    public function deletePerson(Request $request, Person $person, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_person_' . $person->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($person);
            $entityManager->flush();
        }

        return $this->redirectToRoute('persons_list');
    }
}
