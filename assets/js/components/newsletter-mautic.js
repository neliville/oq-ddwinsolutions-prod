/**
 * Embed Mautic newsletter (generate.js) : un seul chargement par affichage de page.
 * Un <script> statique dans le HTML + turbo:load pouvait réinjecter le formulaire (doublon + message de succès).
 *
 * La CSS Mautic distante peut s'appliquer après app.css (fond clair + texte clair sur le CTA).
 * On renforce le contraste via styles inline sur le bouton (même logique que téléchargement 5M).
 */
const VIEWPORT_SEL = '.newsletter-mautic-embed__viewport';

/** @param {HTMLElement} root */
function applyNewsletterMauticContrastFix(root) {
  try {
    if (!root || !root.isConnected) {
      return;
    }
    const cs = getComputedStyle(document.documentElement);
    const p = cs.getPropertyValue('--primary').trim();
    if (!p) {
      return;
    }
    const pf = cs.getPropertyValue('--primary-foreground').trim();
    const bg = `hsl(${p})`;
    const fg = pf ? `hsl(${pf})` : '#fff';
    const q = [
      'button[type="submit"]',
      'input[type="submit"]',
      'a.mauticform-button',
      'button.mauticform-button',
      '.mauticform-button-wrapper button',
      '.mauticform-button-wrapper a',
      'button[class*="mauticform"]',
      '.mauticform_wrapper button[type="submit"]',
      '#mautic-newsletter-container a.btn',
      '#mautic-newsletter-container button.btn',
    ].join(',');
    root.querySelectorAll(q).forEach((el) => {
      el.style.setProperty('background-color', bg, 'important');
      el.style.setProperty('background-image', 'none', 'important');
      el.style.setProperty('color', fg, 'important');
      el.style.setProperty('-webkit-text-fill-color', fg, 'important');
      el.style.setProperty('opacity', '1', 'important');
      el.style.setProperty('font-size', '1rem', 'important');
      el.style.setProperty('line-height', '1.4', 'important');
      el.style.setProperty('text-indent', '0', 'important');
      el.style.setProperty('min-height', '2.75rem', 'important');
      el.style.setProperty('letter-spacing', 'normal', 'important');
      el.querySelectorAll('span, .mauticform-button-label, label, i').forEach((n) => {
        n.style.setProperty('color', fg, 'important');
        n.style.setProperty('-webkit-text-fill-color', fg, 'important');
        n.style.setProperty('opacity', '1', 'important');
      });
      if (el.tagName === 'INPUT' && el.type === 'submit') {
        const v = (el.getAttribute('value') || '').trim();
        if (!v) {
          el.setAttribute('value', "S'abonner");
        }
      }
      if (el.tagName === 'BUTTON' && (!el.textContent || !String(el.textContent).trim())) {
        el.textContent = "S'abonner à la newsletter";
      }
    });
  } catch {
    /* noop */
  }
}

/** @param {HTMLElement} root */
function setupNewsletterContrastObserver(root) {
  if (root._newsletterContrastMo) {
    root._newsletterContrastMo.disconnect();
  }
  let debounce;
  const mo = new MutationObserver(() => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
      applyNewsletterMauticContrastFix(root);
    }, 50);
  });
  mo.observe(root, { childList: true, subtree: true });
  root._newsletterContrastMo = mo;
  [0, 100, 400, 800, 1500, 3000, 5000].forEach((ms) => {
    setTimeout(() => {
      applyNewsletterMauticContrastFix(root);
    }, ms);
  });
}

function clearNewsletterMauticViewports() {
  document.querySelectorAll('[data-newsletter-mautic]').forEach((root) => {
    if (root._newsletterContrastMo) {
      root._newsletterContrastMo.disconnect();
      root._newsletterContrastMo = null;
    }
  });
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
    setupNewsletterContrastObserver(root);
  });
}

document.addEventListener('turbo:before-cache', clearNewsletterMauticViewports);
document.addEventListener('turbo:load', injectNewsletterMauticForms);
