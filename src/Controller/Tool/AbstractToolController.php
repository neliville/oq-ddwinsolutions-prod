<?php

namespace App\Controller\Tool;

use App\Application\Analytics\TrackingService;
use App\Application\Lead\CreateLead;
use App\Application\Lead\CreateLeadRequest;
use App\Application\Notification\NotificationService;
use App\Domain\Analytics\ToolUsedEvent;
use App\Entity\User;
use App\Repository\LeadRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractToolController extends AbstractController
{
    public function __construct(
        protected readonly CreateLead $createLead,
        protected readonly TrackingService $trackingService,
        protected readonly NotificationService $notificationService,
        protected readonly LeadRepository $leadRepository,
        protected readonly LoggerInterface $logger,
    ) {
    }

    abstract protected function getToolName(): string;

    /**
     * Crée un lead et track l'utilisation de l'outil (ne bloque pas en cas d'erreur).
     */
    protected function createLeadFromToolUsage(Request $request, string $tool, ?User $user = null): void
    {
        $sessionId = $request->getSession()->getId();
        $email = $user?->getEmail();

        if ($email) {
            $existingLead = $this->leadRepository->findByEmail($email);
            if ($existingLead && $existingLead->getTool() === $tool) {
                return;
            }
        }

        $utmSource = $request->query->get('utm_source') ?? $request->request->get('utm_source');
        $utmMedium = $request->query->get('utm_medium') ?? $request->request->get('utm_medium');
        $utmCampaign = $request->query->get('utm_campaign') ?? $request->request->get('utm_campaign');

        $leadRequest = new CreateLeadRequest(
            email: $email,
            name: null,
            source: 'tool',
            tool: $tool,
            utmSource: $utmSource,
            utmMedium: $utmMedium,
            utmCampaign: $utmCampaign,
            ipAddress: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
            sessionId: $sessionId,
            gdprConsent: false,
        );

        try {
            $leadResponse = $this->createLead->execute($leadRequest);

            $lead = $this->leadRepository->find($leadResponse->id);
            if ($lead) {
                $this->notificationService->notifyLeadCreated($lead);
            }

            $this->trackingService->trackToolUsed(new ToolUsedEvent(
                tool: $tool,
                sessionId: $sessionId,
                ipAddress: $request->getClientIp(),
                userAgent: $request->headers->get('User-Agent'),
                userId: $user?->getId(),
            ));
        } catch (\Exception $e) {
            $this->logger->error('Lead creation failed', [
                'tool' => $tool,
                'exception' => $e,
            ]);
        }
    }
}
