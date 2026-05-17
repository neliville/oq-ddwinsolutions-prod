import { registerReactControllerComponents } from '@symfony/ux-react';
import './stimulus_app.js';
import '@hotwired/turbo';
import './js/components/newsletter-mautic.js';
import { registerNavbar } from './js/components/navbar.js';
import { registerDownloadForms } from './js/components/download-form.js';
import { registerHomePage } from './js/pages/home.js';
import { registerPremiumMotionControllers } from './motion/bootstrap-premium.js';

// Gestionnaire global pour les déclencheurs de modals (data-modal-open="modalId")
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-modal-open]');
    if (!btn) return;
    const modalId = btn.dataset.modalOpen;
    if (modalId) {
        document.dispatchEvent(new CustomEvent('modal:open', { detail: { modalId } }));
    }
});

const shouldLoadPremiumMotion = () => {
    const root = document.documentElement;
    const body = document.body;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return false;
    }
    return (
        root?.dataset.pageMotion === 'true' ||
        body?.dataset.pageMotion === 'true'
    );
};

const loadPremiumMotionLayer = () => {
    if (!shouldLoadPremiumMotion() || !window.Stimulus) {
        return;
    }
    void registerPremiumMotionControllers(window.Stimulus);
};

const initialiseFrontend = () => {
    registerNavbar();
    registerDownloadForms();
    registerHomePage();
    loadPremiumMotionLayer();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialiseFrontend);
} else {
    initialiseFrontend();
}
document.addEventListener('turbo:load', initialiseFrontend);

if (typeof Turbo !== 'undefined') {
  Turbo.session.drive = true;
}

registerReactControllerComponents();