<?php

namespace App\Tools\Api;

use App\Application\Analytics\TrackingEventRecorder;
use App\Application\Analytics\TrackingEventType;
use App\Entity\User;
use App\Service\Analytics\ExportLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/analytics', name: 'analytics_')]
class ExportTrackingController extends AbstractController
{
    public function __construct(
        private readonly ExportLogger $exportLogger,
        private readonly TrackingEventRecorder $trackingEventRecorder,
    ) {
    }

    #[Route('/track-export', name: 'track_export', methods: ['POST'])]
    #[IsGranted('ROLE_USER', message: 'Connectez-vous pour enregistrer un export.')]
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

        $user = $this->getUser();
        $this->trackingEventRecorder->record(
            TrackingEventType::EXPORT_TRIGGERED,
            array_merge(
                ['format' => $format],
                is_array($metadata) ? $metadata : [],
            ),
            $user instanceof User ? $user : null,
            \is_string($tool) ? $tool : null,
            'export',
            'api',
        );

        return new JsonResponse(['status' => 'ok']);
    }
}
