<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParetoController extends AbstractController
{
    #[Route('/pareto', name: 'app_pareto_index')]
    public function index(Request $request): Response
    {
        $loadId = $request->query->get('load');
        $analysisId = $loadId ? (int) $loadId : null;

        return $this->render('pareto/index.html.twig', [
            'analysisId' => $analysisId,
        ]);
    }
}


