/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { getGsapRuntime } from '../../motion/gsap.js';
import { animateCounter } from '../../motion/presets.js';

/** Compteurs chiffrés animés sur `[data-gsap-counter]` (scroll ou montage immédiat). */
export default class extends Controller {
    static values = {
        childSelector: { type: String, default: '[data-gsap-counter]' },
        /** true = animation au montage (KPI above-the-fold), sans ScrollTrigger ni reset à 0. */
        mount: { type: Boolean, default: false },
    };

    async connect() {
        this.ctx = null;
        const els = Array.from(this.element.querySelectorAll(this.childSelectorValue));
        if (!els.length) {
            return;
        }

        const { gsap, ScrollTrigger } = await getGsapRuntime();
        this.ctx = gsap.context(() => {
            els.forEach((el) => {
                const end = this._parseEnd(el);
                if (end === null) {
                    return;
                }
                if (this.mountValue) {
                    const from = Math.max(0, Math.round(end * 0.82));
                    animateCounter(gsap, el, end, { from, duration: 0.55 });
                    return;
                }
                ScrollTrigger.create({
                    trigger: el,
                    start: 'top 92%',
                    once: true,
                    onEnter: () => animateCounter(gsap, el, end),
                });
            });
        }, this.element);
    }

    _parseEnd(el) {
        const attrVal = el.getAttribute('data-gsap-counter-value');
        const raw =
            attrVal !== null && attrVal !== ''
                ? attrVal
                : (el.textContent || '').replace(/\s/g, '').replace(/[^\d.-]/g, '');
        const end = Number.parseFloat(raw);
        return Number.isFinite(end) ? end : null;
    }

    disconnect() {
        this.ctx?.revert();
        this.ctx = null;
    }
}
