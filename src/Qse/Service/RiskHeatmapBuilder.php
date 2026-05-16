<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\User;
use App\Repository\Qse\RiskMatrixEntryRepository;

/**
 * Grille probabilité × gravité (échelle normalisée 1–5) pour preview et chart PDCA.
 */
final class RiskHeatmapBuilder
{
    private const GRID_SIZE = 5;

    public function __construct(
        private readonly RiskMatrixEntryRepository $riskRepository,
    ) {
    }

    /**
     * @return array{
     *   hasData: bool,
     *   grid: array<string, array{p: int, s: int, count: int, zone: string}>,
     *   maxCount: int,
     *   totalMapped: int
     * }
     */
    public function buildPreview(User $owner): array
    {
        $risks = $this->riskRepository->findByOwner($owner, 500);
        $grid = $this->buildGridCounts($risks);
        $maxCount = 0;
        $totalMapped = 0;
        foreach ($grid as $cell) {
            $maxCount = max($maxCount, $cell['count']);
            $totalMapped += $cell['count'];
        }

        return [
            'hasData' => $totalMapped > 0,
            'grid' => $grid,
            'maxCount' => $maxCount,
            'totalMapped' => $totalMapped,
        ];
    }

    /**
     * Payload ApexCharts pour le cockpit PDCA.
     *
     * @return array<string, mixed>|null
     */
    public function buildChartPayload(User $owner): ?array
    {
        $risks = $this->riskRepository->findByOwner($owner, 500);
        if ($risks === []) {
            return null;
        }

        $grid = $this->buildGridCounts($risks);
        $series = [];
        for ($s = self::GRID_SIZE; $s >= 1; --$s) {
            $row = ['name' => 'G' . $s, 'data' => []];
            for ($p = 1; $p <= self::GRID_SIZE; ++$p) {
                $key = "{$p}-{$s}";
                $row['data'][] = ['x' => 'P' . $p, 'y' => $grid[$key]['count']];
            }
            $series[] = $row;
        }

        return [
            'ref' => 'risk_heatmap',
            'type' => 'heatmap',
            'series' => $series,
            'title' => 'Cartographie des risques',
        ];
    }

    /**
     * @param list<\App\Entity\Qse\RiskMatrixEntry> $risks
     *
     * @return array<string, array{p: int, s: int, count: int, zone: string}>
     */
    private function buildGridCounts(array $risks): array
    {
        $grid = [];
        for ($p = 1; $p <= self::GRID_SIZE; ++$p) {
            for ($s = 1; $s <= self::GRID_SIZE; ++$s) {
                $key = "{$p}-{$s}";
                $grid[$key] = [
                    'p' => $p,
                    's' => $s,
                    'count' => 0,
                    'zone' => $this->zoneForCell($p, $s),
                ];
            }
        }

        foreach ($risks as $risk) {
            $p = $risk->getProbability();
            $s = $risk->getSeverity();
            if ($p === null || $s === null) {
                continue;
            }
            $p = max(1, min(self::GRID_SIZE, $p));
            $s = max(1, min(self::GRID_SIZE, $s));
            ++$grid["{$p}-{$s}"]['count'];
        }

        return $grid;
    }

    private function zoneForCell(int $p, int $s): string
    {
        $sum = $p + $s;

        if ($sum <= 4) {
            return 'low';
        }

        if ($sum <= 7) {
            return 'medium';
        }

        return 'high';
    }
}
