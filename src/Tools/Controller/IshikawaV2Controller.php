<?php

namespace App\Tools\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page de test de l’éditeur React Flow (même ouverture que /ishikawa : public).
 * La sauvegarde en base et le chargement d’un enregistrement passent par l’API (/api/ishikawa),
 * qui impose l’authentification là où c’est nécessaire.
 */
#[Route('/ishikawa-v2', name: 'app_ishikawa_v2_')]
final class IshikawaV2Controller extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // `record` (v2) et `load` (liens hérités de la page /ishikawa) ouvrent le même enregistrement.
        $recordId = $request->query->getInt('record', 0)
            ?: $request->query->getInt('load', 0)
            ?: null;

        return $this->render('ishikawa_v2/index.html.twig', [
            'record_id' => $recordId,
            'page_title' => 'Diagramme Ishikawa (nouvelle interface)',
        ]);
    }
}
