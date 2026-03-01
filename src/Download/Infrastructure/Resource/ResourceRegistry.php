<?php

declare(strict_types=1);

namespace App\Download\Infrastructure\Resource;

use App\Download\Application\Port\ResourceRegistryPort;

/**
 * Adapter : registry des ressources téléchargeables (slug → métadonnées fichier).
 */
final class ResourceRegistry implements ResourceRegistryPort
{
    private const RESOURCES = [
        'modele-5m' => [
            'path' => 'downloads/modele-5m.pdf',
            'filename' => 'modele-5m.pdf',
            'label' => 'Modèle 5M',
        ],
    ];

    public function get(string $resourceSlug): ?array
    {
        return self::RESOURCES[$resourceSlug] ?? null;
    }

    public function has(string $resourceSlug): bool
    {
        return isset(self::RESOURCES[$resourceSlug]);
    }

    public function all(): array
    {
        return self::RESOURCES;
    }
}
