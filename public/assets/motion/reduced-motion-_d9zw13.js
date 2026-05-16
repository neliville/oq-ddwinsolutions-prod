const mql = typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

export function prefersReducedMotion() {
    return Boolean(mql?.matches);
}

/** Retourne une copie des props GSAP avec durée 0 si reduced-motion. */
export function tweenProps(props) {
    if (!prefersReducedMotion()) {
        return props;
    }
    const next = { ...props };
    next.duration = 0;
    if (next.scrollTrigger && typeof next.scrollTrigger === 'object') {
        next.scrollTrigger = { ...next.scrollTrigger, animation: undefined };
    }
    return next;
}

export function onReducedMotionChange(cb) {
    if (!mql?.addEventListener) {
        return () => {};
    }
    mql.addEventListener('change', cb);
    return () => mql.removeEventListener('change', cb);
}
