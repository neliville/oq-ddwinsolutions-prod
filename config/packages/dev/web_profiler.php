<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Évite d’enregistrer l’extension web_profiler si le bundle n’est pas installé
 * (ex. vendor issu de `composer install --no-dev`).
 */
return static function (ContainerConfigurator $container): void {
    if (!class_exists(\Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class)) {
        return;
    }

    $container->extension('web_profiler', [
        'toolbar' => true,
    ]);

    $container->extension('framework', [
        'profiler' => [
            'collect_serializer_data' => true,
        ],
    ]);
};
