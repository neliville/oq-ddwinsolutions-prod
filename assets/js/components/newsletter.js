const handleNewsletterSubmit = async (event) => {
  event.preventDefault();

  const form = event.currentTarget;
  const messageTarget = form.dataset.feedbackTarget
    ? document.querySelector(form.dataset.feedbackTarget)
    : document.getElementById('newsletterMessage');
  const submitButton = form.querySelector('button[type="submit"]');
  const originalText = submitButton.innerHTML;

  const emailInput = form.querySelector('input[type="email"]');
  if (!emailInput || !emailInput.value.trim()) {
    if (messageTarget) {
      messageTarget.classList.remove('d-none');
      messageTarget.classList.add('alert', 'alert-danger');
      messageTarget.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Veuillez saisir votre adresse email.';
    }
    return;
  }

  // Envoyer tout le formulaire (noms de champs Symfony corrects) + Accept JSON
  const formData = new FormData(form);

  submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi...';
  submitButton.disabled = true;
  if (messageTarget) {
    messageTarget.classList.add('d-none');
    messageTarget.classList.remove('alert', 'alert-success', 'alert-danger');
  }

  try {
    const url = form.action || '/api/newsletter/subscribe';
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: formData,
    });

    let data;
    try {
      data = await response.json();
    } catch {
      data = { success: false, message: 'Réponse invalide du serveur.' };
    }

    if (messageTarget) {
      messageTarget.classList.remove('d-none');
      messageTarget.classList.add('alert', data.success ? 'alert-success' : 'alert-danger');

      let errorMessage = data.message || 'Erreur lors de l\'inscription.';

      if (!data.success && data.errors && Array.isArray(data.errors) && data.errors.length > 0) {
        errorMessage += '<ul class="mb-0 mt-2">';
        data.errors.forEach(error => {
          errorMessage += `<li>${error}</li>`;
        });
        errorMessage += '</ul>';
      }

      messageTarget.innerHTML = data.success
        ? '<i class="fas fa-check-circle me-2"></i>' + data.message
        : '<i class="fas fa-exclamation-triangle me-2"></i>' + errorMessage;
    }

    if (data.success) {
      form.reset();
    }
  } catch (error) {
    if (messageTarget) {
      messageTarget.classList.remove('d-none');
      messageTarget.classList.add('alert', 'alert-danger');
      const networkMsg = error.message && error.message.includes('fetch') ? 'Vérifiez votre connexion internet.' : '';
      messageTarget.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Une erreur est survenue. Veuillez réessayer.' + (networkMsg ? ' ' + networkMsg : '');
    }
  } finally {
    submitButton.innerHTML = originalText;
    submitButton.disabled = false;
  }
};

export const registerNewsletterForms = () => {
  document.querySelectorAll('form[id="newsletterForm"], form[data-newsletter="true"]').forEach((form) => {
    if (!form.dataset.listenerAttached) {
      form.dataset.listenerAttached = 'true';
      form.addEventListener('submit', handleNewsletterSubmit);
    }
  });
};
