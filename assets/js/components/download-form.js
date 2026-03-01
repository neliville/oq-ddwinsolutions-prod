/**
 * Gère le formulaire de téléchargement de ressources (ex. Modèle 5M).
 * Envoie les données à Mautic via notre API, puis déclenche le téléchargement.
 */
const handleDownloadFormSubmit = async (event) => {
  event.preventDefault();

  const form = event.currentTarget;
  const messageTarget = form.querySelector('[data-download-feedback]') || document.getElementById('downloadMessage');
  const submitButton = form.querySelector('button[type="submit"]');
  const originalText = submitButton?.innerHTML;
  const requestUrl = form.dataset.requestUrl || '/api/download/modele-5m/request';
  const merciUrl = form.dataset.merciUrl || '';

  const emailInput = form.querySelector('input[name="email"], input[type="email"]');
  if (!emailInput || !emailInput.value.trim()) {
    if (messageTarget) {
      messageTarget.classList.remove('d-none');
      messageTarget.classList.add('alert', 'alert-danger');
      messageTarget.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Veuillez saisir votre adresse email.';
    }
    return;
  }

  const formData = new FormData(form);
  const payload = {
    email: (formData.get('email') || '').trim(),
    firstname: (formData.get('firstname') || '').trim() || undefined,
  };

  if (submitButton) {
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi...';
    submitButton.disabled = true;
  }
  if (messageTarget) {
    messageTarget.classList.add('d-none');
    messageTarget.classList.remove('alert-success', 'alert-danger');
  }

  try {
    const response = await fetch(requestUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });

    let data;
    try {
      data = await response.json();
    } catch {
      data = { success: false, message: 'Réponse invalide du serveur.' };
    }

    if (!data.success) {
      if (messageTarget) {
        messageTarget.classList.remove('d-none');
        messageTarget.classList.add('alert', 'alert-danger');
        messageTarget.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + (data.message || 'Erreur lors de la demande.');
      }
      return;
    }

    // Succès : déclencher le téléchargement puis redirection
    if (data.downloadUrl) {
      const a = document.createElement('a');
      a.href = data.downloadUrl;
      a.download = '';
      a.target = '_blank';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    }

    if (merciUrl) {
      window.location.href = merciUrl;
    } else if (messageTarget) {
      messageTarget.classList.remove('d-none');
      messageTarget.classList.add('alert', 'alert-success');
      messageTarget.innerHTML = '<i class="fas fa-check-circle me-2"></i>Téléchargement lancé !';
    }
  } catch (error) {
    if (messageTarget) {
      messageTarget.classList.remove('d-none');
      messageTarget.classList.add('alert', 'alert-danger');
      messageTarget.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Une erreur est survenue. Veuillez réessayer.';
    }
  } finally {
    if (submitButton) {
      submitButton.innerHTML = originalText;
      submitButton.disabled = false;
    }
  }
};

export const registerDownloadForms = () => {
  document.querySelectorAll('form[data-download-form="true"]').forEach((form) => {
    if (!form.dataset.listenerAttached) {
      form.dataset.listenerAttached = 'true';
      form.addEventListener('submit', handleDownloadFormSubmit);
    }
  });
};
