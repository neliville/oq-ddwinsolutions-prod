<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\RiskMatrixEntry;
use App\Qse\Enum\RiskEntryStatus;

final class RiskCriticalityPresenter
{
    public function __construct(
        private readonly RiskCapaPolicy $riskCapaPolicy,
    ) {
    }

    /**
     * @return array{level: string, label: string}
     */
    public function present(RiskMatrixEntry $entry): array
    {
        $status = $entry->getStatus();
        if ($status === RiskEntryStatus::MAITRISE || $status === RiskEntryStatus::CLOTURE) {
            return ['level' => 'controlled', 'label' => 'Maîtrisé'];
        }

        if ($this->riskCapaPolicy->isCritical($entry) || $status === RiskEntryStatus::CRITIQUE) {
            return ['level' => 'critical', 'label' => 'Critique'];
        }

        $score = $entry->getCriticalityScore();
        if ($score !== null && $score >= 6) {
            return ['level' => 'medium', 'label' => 'Modéré'];
        }

        return ['level' => 'low', 'label' => 'Faible'];
    }
}
