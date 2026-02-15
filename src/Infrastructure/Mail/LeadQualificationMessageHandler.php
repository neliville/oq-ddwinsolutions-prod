<?php

namespace App\Infrastructure\Mail;

use App\Entity\Lead;
use App\Repository\LeadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Handler Messenger : qualifie le lead (B2B/B2C) et enchaîne l'enrichissement.
 */
#[AsMessageHandler]
class LeadQualificationMessageHandler
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(LeadQualificationMessage $message): void
    {
        $lead = $this->leadRepository->find($message->leadId);
        if (!$lead) {
            return;
        }

        $type = $this->qualifyType($lead);
        $lead->setType($type);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new LeadEnrichmentMessage($lead->getId()));
    }

    private function qualifyType(Lead $lead): string
    {
        $email = $lead->getEmail();
        if (!$email) {
            return 'B2C';
        }
        $domain = substr(strrchr($email, '@') ?: '', 1);
        $domain = strtolower($domain ?? '');
        // Domaines courants grand public = B2C
        $b2cDomains = ['gmail.com', 'yahoo.fr', 'hotmail.com', 'orange.fr', 'free.fr', 'laposte.net', 'outlook.com', 'live.fr', 'wanadoo.fr', 'sfr.fr'];
        if (\in_array($domain, $b2cDomains, true)) {
            return 'B2C';
        }
        return 'B2B';
    }
}
