<?php

declare(strict_types=1);

namespace App\Qse\Audit\ViewModel;

final class AuditBoardKpis
{
    public function __construct(
        public readonly int $totalAudits,
        public readonly int $inProgress,
        public readonly int $overdue,
        public readonly ?float $avgCompliancePercent,
        public readonly int $openMajorNc,
        public readonly int $openMinorNc,
        public readonly int $criticalCapasLinked,
        public readonly int $activeStandards,
        public readonly float $globalProgressPercent,
        public readonly int $answeredRequirements,
        public readonly int $totalRequirements,
    ) {
    }
}
