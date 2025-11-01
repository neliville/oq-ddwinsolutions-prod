import { Controller } from '@hotwired/stimulus';

/**
 * Controller Stimulus pour gérer les confirmations de suppression
 * S'utilise avec le composant @stimulus-components/dialog
 */
export default class extends Controller {
    connect() {
        // Trouver le bouton déclencheur dans l'élément parent
        const triggerButton = this.element.querySelector('[data-dialog-url-value]');
        if (triggerButton) {
            this.url = triggerButton.dataset.dialogUrlValue;
            this.method = triggerButton.dataset.dialogMethodValue || 'DELETE';
            this.redirect = triggerButton.dataset.dialogRedirectValue;
            
            // Mettre à jour le message du modal si fourni
            const message = triggerButton.dataset.dialogMessageValue;
            if (message) {
                const messageTarget = this.element.querySelector('[data-dialog-target="message"]');
                if (messageTarget) {
                    messageTarget.textContent = message;
                }
            }
        }
    }

    async onConfirmed(event) {
        if (!this.url) {
            // Chercher l'URL depuis le bouton déclencheur
            const triggerButton = this.element.closest('[data-controller*="dialog"]')?.querySelector('[data-dialog-url-value]');
            if (triggerButton) {
                this.url = triggerButton.dataset.dialogUrlValue;
                this.method = triggerButton.dataset.dialogMethodValue || 'DELETE';
                this.redirect = triggerButton.dataset.dialogRedirectValue;
            } else {
                console.error('URL non fournie pour la suppression');
                return;
            }
        }

        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i data-lucide="loader-2" width="18" height="18" class="me-1"></i>Suppression...';

        // Réinitialiser les icônes Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        try {
            const response = await fetch(this.url, {
                method: this.method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Fermer le modal
                const dialog = this.element.querySelector('[data-dialog-target="dialog"]');
                if (dialog) {
                    dialog.close();
                }

                // Supprimer l'élément du DOM ou rediriger
                if (this.redirect) {
                    window.location.href = this.redirect;
                } else {
                    // Chercher l'élément parent à supprimer
                    const triggerButton = document.querySelector(`[data-dialog-url-value="${this.url}"]`);
                    if (triggerButton) {
                        const elementToRemove = triggerButton.closest('.card, .list-group-item, .col-md-6, [data-delete-target], .mb-1');
                        if (elementToRemove) {
                            elementToRemove.style.transition = 'opacity 0.3s';
                            elementToRemove.style.opacity = '0';
                            setTimeout(() => {
                                elementToRemove.remove();
                                // Vérifier s'il reste des éléments
                                const remaining = document.querySelectorAll('.col-md-6 .card, .list-group-item');
                                if (remaining.length === 0) {
                                    location.reload();
                                }
                            }, 300);
                        } else {
                            location.reload();
                        }
                    } else {
                        location.reload();
                    }
                }

                // Afficher un message de succès
                if (window.showNotification) {
                    window.showNotification(data.message || 'Élément supprimé avec succès', 'success');
                }
            } else {
                throw new Error(data.message || 'Erreur lors de la suppression');
            }
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            if (window.showNotification) {
                window.showNotification(error.message || 'Erreur lors de la suppression', 'error');
            } else {
                alert(error.message || 'Erreur lors de la suppression');
            }
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
            
            // Réinitialiser les icônes Lucide
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    }
}

