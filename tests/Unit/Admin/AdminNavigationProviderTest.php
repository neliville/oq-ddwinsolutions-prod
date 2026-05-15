<?php

declare(strict_types=1);

namespace App\Tests\Unit\Admin;

use App\Admin\Navigation\AdminNavigationProvider;
use PHPUnit\Framework\TestCase;

final class AdminNavigationProviderTest extends TestCase
{
    public function testFlatLinksIncludeDashboardPlatformHealthAndHomepageSlots(): void
    {
        $provider = new AdminNavigationProvider();
        $flat = $provider->getFlatLinks();
        $routes = array_column($flat, 'route');

        self::assertContains('app_admin_dashboard_index', $routes);
        self::assertContains('app_admin_platform_health_index', $routes);
        self::assertContains('app_admin_homepage_slots_index', $routes);
        self::assertContains('app_admin_qse_capa_overview', $routes);
        self::assertContains('app_admin_qse_risk_overview', $routes);
    }

    public function testSectionsHaveStableLabels(): void
    {
        $provider = new AdminNavigationProvider();
        $sections = $provider->getSectionDefinitions();
        self::assertNotEmpty($sections);
        foreach ($sections as $section) {
            self::assertArrayHasKey('label', $section);
            self::assertNotSame('', $section['label']);
            self::assertArrayHasKey('items', $section);
            self::assertNotEmpty($section['items']);
        }
    }
}
