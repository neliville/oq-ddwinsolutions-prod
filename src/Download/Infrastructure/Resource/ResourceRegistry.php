<?php

declare(strict_types=1);

namespace App\Download\Infrastructure\Resource;

use App\Download\Application\Port\ResourceRegistryPort;

/**
 * Registry des ressources téléchargeables (slug → métadonnées).
 * "path" = chemin relatif sous DOWNLOAD_BASE_PATH (ex. templates/fichier.xlsx).
 * "filename" = nom proposé au téléchargement (Content-Disposition).
 */
final class ResourceRegistry implements ResourceRegistryPort
{
    private const RESOURCES = [
        'ishikawa-5m-template' => [
            'path' => 'templates/Ishikawa_5M_Template_Outil-Qualite.xlsx',
            'filename' => 'Ishikawa_5M_Template_Outil-Qualite.xlsx',
            'label' => 'Modèle 5M (Ishikawa)',
        ],
        'modele-5m' => [
            'path' => 'templates/Ishikawa_5M_Template_Outil-Qualite.xlsx',
            'filename' => 'Ishikawa_5M_Template_Outil-Qualite.xlsx',
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
