<?php

declare(strict_types=1);

namespace App\Qse\Import;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Lit la première feuille : ligne 1 = en-têtes, lignes suivantes = données.
 * Les en-têtes sont mappés vers les clés attendues par {@see AuditRequirementRowNormalizer}.
 */
final class AuditRequirementExcelReader
{
    /** @var array<string, string> clé normalisée (sans accent, snake) → champ normalizer */
    private const HEADER_TO_FIELD = [
        'chapitre' => 'chapter',
        'sous_chapitre' => 'sub_chapter',
        'article' => 'article',
        'iso_article' => 'article',
        'exigence' => 'requirement_text',
        'texte_exigence' => 'requirement_text',
        'requirement_text' => 'requirement_text',
        'cle' => 'legacy_key',
        'cle_stable' => 'legacy_key',
        'identifiant' => 'legacy_key',
        'legacy_key' => 'legacy_key',
        'commentaire' => 'iso_comment',
        'commentaire_metier' => 'iso_comment',
        'iso_comment' => 'iso_comment',
        'lien' => 'business_link',
        'lien_metier' => 'business_link',
        'business_link' => 'business_link',
        'pdca' => 'pdca_phase',
        'phase_pdca' => 'pdca_phase',
        'pdca_phase' => 'pdca_phase',
        'ordre' => 'display_order',
        'display_order' => 'display_order',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function readRows(string $absolutePath): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; ++$col) {
            $addr = Coordinate::stringFromColumnIndex($col) . '1';
            $val = $sheet->getCell($addr)->getValue();
            $headers[$col] = $this->normalizeHeader((string) $val);
        }

        $out = [];
        for ($row = 2; $row <= $highestRow; ++$row) {
            $assoc = [];
            $empty = true;
            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                $headerKey = $headers[$col] ?? '';
                if ($headerKey === '') {
                    continue;
                }
                $field = self::HEADER_TO_FIELD[$headerKey] ?? $headerKey;
                $addr = Coordinate::stringFromColumnIndex($col) . (string) $row;
                $cellVal = $sheet->getCell($addr)->getValue();
                if ($cellVal !== null && $cellVal !== '') {
                    $empty = false;
                }
                if (!\array_key_exists($field, $assoc)) {
                    $assoc[$field] = $cellVal;
                }
            }
            if (!$empty) {
                $out[] = $assoc;
            }
        }

        return $out;
    }

    private function normalizeHeader(string $raw): string
    {
        $t = trim(mb_strtolower($raw, 'UTF-8'));
        $t = str_replace(['-', "'", '’', '/'], ['_', '', '', '_'], $t);
        $t = preg_replace('/\s+/u', '_', $t) ?? $t;
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
        if (\is_string($ascii) && $ascii !== '') {
            $t = strtolower($ascii);
        }
        $t = preg_replace('/[^a-z0-9_]/', '', $t) ?? $t;

        return $t;
    }
}
