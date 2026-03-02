<?php

declare(strict_types=1);

namespace App\Download\Controller;

use App\Download\Application\ProcessWebhookDownloadRequest;
use App\Marketing\Exception\MauticApiException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Endpoint called by n8n after Mautic webhook.
 * Thin controller: auth, parse, delegate to ProcessWebhookDownloadRequest, return JSON.
 */
#[Route('/api/download')]
final class DownloadCreateFromMauticController extends AbstractController
{
    public function __construct(
        private readonly ProcessWebhookDownloadRequest $processWebhookDownloadRequest,
        private readonly ?string $authorizeApiKey,
        private readonly LoggerInterface $logger,
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

        $raw = json_decode((string) $request->getContent(), true) ?? $request->request->all();
        // n8n peut envoyer [ { body: {...} } ] ou { body: {...} } ou directement le body
        $data = $raw;
        if (\is_array($raw) && isset($raw['body']) && \is_array($raw['body'])) {
            $data = $raw['body'];
        } elseif (\is_array($raw) && isset($raw[0]['body']) && \is_array($raw[0]['body'])) {
            $data = $raw[0]['body'];
        }

        try {
            $result = $this->processWebhookDownloadRequest->execute($data);
            return $this->json($result, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (MauticApiException $e) {
            $this->logger->error('Mautic API failed in create-from-mautic', [
                'status_code' => $e->getStatusCode(),
                'message' => $e->getMessage(),
                'response_body' => $e->getResponseBody(),
            ]);
            return $this->json([
                'success' => false,
                'message' => 'CRM indisponible. Réessayez plus tard.',
            ], Response::HTTP_BAD_GATEWAY);
        }
    }
}
