import './bootstrap.js';
import '@hotwired/turbo';
import './styles/app.scss';

import './js/bootstrap.js';
import { registerNewsletterForms } from './js/components/newsletter.js';
import { registerHomePage } from './js/pages/home.js';

const initialiseFrontend = () => {
  registerNewsletterForms();
  registerHomePage();
};

document.addEventListener('DOMContentLoaded', initialiseFrontend);
document.addEventListener('turbo:load', initialiseFrontend);
document.addEventListener('turbo:render', initialiseFrontend);

if (typeof Turbo !== 'undefined') {
  Turbo.session.drive = true;
}
