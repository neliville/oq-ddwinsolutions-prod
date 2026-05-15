<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dashboard;

use App\Dashboard\DashboardLayout;
use App\Dashboard\DashboardWidgetId;
use PHPUnit\Framework\TestCase;

final class DashboardLayoutTest extends TestCase
{
    public function testDefaultLayoutHasEightWidgetsInCanonicalOrder(): void
    {
        $layout = DashboardLayout::createDefault();

        $this->assertSame(DashboardWidgetId::DEFAULT_ORDER, $layout->getOrderedWidgetIds());
        $this->assertCount(8, $layout->getOrderedWidgetIds());
        $this->assertTrue($layout->isWidgetVisible('deadlines'));
        $this->assertTrue($layout->isWidgetVisible('kpi_stats'));
        $this->assertTrue($layout->isWidgetVisible('kpi_ai'));
    }

    public function testLegacyVisibilityMapMigratesKpiToStatsAndAi(): void
    {
        $layout = DashboardLayout::fromLegacyVisibilityMap([
            'deadlines' => true,
            'kpi' => false,
            'audits' => false,
        ]);

        $this->assertFalse($layout->isWidgetVisible('kpi_stats'));
        $this->assertFalse($layout->isWidgetVisible('kpi_ai'));
        $this->assertFalse($layout->isWidgetVisible('kpi'));
        $this->assertFalse($layout->isWidgetVisible('audits'));
        $this->assertTrue($layout->isWidgetVisible('capa'));
    }

    public function testLegacyPartialMapDefaultsMissingKeysToVisible(): void
    {
        $layout = DashboardLayout::fromLegacyVisibilityMap(['capa' => false]);

        $this->assertFalse($layout->isWidgetVisible('capa'));
        $this->assertTrue($layout->isWidgetVisible('deadlines'));
        $this->assertTrue($layout->isWidgetVisible('kpi_stats'));
    }

    public function testVersionedLayoutPreservesCustomOrder(): void
    {
        $stored = [
            'version' => 1,
            'widgets' => [
                ['id' => 'pdca', 'visible' => true],
                ['id' => 'deadlines', 'visible' => true],
            ],
        ];

        $layout = DashboardLayout::fromStored($stored);

        $this->assertSame(['pdca', 'deadlines', 'capa', 'risks', 'audits', 'anomalies', 'kpi_stats', 'kpi_ai'], $layout->getOrderedWidgetIds());
    }

    public function testToStorageRoundTrip(): void
    {
        $layout = DashboardLayout::fromLegacyVisibilityMap(['anomalies' => false]);
        $restored = DashboardLayout::fromStored($layout->toStorage());

        $this->assertFalse($restored->isWidgetVisible('anomalies'));
        $this->assertSame(1, $restored->toStorage()['version']);
    }

    public function testVisibleWidgetsByPlacementZone(): void
    {
        $layout = DashboardLayout::fromLegacyVisibilityMap([
            'deadlines' => true,
            'capa' => false,
            'audits' => false,
            'pdca' => false,
            'kpi' => true,
        ]);

        $zones = $layout->getVisibleWidgetsByPlacementZone();

        $this->assertSame(['deadlines'], $zones['before_grid']);
        $this->assertNotContains('capa', $zones['grid']);
        $this->assertNotContains('audits', $zones['grid']);
        $this->assertSame(['kpi_ai'], $zones['after_grid_attention']);
        $this->assertSame(['kpi_stats'], $zones['stats_section']);
    }
}
