<?php

declare(strict_types=1);

namespace App\Dashboard;

/**
 * Identifiants des widgets cockpit QSE (ordre canonique v1).
 */
final class DashboardWidgetId
{
    public const DEADLINES = 'deadlines';
    public const CAPA = 'capa';
    public const RISKS = 'risks';
    public const AUDITS = 'audits';
    public const PDCA = 'pdca';
    public const ANOMALIES = 'anomalies';
    public const KPI_STATS = 'kpi_stats';
    public const KPI_AI = 'kpi_ai';

    /** @deprecated Alias historique — couvre kpi_stats + kpi_ai */
    public const KPI_LEGACY = 'kpi';

    public const LAYOUT_VERSION = 1;

    /** @var list<string> */
    public const DEFAULT_ORDER = [
        self::DEADLINES,
        self::CAPA,
        self::RISKS,
        self::AUDITS,
        self::PDCA,
        self::ANOMALIES,
        self::KPI_STATS,
        self::KPI_AI,
    ];

    /** @var list<string> */
    public const PLACEMENT_BEFORE_GRID = [self::DEADLINES];

    /** @var list<string> */
    public const PLACEMENT_GRID = [
        self::CAPA,
        self::RISKS,
        self::AUDITS,
        self::PDCA,
        self::ANOMALIES,
    ];

    /** @var list<string> */
    public const PLACEMENT_AFTER_GRID_ATTENTION = [self::KPI_AI];

    /** @var list<string> */
    public const PLACEMENT_STATS_SECTION = [self::KPI_STATS];

    private function __construct()
    {
    }

    public static function isKnown(string $id): bool
    {
        return in_array($id, self::DEFAULT_ORDER, true);
    }

    public static function label(string $id): string
    {
        return match ($id) {
            self::DEADLINES => 'Urgence et délais',
            self::CAPA => 'CAPA — vue synthétique',
            self::RISKS => 'Risques',
            self::AUDITS => 'Audits',
            self::PDCA => 'PDCA',
            self::ANOMALIES => 'Décisions / anomalies',
            self::KPI_STATS => 'KPI et statistiques outils',
            self::KPI_AI => 'Espace assistances IA',
            default => $id,
        };
    }

    public static function placementZone(string $id): ?string
    {
        if (in_array($id, self::PLACEMENT_BEFORE_GRID, true)) {
            return 'before_grid';
        }
        if (in_array($id, self::PLACEMENT_GRID, true)) {
            return 'grid';
        }
        if (in_array($id, self::PLACEMENT_AFTER_GRID_ATTENTION, true)) {
            return 'after_grid_attention';
        }
        if (in_array($id, self::PLACEMENT_STATS_SECTION, true)) {
            return 'stats_section';
        }

        return null;
    }
}
