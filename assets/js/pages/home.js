export const registerHomePage = () => {
  const heroSection = document.getElementById('hero');
  if (!heroSection) {
    return;
  }

  registerTestimonialAccordions();
  registerFaqAccordions();
};

function registerTestimonialAccordions() {
  const grid = document.querySelector('.social-proof-section .testimonials-grid');
  if (!grid) return;

  // Supprimer l'ancien listener s'il existe
  const oldHandler = grid._testimonialClickHandler;
  if (oldHandler) {
    grid.removeEventListener('click', oldHandler);
  }

  // Créer un nouveau handler avec délégation d'événements
  const clickHandler = (e) => {
    const btn = e.target.closest('.testimonial-card__trigger');
    if (!btn) return;

    const card = btn.closest('.testimonial-card');
    if (!card) return;

    const wasOpen = card.classList.contains('is-open');

    // Fermer tous les items
    grid.querySelectorAll('.testimonial-card').forEach((c) => c.classList.remove('is-open'));
    grid.querySelectorAll('.testimonial-card__trigger').forEach((b) => b.setAttribute('aria-expanded', 'false'));

    // Ouvrir l'item cliqué seulement s'il était fermé
    if (!wasOpen) {
      card.classList.add('is-open');
      btn.setAttribute('aria-expanded', 'true');
    }
  };

  // Stocker le handler pour pouvoir le supprimer plus tard
  grid._testimonialClickHandler = clickHandler;
  grid.addEventListener('click', clickHandler);
}

function registerFaqAccordions() {
  const list = document.querySelector('.faq-section .faq-list');
  if (!list) return;

  // Supprimer l'ancien listener s'il existe
  const oldHandler = list._faqClickHandler;
  if (oldHandler) {
    list.removeEventListener('click', oldHandler);
  }

  // Créer un nouveau handler avec délégation d'événements
  const clickHandler = (e) => {
    const btn = e.target.closest('.faq-question');
    if (!btn) return;

    const item = btn.closest('.faq-item');
    if (!item) return;

    const wasOpen = item.classList.contains('is-open');

    // Fermer tous les items
    list.querySelectorAll('.faq-item').forEach((el) => el.classList.remove('is-open'));
    list.querySelectorAll('.faq-question').forEach((b) => b.setAttribute('aria-expanded', 'false'));

    // Ouvrir l'item cliqué seulement s'il était fermé
    if (!wasOpen) {
      item.classList.add('is-open');
      btn.setAttribute('aria-expanded', 'true');
    }
  };

  // Stocker le handler pour pouvoir le supprimer plus tard
  list._faqClickHandler = clickHandler;
  list.addEventListener('click', clickHandler);
}
