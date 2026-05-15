<?php

declare(strict_types=1);

namespace App\Qse\Audit\ViewModel;

use App\Entity\Qse\Audit;
use App\Qse\Enum\AuditVerdict;
use App\Qse\Service\AuditEvaluationVerdictHelper;
use App\Repository\Qse\AuditActivityLogRepository;
use App\Repository\Qse\AuditRequirementRepository;

final class AuditCockpitViewModelFactory
{
    public function __construct(
        private readonly AuditRequirementRepository $requirementRepository,
        private readonly AuditActivityLogRepository $activityLogRepository,
    ) {
    }

    public function build(Audit $audit): AuditCockpitMetrics
    {
        $std = $audit->getAuditStandard();
        if ($std === null) {
            return new AuditCockpitMetrics(
                totalRequirements: 0,
                answeredRequirements: 0,
                majorNc: 0,
                minorNc: 0,
                observations: 0,
                toReview: 0,
                conform: 0,
                notApplicable: 0,
                notEvaluated: 0,
                evidenceMissing: 0,
                globalComplianceRate: $audit->getGlobalComplianceRate(),
                verdictCounts: [],
                chapterSummaries: [],
                chartConfig: $this->emptyChartConfig(),
                timelinePreview: [],
            );
        }

        $evaluationsByReqId = [];
        foreach ($audit->getEvaluations() as $ev) {
            $rid = $ev->getRequirement()?->getId();
            if ($rid !== null) {
                $evaluationsByReqId[$rid] = $ev;
            }
        }

        $chapters = $this->requirementRepository->findDistinctChaptersForStandard($std);
        $verdictCounts = [];
        foreach (AuditVerdict::orderedChoices() as $v) {
            $verdictCounts[$v->value] = 0;
        }

        $total = 0;
        $answered = 0;
        $majorNc = 0;
        $minorNc = 0;
        $observations = 0;
        $toReview = 0;
        $conform = 0;
        $notApplicable = 0;
        $notEvaluated = 0;
        $evidenceMissing = 0;
        $chapterSummaries = [];

        foreach ($chapters as $chapter) {
            $requirements = $this->requirementRepository->findByChapterOrderedForStandard($chapter, $std);
            $chTotal = \count($requirements);
            $chAnswered = 0;
            $chConform = 0;
            $chPartiel = 0;
            $chNc = 0;
            $chCounts = [];
            foreach (AuditVerdict::orderedChoices() as $v) {
                $chCounts[$v->value] = 0;
            }

            foreach ($requirements as $req) {
                ++$total;
                $ev = $evaluationsByReqId[$req->getId()] ?? null;
                $v = $ev !== null ? AuditEvaluationVerdictHelper::effectiveVerdict($ev) : null;

                if ($v === null) {
                    ++$notEvaluated;
                    ++$chCounts[AuditVerdict::NOT_EVALUATED->value];
                    ++$verdictCounts[AuditVerdict::NOT_EVALUATED->value];

                    continue;
                }

                ++$chCounts[$v->value];
                ++$verdictCounts[$v->value];

                if ($v === AuditVerdict::NOT_EVALUATED) {
                    ++$notEvaluated;

                    continue;
                }

                ++$answered;
                ++$chAnswered;
                match ($v) {
                    AuditVerdict::CONFORM => (function () use (&$conform, &$chConform): void {
                        ++$conform;
                        ++$chConform;
                    })(),
                    AuditVerdict::OBSERVATION => (function () use (&$observations, &$chPartiel): void {
                        ++$observations;
                        ++$chPartiel;
                    })(),
                    AuditVerdict::TO_REVIEW => (function () use (&$toReview, &$chPartiel): void {
                        ++$toReview;
                        ++$chPartiel;
                    })(),
                    AuditVerdict::MINOR_NC => (function () use (&$minorNc, &$chNc): void {
                        ++$minorNc;
                        ++$chNc;
                    })(),
                    AuditVerdict::MAJOR_NC => (function () use (&$majorNc, &$chNc): void {
                        ++$majorNc;
                        ++$chNc;
                    })(),
                    AuditVerdict::NOT_APPLICABLE => ++$notApplicable,
                    default => null,
                };

                if ($v->suggestsCapa() && ($ev === null || trim((string) $ev->getEvidence()) === '')) {
                    ++$evidenceMissing;
                }
            }

            $applicable = $chConform + $chPartiel + $chNc;
            $chRate = $applicable > 0 ? round(100.0 * $chConform / $applicable, 1) : null;
            $chapterSummaries[$chapter] = [
                'chapter' => $chapter,
                'total' => $chTotal,
                'answered' => $chAnswered,
                'conformRate' => $chRate,
                'verdictCounts' => $chCounts,
            ];
        }

        $logs = $this->activityLogRepository->findRecentForAudit($audit, 12);
        $timelinePreview = [];
        foreach (array_reverse($logs) as $log) {
            $req = $log->getAuditEvaluation()?->getRequirement();
            $timelinePreview[] = [
                'time' => $log->getCreatedAt()->format('d/m H:i'),
                'label' => $req !== null
                    ? sprintf('%s — %s', $req->getIsoArticle(), $log->getAction())
                    : $log->getAction(),
            ];
        }

        return new AuditCockpitMetrics(
            totalRequirements: $total,
            answeredRequirements: $answered,
            majorNc: $majorNc,
            minorNc: $minorNc,
            observations: $observations,
            toReview: $toReview,
            conform: $conform,
            notApplicable: $notApplicable,
            notEvaluated: $notEvaluated,
            evidenceMissing: $evidenceMissing,
            globalComplianceRate: $audit->getGlobalComplianceRate(),
            verdictCounts: $verdictCounts,
            chapterSummaries: $chapterSummaries,
            chartConfig: $this->buildChartConfig($chapterSummaries, $verdictCounts),
            timelinePreview: $timelinePreview,
        );
    }

    /**
     * @param array<string, array<string, mixed>> $chapterSummaries
     * @param array<string, int>                  $verdictCounts
     *
     * @return array<string, mixed>
     */
    private function buildChartConfig(array $chapterSummaries, array $verdictCounts): array
    {
        $labels = [];
        $radarValues = [];
        foreach ($chapterSummaries as $row) {
            $labels[] = (string) $row['chapter'];
            $radarValues[] = $row['conformRate'] ?? 0.0;
        }

        return [
            'radar' => [
                'labels' => $labels,
                'values' => $radarValues,
            ],
            'distribution' => [
                'labels' => ['Conforme', 'Observation', 'À revoir', 'NC mineure', 'NC majeure', 'N/A', 'Non évalué'],
                'values' => [
                    $verdictCounts[AuditVerdict::CONFORM->value] ?? 0,
                    $verdictCounts[AuditVerdict::OBSERVATION->value] ?? 0,
                    $verdictCounts[AuditVerdict::TO_REVIEW->value] ?? 0,
                    $verdictCounts[AuditVerdict::MINOR_NC->value] ?? 0,
                    $verdictCounts[AuditVerdict::MAJOR_NC->value] ?? 0,
                    $verdictCounts[AuditVerdict::NOT_APPLICABLE->value] ?? 0,
                    $verdictCounts[AuditVerdict::NOT_EVALUATED->value] ?? 0,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyChartConfig(): array
    {
        return [
            'radar' => ['labels' => [], 'values' => []],
            'distribution' => ['labels' => [], 'values' => []],
        ];
    }
}
