<?php

declare(strict_types=1);

namespace App\Qse\Iso9001;

/**
 * Extrait l’objet JSON CHAPITRES_DATA du fichier HTML de référence.
 */
final class ChapitresDataExtractor
{
    /**
     * @return array<string, list<array{id: string, numero: int, article: string, exigence: string, commentaire?: string}>>
     */
    public static function extractFromHtml(string $html): array
    {
        $needle = 'const CHAPITRES_DATA = ';
        $pos = strpos($html, $needle);
        if ($pos === false) {
            throw new \InvalidArgumentException('Balise CHAPITRES_DATA introuvable dans le HTML.');
        }
        $pos += strlen($needle);
        $len = strlen($html);
        while ($pos < $len && ($html[$pos] === ' ' || $html[$pos] === "\n" || $html[$pos] === "\r")) {
            ++$pos;
        }
        if ($pos >= $len || $html[$pos] !== '{') {
            throw new \InvalidArgumentException('JSON CHAPITRES_DATA mal formé (début).');
        }
        $depth = 0;
        $start = $pos;
        for ($i = $pos; $i < $len; ++$i) {
            $c = $html[$i];
            if ($c === '{') {
                ++$depth;
            } elseif ($c === '}') {
                --$depth;
                if ($depth === 0) {
                    $json = substr($html, $start, $i - $start + 1);
                    $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
                    if (!\is_array($data)) {
                        throw new \InvalidArgumentException('CHAPITRES_DATA n’est pas un objet JSON.');
                    }

                    /** @var array<string, mixed> $data */
                    return self::normalizeChapters($data);
                }
            }
        }

        throw new \InvalidArgumentException('JSON CHAPITRES_DATA mal formé (fin).');
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, list<array{id: string, numero: int, article: string, exigence: string, commentaire?: string}>>
     */
    private static function normalizeChapters(array $raw): array
    {
        $out = [];
        foreach ($raw as $chapterName => $items) {
            if (!\is_string($chapterName) || !\is_array($items)) {
                continue;
            }
            $list = [];
            foreach ($items as $item) {
                if (!\is_array($item)) {
                    continue;
                }
                $id = isset($item['id']) ? (string) $item['id'] : '';
                if ($id === '') {
                    continue;
                }
                $list[] = [
                    'id' => $id,
                    'numero' => isset($item['numero']) ? (int) $item['numero'] : 0,
                    'article' => isset($item['article']) ? (string) $item['article'] : '',
                    'exigence' => isset($item['exigence']) ? (string) $item['exigence'] : '',
                    'commentaire' => isset($item['commentaire']) ? (string) $item['commentaire'] : null,
                ];
            }
            $out[$chapterName] = $list;
        }

        return $out;
    }
}
