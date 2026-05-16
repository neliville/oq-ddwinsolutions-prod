<?php

declare(strict_types=1);

namespace App\Qse\Risk\ViewModel;

final class RiskBoardViewModel
{
    /**
     * @param list<RiskBoardRow> $rows
     * @param array{
     *   statuses: list<array{value: string, label: string}>,
     *   criticalities: list<array{value: string, label: string}>
     * } $filterOptions
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $filterOptions,
        public readonly int $totalCount,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $totalPages,
    ) {
    }
}
