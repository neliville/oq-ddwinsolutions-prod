/**
 * Intégration page Symfony : Toastify via Stimulus `notifications` (événement `app:notification`)
 * et modal `#globalConfirmationModal` — même logique que `public/js/ishikawa.js` (`requestConfirmation`).
 */

export function notifyIshikawa(message, type = 'info') {
  if (typeof document === 'undefined' || !message) return;
  document.dispatchEvent(
    new CustomEvent('app:notification', {
      bubbles: true,
      detail: { message: String(message), type },
    })
  );
}

/**
 * @param {{ title?: string, message?: string, confirmText?: string, cancelText?: string }} [options]
 * @returns {Promise<boolean>} true si l'utilisateur confirme
 */
export async function requestUserConfirmation(
  options = {},
  fallbackMessage = 'Êtes-vous sûr de vouloir continuer ?'
) {
  const modalElement = document.getElementById('globalConfirmationModal');
  if (modalElement && window.Stimulus) {
    try {
      const title = options.title || 'Confirmation';
      const message = options.message || fallbackMessage;
      const confirmText = options.confirmText || 'Confirmer';
      const cancelText = options.cancelText || 'Annuler';

      const messageElement = modalElement.querySelector('[data-confirmation-modal-target="message"]');
      const titleElement = modalElement.querySelector('[id$="-title"], h5.font-semibold');
      const confirmButton = modalElement.querySelector('button[data-action*="onConfirmed"]');
      const cancelButton = modalElement.querySelector('button[data-action*="onCancelled"]');

      if (messageElement) messageElement.textContent = message;
      if (titleElement) {
        const icon = titleElement.querySelector('i');
        titleElement.innerHTML = '';
        if (icon) titleElement.appendChild(icon);
        titleElement.appendChild(document.createTextNode(` ${title}`));
      }
      if (confirmButton) confirmButton.textContent = confirmText;
      if (cancelButton) cancelButton.textContent = cancelText;

      return new Promise((resolve) => {
        const resolveId = `confirmResolve_${Date.now()}`;
        let settled = false;

        const cleanup = () => {
          if (confirmButton) confirmButton.removeEventListener('click', onConfirmClick);
          if (cancelButton) cancelButton.removeEventListener('click', onCancelClick);
          if (closeButton) closeButton.removeEventListener('click', onCancelClick);
          modalElement.removeEventListener('app-modal:hidden', onHiddenModal);
          delete modalElement.dataset.confirmPromiseResolve;
          delete window[resolveId];
        };

        const settle = (value) => {
          if (settled) return;
          settled = true;
          cleanup();
          resolve(value);
        };

        const hideModal = () => {
          const modalController = window.Stimulus.getControllerForElementAndIdentifier(
            modalElement,
            'app-modal'
          );
          if (modalController && typeof modalController.hide === 'function') {
            modalController.hide();
            return;
          }
          modalElement.style.display = 'none';
        };

        const onConfirmClick = (event) => {
          event.preventDefault();
          settle(true);
          hideModal();
        };

        const onCancelClick = (event) => {
          event.preventDefault();
          settle(false);
          hideModal();
        };

        const onHiddenModal = () => {
          settle(false);
        };

        const closeButton = modalElement.querySelector('button[aria-label="Fermer le dialogue"]');

        window[resolveId] = settle;
        modalElement.dataset.confirmPromiseResolve = resolveId;

        if (confirmButton) confirmButton.addEventListener('click', onConfirmClick);
        if (cancelButton) cancelButton.addEventListener('click', onCancelClick);
        if (closeButton) closeButton.addEventListener('click', onCancelClick);
        modalElement.addEventListener('app-modal:hidden', onHiddenModal);

        if (typeof window.lucide !== 'undefined') {
          window.lucide.createIcons?.();
        }

        const modalController = window.Stimulus.getControllerForElementAndIdentifier(
          modalElement,
          'app-modal'
        );
        if (modalController && typeof modalController.show === 'function') {
          modalController.show();
        } else {
          settle(window.confirm(message));
        }
      });
    } catch (error) {
      console.error('IshikawaEditor: erreur ouverture modal de confirmation', error);
    }
  }

  if (typeof window.showConfirmationModal === 'function') {
    try {
      return await window.showConfirmationModal(options);
    } catch (error) {
      console.error('IshikawaEditor: showConfirmationModal', error);
    }
  }

  return window.confirm(options.message || fallbackMessage);
}
