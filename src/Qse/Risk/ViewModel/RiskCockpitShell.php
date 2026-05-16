<?php

declare(strict_types=1);

namespace App\Qse\Risk\ViewModel;

use App\Qse\Dto\ActivityItem;

final class RiskCockpitShell
{
    /**
     * @param array{
     *   hasData: bool,
     *   grid: array<string, array{p: int, s: int, count: int, zone: string}>,
     *   maxCount: int,
     *   totalMapped: int
     * } $heatmap
     * @param list<array{
     *   id: string,
     *   label: string,
     *   detail: string,
     *   count: int,
     *   tone: string,
     *   route: string,
     *   routeParams?: array<string, int|string>,
     *   cta_primary: string,
     *   cta_secondary: string|null
     * }> $priorityItems
     * @param list<ActivityItem> $activity
     */
    public function __construct(
        public readonly RiskBoardKpis $kpis,
        public readonly array $heatmap,
        public readonly array $priorityItems,
        public readonly array $activity,
        public readonly int $totalRisks,
    ) {
    }
}
