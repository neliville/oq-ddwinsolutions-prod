/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { getGsapRuntime } from '../../motion/gsap.js';
import { prefersReducedMotion } from '../../motion/reduced-motion.js';
import { DURATIONS, EASES } from '../../motion/tokens.js';

/**
 * Révélation au scroll (remplace AOS fade-up / zoom-in légers).
 * Usage : placer sur un conteneur ; les enfants directs `[data-gsap-reveal-item]` s’animent.
 */
export default class extends Controller {
    static values = {
        childSelector: { type: String, default: '[data-gsap-reveal-item]' },
        stagger: { type: Number, default: 0.07 },
        y: { type: Number, default: 18 },
    };

    async connect() {
        this.ctx = null;
        const items = Array.from(this.element.querySelectorAll(this.childSelectorValue));
        if (!items.length) {
            return;
        }

        const { gsap, ScrollTrigger } = await getGsapRuntime();
        this.ctx = gsap.context(() => {
            const reduced = prefersReducedMotion();
            gsap.set(items, { autoAlpha: reduced ? 1 : 0, y: reduced ? 0 : this.yValue });
            if (reduced) {
                return;
            }
            gsap.to(items, {
                autoAlpha: 1,
                y: 0,
                duration: DURATIONS.base,
                ease: EASES.out,
                stagger: this.staggerValue,
                scrollTrigger: {
                    trigger: this.element,
                    start: 'top bottom',
                    toggleActions: 'play none none none',
                },
            });
        }, this.element);
    }

    disconnect() {
        this.ctx?.revert();
        this.ctx = null;
    }
}
