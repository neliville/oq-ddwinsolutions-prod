<?php

declare(strict_types=1);

namespace App\EventListener\Qse;

use App\Entity\User;
use App\Entity\Qse\AuditActivityLog;
use App\Qse\Event\AuditEvaluationSavedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class AuditActivityLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AuditEvaluationSavedEvent::class => 'onEvaluationSaved',
        ];
    }

    public function onEvaluationSaved(AuditEvaluationSavedEvent $event): void
    {
        $ev = $event->evaluation;
        $audit = $ev->getAudit();
        if ($audit === null) {
            return;
        }

        $log = new AuditActivityLog();
        $log->setAudit($audit);
        $log->setAuditEvaluation($ev);
        $log->setAction('evaluation_saved');
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $log->setActor($user);
        }
        $req = $ev->getRequirement();
        $log->setPayload([
            'requirement_id' => $req?->getId(),
            'iso_article' => $req?->getIsoArticle(),
            'verdict' => $ev->getVerdict()?->value,
            'score' => $ev->getScore(),
        ]);

        $this->entityManager->persist($log);
    }
}
