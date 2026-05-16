<?php

declare(strict_types=1);

namespace App\Qse\Dto;

/**
 * Événement d’activité QSE pour la timeline cockpit.
 */
final readonly class ActivityItem
{
    public function __construct(
        public string $id,
        public string $type,
        public string $label,
        public string $description,
        public ?string $routeName,
        public ?array $routeParams,
        public \DateTimeImmutable $occurredAt,
        public string $icon,
        public string $tone,
        /** Route index outil avec ?load= (prioritaire sur routeName si défini). */
        public ?string $toolIndexRoute = null,
        public ?int $toolRecordId = null,
    ) {
    }
}
