import { Controller } from '@hotwired/stimulus';
import { createPageContext, getGsapRuntime, prefersReducedMotion } from '../../motion/runtime.js';
import { DURATIONS, EASES } from '../../motion/tokens.js';

/** Transition douce entre pages Turbo (opacité du main) — pas au premier paint. */
export default class extends Controller {
    static values = {
        selector: { type: String, default: '#main-content' },
    };

    connect() {
        this._skipNextRender = true;
        this._pageContext = null;
        this._before = this._before.bind(this);
        this._render = this._render.bind(this);
        document.addEventListener('turbo:before-render', this._before);
        document.addEventListener('turbo:render', this._render);
    }

    disconnect() {
        document.removeEventListener('turbo:before-render', this._before);
        document.removeEventListener('turbo:render', this._render);
        this._pageContext?.revert();
        this._pageContext = null;
    }

    async _before() {
        if (prefersReducedMotion()) {
            return;
        }
        // Évite un flash supplémentaire entre pages du tableau de bord (Turbo + GSAP cockpit).
        if (document.body.classList.contains('layout-dashboard')) {
            return;
        }
        const main = document.querySelector(this.selectorValue);
        if (!main) {
            return;
        }
        const { gsap } = getGsapRuntime();
        await gsap.to(main, { autoAlpha: 0.92, duration: DURATIONS.instant, ease: EASES.inOut });
    }

    async _render() {
        if (this._skipNextRender) {
            this._skipNextRender = false;
            return;
        }
        if (document.body.classList.contains('layout-dashboard')) {
            return;
        }

        const main = document.querySelector(this.selectorValue);
        if (!main || prefersReducedMotion()) {
            return;
        }

        this._pageContext?.revert();
        const { gsap } = getGsapRuntime();
        this._pageContext = createPageContext(main, ({ gsap: g }) => {
            g.fromTo(main, { autoAlpha: 0.94 }, { autoAlpha: 1, duration: DURATIONS.fast, ease: EASES.out });
        });
    }
}
