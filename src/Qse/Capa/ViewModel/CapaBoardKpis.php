<?php

declare(strict_types=1);

namespace App\Qse\Capa\ViewModel;

final class CapaBoardKpis
{
    public function __construct(
        public readonly int $totalCapas,
        public readonly int $openCapas,
        public readonly int $overdue,
        public readonly int $awaitingValidation,
        public readonly int $awaitingVerification,
        public readonly int $dueNext7Days,
    ) {
    }
}
