/**
 * Layer 2 — Premium Motion : enregistrement dynamique des contrôleurs Stimulus
 * (GSAP, Motion One, toasts) uniquement si `data-page-motion="true"`.
 */
let registered = false;

/** @type {[string, () => Promise<{ default: unknown }>][]} */
const MODULES = [
    ['gsap-reveal', () => import('../controllers/motion/gsap_reveal_controller.js')],
    ['gsap-stagger', () => import('../controllers/motion/gsap_stagger_controller.js')],
    ['home-entrance', () => import('../controllers/motion/home_entrance_controller.js')],
    ['gsap-counter', () => import('../controllers/motion/gsap_counter_controller.js')],
    ['motion-hover', () => import('../controllers/motion/motion_hover_controller.js')],
    ['auto-animate', () => import('../controllers/motion/auto_animate_controller.js')],
    ['page-transition', () => import('../controllers/motion/page_transition_controller.js')],
    ['motion-premium', () => import('../controllers/motion/motion_premium_controller.js')],
    ['toast', () => import('../controllers/interactions/toast_controller.js')],
    ['sidebar-interaction', () => import('../controllers/interactions/sidebar_interaction_controller.js')],
    ['audit-save-pulse', () => import('../controllers/interactions/audit_save_pulse_controller.js')],
];

/**
 * @param {import('@hotwired/stimulus').Application} app
 */
export async function registerPremiumMotionControllers(app) {
    if (registered || !app || typeof app.register !== 'function') {
        return;
    }
    registered = true;

    const results = await Promise.allSettled(MODULES.map(([, load]) => load()));
    results.forEach((res, i) => {
        const [identifier] = MODULES[i];
        if (res.status !== 'fulfilled') {
            console.warn('[motion-premium] import failed', identifier, res.reason);
            return;
        }
        const ctor = res.value?.default;
        if (typeof ctor !== 'function') {
            console.warn('[motion-premium] invalid default export', identifier);
            return;
        }
        try {
            app.register(identifier, ctor);
        } catch (err) {
            console.warn('[motion-premium] register failed', identifier, err);
        }
    });
}

/** @returns {boolean} */
export function isPremiumMotionRegistered() {
    return registered;
}
