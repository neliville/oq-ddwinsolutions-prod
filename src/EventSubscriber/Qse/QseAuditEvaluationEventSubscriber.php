<?php

declare(strict_types=1);

namespace App\EventSubscriber\Qse;

use App\Qse\Event\AuditEvaluationSavedEvent;
use App\Qse\Service\AuditEvaluationAutoCapaService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Point d’accroche domaine (CAPA auto, IA, exports, notifications).
 */
final class QseAuditEvaluationEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuditEvaluationAutoCapaService $autoCapaService,
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
        $this->autoCapaService->ensureDraftForEvaluation($event->evaluation);
    }
}
