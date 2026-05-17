/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import {
    bindTurboScrollRefresh,
    createPageContext,
    motionLog,
    prefersReducedMotion,
    scheduleScrollRefresh,
} from '../../motion/runtime.js';
import {
    animateCinematicHero,
    getHomeSectionContentTargets,
    getHomeSectionIntroTargets,
    getHomeSectionRevealTargets,
    revealHomeSectionOnScroll,
} from '../../motion/presets.js';

/** Déclenche quand ~15 % de la section est visible — reveal perçu à l’écran. */
const SECTION_SCROLL_START = 'top bottom-=88';

/**
 * Entrée page d’accueil : hero cinématique au chargement, blocs de section au scroll.
 */
export default class extends Controller {
    async connect() {
        bindTurboScrollRefresh();

        if (prefersReducedMotion()) {
            motionLog('home-entrance', 'skipped (prefers-reduced-motion)');
            return;
        }

        const sections = [...this.element.querySelectorAll(':scope > section:not(#hero)')];

        motionLog('home-entrance', 'connect', {
            sections: sections.length,
            sectionAudit: sections.map((s) => ({
                id: s.id,
                intro: getHomeSectionIntroTargets(s).length,
                content: getHomeSectionContentTargets(s).length,
                blocks: getHomeSectionRevealTargets(s).length,
            })),
            scrollStart: SECTION_SCROLL_START,
        });

        this.pageContext = await createPageContext(this.element, ({ gsap, ScrollTrigger }) => {
            animateCinematicHero(gsap, this.element);

            sections.forEach((section) => {
                revealHomeSectionOnScroll(gsap, section, { start: SECTION_SCROLL_START });
            });

            motionLog('home-entrance', 'context built', {
                scrollTriggers: ScrollTrigger.getAll().length,
            });
        });

        scheduleScrollRefresh();
    }

    disconnect() {
        motionLog('home-entrance', 'disconnect');
        this.pageContext?.revert();
        this.pageContext = null;
    }
}
