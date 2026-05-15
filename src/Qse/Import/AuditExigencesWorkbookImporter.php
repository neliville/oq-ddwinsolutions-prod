<?php

declare(strict_types=1);

namespace App\Qse\Import;

use App\Entity\Qse\AuditStandard;
use App\Repository\Qse\AuditStandardRepository;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Import des feuilles « 9001 / 14001 / 45001 » du classeur métier
 * {@see self::DEFAULT_FILENAME} (ligne 1 = titre, ligne 2 = en-têtes, données dès la ligne 3).
 */
final class AuditExigencesWorkbookImporter
{
    public const DEFAULT_FILENAME = 'Exigences ISO 9001v2015__en_cours.xlsx';

    /**
     * Noms d’onglets Excel (toujours des chaînes : en PHP, les clés '9001' dans un tableau
     * deviennent des entiers, ce qui casse {@see Spreadsheet::getSheetByName()} typé string).
     *
     * @var list<string>
     */
    private const WORKBOOK_SHEET_TABS = ['9001', '14001', '45001'];

    /** Onglets Excel → code {@see AuditStandard} */
    private const TAB_TO_STANDARD_CODE = [
        '9001' => 'iso_9001',
        '14001' => 'iso_14001',
        '45001' => 'iso_45001',
    ];

    public function __construct(
        private readonly AuditStandardRepository $auditStandardRepository,
        private readonly AuditRequirementUpserter $upserter,
    ) {
    }

    /**
     * @param list<string> $tabs Onglets à traiter : « 9001 », « 14001 », « 45001 »
     *
     * @return array<string, array{inserted: int, updated: int, rows: int}>
     */
    public function import(string $absolutePath, array $tabs, ?string $sourceVersion = null): array
    {
        if (!is_readable($absolutePath)) {
            throw new \InvalidArgumentException('Fichier illisible : ' . $absolutePath);
        }
        $spreadsheet = IOFactory::load($absolutePath);
        $out = [];
        foreach ($tabs as $tab) {
            $tab = trim((string) $tab);
            $code = self::TAB_TO_STANDARD_CODE[$tab] ?? null;
            if ($code === null) {
                throw new \InvalidArgumentException('Onglet inconnu : ' . $tab . ' (attendu : 9001, 14001 ou 45001).');
            }
            $standard = $this->auditStandardRepository->findOneByCode($code);
            if (!$standard instanceof AuditStandard) {
                throw new \InvalidArgumentException('Référentiel introuvable en base : ' . $code);
            }
            $sheet = $spreadsheet->getSheetByName($tab);
            if (!$sheet instanceof Worksheet) {
                throw new \InvalidArgumentException('Feuille « ' . $tab . ' » introuvable dans le classeur.');
            }
            $rows = $this->buildRowsForSheet($sheet, $standard);
            $stats = $this->upserter->upsertRows($standard, $rows, $sourceVersion ?? basename($absolutePath));
            $stats['rows'] = \count($rows);
            $out[$tab] = $stats;
        }

        return $out;
    }

    /**
     * Si le fichier contient les trois onglets « 9001 », « 14001 » et « 45001 », extrait les lignes
     * pour l’onglet qui correspond au référentiel choisi. Sinon retourne null (fichier au format classique).
     *
     * @return list<array<string, mixed>>|null
     */
    public function tryReadDdwinWorkbookRows(string $absolutePath, AuditStandard $standard): ?array
    {
        if (!is_readable($absolutePath)) {
            throw new \InvalidArgumentException('Fichier illisible : ' . $absolutePath);
        }
        $spreadsheet = IOFactory::load($absolutePath);
        foreach (self::WORKBOOK_SHEET_TABS as $tab) {
            if (!$spreadsheet->getSheetByName($tab) instanceof Worksheet) {
                return null;
            }
        }
        $tabName = $this->findWorkbookTabForStandard($standard);
        if ($tabName === null) {
            throw new \InvalidArgumentException(
                'Ce fichier est le classeur multi-normes (onglets 9001, 14001, 45001). '
                . 'Choisissez le référentiel ISO 9001, 14001 ou 45001 correspondant à l’onglet à importer, '
                . 'ou utilisez un fichier au format simple (en-têtes en ligne 1, colonne cle / legacy_key).',
            );
        }
        $code = self::TAB_TO_STANDARD_CODE[$tabName];
        $std = $this->auditStandardRepository->findOneByCode($code);
        if (!$std instanceof AuditStandard) {
            throw new \InvalidArgumentException('Référentiel introuvable en base : ' . $code);
        }
        $sheet = $spreadsheet->getSheetByName($tabName);
        if (!$sheet instanceof Worksheet) {
            throw new \InvalidArgumentException('Feuille « ' . $tabName . ' » introuvable dans le classeur.');
        }

        return $this->buildRowsForSheet($sheet, $std);
    }

    /**
     * Retourne l’onglet Excel correspondant au référentiel (ex. iso_14001 → « 14001 »), ou null.
     */
    public function findWorkbookTabForStandard(AuditStandard $standard): ?string
    {
        foreach (self::WORKBOOK_SHEET_TABS as $tab) {
            $code = self::TAB_TO_STANDARD_CODE[$tab] ?? null;
            if ($code === $standard->getCode()) {
                return $tab;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildRowsForSheet(Worksheet $sheet, AuditStandard $standard): array
    {
        $headerRowIndex = $this->detectHeaderRow($sheet);
        $colMap = $this->mapColumns($sheet, $headerRowIndex);
        if (!isset($colMap['requirement_text'], $colMap['article'])) {
            throw new \InvalidArgumentException(
                'En-têtes insuffisants sur la feuille « ' . $sheet->getTitle() . ' » (exigence + article/paragraphe requis).'
            );
        }
        $highestRow = (int) $sheet->getHighestDataRow();
        $built = [];
        $lastArticleRaw = '';
        $lastMajorClause = null;
        $standardCode = $standard->getCode();
        $useIsoChapterLabels = \in_array($standardCode, ['iso_14001', 'iso_45001'], true);

        for ($r = $headerRowIndex + 1; $r <= $highestRow; ++$r) {
            $text = $this->cellStr($sheet, $colMap['requirement_text'], $r);
            if ($text === '') {
                continue;
            }
            $articleRaw = $this->cellStr($sheet, $colMap['article'], $r);
            if ($articleRaw === '') {
                $articleRaw = $lastArticleRaw;
            }
            $article = $articleRaw !== '' ? $articleRaw : '—';
            if ($articleRaw !== '') {
                $lastArticleRaw = $articleRaw;
            }

            if ($useIsoChapterLabels) {
                $major = AuditIsoManagementSystemChapterLabel::inferMajorClauseNumber($article);
                if ($major === null) {
                    $major = $lastMajorClause;
                }
                if ($major === null) {
                    throw new \InvalidArgumentException(
                        'Ligne ' . $r . ' de la feuille « ' . $sheet->getTitle()
                        . ' » : impossible de déterminer le grand chapitre (colonne article/paragraphe vide ou invalide).',
                    );
                }
                $lastMajorClause = $major;
                $chapter = AuditIsoManagementSystemChapterLabel::chapterHeading($standardCode, $major);
            } else {
                $chapter = $this->inferChapter($article);
            }
            $orderRaw = isset($colMap['display_order']) ? $this->cellStr($sheet, $colMap['display_order'], $r) : '';
            $displayOrder = is_numeric($orderRaw) ? (int) $orderRaw : 0;
            $comment = isset($colMap['iso_comment']) ? $this->cellStr($sheet, $colMap['iso_comment'], $r) : '';
            $comment = $comment === '' ? null : $comment;
            $legacyKey = $this->buildLegacyKey($standard, $article, $text, $r);
            $built[] = [
                'legacy_key' => $legacyKey,
                'chapter' => $chapter,
                'sub_chapter' => null,
                'article' => $article,
                'requirement_text' => $text,
                'iso_comment' => $comment,
                'business_link' => null,
                'pdca_phase' => null,
                'display_order' => $displayOrder,
            ];
        }

        return $built;
    }

    private function detectHeaderRow(Worksheet $sheet): int
    {
        for ($r = 1; $r <= 8; ++$r) {
            $foundExigence = false;
            $foundArticle = false;
            $highestColumn = $sheet->getHighestDataColumn($r);
            $maxCol = Coordinate::columnIndexFromString($highestColumn);
            for ($c = 1; $c <= $maxCol; ++$c) {
                $raw = $this->cellStr($sheet, $c, $r);
                $h = $this->normalizeHeaderLabel($raw);
                if (str_contains($h, 'exigence')) {
                    $foundExigence = true;
                }
                if (str_contains($h, 'article') || str_contains($h, 'paragraphe')) {
                    $foundArticle = true;
                }
            }
            if ($foundExigence && $foundArticle) {
                return $r;
            }
        }

        throw new \InvalidArgumentException('Ligne d’en-tête introuvable (attendu : « Exigence » + « Article » ou « Paragraphe »).');
    }

    /**
     * @return array<string, int> champ → index de colonne (1-based)
     */
    private function mapColumns(Worksheet $sheet, int $headerRow): array
    {
        $map = [];
        $highestColumn = $sheet->getHighestDataColumn($headerRow);
        $maxCol = Coordinate::columnIndexFromString($highestColumn);
        for ($c = 1; $c <= $maxCol; ++$c) {
            $raw = $this->cellStr($sheet, $c, $headerRow);
            $h = $this->normalizeHeaderLabel($raw);
            if ($h === '' || $h === 'concerné_on' || $h === 'conformité_on') {
                continue;
            }
            if (str_contains($h, 'exigence')) {
                $map['requirement_text'] = $c;
            } elseif (str_contains($h, 'article') || str_contains($h, 'paragraphe')) {
                $map['article'] = $c;
            } elseif (str_contains($h, 'commentaire') || str_contains($h, 'lien') || str_contains($h, 'cycle')) {
                $map['iso_comment'] = $c;
            } elseif (preg_match('/^n_?o?$/', $h) === 1 || str_contains($h, 'numero')) {
                $map['display_order'] = $c;
            }
        }

        return $map;
    }

    private function cellStr(Worksheet $sheet, int $col, int $row): string
    {
        $addr = Coordinate::stringFromColumnIndex($col) . $row;
        $v = $sheet->getCell($addr)->getCalculatedValue();

        return trim((string) ($v ?? ''));
    }

    private function normalizeHeaderLabel(string $raw): string
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

    private function inferChapter(string $article): string
    {
        $s = trim($article);
        if (preg_match('/^(\d+)/', $s, $m)) {
            return $m[1];
        }

        return '0';
    }

    private function buildLegacyKey(AuditStandard $standard, string $article, string $text, int $row): string
    {
        $basis = $standard->getCode() . '|' . mb_strtolower($article) . '|' . mb_strtolower(mb_substr($text, 0, 400)) . '|r' . $row;
        $hash = hash('sha256', $basis);

        return 'wb_' . substr($hash, 0, 56);
    }
}
