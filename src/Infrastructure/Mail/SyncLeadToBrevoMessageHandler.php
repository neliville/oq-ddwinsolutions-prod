<?php

namespace App\Infrastructure\Mail;

use App\Lead\Infrastructure\Brevo\BrevoSyncService;
use App\Repository\LeadRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler Messenger : synchronise un lead vers Brevo (API contacts).
 */
#[AsMessageHandler]
class SyncLeadToBrevoMessageHandler
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly BrevoSyncService $brevoSyncService,
    ) {
    }

    public function __invoke(SyncLeadToBrevoMessage $message): void
    {
        $lead = $this->leadRepository->find($message->leadId);
        if (!$lead) {
            return;
        }

        $this->brevoSyncService->syncLead($lead);
    }
}
