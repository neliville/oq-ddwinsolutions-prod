import './bootstrap.js';
import '@hotwired/turbo';
import './js/components/newsletter-mautic.js';
import './js/bootstrap.js';
import { registerNavbar } from './js/components/navbar.js';
import { registerDownloadForms } from './js/components/download-form.js';
import { registerHomePage } from './js/pages/home.js';

// Gestionnaire global pour les déclencheurs de modals (data-modal-open="modalId")
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-modal-open]');
    if (!btn) return;
    const modalId = btn.dataset.modalOpen;
    if (modalId) {
        document.dispatchEvent(new CustomEvent('modal:open', { detail: { modalId } }));
    }
});

const initialiseFrontend = () => {
    registerNavbar();
    registerDownloadForms();
  registerHomePage();
};

document.addEventListener('DOMContentLoaded', initialiseFrontend);
document.addEventListener('turbo:load', initialiseFrontend);

if (typeof Turbo !== 'undefined') {
  Turbo.session.drive = true;
}
