import { Controller } from '@hotwired/stimulus';
import { animate } from 'motion';
import { prefersReducedMotion } from '../../motion/reduced-motion.js';
import { SPRING_SOFT } from '../../motion/tokens.js';

/**
 * Micro-interactions sidebar : icône + lien (complète HoverCard Symfony UX).
 */
export default class extends Controller {
    connect() {
        this._cleanups = [];
        const links = Array.from(this.element.querySelectorAll('.sidebar-item'));
        links.forEach((link) => {
            const icon = link.querySelector('.sidebar-item-icon');
            const enter = () => {
                if (prefersReducedMotion() || !icon) {
                    return;
                }
                animate(
                    icon,
                    { rotate: 4, scale: 1.06 },
                    { type: 'spring', stiffness: SPRING_SOFT.stiffness, damping: SPRING_SOFT.damping },
                );
            };
            const leave = () => {
                if (!icon) {
                    return;
                }
                animate(icon, { rotate: 0, scale: 1 }, { type: 'spring', stiffness: SPRING_SOFT.stiffness, damping: SPRING_SOFT.damping });
            };
            link.addEventListener('mouseenter', enter);
            link.addEventListener('mouseleave', leave);
            link.addEventListener('focusin', enter);
            link.addEventListener('focusout', leave);
            this._cleanups.push(() => {
                link.removeEventListener('mouseenter', enter);
                link.removeEventListener('mouseleave', leave);
                link.removeEventListener('focusin', enter);
                link.removeEventListener('focusout', leave);
            });
        });
    }

    disconnect() {
        this._cleanups.forEach((fn) => fn());
        this._cleanups = [];
    }
}
