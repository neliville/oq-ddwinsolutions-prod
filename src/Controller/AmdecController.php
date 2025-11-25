<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AmdecController extends AbstractController
{
    #[Route('/amdec', name: 'app_amdec_index')]
    public function index(Request $request): Response
    {
        $loadId = $request->query->get('load');
        $analysisId = $loadId ? (int) $loadId : null;

        return $this->render('amdec/index.html.twig', [
            'analysisId' => $analysisId,
        ]);
    }
}


