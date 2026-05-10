<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    if (!class_exists(\Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class)) {
        return;
    }

    $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.php')
        ->prefix('/_wdt');

    $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.php')
        ->prefix('/_profiler');
};
