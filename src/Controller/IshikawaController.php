<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IshikawaController extends AbstractController
{
    #[Route('/ishikawa', name: 'app_ishikawa_index')]
    public function index(Request $request): Response
    {
        // Récupérer l'ID depuis le paramètre de requête ?load=ID
        $loadId = $request->query->get('load');
        $diagramId = $loadId ? (int) $loadId : null;
        
        return $this->render('ishikawa/index.html.twig', [
            'diagramId' => $diagramId,
        ]);
    }
}
