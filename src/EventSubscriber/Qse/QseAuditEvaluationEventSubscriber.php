<?php

declare(strict_types=1);

namespace App\EventSubscriber\Qse;

use App\Qse\Event\AuditEvaluationSavedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Point d’accroche domaine (IA, exports, notifications) — aucune logique métier pour l’instant.
 */
final class QseAuditEvaluationEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AuditEvaluationSavedEvent::class => 'onEvaluationSaved',
        ];
    }

    public function onEvaluationSaved(AuditEvaluationSavedEvent $event): void
    {
        // Extension future : ne pas supprimer ce subscriber (contrat produit / observabilité).
    }
}
