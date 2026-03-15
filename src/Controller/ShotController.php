<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class ShotController extends AbstractController
{
    #[Route('/shots', name: 'shots_person_list', methods: ['GET'])]
    public function welcome(): Response
    {
        return $this->render('shot/person_list.html.twig', [
            'time' => time(),
        ]);
    }
}
