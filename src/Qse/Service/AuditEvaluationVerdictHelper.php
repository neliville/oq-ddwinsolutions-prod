<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\AuditEvaluation;
use App\Qse\Enum\AuditVerdict;

/**
 * Verdict effectif (priorité au nouveau champ) + synchronisation score legacy.
 */
final class AuditEvaluationVerdictHelper
{
    public static function effectiveVerdict(AuditEvaluation $evaluation): ?AuditVerdict
    {
        $v = $evaluation->getVerdict();
        if ($v !== null) {
            return $v;
        }

        return AuditVerdict::tryFromLegacyScore($evaluation->getScore());
    }

    public static function syncLegacyScore(AuditEvaluation $evaluation): void
    {
        $v = $evaluation->getVerdict();
        if ($v === null) {
            return;
        }
        $evaluation->setScore($v->toLegacyScore());
    }
}
