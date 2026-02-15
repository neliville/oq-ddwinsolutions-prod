<?php

namespace App\Infrastructure\Mail;

use App\Entity\Lead;
use App\Repository\LeadRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Handler Messenger : calcule le score d'un lead et enchaîne la qualification.
 * Notifie l'admin si score > 50.
 */
#[AsMessageHandler]
class LeadScoringMessageHandler
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly MailerService $mailerService,
    ) {
    }

    public function __invoke(LeadScoringMessage $message): void
    {
        $lead = $this->leadRepository->find($message->leadId);
        if (!$lead) {
            return;
        }

        $score = $this->computeScore($lead);
        $lead->setScore($score);
        $this->entityManager->flush();

        if ($score > 50) {
            $this->mailerService->sendAdminLeadNotification($lead);
        }

        $this->messageBus->dispatch(new LeadQualificationMessage($lead->getId()));
    }

    private function computeScore(Lead $lead): int
    {
        $score = 0;
        $source = $lead->getSource();
        $tool = $lead->getTool();

        if ($lead->getEmail()) {
            $score += 20;
        }
        if ($lead->getName()) {
            $score += 10;
        }
        if ($tool) {
            $score += 25;
        }
        if ('contact' === $source) {
            $score += 30;
        } elseif ('tool' === $source) {
            $score += 25;
        } elseif ('newsletter' === $source) {
            $score += 15;
        }

        return min(100, $score);
    }
}
