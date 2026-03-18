<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SpaController extends AbstractController
{
    #[Route('/api/doc', name: 'swagger_ui')]
    public function swagger(): Response
    {
        return $this->render('swagger/index.html.twig');
    }

    #[Route('/{reactRouting}', name: 'spa', requirements: ['reactRouting' => '^(?!api).*'], defaults: ['reactRouting' => ''], priority: -1)]
    public function index(): Response
    {
        return $this->render('spa/index.html.twig');
    }
}
