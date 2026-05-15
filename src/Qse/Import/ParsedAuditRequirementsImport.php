<?php

declare(strict_types=1);

namespace App\Qse\Import;

/**
 * Résultat du parsing d’un fichier JSON d’exigences audit.
 */
final readonly class ParsedAuditRequirementsImport
{
    /**
     * @param list<array<string, mixed>> $rows
     */
    public function __construct(
        public string $standardCode,
        public array $rows,
    ) {
    }
}
