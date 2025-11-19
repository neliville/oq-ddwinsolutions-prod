<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    '@stimulus-components/dialog' => [
        'version' => '1.0.1',
    ],
    '@stimulus-components/sortable' => [
        'version' => '5.0.3',
    ],
    'sortablejs' => [
        'version' => '1.15.6',
    ],
    '@rails/request.js' => [
        'version' => '0.0.12',
    ],
    '@stimulus-components/dropdown' => [
        'version' => '3.0.0',
    ],
    '@stimulus-components/reveal' => [
        'version' => '5.0.0',
    ],
    'stimulus-use' => [
        'version' => '0.52.2',
    ],
    'bootstrap' => [
        'version' => '5.3.8',
    ],
    'bootstrap/js/dist/collapse' => [
        'version' => '5.3.8',
    ],
    'bootstrap/js/dist/dropdown' => [
        'version' => '5.3.8',
    ],
    'bootstrap/js/dist/modal' => [
        'version' => '5.3.8',
    ],
    'bootstrap/js/dist/toast' => [
        'version' => '5.3.8',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.8',
        'type' => 'css',
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    'flatpickr' => [
        'version' => '4.6.13',
    ],
    'flatpickr/dist/flatpickr.min.css' => [
        'version' => '4.6.13',
        'type' => 'css',
    ],
];
