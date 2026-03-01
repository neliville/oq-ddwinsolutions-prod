import './bootstrap.js';
import '@hotwired/turbo';
import './js/bootstrap.js';
import { registerNavbar } from './js/components/navbar.js';
import { registerNewsletterForms } from './js/components/newsletter.js';
import { registerDownloadForms } from './js/components/download-form.js';
import { registerHomePage } from './js/pages/home.js';

const initialiseFrontend = () => {
  registerNavbar();
  registerNewsletterForms();
  registerDownloadForms();
  registerHomePage();
};

document.addEventListener('DOMContentLoaded', initialiseFrontend);
document.addEventListener('turbo:load', initialiseFrontend);
document.addEventListener('turbo:render', initialiseFrontend);

if (typeof Turbo !== 'undefined') {
  Turbo.session.drive = true;
}
