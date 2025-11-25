const handleNewsletterSubmit = async (event) => {
  event.preventDefault();

  const form = event.currentTarget;
  const messageTarget = form.dataset.feedbackTarget
    ? document.querySelector(form.dataset.feedbackTarget)
    : document.getElementById('newsletterMessage');
  const submitButton = form.querySelector('button[type="submit"]');
  const originalText = submitButton.innerHTML;

  // Récupérer uniquement le champ email pour éviter les champs supplémentaires
  const emailInput = form.querySelector('input[type="email"]');
  if (!emailInput || !emailInput.value.trim()) {
    if (messageTarget) {
      messageTarget.classList.remove('d-none');
      messageTarget.classList.add('alert', 'alert-danger');
      messageTarget.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Veuillez saisir votre adresse email.';
    }
    return;
  }

  // Créer un FormData propre avec uniquement l'email
  // Utiliser le nom exact du champ depuis l'input (plus robuste)
  const formData = new FormData();
  const fieldName = emailInput.name || 'newsletter_form[email]';
  formData.append(fieldName, emailInput.value.trim());

  submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi...';
  submitButton.disabled = true;
  if (messageTarget) {
    messageTarget.classList.add('d-none');
    messageTarget.classList.remove('alert', 'alert-success', 'alert-danger');
  }

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: formData,
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (messageTarget) {
      messageTarget.classList.remove('d-none');
      messageTarget.classList.add('alert', data.success ? 'alert-success' : 'alert-danger');
      
      let errorMessage = data.message || 'Erreur lors de l\'inscription.';
      
      // Afficher les erreurs détaillées si disponibles
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
      messageTarget.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Une erreur est survenue. Veuillez réessayer.';
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
