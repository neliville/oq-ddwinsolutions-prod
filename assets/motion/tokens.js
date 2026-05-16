/** Durées et courbes — cohérence « premium » sur tout le site. */
export const DURATIONS = {
    instant: 0.12,
    fast: 0.28,
    base: 0.45,
    slow: 0.75,
    hero: 1.05,
    section: 0.95,
};

export const EASES = {
    out: 'power3.out',
    inOut: 'power2.inOut',
    smooth: 'expo.out',
    hero: 'power4.out',
    reveal: 'power4.out',
};

/** Spring Motion One (objet passé à `spring` / `animate`). */
export const SPRING_SNAPPY = { stiffness: 420, damping: 32 };
export const SPRING_SOFT = { stiffness: 220, damping: 24 };

/** Entrée hero — amplitude forte, lisible au premier coup d’œil. */
export const MOTION_HERO = {
    y: 80,
    scale: 0.86,
    staggerGap: 0.16,
};

/** Révélation scroll sections — plus de relief que le hero unitaire. */
export const MOTION_REVEAL = {
    y: 72,
    scale: 0.88,
    sectionStagger: 0.18,
};

/** Titres / séparateurs de section — entrée plus courte, avant le contenu. */
export const MOTION_INTRO = {
    y: 52,
    scale: 0.94,
    stagger: 0.1,
};

/** Pont et halo entre sections — atmosphère au scroll. */
export const MOTION_ATMOSPHERE = {
    glowYPercent: 14,
    glowScrub: 0.55,
    bridgeDuration: 0.6,
};

/** Délais relatifs timeline hero (secondes) — rythme cinématique espacé. */
export const HERO_SEQUENCE = [
    { selector: '#hero .home-hero-badges', at: 0 },
    { selector: '#hero .hero-title', at: 0.14 },
    { selector: '#hero .hero-subtitle', at: 0.3 },
    { selector: '#hero .hero-usp-note', at: 0.4 },
    { selector: '#hero .hero-actions', at: 0.5 },
    { selector: '#hero .hero-stats', at: 0.66 },
    { selector: '#hero .hero-tool-preview', at: 0.82 },
];
