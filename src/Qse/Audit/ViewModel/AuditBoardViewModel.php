<?php

declare(strict_types=1);

namespace App\Qse\Audit\ViewModel;

final class AuditBoardViewModel
{
    /**
     * @param list<AuditBoardRow>            $rows
     * @param list<AuditBoardAttentionItem>  $attentionItems
     * @param list<array<string, mixed>>     $timelineEntries
     * @param array{
     *   standards: list<array{code: string, name: string}>,
     *   auditors: list<string>,
     *   statuses: list<array{value: string, label: string}>
     * } $filterOptions
     */
    public function __construct(
        public readonly AuditBoardKpis $kpis,
        public readonly array $rows,
        public readonly array $attentionItems,
        public readonly array $timelineEntries,
        public readonly array $filterOptions,
        public readonly int $totalCount,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $totalPages,
        /** @var array<int, int> */
        public readonly array $capaCountsByAuditId,
    ) {
    }
}
