<?php

namespace App\Infrastructure\Mail;

use App\Lead\Infrastructure\Brevo\BrevoSyncService;
use App\Repository\LeadRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler Messenger : enrichit le lead (re-sync Brevo avec score/type à jour).
 */
#[AsMessageHandler]
class LeadEnrichmentMessageHandler
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly BrevoSyncService $brevoSyncService,
    ) {
    }

    public function __invoke(LeadEnrichmentMessage $message): void
    {
        $lead = $this->leadRepository->find($message->leadId);
        if (!$lead) {
            return;
        }

        $this->brevoSyncService->syncLead($lead);
    }
}
