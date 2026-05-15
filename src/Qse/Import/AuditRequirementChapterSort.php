<?php

declare(strict_types=1);

namespace App\Qse\Import;

/**
 * Tri des intitulés de chapitre d’audit (évite l’ordre lexicographique : 10 avant 4).
 */
final class AuditRequirementChapterSort
{
    /**
     * @param list<string> $chapters
     *
     * @return list<string>
     */
    public static function sortDistinctChapters(array $chapters): array
    {
        $chapters = array_values(array_unique($chapters));
        usort($chapters, static fn (string $a, string $b): int => self::compareChapterHeadings($a, $b));

        return $chapters;
    }

    public static function compareChapterHeadings(string $a, string $b): int
    {
        $na = self::leadingClauseNumber($a);
        $nb = self::leadingClauseNumber($b);
        if ($na !== null && $nb !== null && $na !== $nb) {
            return $na <=> $nb;
        }

        return strcmp($a, $b);
    }

    private static function leadingClauseNumber(string $label): ?int
    {
        $label = trim($label);
        if (preg_match('/^(\d+)/', $label, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
