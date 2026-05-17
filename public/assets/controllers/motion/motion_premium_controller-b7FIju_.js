/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { getGsapRuntime } from '../../motion/gsap.js';
import { prefersReducedMotion } from '../../motion/reduced-motion.js';
import { DURATIONS, EASES } from '../../motion/tokens.js';

const HOME_CARD_SELECTOR =
    '.home-tool-card, .home-stat-card, .home-testimonial-card, .home-cockpit-pillar, .home-workflow-step__card, .home-benefit-card, .home-cockpit-preview';

const HOVER_LIFT = { y: -12, scale: 1.028 };
const HOVER_ICON = { scale: 1.14, rotate: -5 };

/**
 * Hover cartes landing : élévation GSAP (plus visible que le seul CSS scale).
 */
export default class extends Controller {
    async connect() {
        if (prefersReducedMotion()) {
            return;
        }

        const { gsap } = await getGsapRuntime();
        this._cardCleanups = [];

        this._ctx = gsap.context(() => {
            this.element.querySelectorAll(HOME_CARD_SELECTOR).forEach((card) => {
                this._bindCardHover(gsap, card);
            });

            this.element.querySelectorAll('.motion-card, .dashboard-tool-card, .tool-card:not(.coming-soon)').forEach((card) => {
                if (card.matches(HOME_CARD_SELECTOR)) {
                    return;
                }
                this._bindCardHover(gsap, card, { lift: { y: -8, scale: 1.02 } });
            });
        }, this.element);
    }

    disconnect() {
        this._ctx?.revert();
        this._ctx = null;
        this._cardCleanups?.forEach((fn) => fn());
        this._cardCleanups = [];
    }

    _bindCardHover(gsap, card, options = {}) {
        const lift = options.lift ?? HOVER_LIFT;
        const hoverTarget =
            card.classList.contains('home-testimonial-card') ?
                card.querySelector('.testimonial-card__inner') ?? card
            :   card;
        const icon =
            card.querySelector('.home-tool-icon, .home-cockpit-pillar__icon, .home-benefit-card__icon, .home-stat-card__icon') ??
            card.querySelector('[data-lucide]');

        gsap.set(hoverTarget, { transformOrigin: '50% 50%', force3D: true });
        if (icon) {
            gsap.set(icon, { transformOrigin: '50% 50%', force3D: true });
        }

        const enter = () => {
            gsap.to(hoverTarget, {
                ...lift,
                duration: DURATIONS.fast,
                ease: EASES.out,
                overwrite: 'auto',
            });
            if (icon) {
                gsap.to(icon, {
                    ...HOVER_ICON,
                    duration: DURATIONS.fast,
                    ease: EASES.out,
                    overwrite: 'auto',
                });
            }
        };

        const leave = () => {
            gsap.to(hoverTarget, {
                y: 0,
                scale: 1,
                duration: DURATIONS.base,
                ease: EASES.smooth,
                overwrite: 'auto',
            });
            if (icon) {
                gsap.to(icon, {
                    scale: 1,
                    rotate: 0,
                    duration: DURATIONS.base,
                    ease: EASES.smooth,
                    overwrite: 'auto',
                });
            }
        };

        card.addEventListener('mouseenter', enter);
        card.addEventListener('mouseleave', leave);
        card.addEventListener('focusin', enter);
        card.addEventListener('focusout', leave);

        this._cardCleanups.push(() => {
            card.removeEventListener('mouseenter', enter);
            card.removeEventListener('mouseleave', leave);
            card.removeEventListener('focusin', enter);
            card.removeEventListener('focusout', leave);
        });
    }
}
