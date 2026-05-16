<?php

declare(strict_types=1);

namespace App\Qse\Audit\ViewModel;

use App\Entity\Qse\Audit;
use App\Qse\Enum\AuditExecutionStatus;

final class AuditBoardRow
{
    public function __construct(
        public readonly Audit $audit,
        public readonly string $label,
        public readonly string $standardCode,
        public readonly string $standardName,
        public readonly ?float $complianceRate,
        public readonly float $progressPercent,
        public readonly int $answeredRequirements,
        public readonly int $totalRequirements,
        public readonly int $majorNc,
        public readonly int $minorNc,
        public readonly int $capaCount,
        public readonly bool $hasOpenMajorNc,
        public readonly bool $isStale,
        public readonly bool $isBlocked,
        public readonly AuditExecutionStatus $status,
    ) {
    }
}
