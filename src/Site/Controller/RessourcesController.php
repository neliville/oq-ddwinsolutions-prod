<?php

namespace App\Site\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RessourcesController extends AbstractController
{
    #[Route('/ressources', name: 'app_ressources_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('site/ressources.html.twig');
    }
}
