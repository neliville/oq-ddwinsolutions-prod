<?php

declare(strict_types=1);

namespace App\Qse\Audit\ViewModel;

final class AuditBoardAttentionItem
{
    public const TYPE_STALE = 'stale';
    public const TYPE_MAJOR_NC = 'major_nc';
    public const TYPE_CAPA = 'capa';
    public const TYPE_BLOCKED = 'blocked';

    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $description,
        public readonly ?int $auditId,
        public readonly string $ctaLabel,
        public readonly string $ctaRoute,
        /** @var array<string, int|string> */
        public readonly array $ctaParams = [],
    ) {
    }
}
