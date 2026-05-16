<?php

declare(strict_types=1);

namespace App\Qse\Audit\ViewModel;

use App\Qse\Enum\AuditVerdict;

/**
 * Agrégats cockpit pour un audit (lecture seule).
 */
final readonly class AuditCockpitMetrics
{
    /**
     * @param array<string, int> $verdictCounts
     * @param array<string, array{chapter: string, total: int, answered: int, conformRate: ?float, verdictCounts: array<string, int>}> $chapterSummaries
     * @param array<string, mixed> $chartConfig
     * @param list<array{
     *     action: string,
     *     time: string,
     *     date: string,
     *     iso8601: string,
     *     title: string,
     *     detail: ?string,
     *     icon: string,
     *     tone: string,
     *     actor: ?string
     * }> $timelinePreview
     */
    public function __construct(
        public int $totalRequirements,
        public int $answeredRequirements,
        public int $majorNc,
        public int $minorNc,
        public int $observations,
        public int $toReview,
        public int $conform,
        public int $notApplicable,
        public int $notEvaluated,
        public int $evidenceMissing,
        public ?float $globalComplianceRate,
        public array $verdictCounts,
        public array $chapterSummaries,
        public array $chartConfig,
        public array $timelinePreview,
    ) {
    }

    public function progressPercent(): float
    {
        if ($this->totalRequirements <= 0) {
            return 0.0;
        }

        return round(100.0 * $this->answeredRequirements / $this->totalRequirements, 1);
    }
}
