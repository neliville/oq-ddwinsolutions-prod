<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\User;

/**
 * Façade : agrège score, priorités, graphiques et activité pour le cockpit PDCA.
 */
final class PdcaCockpitDataProvider
{
    public function __construct(
        private readonly CockpitScoreCalculator $scoreCalculator,
        private readonly PdcaPriorityActionsBuilder $priorityActionsBuilder,
        private readonly PdcaChartsBuilder $chartsBuilder,
        private readonly QseActivityAggregator $activityAggregator,
    ) {
    }

    /**
     * @param array<string, mixed> $cockpit
     * @param array{qqoqccp: int, amdec: int, audit_plans: int, risks: int} $plan
     * @param array{capa_open: int} $do
     * @param array{audits: int, audits_by_standard?: list<array{code: string, label: string, cnt: int}>, pareto: int} $check
     * @param array{ishikawa: int, five_why: int, eight_d: int} $act
     *
     * @return array{
     *   score: array,
     *   phaseScores: array{plan: int|null, do: int|null, check: int|null, act: int|null},
     *   pdcaProgress: int|null,
     *   priority: list<array>,
     *   charts: array{charts: list<array>},
     *   activity: list<\App\Qse\Dto\ActivityItem>
     * }
     */
    public function build(User $owner, array $cockpit, array $plan, array $do, array $check, array $act): array
    {
        $score = $this->scoreCalculator->computeGlobalScore($cockpit, $plan, $do, $check, $act);
        $phaseScores = $this->scoreCalculator->computePhaseScores($cockpit, $plan, $do, $check, $act);
        $pdcaProgress = $this->scoreCalculator->computePdcaProgress($phaseScores);

        return [
            'score' => $score,
            'phaseScores' => $phaseScores,
            'pdcaProgress' => $pdcaProgress,
            'priority' => $this->priorityActionsBuilder->build($cockpit),
            'charts' => $this->chartsBuilder->build($owner, $cockpit, $check, $phaseScores),
            'activity' => $this->activityAggregator->recent($owner, 8),
        ];
    }
}
