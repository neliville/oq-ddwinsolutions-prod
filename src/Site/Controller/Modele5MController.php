<?php

namespace App\Site\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Modele5MController extends AbstractController
{
    private const FICHIER_MODELE = 'downloads/modele-5m.pdf';

    public function __construct(
        private readonly string $mauticUrl,
        private readonly int $mauticFormDownload5mId,
    ) {
    }

    #[Route('/telechargement-modele-5m', name: 'app_telechargement_modele_5m', methods: ['GET'])]
    public function telechargementModele5m(): Response
    {
        $mauticBase = rtrim($this->mauticUrl, '/');
        $formScriptUrl = $mauticBase ? $mauticBase . '/form/generate.js?id=' . $this->mauticFormDownload5mId : null;

        return $this->render('site/telechargement_modele_5m.html.twig', [
            'downloadUrl' => $this->generateUrl('app_telechargement_modele_5m_fichier'),
            'merciUrl' => $this->generateUrl('app_merci_modele_5m'),
            'mauticFormScriptUrl' => $formScriptUrl,
        ]);
    }

    #[Route('/telechargement-modele-5m/fichier', name: 'app_telechargement_modele_5m_fichier', methods: ['GET'])]
    public function downloadModele5m(): BinaryFileResponse
    {
        $path = $this->getParameter('kernel.project_dir') . '/public/' . self::FICHIER_MODELE;
        if (!is_file($path)) {
            throw $this->createNotFoundException('Le fichier modèle n\'est pas encore disponible.');
        }
        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'modele-5m.pdf'
        );
        return $response;
    }

    #[Route('/merci-modele-5m', name: 'app_merci_modele_5m', methods: ['GET'])]
    public function merciModele5m(): Response
    {
        return $this->render('site/merci_modele_5m.html.twig');
    }
}
