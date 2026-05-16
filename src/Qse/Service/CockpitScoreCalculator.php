<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\User;
use App\Qse\Enum\CapaStatus;

/**
 * Calcule le score QHSE global et les sous-scores PDCA (Plan / Do / Check / Act).
 */
final class CockpitScoreCalculator
{
    private const PLAN_TARGET = 8;

    /**
     * @param array<string, mixed> $cockpit
     * @param array{qqoqccp: int, amdec: int, audit_plans: int, risks: int} $plan
     * @param array{capa_open: int} $do
     * @param array{audits: int, pareto: int} $check
     * @param array{ishikawa: int, five_why: int, eight_d: int} $act
     *
     * @return array{
     *   score: int|null,
     *   trend: int|null,
     *   breakdown: array{compliance: float|null, capaHealth: float|null, riskCoverage: float|null, evaluationMaturity: float|null},
     *   initialized: bool
     * }
     */
    public function computeGlobalScore(array $cockpit, array $plan, array $do, array $check, array $act): array
    {
        $compliance = $this->percentOrNull($cockpit['avgAuditCompliancePercent'] ?? null);
        $openCapa = max(0, (int) ($cockpit['openCapaCount'] ?? $do['capa_open'] ?? 0));
        $overdueCapas = max(0, (int) ($cockpit['overdueOpenCapas'] ?? 0));
        $capaHealth = $openCapa > 0
            ? $this->clampPercent(100.0 * (1.0 - ($overdueCapas / $openCapa)))
            : ($openCapa === 0 ? 100.0 : null);

        $criticalNoCapa = max(0, (int) ($cockpit['criticalRisksWithoutCapa'] ?? 0));
        $riskDenom = max(1, (int) ($plan['risks'] ?? 0));
        $riskCoverage = $this->clampPercent(100.0 * (1.0 - min(1.0, $criticalNoCapa / $riskDenom)));

        $openNc = max(0, (int) ($cockpit['openNonConformEvaluations'] ?? 0));
        $evalDenom = max(1, $openNc + max(0, (int) ($check['audits'] ?? 0)) * 5);
        $evaluationMaturity = $this->clampPercent(100.0 * (1.0 - min(1.0, $openNc / $evalDenom)));

        $parts = [];
        if ($compliance !== null) {
            $parts[] = ['w' => 0.40, 'v' => $compliance];
        }
        if ($capaHealth !== null) {
            $parts[] = ['w' => 0.25, 'v' => $capaHealth];
        }
        if ($riskCoverage !== null) {
            $parts[] = ['w' => 0.20, 'v' => $riskCoverage];
        }
        if ($evaluationMaturity !== null) {
            $parts[] = ['w' => 0.15, 'v' => $evaluationMaturity];
        }

        $weightSum = array_sum(array_column($parts, 'w'));
        $score = null;
        if ($weightSum > 0) {
            $weighted = 0.0;
            foreach ($parts as $p) {
                $weighted += ($p['w'] / $weightSum) * $p['v'];
            }
            $score = (int) round($weighted);
        }

        $hasData = ($check['audits'] ?? 0) > 0
            || $openCapa > 0
            || ($plan['risks'] ?? 0) > 0
            || (($plan['qqoqccp'] ?? 0) + ($plan['amdec'] ?? 0) + ($plan['audit_plans'] ?? 0)) > 0
            || (($act['ishikawa'] ?? 0) + ($act['five_why'] ?? 0) + ($act['eight_d'] ?? 0)) > 0;

        if (!$hasData) {
            return [
                'score' => null,
                'trend' => null,
                'breakdown' => [
                    'compliance' => null,
                    'capaHealth' => null,
                    'riskCoverage' => null,
                    'evaluationMaturity' => null,
                ],
                'initialized' => false,
            ];
        }

        return [
            'score' => $score,
            'trend' => null,
            'breakdown' => [
                'compliance' => $compliance,
                'capaHealth' => $capaHealth,
                'riskCoverage' => $riskCoverage,
                'evaluationMaturity' => $evaluationMaturity,
            ],
            'initialized' => true,
        ];
    }

    /**
     * @param array<string, mixed> $cockpit
     * @param array{qqoqccp: int, amdec: int, audit_plans: int, risks: int} $plan
     * @param array{capa_open: int} $do
     * @param array{audits: int, pareto: int} $check
     * @param array{ishikawa: int, five_why: int, eight_d: int} $act
     *
     * @return array{plan: int|null, do: int|null, check: int|null, act: int|null}
     */
    public function computePhaseScores(array $cockpit, array $plan, array $do, array $check, array $act): array
    {
        $planActivity = (int) ($plan['qqoqccp'] ?? 0)
            + (int) ($plan['amdec'] ?? 0)
            + (int) ($plan['audit_plans'] ?? 0)
            + min((int) ($plan['risks'] ?? 0), 3);
        $planScore = $this->clampPercent(100.0 * min(1.0, $planActivity / self::PLAN_TARGET));

        $openCapa = max(0, (int) ($cockpit['openCapaCount'] ?? $do['capa_open'] ?? 0));
        $overdueCapas = max(0, (int) ($cockpit['overdueOpenCapas'] ?? 0));
        $awaitingVerif = max(0, (int) ($cockpit['capasAwaitingVerification'] ?? 0));
        $doBase = $openCapa > 0
            ? 100.0 * (1.0 - ($overdueCapas / $openCapa))
            : 100.0;
        $doPenalty = min(25.0, $awaitingVerif * 5.0);
        $doScore = $openCapa > 0 || $awaitingVerif > 0
            ? $this->clampPercent($doBase - $doPenalty)
            : null;

        $checkScore = $this->percentOrNull($cockpit['avgAuditCompliancePercent'] ?? null);
        if ($checkScore === null && ($check['audits'] ?? 0) > 0) {
            $overduePlans = (int) ($cockpit['overdueAuditPlans'] ?? 0);
            $checkScore = $this->clampPercent(100.0 - min(40.0, $overduePlans * 10.0));
        }

        $analysisTools = (int) ($act['ishikawa'] ?? 0) + (int) ($act['five_why'] ?? 0) + (int) ($act['eight_d'] ?? 0);
        $actDenom = max(1, $openCapa);
        $actScore = $analysisTools > 0
            ? $this->clampPercent(100.0 * min(1.0, $analysisTools / $actDenom))
            : null;

        return [
            'plan' => (int) round($planScore),
            'do' => $doScore !== null ? (int) round($doScore) : null,
            'check' => $checkScore !== null ? (int) round($checkScore) : null,
            'act' => $actScore !== null ? (int) round($actScore) : null,
        ];
    }

    /**
     * Progression PDCA globale (moyenne des phases disponibles).
     *
     * @param array{plan: int|null, do: int|null, check: int|null, act: int|null} $phaseScores
     */
    public function computePdcaProgress(array $phaseScores): ?int
    {
        $values = array_values(array_filter($phaseScores, static fn (?int $v): bool => $v !== null));
        if ($values === []) {
            return null;
        }

        return (int) round(array_sum($values) / \count($values));
    }

    private function percentOrNull(mixed $value): ?float
    {
        if ($value === null || !is_numeric($value)) {
            return null;
        }

        return $this->clampPercent((float) $value);
    }

    private function clampPercent(float $value): float
    {
        return max(0.0, min(100.0, $value));
    }
}
