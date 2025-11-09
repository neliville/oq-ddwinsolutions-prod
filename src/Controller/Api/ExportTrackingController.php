<?php

namespace App\Controller\Api;

use App\Service\Analytics\ExportLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/analytics', name: 'analytics_')]
class ExportTrackingController extends AbstractController
{
    public function __construct(private readonly ExportLogger $exportLogger)
    {
    }

    #[Route('/track-export', name: 'track_export', methods: ['POST'])]
    public function trackExport(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid JSON payload.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $tool = $payload['tool'] ?? null;
        $format = $payload['format'] ?? null;
        $metadata = $payload['metadata'] ?? [];

        if (!$tool || !$format) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Missing "tool" or "format" information.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->exportLogger->log($tool, $format, $request, is_array($metadata) ? $metadata : []);

        return new JsonResponse(['status' => 'ok']);
    }
}
