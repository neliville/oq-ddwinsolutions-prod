(function () {
    'use strict';

    function handleScroll(trigger, prefersReducedMotion) {
        const targetId = trigger.getAttribute('data-scroll-target');
        if (!targetId) {
            return;
        }

        const section = document.getElementById(targetId);
        if (!section) {
            return;
        }

        const options = prefersReducedMotion ? { block: 'start' } : { behavior: 'smooth', block: 'start' };
        section.scrollIntoView(options);
    }

    function initHomePage() {
        const root = document.querySelector('.home-page');
        if (!root || root.dataset.homePageInitialised === 'true') {
            return;
        }

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        root.querySelectorAll('[data-scroll-target]').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                handleScroll(trigger, prefersReducedMotion);
                trigger.blur();
            });
        });

        root.dataset.homePageInitialised = 'true';
    }

    function onTurboBeforeCache() {
        const root = document.querySelector('.home-page');
        if (root) {
            delete root.dataset.homePageInitialised;
        }
    }

    document.addEventListener('DOMContentLoaded', initHomePage);
    document.addEventListener('turbo:load', initHomePage);
    document.addEventListener('turbo:frame-load', initHomePage);
    document.addEventListener('turbo:before-cache', onTurboBeforeCache);
})();

