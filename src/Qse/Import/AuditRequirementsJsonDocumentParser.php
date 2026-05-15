<?php

declare(strict_types=1);

namespace App\Qse\Import;

/**
 * Parse les documents JSON d’exigences audit (plusieurs formats sources).
 */
final class AuditRequirementsJsonDocumentParser
{
    /** Onglet / libellé source → code {@see \App\Entity\Qse\AuditStandard}. */
    private const TAB_TO_STANDARD_CODE = [
        '9001' => 'iso_9001',
        '14001' => 'iso_14001',
        '45001' => 'iso_45001',
    ];

    /**
     * @param array<string, mixed> $json
     */
    public function parse(array $json, ?string $standardCodeOverride = null): ParsedAuditRequirementsImport
    {
        if (isset($json['exigences']) && \is_array($json['exigences'])) {
            return $this->parseDdwinExigencesDocument($json, $standardCodeOverride);
        }

        if (isset($json['rows']) && \is_array($json['rows'])) {
            $code = $standardCodeOverride ?? 'iso_14001';

            return new ParsedAuditRequirementsImport($code, $json['rows']);
        }

        if (array_is_list($json)) {
            $code = $standardCodeOverride ?? 'iso_14001';

            return new ParsedAuditRequirementsImport($code, $json);
        }

        if ($this->isIso9001ChaptersDocument($json)) {
            if ($standardCodeOverride !== null && $standardCodeOverride !== 'iso_9001') {
                throw new \InvalidArgumentException('Ce fichier est un référentiel ISO 9001 (chapitres nommés).');
            }

            return new ParsedAuditRequirementsImport('iso_9001', $this->flattenIso9001Chapters($json));
        }

        throw new \InvalidArgumentException(
            'Format JSON non reconnu : attendu clé « exigences », « rows », tableau de lignes, ou chapitres ISO 9001.',
        );
    }

    /**
     * @param array<string, mixed> $json
     */
    private function parseDdwinExigencesDocument(array $json, ?string $standardCodeOverride): ParsedAuditRequirementsImport
    {
        $onglet = trim((string) ($json['onglet'] ?? ''));
        $code = $standardCodeOverride;
        if ($code === null || $code === '') {
            $code = self::TAB_TO_STANDARD_CODE[$onglet] ?? null;
        }
        if ($code === null) {
            throw new \InvalidArgumentException(
                'Impossible de déduire le référentiel : précisez --standard=iso_14001|iso_45001 ou un onglet 14001/45001.',
            );
        }
        if ($onglet !== '' && isset(self::TAB_TO_STANDARD_CODE[$onglet]) && self::TAB_TO_STANDARD_CODE[$onglet] !== $code) {
            throw new \InvalidArgumentException(sprintf(
                'Incohérence : onglet « %s » (%s) ≠ option --standard=%s.',
                $onglet,
                self::TAB_TO_STANDARD_CODE[$onglet],
                $code,
            ));
        }

        /** @var list<array<string, mixed>> $exigences */
        $exigences = $json['exigences'];
        $rows = [];
        $order = 0;
        $lastMajorClause = null;
        $lastParagraphe = '';
        foreach ($exigences as $item) {
            if (!\is_array($item)) {
                continue;
            }
            $text = trim((string) ($item['exigence'] ?? ''));
            if ($text === '') {
                continue;
            }
            ++$order;
            $paragraphe = trim((string) ($item['paragraphe'] ?? ''));
            $articleCol = trim((string) ($item['article'] ?? ''));

            $effectiveParagraph = $paragraphe !== '' ? $paragraphe : $lastParagraphe;
            $major = AuditIsoManagementSystemChapterLabel::inferMajorClauseNumber($effectiveParagraph);
            if ($major === null) {
                $major = AuditIsoManagementSystemChapterLabel::inferMajorClauseNumber($articleCol);
            }
            if ($major === null) {
                $major = $lastMajorClause;
            }
            if ($major === null) {
                throw new \InvalidArgumentException(sprintf(
                    'Exigence #%d : impossible de déterminer le chapitre (paragraphe / article vides ou invalides).',
                    $order,
                ));
            }
            $lastMajorClause = $major;
            if ($paragraphe !== '') {
                $lastParagraphe = $paragraphe;
            }

            $chapter = AuditIsoManagementSystemChapterLabel::chapterHeading($code, $major);
            $isoArticle = $paragraphe !== ''
                ? $paragraphe
                : ($lastParagraphe !== '' ? $lastParagraphe : ($articleCol !== '' ? $articleCol : '—'));

            $comment = trim((string) ($item['commentaire'] ?? ''));
            $rows[] = [
                'legacy_key' => sprintf('json_%s_%04d', $code, $order),
                'chapter' => $chapter,
                'article' => $isoArticle,
                'requirement_text' => $text,
                'iso_comment' => $comment !== '' ? $comment : null,
                'display_order' => $order,
            ];
        }

        if ($rows === []) {
            throw new \InvalidArgumentException('Aucune exigence valide dans le fichier.');
        }

        return new ParsedAuditRequirementsImport($code, $rows);
    }

    /**
     * @param array<string, mixed> $json
     */
    private function isIso9001ChaptersDocument(array $json): bool
    {
        if ($json === [] || array_is_list($json)) {
            return false;
        }
        foreach ($json as $value) {
            if (!\is_array($value) || !array_is_list($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $json
     *
     * @return list<array<string, mixed>>
     */
    private function flattenIso9001Chapters(array $json): array
    {
        $rows = [];
        foreach ($json as $chapterName => $items) {
            if (!\is_array($items)) {
                continue;
            }
            $chapter = trim((string) $chapterName);
            $orderInChapter = 0;
            foreach ($items as $item) {
                if (!\is_array($item)) {
                    continue;
                }
                $text = trim((string) ($item['exigence'] ?? ''));
                if ($text === '') {
                    continue;
                }
                ++$orderInChapter;
                $legacyKey = trim((string) ($item['id'] ?? ''));
                if ($legacyKey === '') {
                    $legacyKey = sprintf('json_iso_9001_%s_%04d', preg_replace('/\W+/', '_', $chapter) ?? 'ch', $orderInChapter);
                }
                $numero = isset($item['numero']) ? (int) $item['numero'] : 0;
                $rows[] = [
                    'legacy_key' => $legacyKey,
                    'chapter' => $chapter,
                    'article' => trim((string) ($item['article'] ?? '')),
                    'requirement_text' => $text,
                    'iso_comment' => trim((string) ($item['commentaire'] ?? '')) ?: null,
                    'display_order' => $numero > 0 ? $numero : $orderInChapter,
                ];
            }
        }

        return $rows;
    }
}
