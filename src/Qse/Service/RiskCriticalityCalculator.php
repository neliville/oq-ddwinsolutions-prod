<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\RiskMatrixEntry;

/**
 * Calcul unifié du score et du niveau de risque (aligné board / RiskCapaPolicy).
 */
final class RiskCriticalityCalculator
{
    public const MEDIUM_SCORE_THRESHOLD = 6;

    public const CRITICAL_SCORE_THRESHOLD = 12;

    public const LEVEL_LOW = 'low';

    public const LEVEL_MEDIUM = 'medium';

    public const LEVEL_HIGH = 'high';

    public const LEVEL_CRITICAL = 'critical';

    public function computeScore(?int $severity, ?int $probability, ?int $detection): ?int
    {
        if ($severity === null || $probability === null || $detection === null) {
            return null;
        }

        return $severity * $probability * $detection;
    }

    /**
     * @return self::LEVEL_*|null
     */
    public function deriveRiskLevel(?int $score): ?string
    {
        if ($score === null) {
            return null;
        }

        if ($score >= self::CRITICAL_SCORE_THRESHOLD) {
            return self::LEVEL_CRITICAL;
        }

        if ($score >= self::MEDIUM_SCORE_THRESHOLD) {
            return self::LEVEL_HIGH;
        }

        return self::LEVEL_LOW;
    }

    /**
     * @return array{level: string, label: string}
     */
    public function presentLevel(?int $score): array
    {
        $level = $this->deriveRiskLevel($score);

        return match ($level) {
            self::LEVEL_CRITICAL => ['level' => 'critical', 'label' => 'Critique'],
            self::LEVEL_HIGH => ['level' => 'medium', 'label' => 'Modéré'],
            self::LEVEL_LOW => ['level' => 'low', 'label' => 'Faible'],
            default => ['level' => 'low', 'label' => '—'],
        };
    }

    /**
     * Normalise gravité / probabilité sur l'échelle matrice 1–5.
     */
    public function normalizeForMatrix(int $value): int
    {
        return max(1, min(5, $value));
    }

    /**
     * Zone heatmap (low / medium / high) pour une cellule P×G normalisée.
     */
    public function zoneForCell(int $probability, int $severity): string
    {
        $p = $this->normalizeForMatrix($probability);
        $s = $this->normalizeForMatrix($severity);
        $sum = $p + $s;

        if ($sum <= 4) {
            return 'low';
        }

        if ($sum <= 7) {
            return 'medium';
        }

        return 'high';
    }

    public function applyToEntry(RiskMatrixEntry $entry): void
    {
        $score = $this->computeScore(
            $entry->getSeverity(),
            $entry->getProbability(),
            $entry->getDetection(),
        );
        $entry->setCriticalityScore($score);
        $entry->setRiskLevel($this->deriveRiskLevel($score));
    }
}
