<?php

declare(strict_types=1);

namespace App\Dashboard;

/**
 * Modèle versionné du layout dashboard (widgets ordonnés + visibilité).
 *
 * @phpstan-type WidgetEntry array{id: string, visible: bool}
 * @phpstan-type StoredLayout array{version: int, widgets: list<WidgetEntry>}
 */
final class DashboardLayout
{
    /** @var list<WidgetEntry> */
    private array $widgets;

    private function __construct(array $widgets)
    {
        $this->widgets = $widgets;
    }

    public static function createDefault(): self
    {
        $widgets = [];
        foreach (DashboardWidgetId::DEFAULT_ORDER as $id) {
            $widgets[] = ['id' => $id, 'visible' => true];
        }

        return new self($widgets);
    }

    /**
     * @param array<string, mixed>|null $stored
     */
    public static function fromStored(?array $stored): self
    {
        if ($stored === null) {
            return self::createDefault();
        }

        if (self::isVersionedLayout($stored)) {
            return self::fromVersionedLayout($stored);
        }

        return self::fromLegacyVisibilityMap($stored);
    }

    /**
     * @param array<string, mixed> $stored
     */
    private static function isVersionedLayout(array $stored): bool
    {
        return isset($stored['version'], $stored['widgets']) && is_array($stored['widgets']);
    }

    /**
     * @param array<string, mixed> $stored
     */
    private static function fromVersionedLayout(array $stored): self
    {
        $widgets = [];
        $seen = [];

        foreach ($stored['widgets'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $id = $entry['id'] ?? null;
            if (!is_string($id) || !DashboardWidgetId::isKnown($id)) {
                continue;
            }
            $widgets[] = [
                'id' => $id,
                'visible' => (bool) ($entry['visible'] ?? true),
            ];
            $seen[$id] = true;
        }

        foreach (DashboardWidgetId::DEFAULT_ORDER as $id) {
            if (!isset($seen[$id])) {
                $widgets[] = ['id' => $id, 'visible' => true];
            }
        }

        return new self($widgets);
    }

    /**
     * Ancien format : { "deadlines": true, "kpi": false, ... }.
     *
     * @param array<string, mixed> $legacy
     */
    public static function fromLegacyVisibilityMap(array $legacy): self
    {
        if ($legacy === []) {
            return self::createDefault();
        }

        $byId = [];
        foreach (DashboardWidgetId::DEFAULT_ORDER as $id) {
            $byId[$id] = true;
        }

        foreach ($legacy as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if ($key === DashboardWidgetId::KPI_LEGACY) {
                $visible = (bool) $value;
                $byId[DashboardWidgetId::KPI_STATS] = $visible;
                $byId[DashboardWidgetId::KPI_AI] = $visible;
                continue;
            }
            if (DashboardWidgetId::isKnown($key)) {
                $byId[$key] = (bool) $value;
            }
        }

        return self::buildFromVisibilityById($byId);
    }

    /**
     * @param array<string, bool> $byId visibilité par id (clés absentes = visible par défaut)
     */
    private static function buildFromVisibilityById(array $byId): self
    {
        $widgets = [];
        foreach (DashboardWidgetId::DEFAULT_ORDER as $id) {
            $widgets[] = [
                'id' => $id,
                'visible' => array_key_exists($id, $byId) ? (bool) $byId[$id] : true,
            ];
        }

        return new self($widgets);
    }

    /**
     * @return StoredLayout
     */
    public function toStorage(): array
    {
        return [
            'version' => DashboardWidgetId::LAYOUT_VERSION,
            'widgets' => $this->widgets,
        ];
    }

    public function isWidgetVisible(string $widgetId): bool
    {
        if ($widgetId === DashboardWidgetId::KPI_LEGACY) {
            return $this->isWidgetVisible(DashboardWidgetId::KPI_STATS)
                && $this->isWidgetVisible(DashboardWidgetId::KPI_AI);
        }

        if (!DashboardWidgetId::isKnown($widgetId)) {
            return true;
        }

        foreach ($this->widgets as $widget) {
            if ($widget['id'] === $widgetId) {
                return $widget['visible'];
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function getOrderedWidgetIds(): array
    {
        return array_map(static fn (array $w): string => $w['id'], $this->widgets);
    }

    /**
     * @return list<string>
     */
    public function getOrderedVisibleWidgetIds(): array
    {
        $visible = [];
        foreach ($this->widgets as $widget) {
            if ($widget['visible']) {
                $visible[] = $widget['id'];
            }
        }

        return $visible;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getVisibleWidgetsByPlacementZone(): array
    {
        $zones = [
            'before_grid' => [],
            'grid' => [],
            'after_grid_attention' => [],
            'stats_section' => [],
        ];

        foreach ($this->getOrderedVisibleWidgetIds() as $widgetId) {
            $zone = DashboardWidgetId::placementZone($widgetId);
            if ($zone !== null && isset($zones[$zone])) {
                $zones[$zone][] = $widgetId;
            }
        }

        return $zones;
    }

    /**
     * @param array<string, bool> $visibility keyed by widget id (formulaire préférences)
     */
    public static function fromVisibilityMap(array $visibility): self
    {
        $byId = [];
        foreach (DashboardWidgetId::DEFAULT_ORDER as $id) {
            $byId[$id] = (bool) ($visibility[$id] ?? false);
        }

        return self::buildFromVisibilityById($byId);
    }

    /**
     * @return array<string, bool>
     */
    public function toLegacyVisibilityMap(): array
    {
        $map = [];
        foreach ($this->widgets as $widget) {
            $map[$widget['id']] = $widget['visible'];
        }

        $map[DashboardWidgetId::KPI_LEGACY] = $this->isWidgetVisible(DashboardWidgetId::KPI_LEGACY);

        return $map;
    }
}
