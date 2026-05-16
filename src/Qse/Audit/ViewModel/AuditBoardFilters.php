<?php

declare(strict_types=1);

namespace App\Qse\Audit\ViewModel;

use App\Qse\Enum\AuditExecutionStatus;

final class AuditBoardFilters
{
    public const SORT_RECENT = 'recent';
    public const SORT_OLDEST = 'oldest';
    public const SORT_COMPLIANCE_ASC = 'compliance_asc';
    public const SORT_COMPLIANCE_DESC = 'compliance_desc';
    public const SORT_COMPANY = 'company';

    public function __construct(
        public readonly string $search = '',
        public readonly string $status = '',
        public readonly string $standardCode = '',
        public readonly string $auditor = '',
        public readonly ?int $complianceMin = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly string $sort = self::SORT_RECENT,
        public readonly int $page = 1,
        public readonly int $perPage = 10,
    ) {
    }

    public static function fromLiveProps(
        string $search,
        string $status,
        string $standardCode,
        string $auditor,
        string $complianceMin,
        string $dateFrom,
        string $dateTo,
        string $sort,
        int $page,
    ): self {
        $compliance = trim($complianceMin);
        $complianceInt = $compliance !== '' && is_numeric($compliance) ? (int) $compliance : null;

        return new self(
            search: trim($search),
            status: trim($status),
            standardCode: trim($standardCode),
            auditor: trim($auditor),
            complianceMin: $complianceInt,
            dateFrom: self::normalizeDate($dateFrom),
            dateTo: self::normalizeDate($dateTo),
            sort: self::normalizeSort($sort),
            page: max(1, $page),
        );
    }

    public function statusEnum(): ?AuditExecutionStatus
    {
        if ($this->status === '') {
            return null;
        }

        return AuditExecutionStatus::tryFrom($this->status);
    }

    /**
     * @return list<string>
     */
    public static function sortChoices(): array
    {
        return [
            self::SORT_RECENT,
            self::SORT_OLDEST,
            self::SORT_COMPLIANCE_ASC,
            self::SORT_COMPLIANCE_DESC,
            self::SORT_COMPANY,
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
            $dt = new \DateTimeImmutable($date);

            return $dt->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
