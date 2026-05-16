import {
    DURATIONS,
    EASES,
    HERO_SEQUENCE,
    MOTION_ATMOSPHERE,
    MOTION_HERO,
    MOTION_INTRO,
    MOTION_REVEAL,
} from './tokens.js';
import { prefersReducedMotion, tweenProps } from './reduced-motion.js';

/** Blocs à révéler dans une section home (`.container` ou enfants d’un wrapper unique). */
/** En-têtes de section (phase 1 de la chorégraphie). */
export function getHomeSectionIntroTargets(section) {
    return [...section.querySelectorAll(':scope [data-home-section-intro]')];
}

/** Blocs de contenu (phase 2). */
export function getHomeSectionContentTargets(section) {
    return [...section.querySelectorAll(':scope [data-home-reveal]')];
}

export function getHomeSectionRevealTargets(section) {
    const intro = getHomeSectionIntroTargets(section);
    const content = getHomeSectionContentTargets(section);
    if (intro.length || content.length) {
        return [...intro, ...content];
    }

    const marked = [...section.querySelectorAll(':scope [data-home-reveal]')];
    if (marked.length) {
        return marked;
    }

    const container = section.querySelector(':scope > .container');
    if (!container) {
        return [];
    }

    const direct = [...container.children].filter((el) => el.nodeType === Node.ELEMENT_NODE);
    if (direct.length === 1) {
        const only = direct[0];
        if (only.classList.contains('expertise-content')) {
            return [...only.children].filter((el) => el.nodeType === Node.ELEMENT_NODE);
        }
    }

    return direct;
}

const SECTION_REVEAL_FROM = {
    autoAlpha: 0,
    y: MOTION_REVEAL.y,
    scale: MOTION_REVEAL.scale,
};

const INTRO_REVEAL_FROM = {
    autoAlpha: 0,
    y: MOTION_INTRO.y,
    scale: MOTION_INTRO.scale,
};

const HERO_REVEAL_FROM = {
    autoAlpha: 0,
    y: MOTION_HERO.y,
    scale: MOTION_HERO.scale,
};

const REVEAL_TO = {
    autoAlpha: 1,
    y: 0,
    scale: 1,
};

/**
 * Hero cinématique : badges → H1 → subtitle → note → CTA → stats → preview.
 * @param {import('gsap').gsap} gsap
 * @param {ParentNode} scope
 */
export function animateCinematicHero(gsap, scope) {
    const steps = HERO_SEQUENCE.map(({ selector, at }) => ({
        el: scope.querySelector(selector),
        at,
    })).filter((s) => s.el);

    if (!steps.length || prefersReducedMotion()) {
        steps.forEach(({ el }) => gsap.set(el, REVEAL_TO));
        return null;
    }

    steps.forEach(({ el }) => gsap.set(el, HERO_REVEAL_FROM));

    const tl = gsap.timeline({ defaults: { ease: EASES.hero } });

    steps.forEach(({ el, at }) => {
        tl.to(
            el,
            {
                ...REVEAL_TO,
                duration: DURATIONS.hero,
                ease: EASES.hero,
            },
            at,
        );
    });

    return tl;
}

/**
 * Parallax léger du halo — relie visuellement les sections au scroll.
 * @param {import('gsap').gsap} gsap
 * @param {Element} section
 * @param {Element | null} glow
 */
function bindHomeSectionGlowParallax(gsap, section, glow) {
    if (!glow || prefersReducedMotion()) {
        return null;
    }

    return gsap.to(glow, {
        yPercent: MOTION_ATMOSPHERE.glowYPercent,
        ease: 'none',
        scrollTrigger: {
            trigger: section,
            start: 'top bottom',
            end: 'bottom top',
            scrub: MOTION_ATMOSPHERE.glowScrub,
            invalidateOnRefresh: true,
        },
    });
}

/**
 * Chorégraphie scroll : pont → halo → intro → contenu (rythme entre sections).
 */
export function revealHomeSectionOnScroll(gsap, section, options = {}) {
    const { start = 'top bottom-=88', stagger = MOTION_REVEAL.sectionStagger } = options;
    const intro = getHomeSectionIntroTargets(section);
    let content = getHomeSectionContentTargets(section);
    const glow = section.querySelector(':scope .home-section-premium__glow');
    const bridge = section.querySelector(':scope [data-home-section-bridge]');

    if (!intro.length && !content.length) {
        content = getHomeSectionRevealTargets(section);
    }

    const allTargets = [...intro, ...content];
    if (!allTargets.length) {
        return null;
    }

    if (prefersReducedMotion()) {
        gsap.set(allTargets, REVEAL_TO);
        if (glow) {
            gsap.set(glow, { autoAlpha: 1, scale: 1, yPercent: 0 });
        }
        if (bridge) {
            gsap.set(bridge, { autoAlpha: 1, scaleX: 1 });
        }
        return null;
    }

    gsap.set(intro, INTRO_REVEAL_FROM);
    gsap.set(content, SECTION_REVEAL_FROM);
    if (glow) {
        gsap.set(glow, { autoAlpha: 0, scale: 1.06, yPercent: 0 });
    }
    if (bridge) {
        gsap.set(bridge, {
            autoAlpha: 0,
            scaleX: 0,
            xPercent: -50,
            left: '50%',
            transformOrigin: 'center center',
        });
    }

    const tl = gsap.timeline({
        scrollTrigger: {
            trigger: section,
            start,
            toggleActions: 'play none none none',
            once: true,
            invalidateOnRefresh: true,
        },
        defaults: { ease: EASES.reveal },
    });

    let cursor = 0;

    if (bridge) {
        tl.to(
            bridge,
            tweenProps({
                autoAlpha: 1,
                scaleX: 1,
                duration: MOTION_ATMOSPHERE.bridgeDuration,
                ease: EASES.out,
            }),
            cursor,
        );
        cursor += 0.06;
    }

    if (glow) {
        tl.to(
            glow,
            tweenProps({
                autoAlpha: 1,
                scale: 1,
                duration: DURATIONS.slow,
                ease: EASES.inOut,
            }),
            cursor,
        );
        cursor += 0.1;
        bindHomeSectionGlowParallax(gsap, section, glow);
    }

    if (intro.length) {
        tl.to(
            intro,
            tweenProps({
                ...REVEAL_TO,
                duration: DURATIONS.base,
                stagger: MOTION_INTRO.stagger,
            }),
            cursor,
        );
        cursor += 0.2;
    }

    if (content.length) {
        tl.to(
            content,
            tweenProps({
                ...REVEAL_TO,
                duration: DURATIONS.section,
                stagger,
            }),
            intro.length ? cursor : cursor * 0.5,
        );
    }

    return tl;
}

/**
 * @param {import('gsap').gsap} gsap
 * @param {Element[]} elements
 * @param {import('gsap').ScrollTrigger} ScrollTrigger
 */
export function staggerFadeUp(gsap, elements, ScrollTrigger, options = {}) {
    const { trigger, start = 'top bottom', stagger = 0.1 } = options;
    const reduced = prefersReducedMotion();
    const mount = !trigger;
    const yFrom = reduced ? 0 : mount ? MOTION_HERO.y : MOTION_REVEAL.y;
    const scaleFrom = reduced ? 1 : mount ? MOTION_HERO.scale : MOTION_REVEAL.scale;
    const duration = reduced ? 0 : mount ? DURATIONS.slow : DURATIONS.section;
    return gsap.fromTo(
        elements,
        { autoAlpha: 0, y: yFrom, scale: scaleFrom },
        tweenProps({
            ...REVEAL_TO,
            duration,
            delay: mount && !reduced ? 0.08 : 0,
            ease: EASES.reveal,
            stagger: reduced ? 0 : stagger,
            scrollTrigger: trigger
                ? {
                      trigger,
                      start,
                      once: true,
                      toggleActions: 'play none none none',
                  }
                : undefined,
        }),
    );
}

/**
 * @param {import('gsap').gsap} gsap
 * @param {HTMLElement} el
 * @param {number} endValue
 */
export function animateCounter(gsap, el, endValue, options = {}) {
    const { duration = DURATIONS.slow, from } = options;
    const reduced = prefersReducedMotion();
    const startVal = reduced ? endValue : (from ?? 0);
    const obj = { val: startVal };
    if (!reduced && from === undefined) {
        el.textContent = '0';
    } else if (!reduced) {
        el.textContent = String(Math.round(startVal));
    }
    return gsap.to(obj, {
        val: endValue,
        duration: reduced ? 0 : duration,
        ease: EASES.out,
        onUpdate: () => {
            el.textContent = String(Math.round(obj.val));
        },
    });
}
