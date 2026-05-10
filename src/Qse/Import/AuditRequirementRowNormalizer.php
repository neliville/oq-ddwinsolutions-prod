<?php

declare(strict_types=1);

namespace App\Qse\Import;

use App\Qse\Enum\PdcaPhase;

/**
 * Normalise une ligne d’import (JSON/Excel mappé) sans inventer de contenu normatif.
 *
 * @param array<string, mixed> $row
 * @return array{
 *   legacy_key: string,
 *   chapter: string,
 *   sub_chapter: ?string,
 *   article: string,
 *   requirement_text: string,
 *   iso_comment: ?string,
 *   business_link: ?string,
 *   pdca_phase: ?PdcaPhase,
 *   display_order: int
 * }
 */
final class AuditRequirementRowNormalizer
{
    /**
     * @param array<string, mixed> $row
     */
    public function normalize(array $row): array
    {
        $legacyKey = trim((string) ($row['legacy_key'] ?? $row['legacyKey'] ?? ''));
        if ($legacyKey === '') {
            throw new \InvalidArgumentException('legacy_key obligatoire.');
        }
        $chapter = trim((string) ($row['chapter'] ?? ''));
        if ($chapter === '') {
            throw new \InvalidArgumentException('chapter obligatoire.');
        }
        $article = trim((string) ($row['article'] ?? $row['iso_article'] ?? ''));
        $text = trim((string) ($row['requirement_text'] ?? $row['exigence'] ?? $row['requirementText'] ?? ''));
        if ($text === '') {
            throw new \InvalidArgumentException('Texte d’exigence obligatoire.');
        }
        $sub = isset($row['sub_chapter']) ? trim((string) $row['sub_chapter']) : null;
        $sub = $sub === '' ? null : $sub;
        $comment = isset($row['iso_comment']) ? trim((string) $row['iso_comment']) : null;
        if ($comment === '') {
            $comment = null;
        }
        $link = isset($row['business_link']) ? trim((string) $row['business_link']) : null;
        if ($link === '') {
            $link = null;
        }
        $pdcaRaw = isset($row['pdca_phase']) ? strtolower(trim((string) $row['pdca_phase'])) : '';
        $pdca = PdcaPhase::tryFrom($pdcaRaw);
        $order = isset($row['display_order']) ? (int) $row['display_order'] : 0;

        return [
            'legacy_key' => $legacyKey,
            'chapter' => $chapter,
            'sub_chapter' => $sub,
            'article' => $article !== '' ? $article : '—',
            'requirement_text' => $text,
            'iso_comment' => $comment,
            'business_link' => $link,
            'pdca_phase' => $pdca,
            'display_order' => $order > 0 ? $order : 0,
        ];
    }
}
