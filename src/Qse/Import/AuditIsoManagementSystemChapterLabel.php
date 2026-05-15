<?php

declare(strict_types=1);

namespace App\Qse\Import;

/**
 * Libellés de grands chapitres ISO (style proche du référentiel 9001 HTML) pour 14001 / 45001.
 */
final class AuditIsoManagementSystemChapterLabel
{
    /** @var array<int, string> */
    private const ISO_14001 = [
        4 => 'Contexte de l\'organisation',
        5 => 'Direction',
        6 => 'Planification',
        7 => 'Support',
        8 => 'Exploitation',
        9 => 'Évaluation des performances',
        10 => 'Amélioration',
    ];

    /** @var array<int, string> */
    private const ISO_45001 = [
        4 => 'Contexte de l\'organisation',
        5 => 'Leadership et participation des travailleurs',
        6 => 'Planification',
        7 => 'Support',
        8 => 'Exploitation',
        9 => 'Évaluation des performances',
        10 => 'Amélioration',
    ];

    public static function inferMajorClauseNumber(string $reference): ?int
    {
        $reference = trim($reference);
        if ($reference === '' || $reference === '—') {
            return null;
        }
        if (preg_match('/^(\d+)/', $reference, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    public static function chapterHeading(string $standardCode, int $majorClause): string
    {
        $map = match ($standardCode) {
            'iso_14001' => self::ISO_14001,
            'iso_45001' => self::ISO_45001,
            default => [],
        };
        $suffix = $map[$majorClause] ?? ('Clause ' . $majorClause);

        return sprintf('%d. %s', $majorClause, $suffix);
    }
}
