<?php

declare(strict_types=1);

namespace App\Download\Application\Port;

/**
 * Port : accès aux métadonnées des ressources téléchargeables.
 *
 * @phpstan-type ResourceInfo array{path: string, filename: string, label: string}
 */
interface ResourceRegistryPort
{
    /**
     * @return ResourceInfo|null
     */
    public function get(string $resourceSlug): ?array;

    public function has(string $resourceSlug): bool;

    /**
     * @return array<string, ResourceInfo>
     */
    public function all(): array;
}
