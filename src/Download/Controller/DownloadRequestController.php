<?php

declare(strict_types=1);

namespace App\Download\Controller;

use App\Download\Infrastructure\MauticFormClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/api/download')]
final class DownloadRequestController extends AbstractController
{
    public function __construct(
        private readonly MauticFormClient $mauticFormClient,
        private readonly int $formIdModele5m,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/modele-5m/request', name: 'app_download_modele_5m_request', methods: ['POST'])]
    public function requestModele5m(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true) ?? [];
        $email = trim((string) ($data['email'] ?? ''));
        $firstname = trim((string) ($data['firstname'] ?? ''));

        if ('' === $email) {
            return $this->json([
                'success' => false,
                'message' => 'L\'adresse email est requise.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'message' => 'L\'adresse email n\'est pas valide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->mauticFormClient->submit($this->formIdModele5m, [
                'votre_email' => $email,
                'votre_prenom' => $firstname !== '' ? $firstname : null,
                'ressource' => 'Modèle 5M',
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de finaliser la demande. Veuillez réessayer.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $downloadUrl = $this->urlGenerator->generate(
            'app_telechargement_modele_5m_fichier',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json([
            'success' => true,
            'downloadUrl' => $downloadUrl,
        ], Response::HTTP_OK);
    }
}
