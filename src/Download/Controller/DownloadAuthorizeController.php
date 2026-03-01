<?php

declare(strict_types=1);

namespace App\Download\Controller;

use App\Download\Application\DTO\DownloadAuthorizationDTO;
use App\Download\Domain\Repository\DownloadRequestRepositoryInterface;
use App\Download\Service\DownloadAccessService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Endpoint appelé par n8n après réception du webhook Mautic.
 * Génère un token signé et marque la demande comme autorisée.
 */
#[Route('/api/download')]
final class DownloadAuthorizeController extends AbstractController
{
    public function __construct(
        private readonly DownloadRequestRepositoryInterface $repository,
        private readonly DownloadAccessService $downloadAccessService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ?string $authorizeApiKey,
    ) {
    }

    #[Route('/authorize', name: 'app_download_authorize', methods: ['POST'])]
    public function authorize(Request $request): JsonResponse
    {
        if ($this->authorizeApiKey !== null && $this->authorizeApiKey !== '') {
            $apiKey = $request->headers->get('X-Api-Key') ?? $request->request->get('api_key');
            if ($apiKey !== $this->authorizeApiKey) {
                return $this->json(['success' => false, 'message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
        }

        $data = json_decode((string) $request->getContent(), true) ?? $request->request->all();
        $dto = DownloadAuthorizationDTO::fromRequest($data);

        if (!$dto->authorized) {
            return $this->json([
                'success' => false,
                'message' => 'Authorization declined.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $uuid = Uuid::fromString($dto->downloadRequestId);
        } catch (\ValueError) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid download_request_id.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $downloadRequest = $this->repository->findByUuid($uuid);
        if ($downloadRequest === null) {
            return $this->json([
                'success' => false,
                'message' => 'Download request not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $token = $this->downloadAccessService->authorize($downloadRequest);
        $accessUrl = $this->urlGenerator->generate(
            'app_download_access',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json([
            'success' => true,
            'download_url' => $accessUrl,
            'token' => $token,
            'expires_in_hours' => 24,
        ], Response::HTTP_OK);
    }
}
