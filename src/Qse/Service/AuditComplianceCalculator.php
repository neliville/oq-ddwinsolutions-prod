<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\Audit;

/**
 * Recalcule le taux de conformité global d’un audit (logique alignée sur le HTML de référence : N/A exclus du dénominateur).
 */
final class AuditComplianceCalculator
{
    public function recalculate(Audit $audit): void
    {
        $conformes = 0;
        $partiels = 0;
        $nc = 0;
        $na = 0;
        $evaluated = 0;

        foreach ($audit->getEvaluations() as $ev) {
            $n = $ev->getScore();
            if ($n === null) {
                continue;
            }
            ++$evaluated;
            match ($n) {
                3 => ++$conformes,
                2 => ++$partiels,
                1 => ++$nc,
                0 => ++$na,
                default => null,
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
