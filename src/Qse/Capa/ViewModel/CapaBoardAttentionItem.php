<?php

declare(strict_types=1);

namespace App\Qse\Capa\ViewModel;

final class CapaBoardAttentionItem
{
    public const TYPE_OVERDUE = 'overdue';
    public const TYPE_VERIFICATION = 'verification';
    public const TYPE_VALIDATION = 'validation';
    public const TYPE_CRITICAL = 'critical';

    /**
     * @param array<string, int|string> $ctaParams
     */
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $description,
        public readonly ?int $capaId,
        public readonly string $ctaLabel,
        public readonly string $ctaRoute,
        public readonly array $ctaParams = [],
    ) {
    }
}
