<?php

declare(strict_types=1);

namespace App\Qse\Capa\ViewModel;

use App\Entity\Qse\CAPAAction;
use App\Qse\Enum\CapaStatus;

final class CapaBoardRow
{
    public function __construct(
        public readonly CAPAAction $capa,
        public readonly CapaStatus $status,
        public readonly int $workflowStep,
        public readonly int $workflowTotal,
        public readonly bool $isOverdue,
        public readonly bool $isDueSoon,
        public readonly ?string $sourceLabel,
        public readonly ?string $sourceRoute,
        /** @var array<string, int|string>|null */
        public readonly ?array $sourceRouteParams,
        public readonly string $originName,
        public readonly string $typeLabel,
        public readonly ?string $criticalityLabel,
    ) {
    }
}
