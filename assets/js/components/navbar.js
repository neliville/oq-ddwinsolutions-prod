const MOBILE_NAV_MAX = 1023;

const isMobileNav = () => window.matchMedia(`(max-width: ${MOBILE_NAV_MAX}px)`).matches;

const getNavbarEls = () => ({
    toggler: document.querySelector('[data-navbar-toggler]'),
    collapse: document.querySelector('[data-navbar-collapse]'),
    backdrop: document.querySelector('[data-navbar-backdrop]'),
});

const syncMainNavDrawerClass = () => {
    const nav = document.getElementById('mainNav');
    const collapse = document.querySelector('[data-navbar-collapse]');
    if (!nav || !collapse) {
        return;
    }
    const drawerOpen = collapse.classList.contains('show') && isMobileNav();
    nav.classList.toggle('main-navbar--drawer-open', drawerOpen);
};

const refreshLucide = () => {
    if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
    }
};

/** Fermeture en ne s’appuyant que sur le DOM courant (Turbo / navigation). */
const closeMobileMenu = () => {
    const { toggler, collapse, backdrop } = getNavbarEls();
    if (!collapse) {
        return;
    }
    if (!collapse.classList.contains('show')) {
        syncMainNavDrawerClass();
        return;
    }
    collapse.classList.remove('show');
    if (toggler) {
        toggler.setAttribute('aria-expanded', 'false');
        toggler.setAttribute('aria-label', 'Afficher le menu');
    }
    if (backdrop) {
        backdrop.classList.remove('is-visible');
        backdrop.setAttribute('aria-hidden', 'true');
    }
    document.documentElement.style.overflow = '';
    syncMainNavDrawerClass();
    refreshLucide();
};

let navbarGlobalsRegistered = false;

const initNavbar = () => {
    const toggler = document.querySelector('[data-navbar-toggler]');
    const collapse = document.querySelector('[data-navbar-collapse]');
    const backdrop = document.querySelector('[data-navbar-backdrop]');

    if (!toggler || !collapse) return;
    if (toggler.dataset.listenerAttached) return;

    toggler.dataset.listenerAttached = 'true';

    const setBackdrop = (visible) => {
        if (!backdrop) return;
        backdrop.classList.toggle('is-visible', visible);
        backdrop.setAttribute('aria-hidden', visible ? 'false' : 'true');
    };

    const setScrollLock = (locked) => {
        document.documentElement.style.overflow = locked ? 'hidden' : '';
    };

    const open = () => {
        collapse.classList.add('show');
        toggler.setAttribute('aria-expanded', 'true');
        toggler.setAttribute('aria-label', 'Fermer le menu');
        if (isMobileNav()) {
            setBackdrop(true);
            setScrollLock(true);
        }
        syncMainNavDrawerClass();
        refreshLucide();
    };

    const close = () => {
        collapse.classList.remove('show');
        toggler.setAttribute('aria-expanded', 'false');
        toggler.setAttribute('aria-label', 'Afficher le menu');
        setBackdrop(false);
        setScrollLock(false);
        syncMainNavDrawerClass();
        refreshLucide();
    };

    const toggle = () => (collapse.classList.contains('show') ? close() : open());

    toggler.addEventListener('click', (e) => {
        e.preventDefault();
        toggle();
    });

    if (backdrop) {
        backdrop.addEventListener('click', () => {
            if (collapse.classList.contains('show')) {
                close();
            }
        });
    }

    collapse.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));

    if (!navbarGlobalsRegistered) {
        navbarGlobalsRegistered = true;
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeMobileMenu();
            }
        });
        window.addEventListener('resize', () => {
            if (!isMobileNav()) {
                closeMobileMenu();
            }
        });
    }

    syncMainNavDrawerClass();
};

export const registerNavbar = () => initNavbar();
