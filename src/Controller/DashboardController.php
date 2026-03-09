<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('', name: 'welcome', methods: ['GET'])]
    public function welcome(): Response
    {
        return $this->render('dashboard/welcome.html.twig', [
            'time' => time(),
        ]);
    }
}
