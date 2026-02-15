<?php

namespace App\Lead\Controller;

use App\Application\Lead\CreateLead;
use App\Application\Lead\CreateLeadRequest;
use App\Application\Notification\NotificationService;
use App\Entity\Lead;
use App\Repository\LeadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur public pour la gestion des leads
 */
#[Route('/api/lead')]
final class LeadController extends AbstractController
{
    public function __construct(
        private readonly CreateLead $createLead,
        private readonly NotificationService $notificationService,
        private readonly LeadRepository $leadRepository,
    ) {
    }

    /**
     * Crée un lead depuis un formulaire ou un outil
     */
    #[Route('', name: 'app_public_lead_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        // Récupération des paramètres UTM depuis la requête ou la session
        $utmSource = $data['utm_source'] ?? $request->query->get('utm_source');
        $utmMedium = $data['utm_medium'] ?? $request->query->get('utm_medium');
        $utmCampaign = $data['utm_campaign'] ?? $request->query->get('utm_campaign');

        $requestDto = new CreateLeadRequest(
            email: $data['email'] ?? null,
            name: $data['name'] ?? null,
            source: $data['source'] ?? 'tool',
            tool: $data['tool'] ?? null,
            utmSource: $utmSource,
            utmMedium: $utmMedium,
            utmCampaign: $utmCampaign,
            ipAddress: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
            sessionId: $request->getSession()->getId(),
            gdprConsent: $data['gdpr_consent'] ?? false,
        );

        try {
            $response = $this->createLead->execute($requestDto);

            // Notifier la création du lead (async)
            $lead = $this->leadRepository->find($response->id);
            if ($lead) {
                $this->notificationService->notifyLeadCreated($lead);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Lead créé avec succès',
                'data' => [
                    'id' => $response->id,
                    'score' => $response->score,
                    'type' => $response->type,
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la création du lead',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

