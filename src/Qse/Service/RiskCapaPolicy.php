<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\RiskMatrixEntry;

/**
 * Règles : risque critique → CAPA obligatoire (contrôle côté application avant passage en statut actif).
 */
final class RiskCapaPolicy
{
    private const CRITICAL_SCORE_THRESHOLD = 12;

    public function isCritical(RiskMatrixEntry $entry): bool
    {
        $score = $entry->getCriticalityScore();
        if ($score !== null && $score >= self::CRITICAL_SCORE_THRESHOLD) {
            return true;
        }

        return $entry->getRiskLevel() === 'critical';
    }

    public function requiresLinkedCapa(RiskMatrixEntry $entry): bool
    {
        if (!$this->isCritical($entry)) {
            return false;
        }

        return $entry->getLinkedCapas()->isEmpty();
    }

    public function assertCanActivateCriticalRisk(RiskMatrixEntry $entry): void
    {
        if ($this->isCritical($entry) && $entry->getLinkedCapas()->isEmpty()) {
            throw new \InvalidArgumentException('Un risque critique doit être lié à au moins une action CAPA.');
        }
    }
}
