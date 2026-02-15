<?php

namespace App\Application\Notification;

use App\Entity\Lead;
use App\Entity\NewsletterSubscriber;
use App\Entity\User;
use App\Infrastructure\Mail\LeadCreatedMessage;
use App\Service\MailerService;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Service de notification (emails, webhooks)
 */
class NotificationService
{
    public function __construct(
        private readonly MailerService $mailerService,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * Notifie la création d'un lead (async via Messenger)
     */
    public function notifyLeadCreated(Lead $lead): void
    {
        $message = new LeadCreatedMessage(
            leadId: $lead->getId(),
            email: $lead->getEmail(),
            source: $lead->getSource(),
            tool: $lead->getTool(),
        );

        $this->messageBus->dispatch($message);
    }

    /**
     * Envoie un email de confirmation à l'utilisateur après création de valeur
     */
    public function sendUserConfirmationEmail(?string $email, ?string $name, string $tool): void
    {
        if (!$email) {
            return;
        }

        // TODO: Créer le template d'email
        // $this->mailerService->send(...);
    }

    /**
     * Notifie l'admin d'un nouveau lead qualifié
     */
    public function notifyAdminNewLead(Lead $lead): void
    {
        // Envoi async via Messenger
        $this->notifyLeadCreated($lead);
    }
}

