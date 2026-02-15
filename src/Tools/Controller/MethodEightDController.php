<?php

namespace App\Tools\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MethodEightDController extends AbstractController
{
    #[Route('/methode-8d', name: 'app_eightd_index')]
    public function index(Request $request): Response
    {
        $loadId = $request->query->get('load');
        $analysisId = $loadId ? (int) $loadId : null;

        return $this->render('method8d/index.html.twig', [
            'analysisId' => $analysisId,
        ]);
    }
}
