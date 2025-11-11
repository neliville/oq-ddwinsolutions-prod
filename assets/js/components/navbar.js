import { Collapse, Dropdown } from '../bootstrap.js';

const initNavbar = () => {
  const toggler = document.querySelector('.navbar-toggler[data-bs-toggle="collapse"]');
  const targetSelector = toggler ? toggler.getAttribute('data-bs-target') || toggler.dataset.bsTarget : null;
  const collapseElement = targetSelector ? document.querySelector(targetSelector) : null;

  if (!toggler || !collapseElement) {
    return;
  }

  const collapseInstance = Collapse.getOrCreateInstance(collapseElement, { toggle: false });
  const syncAria = () => {
    toggler.setAttribute('aria-expanded', collapseElement.classList.contains('show') ? 'true' : 'false');
  };

  if (!toggler.dataset.listenerAttached) {
    toggler.dataset.listenerAttached = 'true';

    toggler.addEventListener('click', (event) => {
      event.preventDefault();
      collapseInstance.toggle();
    });

    collapseElement.addEventListener('shown.bs.collapse', syncAria);
    collapseElement.addEventListener('hidden.bs.collapse', syncAria);

    collapseElement.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => collapseInstance.hide());
    });
  }

  document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((toggle) => {
    Dropdown.getOrCreateInstance(toggle);
  });
};

export const registerNavbar = () => {
  initNavbar();
};


