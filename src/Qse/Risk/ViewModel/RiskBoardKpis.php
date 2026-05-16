<?php

declare(strict_types=1);

namespace App\Qse\Risk\ViewModel;

final class RiskBoardKpis
{
    public function __construct(
        public readonly int $openRisks,
        public readonly int $criticalRisks,
        public readonly int $inTreatment,
        public readonly int $closedThisMonth,
        public readonly int $totalRisks,
    ) {
    }
}
