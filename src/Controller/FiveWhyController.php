<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FiveWhyController extends AbstractController
{
    #[Route('/5pourquoi/', name: 'app_fivewhy_index')]
    public function index(): Response
    {
        return $this->render('five_why/index.html.twig');
    }
}
