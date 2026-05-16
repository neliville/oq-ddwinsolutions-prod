<?php

declare(strict_types=1);

namespace App\Qse\Risk\ViewModel;

use App\Qse\Enum\RiskEntryStatus;

final class RiskBoardFilters
{
    public const SORT_RECENT = 'recent';
    public const SORT_OLDEST = 'oldest';
    public const SORT_SCORE_DESC = 'score_desc';
    public const SORT_SCORE_ASC = 'score_asc';
    public const SORT_REVIEW_SOON = 'review_soon';

    public const CRITICALITY_LOW = 'low';
    public const CRITICALITY_MEDIUM = 'medium';
    public const CRITICALITY_CRITICAL = 'critical';
    public const CRITICALITY_CONTROLLED = 'controlled';

    public function __construct(
        public readonly string $search = '',
        public readonly string $status = '',
        public readonly string $criticality = '',
        public readonly string $sort = self::SORT_RECENT,
        public readonly int $page = 1,
        public readonly int $perPage = 10,
    ) {
    }

    public static function fromLiveProps(
        string $search,
        string $status,
        string $criticality,
        string $sort,
        int $page,
    ): self {
        return new self(
            search: trim($search),
            status: trim($status),
            criticality: self::normalizeCriticality(trim($criticality)),
            sort: self::normalizeSort($sort),
            page: max(1, $page),
        );
    }

    public function statusEnum(): ?RiskEntryStatus
    {
        if ($this->status === '') {
            return null;
        }

        return RiskEntryStatus::tryFrom($this->status);
    }

    /**
     * @return list<string>
     */
    public static function sortChoices(): array
    {
        return [
            self::SORT_RECENT,
            self::SORT_OLDEST,
            self::SORT_SCORE_DESC,
            self::SORT_SCORE_ASC,
            self::SORT_REVIEW_SOON,
        ];
    }

    private static function normalizeSort(string $sort): string
    {
        return \in_array($sort, self::sortChoices(), true) ? $sort : self::SORT_RECENT;
    }

    private static function normalizeCriticality(string $criticality): string
    {
        $allowed = [
            '',
            self::CRITICALITY_LOW,
            self::CRITICALITY_MEDIUM,
            self::CRITICALITY_CRITICAL,
            self::CRITICALITY_CONTROLLED,
        ];

        return \in_array($criticality, $allowed, true) ? $criticality : '';
    }
}
