<?php

namespace App\Tools\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QqoqccpController extends AbstractController
{
    #[Route('/qqoqccp', name: 'app_qqoqccp_index')]
    public function index(Request $request): Response
    {
        $loadId = $request->query->get('load');
        $analysisId = $loadId ? (int) $loadId : null;

        return $this->render('qqoqccp/index.html.twig', [
            'analysisId' => $analysisId,
        ]);
    }
}
