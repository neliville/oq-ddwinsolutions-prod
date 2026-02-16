<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Expose tool metadata (level, tags) for display on tool and catalogue pages.
 * No database: static config only.
 */
class ToolMetaExtension extends AbstractExtension
{
    private const META = [
        'ishikawa' => [
            'level' => 'Débutant',
            'tags' => ['Analyse des causes', 'Non-conformité', 'ISO 9001', 'Amélioration continue'],
            'category' => 'analyse_causes',
        ],
        '5-pourquoi' => [
            'level' => 'Débutant',
            'tags' => ['Résolution de problèmes', 'Cause racine', 'Lean', 'Amélioration continue'],
            'category' => 'resolution_problemes',
        ],
        'fivewhy' => [
            'level' => 'Débutant',
            'tags' => ['Résolution de problèmes', 'Cause racine', 'Lean', 'Amélioration continue'],
            'category' => 'resolution_problemes',
        ],
        'qqoqccp' => [
            'level' => 'Débutant',
            'tags' => ['Reporting', 'Audit', 'Questionnement 5W2H', 'ISO 9001'],
            'category' => 'reporting',
        ],
        '8d' => [
            'level' => 'Intermédiaire',
            'tags' => ['Résolution de problèmes', 'Plan d\'action', 'IATF 16949', 'Amélioration continue'],
            'category' => 'resolution_problemes',
        ],
        'eightd' => [
            'level' => 'Intermédiaire',
            'tags' => ['Résolution de problèmes', 'Plan d\'action', 'IATF 16949', 'Amélioration continue'],
            'category' => 'resolution_problemes',
        ],
        'amdec' => [
            'level' => 'Intermédiaire',
            'tags' => ['Risques', 'Prévention', 'Criticité', 'Qualité'],
            'category' => 'autres',
        ],
        'pareto' => [
            'level' => 'Débutant',
            'tags' => ['Priorisation', 'Reporting', 'Lean', '80/20'],
            'category' => 'autres',
        ],
    ];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tool_meta', [$this, 'getToolMeta']),
            new TwigFunction('tools_meta_by_category', [$this, 'getToolsMetaByCategory']),
        ];
    }

    /**
     * @return array{level: string, tags: string[], category: string}|null
     */
    public function getToolMeta(string $slug): ?array
    {
        return self::META[$slug] ?? null;
    }

    /**
     * Returns category label keyed by category slug for filters.
     */
    public function getToolsMetaByCategory(): array
    {
        return [
            'resolution_problemes' => 'Résolution de problèmes',
            'analyse_causes' => 'Analyse des causes',
            'reporting' => 'Reporting / Questionnement',
            'autres' => 'Autres outils',
        ];
    }
}
