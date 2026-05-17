/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { getGsapRuntime } from '../../motion/gsap.js';
import { staggerFadeUp } from '../../motion/presets.js';

/**
 * Stagger d’apparition sur les enfants (`childSelector` ou `[data-gsap-stagger-item]` par défaut).
 * `mountOnly` : true = pas de ScrollTrigger, animation au montage (accueil marketing). False = scroll sur ce nœud.
 */
export default class extends Controller {
    static values = {
        childSelector: { type: String, default: '[data-gsap-stagger-item]' },
        stagger: { type: Number, default: 0.06 },
        mountOnly: { type: Boolean, default: false },
    };

    async connect() {
        this._abort = new AbortController();
        const signal = this._abort.signal;
        const items = Array.from(this.element.querySelectorAll(this.childSelectorValue)).filter(Boolean);
        if (!items.length) {
            return;
        }

        const run = async () => {
            if (signal.aborted) {
                return;
            }
            const { gsap, ScrollTrigger } = await getGsapRuntime();
            this.ctx?.revert();
            this.ctx = gsap.context(() => {
                staggerFadeUp(gsap, items, ScrollTrigger, {
                    trigger: this.mountOnlyValue ? undefined : this.element,
                    stagger: this.staggerValue,
                });
            }, this.element);
        };

        if (this.mountOnlyValue) {
            requestAnimationFrame(() => requestAnimationFrame(() => void run()));
        } else {
            void run();
        }
    }

    disconnect() {
        this._abort?.abort();
        this.ctx?.revert();
        this.ctx = null;
    }
}
