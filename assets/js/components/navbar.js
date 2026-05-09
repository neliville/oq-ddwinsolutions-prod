const initNavbar = () => {
    const toggler = document.querySelector('[data-navbar-toggler]');
    const collapse = document.querySelector('[data-navbar-collapse]');

    if (!toggler || !collapse) return;
    if (toggler.dataset.listenerAttached) return;

    toggler.dataset.listenerAttached = 'true';

    const open  = () => { collapse.classList.add('show');    toggler.setAttribute('aria-expanded', 'true');  };
    const close = () => { collapse.classList.remove('show'); toggler.setAttribute('aria-expanded', 'false'); };
    const toggle = () => collapse.classList.contains('show') ? close() : open();

    toggler.addEventListener('click', (e) => { e.preventDefault(); toggle(); });

    collapse.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
};

export const registerNavbar = () => initNavbar();
