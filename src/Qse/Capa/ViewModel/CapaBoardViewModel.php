<?php

declare(strict_types=1);

namespace App\Qse\Capa\ViewModel;

use App\Qse\Dto\ActivityItem;

final class CapaBoardViewModel
{
    /**
     * @param list<CapaBoardRow>              $rows
     * @param list<CapaBoardAttentionItem>  $attentionItems
     * @param list<ActivityItem>            $activityItems
     * @param array{
     *     statuses: list<array{value: string, label: string}>,
     *     types: list<array{value: string, label: string}>,
     *     criticalities: list<string>
     * } $filterOptions
     */
    public function __construct(
        public readonly CapaBoardKpis $kpis,
        public readonly array $rows,
        public readonly array $attentionItems,
        public readonly array $activityItems,
        public readonly array $filterOptions,
        public readonly int $totalCount,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $totalPages,
    ) {
    }
}
