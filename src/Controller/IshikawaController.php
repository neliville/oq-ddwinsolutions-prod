<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IshikawaController extends AbstractController
{
    #[Route('/ishikawa/', name: 'app_ishikawa_index')]
    public function index(): Response
    {
        return $this->render('ishikawa/index.html.twig');
    }
}
