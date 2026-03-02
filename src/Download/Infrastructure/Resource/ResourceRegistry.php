<?php

declare(strict_types=1);

namespace App\Download\Infrastructure\Resource;

use App\Download\Application\Port\ResourceRegistryPort;

/**
 * Adapter : registry des ressources téléchargeables (slug → métadonnées fichier).
 * La ressource de la page 5M est le fichier var/downloads/Ishikawa_5M_Template_Outil-Qualite.xlsx.
 */
final class ResourceRegistry implements ResourceRegistryPort
{
    /** Fichier de la page téléchargement 5M : var/downloads/Ishikawa_5M_Template_Outil-Qualite.xlsx */
    private const FILE_5M = 'Ishikawa_5M_Template_Outil-Qualite.xlsx';

    private const RESOURCES = [
        'ishikawa-5m-template' => [
            'path' => self::FILE_5M,
            'filename' => self::FILE_5M,
            'label' => 'Modèle 5M (Ishikawa)',
        ],
        // Alias pour rétrocompatibilité (anciens tokens / ressource_id)
        'modele-5m' => [
            'path' => self::FILE_5M,
            'filename' => self::FILE_5M,
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
