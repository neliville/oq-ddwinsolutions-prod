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
    'oq/flatpickr-config' => [
        'path' => './assets/js/flatpickr/config.js',
    ],
    '@stimulus-components/checkbox-select-all' => [
        'version' => '6.1.0',
    ],
    'react' => [
        'version' => '19.2.4',
    ],
    'react-dom/client' => [
        'version' => '19.2.4',
    ],
    'react-dom' => [
        'version' => '19.2.4',
    ],
    'scheduler' => [
        'version' => '0.27.0',
    ],
    '@symfony/ux-react' => [
        'path' => './vendor/symfony/ux-react/assets/dist/loader.js',
    ],
    '@xyflow/react' => [
        'version' => '12.10.2',
    ],
    'react/jsx-runtime' => [
        'version' => '19.2.4',
    ],
    'classcat' => [
        'version' => '5.0.5',
    ],
    '@xyflow/system' => [
        'version' => '0.0.76',
    ],
    'zustand/traditional' => [
        'version' => '4.5.7',
    ],
    'zustand/shallow' => [
        'version' => '4.5.7',
    ],
    'd3-drag' => [
        'version' => '3.0.0',
    ],
    'd3-selection' => [
        'version' => '3.0.0',
    ],
    'd3-zoom' => [
        'version' => '3.0.0',
    ],
    'd3-interpolate' => [
        'version' => '3.0.1',
    ],
    'use-sync-external-store/shim/with-selector.js' => [
        'version' => '1.5.0',
    ],
    'zustand/vanilla' => [
        'version' => '5.0.13',
    ],
    'd3-dispatch' => [
        'version' => '3.0.1',
    ],
    'd3-transition' => [
        'version' => '3.0.1',
    ],
    'd3-color' => [
        'version' => '3.0.1',
    ],
    'use-sync-external-store/shim' => [
        'version' => '1.5.0',
    ],
    'd3-timer' => [
        'version' => '3.0.1',
    ],
    'd3-ease' => [
        'version' => '3.0.1',
    ],
    'zustand' => [
        'version' => '5.0.13',
    ],
    'zustand/react' => [
        'version' => '5.0.13',
    ],
    'html-to-image' => [
        'version' => '1.11.13',
    ],
    'zustand/middleware' => [
        'version' => '5.0.13',
    ],
    'zustand/react/shallow' => [
        'version' => '5.0.13',
    ],
    'zustand/vanilla/shallow' => [
        'version' => '5.0.13',
    ],
    'gsap' => [
        'version' => '3.15.0',
    ],
    'motion' => [
        'version' => '12.38.0',
    ],
    '@formkit/auto-animate' => [
        'version' => '0.9.0',
    ],
    'apexcharts' => [
        'version' => '5.12.0',
    ],
    'framer-motion/dom' => [
        'version' => '12.38.0',
    ],
    'motion-dom' => [
        'version' => '12.38.0',
    ],
    'motion-utils' => [
        'version' => '12.36.0',
    ],
    'gsap/ScrollTrigger' => [
        'version' => '3.15.0',
    ],
    'gsap/Flip' => [
        'version' => '3.15.0',
    ],
    '@floating-ui/dom' => [
        'version' => '1.7.6',
    ],
    '@floating-ui/core' => [
        'version' => '1.7.5',
    ],
    '@floating-ui/utils' => [
        'version' => '0.2.11',
    ],
    '@floating-ui/utils/dom' => [
        'version' => '0.2.11',
    ],
];
