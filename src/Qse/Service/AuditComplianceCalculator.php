<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\Audit;
use App\Qse\Enum\AuditVerdict;

/**
 * Recalcule le taux de conformité global d’un audit (N/A et non évalués exclus du dénominateur).
 * Utilise le verdict effectif (verdict prioritaire, sinon score legacy 0–3).
 */
final class AuditComplianceCalculator
{
    public function recalculate(Audit $audit): void
    {
        $conformes = 0;
        $partiels = 0;
        $nc = 0;
        $na = 0;

        foreach ($audit->getEvaluations() as $ev) {
            $v = AuditEvaluationVerdictHelper::effectiveVerdict($ev);
            if ($v === null || $v === AuditVerdict::NOT_EVALUATED) {
                continue;
            }
            match ($v) {
                AuditVerdict::CONFORM => ++$conformes,
                AuditVerdict::OBSERVATION, AuditVerdict::TO_REVIEW => ++$partiels,
                AuditVerdict::MINOR_NC, AuditVerdict::MAJOR_NC => ++$nc,
                AuditVerdict::NOT_APPLICABLE => ++$na,
                AuditVerdict::NOT_EVALUATED => null,
            };
        }

        $applicable = $conformes + $partiels + $nc;
        $rate = $applicable > 0 ? round(100.0 * $conformes / $applicable, 2) : null;

        $audit->setGlobalComplianceRate($rate);
        $globalScore = $rate !== null ? (int) round($rate) : null;
        $audit->setGlobalScore($globalScore);
        $audit->setUpdatedAt(new \DateTimeImmutable());
    }
}
