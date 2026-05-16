<?php

declare(strict_types=1);

namespace App\Qse\Capa\ViewModel;

use App\Qse\Enum\CapaStatus;
use App\Qse\Enum\CapaType;

final class CapaBoardFilters
{
    public const SORT_RECENT = 'recent';
    public const SORT_OLDEST = 'oldest';
    public const SORT_DUE_ASC = 'due_asc';
    public const SORT_DUE_DESC = 'due_desc';
    public const SORT_TITLE = 'title';

    public function __construct(
        public readonly string $search = '',
        public readonly string $status = '',
        public readonly string $capaType = '',
        public readonly string $criticality = '',
        public readonly bool $overdueOnly = false,
        public readonly ?string $dueFrom = null,
        public readonly ?string $dueTo = null,
        public readonly string $sort = self::SORT_RECENT,
        public readonly int $page = 1,
        public readonly int $perPage = 10,
    ) {
    }

    public static function fromLiveProps(
        string $search,
        string $status,
        string $capaType,
        string $criticality,
        bool $overdueOnly,
        string $dueFrom,
        string $dueTo,
        string $sort,
        int $page,
    ): self {
        return new self(
            search: trim($search),
            status: trim($status),
            capaType: trim($capaType),
            criticality: trim($criticality),
            overdueOnly: $overdueOnly,
            dueFrom: self::normalizeDate($dueFrom),
            dueTo: self::normalizeDate($dueTo),
            sort: self::normalizeSort($sort),
            page: max(1, $page),
        );
    }

    public function statusEnum(): ?CapaStatus
    {
        if ($this->status === '') {
            return null;
        }

        return CapaStatus::tryFrom($this->status);
    }

    public function capaTypeEnum(): ?CapaType
    {
        if ($this->capaType === '') {
            return null;
        }

        return CapaType::tryFrom($this->capaType);
    }

    /**
     * @return list<string>
     */
    public static function sortChoices(): array
    {
        return [
            self::SORT_RECENT,
            self::SORT_OLDEST,
            self::SORT_DUE_ASC,
            self::SORT_DUE_DESC,
            self::SORT_TITLE,
        ];
    }

    private static function normalizeSort(string $sort): string
    {
        return \in_array($sort, self::sortChoices(), true) ? $sort : self::SORT_RECENT;
    }

    private static function normalizeDate(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }
        try {
            return (new \DateTimeImmutable($date))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
