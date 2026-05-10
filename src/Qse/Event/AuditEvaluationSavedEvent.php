<?php

declare(strict_types=1);

namespace App\Qse\Event;

use App\Entity\Qse\AuditEvaluation;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Émis après persistance d’une évaluation d’audit — point d’accroche futur pour IA / analytics.
 *
 * @see docs/qse-cockpit-inventory.md
 */
final class AuditEvaluationSavedEvent extends Event
{
    public function __construct(
        public readonly AuditEvaluation $evaluation,
    ) {
    }
}
