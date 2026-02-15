<?php

namespace App\Infrastructure\Mail;

use App\Repository\LeadRepository;
use App\Service\MailerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Handler Messenger pour traiter les notifications de leads créés.
 * Dispatche la sync Brevo (async) et envoie les emails de confirmation / notification admin.
 */
#[AsMessageHandler]
class LeadCreatedMessageHandler
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly MailerService $mailerService,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(LeadCreatedMessage $message): void
    {
        $lead = $this->leadRepository->find($message->leadId);
        if (!$lead) {
            return;
        }

        // Sync vers Brevo (async)
        $this->messageBus->dispatch(new SyncLeadToBrevoMessage($lead->getId()));
        // Pipeline scoring → qualification → enrichment (async)
        $this->messageBus->dispatch(new LeadScoringMessage($lead->getId()));

        // Email de confirmation à l'utilisateur si email + outil
        if ($lead->getEmail() && $lead->getTool()) {
            $this->mailerService->sendToolConfirmationEmail($lead);
        }

        // Notification admin (score > 50) est envoyée par LeadScoringMessageHandler après calcul du score
    }
}

