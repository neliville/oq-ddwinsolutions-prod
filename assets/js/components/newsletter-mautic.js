/**
 * Embed Mautic newsletter (generate.js) : un seul chargement par affichage de page.
 * Un <script> statique dans le HTML + turbo:load pouvait réinjecter le formulaire (doublon + message de succès).
 */
const VIEWPORT_SEL = '.newsletter-mautic-embed__viewport';

function clearNewsletterMauticViewports() {
  document.querySelectorAll('[data-newsletter-mautic] ' + VIEWPORT_SEL).forEach((el) => {
    el.replaceChildren();
  });
}

function injectNewsletterMauticForms() {
  document.querySelectorAll('[data-newsletter-mautic]').forEach((root) => {
    const url = root.dataset.mauticNewsletterScriptUrl;
    const viewport = root.querySelector(VIEWPORT_SEL);
    if (!url || !viewport) {
      return;
    }
    viewport.replaceChildren();
    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = url;
    viewport.appendChild(script);
  });
}

document.addEventListener('turbo:before-cache', clearNewsletterMauticViewports);
document.addEventListener('turbo:load', injectNewsletterMauticForms);
