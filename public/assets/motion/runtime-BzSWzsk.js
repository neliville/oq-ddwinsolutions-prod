/**
 * Fondation motion GSAP (home + pages futures).
 * Dev : ne pas laisser public/assets/ compilé — supprimer le dossier pour servir les sources Asset Mapper.
 * Prod : asset-map:compile uniquement au build déploiement.
 */
import { getGsapRuntime } from './gsap.js';
import { prefersReducedMotion } from './reduced-motion.js';
import { isMotionDebug, motionLog } from './debug.js';

let refreshScheduled = false;
let turboListenerBound = false;

function runScrollRefresh() {
    const { ScrollTrigger } = getGsapRuntime();
    ScrollTrigger.refresh();
    motionLog('runtime', 'ScrollTrigger.refresh()', { count: ScrollTrigger.getAll().length });
}

/**
 * Rafraîchit ScrollTrigger après layout (polices, images, Turbo).
 * Idempotent : plusieurs appels dans la même frame = un seul refresh.
 */
export function scheduleScrollRefresh() {
    if (prefersReducedMotion()) {
        return;
    }
    if (refreshScheduled) {
        return;
    }
    refreshScheduled = true;

    const flush = () => {
        refreshScheduled = false;
        runScrollRefresh();
    };

    requestAnimationFrame(() => {
        requestAnimationFrame(flush);
    });

    if (document.fonts?.ready) {
        document.fonts.ready.then(flush).catch(() => {});
    }

    if (document.readyState === 'complete') {
        flush();
    } else {
        window.addEventListener('load', flush, { once: true });
    }
}

/** Écoute turbo:load une seule fois (refresh global après navigation). */
export function bindTurboScrollRefresh() {
    if (turboListenerBound || typeof document === 'undefined') {
        return;
    }
    turboListenerBound = true;
    document.addEventListener('turbo:load', () => scheduleScrollRefresh());
    motionLog('runtime', 'turbo:load listener bound');
}

/**
 * @param {Element} scope
 * @param {(api: { gsap: import('gsap').gsap, ScrollTrigger: import('gsap').ScrollTrigger }) => void} factory
 * @returns {{ revert: () => void }}
 */
export function createPageContext(scope, factory) {
    const { gsap, ScrollTrigger } = getGsapRuntime();
    const ctx = gsap.context(() => factory({ gsap, ScrollTrigger }), scope);
    return {
        revert: () => ctx.revert(),
    };
}

export { getGsapRuntime, prefersReducedMotion, isMotionDebug, motionLog };
