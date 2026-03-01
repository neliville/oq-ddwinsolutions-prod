<?php

declare(strict_types=1);

namespace App\Download\Controller;

use App\Download\Service\DownloadRequestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/download')]
final class DownloadRequestController extends AbstractController
{
    public function __construct(
        private readonly DownloadRequestService $downloadRequestService,
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
            $this->downloadRequestService->createAndSubmitToMautic($email, $firstname, 'modele-5m');
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'message' => 'Ressource inconnue.',
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de finaliser la demande. Veuillez réessayer.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json([
            'success' => true,
            'message' => 'Consultez votre boîte mail pour accéder au téléchargement.',
        ], Response::HTTP_OK);
    }
}
