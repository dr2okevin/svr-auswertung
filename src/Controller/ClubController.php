<?php

namespace App\Controller;

use App\Entity\Club;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ClubController extends AbstractController
{

    public function listClubs(): Response
    {
        return $this->render(
            'club/list.html.twig', []
        );
    }

    public function editClub(Club $club): Response
    {
        return $this->render(
            'club/edit.html.twig', [
                'club' => $club
            ]
        );
    }

}
