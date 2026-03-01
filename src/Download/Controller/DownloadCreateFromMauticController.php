<?php

declare(strict_types=1);

namespace App\Download\Controller;

use App\Download\Application\Port\ResourceRegistryPort;
use App\Download\Domain\Repository\DownloadRequestRepositoryInterface;
use App\Download\Service\DownloadAccessService;
use App\Entity\DownloadRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Endpoint appelé par n8n après réception du webhook Mautic.
 * Quand le formulaire Mautic est soumis directement par l'utilisateur,
 * on crée la DownloadRequest et on génère le token côté Symfony.
 */
#[Route('/api/download')]
final class DownloadCreateFromMauticController extends AbstractController
{
    public function __construct(
        private readonly DownloadRequestRepositoryInterface $repository,
        private readonly ResourceRegistryPort $resourceRegistry,
        private readonly DownloadAccessService $downloadAccessService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ?string $authorizeApiKey,
    ) {
    }

    #[Route('/create-from-mautic', name: 'app_download_create_from_mautic', methods: ['POST'])]
    public function createFromMautic(Request $request): JsonResponse
    {
        if ($this->authorizeApiKey !== null && $this->authorizeApiKey !== '') {
            $apiKey = $request->headers->get('X-Api-Key') ?? $request->request->get('api_key');
            if ($apiKey !== $this->authorizeApiKey) {
                return $this->json(['success' => false, 'message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
        }

        $data = json_decode((string) $request->getContent(), true) ?? $request->request->all();
        $email = trim((string) ($data['email'] ?? ''));
        $resourceSlug = trim((string) ($data['ressource_id'] ?? $data['resource_id'] ?? ''));

        if ('' === $email) {
            return $this->json([
                'success' => false,
                'message' => 'Email requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'message' => 'Email invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ('' === $resourceSlug) {
            $resourceSlug = 'modele-5m';
        }

        if (!$this->resourceRegistry->has($resourceSlug)) {
            return $this->json([
                'success' => false,
                'message' => 'Ressource inconnue.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $downloadRequest = new DownloadRequest($email, $resourceSlug);
        $this->repository->save($downloadRequest);

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
            'download_request_id' => $downloadRequest->getId()->toRfc4122(),
            'expires_in_hours' => 24,
        ], Response::HTTP_OK);
    }
}
