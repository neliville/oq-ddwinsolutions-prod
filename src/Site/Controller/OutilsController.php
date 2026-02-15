<?php

namespace App\Site\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OutilsController extends AbstractController
{
    #[Route('/outils', name: 'app_outils_index')]
    public function index(): Response
    {
        return $this->render('outils/index.html.twig');
    }
}
