<?php

declare(strict_types=1);

namespace App\Qse\Risk\ViewModel;

use App\Entity\Qse\RiskMatrixEntry;
use App\Qse\Enum\RiskEntryStatus;

final class RiskBoardRow
{
    public function __construct(
        public readonly RiskMatrixEntry $entry,
        public readonly int $id,
        public readonly string $label,
        public readonly ?int $criticalityScore,
        public readonly string $criticalityLevel,
        public readonly string $criticalityLabel,
        public readonly RiskEntryStatus $status,
        public readonly string $statusLabel,
        public readonly ?string $responsible,
        public readonly ?\DateTimeImmutable $reviewAt,
        public readonly int $capaCount,
        public readonly bool $requiresCapa,
        public readonly bool $reviewOverdue,
    ) {
    }
}
