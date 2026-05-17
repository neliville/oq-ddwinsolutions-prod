/** GSAP + ScrollTrigger — chargement dynamique (Layer 2, pages `data-page-motion="true"`). */
let loadPromise = null;
/** @type {{ gsap: import('gsap').gsap, ScrollTrigger: import('gsap').ScrollTrigger } | null} */
let runtime = null;

/**
 * @returns {Promise<{ gsap: import('gsap').gsap, ScrollTrigger: import('gsap').ScrollTrigger }>}
 */
export function getGsapRuntime() {
    if (runtime) {
        return Promise.resolve(runtime);
    }

    if (!loadPromise) {
        loadPromise = (async () => {
            const [{ default: gsap }, { ScrollTrigger }] = await Promise.all([
                import('gsap'),
                import('gsap/ScrollTrigger'),
            ]);
            gsap.registerPlugin(ScrollTrigger);
            runtime = { gsap, ScrollTrigger };
            return runtime;
        })();
    }

    return loadPromise;
}
