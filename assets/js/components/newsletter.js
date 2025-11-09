const handleNewsletterSubmit = async (event) => {
  event.preventDefault();

  const form = event.currentTarget;
  const messageTarget = form.dataset.feedbackTarget
    ? document.querySelector(form.dataset.feedbackTarget)
    : document.getElementById('newsletterMessage');
  const submitButton = form.querySelector('button[type="submit"]');
  const originalText = submitButton.innerHTML;

  const formData = new FormData(form);

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

    const data = await response.json();

    if (messageTarget) {
      messageTarget.classList.remove('d-none');
      messageTarget.classList.add('alert', data.success ? 'alert-success' : 'alert-danger');
      messageTarget.innerHTML = data.success
        ? '<i class="fas fa-check-circle me-2"></i>' + data.message
        : '<i class="fas fa-exclamation-triangle me-2"></i>' + data.message;
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
