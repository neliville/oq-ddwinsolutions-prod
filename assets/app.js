import './bootstrap.js';
import '@hotwired/turbo';
import './js/components/newsletter-mautic.js';
import './js/bootstrap.js';
import { registerNavbar } from './js/components/navbar.js';
import { registerDownloadForms } from './js/components/download-form.js';
import { registerHomePage } from './js/pages/home.js';

const initialiseFrontend = () => {
    registerNavbar();
    registerDownloadForms();
  registerHomePage();
};

document.addEventListener('DOMContentLoaded', initialiseFrontend);
document.addEventListener('turbo:load', initialiseFrontend);
document.addEventListener('turbo:render', initialiseFrontend);

if (typeof Turbo !== 'undefined') {
  Turbo.session.drive = true;
}
