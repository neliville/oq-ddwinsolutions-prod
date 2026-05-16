import { Controller } from '@hotwired/stimulus';
import { animate } from 'motion';
import { prefersReducedMotion } from '../../motion/reduced-motion.js';
import { SPRING_SNAPPY } from '../../motion/tokens.js';

/** Micro-scale sur hover/focus pour cartes / boutons (cible : `[data-motion-hover]`). */
export default class extends Controller {
    static values = {
        scale: { type: Number, default: 1.02 },
    };

    connect() {
        this._cleanups = [];
        const nodes = this.element.matches('[data-motion-hover]')
            ? [this.element]
            : Array.from(this.element.querySelectorAll('[data-motion-hover]'));

        nodes.forEach((node) => {
            const enter = () => {
                if (prefersReducedMotion()) {
                    return;
                }
                animate(node, { scale: this.scaleValue }, { type: 'spring', ...SPRING_SNAPPY });
            };
            const leave = () => {
                animate(node, { scale: 1 }, { type: 'spring', ...SPRING_SNAPPY });
            };
            node.addEventListener('mouseenter', enter);
            node.addEventListener('mouseleave', leave);
            node.addEventListener('focusin', enter);
            node.addEventListener('focusout', leave);
            this._cleanups.push(() => {
                node.removeEventListener('mouseenter', enter);
                node.removeEventListener('mouseleave', leave);
                node.removeEventListener('focusin', enter);
                node.removeEventListener('focusout', leave);
            });
        });
    }

    disconnect() {
        this._cleanups.forEach((fn) => fn());
        this._cleanups = [];
    }
}
