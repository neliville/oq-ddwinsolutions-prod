import { Controller } from '@hotwired/stimulus';

/**
 * Controller Stimulus pour gérer les confirmations de suppression
 * S'utilise avec le composant @stimulus-components/dialog
 */
export default class extends Controller {
    static values = {
        url: String,
        method: { type: String, default: 'DELETE' },
        redirect: String
    };

    connect() {
        // Si les valeurs sont définies via les attributs data-*, les utiliser
        if (this.hasUrlValue) {
            this.url = this.urlValue;
        }
        if (this.hasMethodValue) {
            this.method = this.methodValue;
        }
        if (this.hasRedirectValue) {
            this.redirect = this.redirectValue;
        }

        // Sinon, chercher depuis les attributs data-delete-confirmation-*-value
        if (!this.url) {
            this.url = this.element.dataset.deleteConfirmationUrlValue;
        }
        if (!this.method) {
            this.method = this.element.dataset.deleteConfirmationMethodValue || 'DELETE';
        }
        if (!this.redirect) {
            this.redirect = this.element.dataset.deleteConfirmationRedirectValue;
        }

        // Si toujours pas trouvé, chercher dans le bouton de suppression
        if (!this.url) {
            const deleteButton = this.element.querySelector('[data-delete-confirmation-url-value]');
            if (deleteButton) {
                this.url = deleteButton.dataset.deleteConfirmationUrlValue;
                this.method = deleteButton.dataset.deleteConfirmationMethodValue || 'DELETE';
                this.redirect = deleteButton.dataset.deleteConfirmationRedirectValue;
            }
        }
    }

    async onConfirmed(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (!this.url && event?.target) {
            this.url = event.target.dataset.deleteConfirmationUrlValue;
            this.method = event.target.dataset.deleteConfirmationMethodValue || 'DELETE';
            this.redirect = event.target.dataset.deleteConfirmationRedirectValue;
        }

        if (!this.url) {
            console.error('URL non fournie pour la suppression');
            return;
        }

        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i data-lucide="loader-2" width="18" height="18" class="me-1"></i>Suppression...';

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Identifier la carte et le modal AVANT la requête, pendant que le DOM est intact
        const modal = this.element.closest('.modal');
        const modalId = modal ? modal.id : null;
        const triggerButton = modalId
            ? document.querySelector(`[data-modal-open="${modalId}"]`)
            : null;
        const cardToRemove = triggerButton
            ? triggerButton.closest('.creation-card, .card, .list-group-item, article')
            : null;

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
                // Fermer le modal immédiatement
                if (modal) {
                    const modalController = this.application.getControllerForElementAndIdentifier(modal, 'app-modal');
                    if (modalController && typeof modalController.hide === 'function') {
                        modalController.hide();
                    }
                }

                this.showNotification(data.message || 'Création supprimée avec succès', 'success');

                if (this.redirect) {
                    window.location.href = this.redirect;
                } else if (cardToRemove) {
                    // Supprimer la carte du DOM avec animation — pas de rechargement
                    cardToRemove.style.transition = 'opacity 0.3s, transform 0.3s';
                    cardToRemove.style.opacity = '0';
                    cardToRemove.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        const section = cardToRemove.closest('.creations-section');
                        cardToRemove.remove();
                        // Supprimer aussi le modal pour éviter tout déclenchement orphelin
                        if (modal) modal.remove();
                        if (section) {
                            this._updateSection(section);
                        }
                        this._updateStatsCounters();
                    }, 300);
                } else {
                    // Fallback : rechargement propre sans cache Turbo
                    if (window.Turbo) {
                        window.Turbo.cache.clear();
                        window.Turbo.visit(window.location.href, { action: 'replace' });
                    } else {
                        location.reload();
                    }
                }
            } else {
                throw new Error(data.message || 'Erreur lors de la suppression');
            }
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            this.showNotification(error.message || 'Erreur lors de la suppression', 'error');
            button.disabled = false;
            button.innerHTML = originalText;
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    }

    // Met à jour le badge de section et masque la section si elle est vide
    _updateSection(section) {
        const remainingCards = section.querySelectorAll('.creation-card');
        if (remainingCards.length === 0) {
            section.remove();
            return;
        }
        const badge = section.querySelector('.creations-section__header .badge');
        if (badge) {
            badge.textContent = remainingCards.length;
        }
    }

    // Met à jour tous les compteurs de statistiques en relisant le DOM
    _updateStatsCounters() {
        const types = ['ishikawa', 'fivewhy', 'qqoqccp', 'amdec', 'pareto', 'eightd'];
        let total = 0;
        for (const type of types) {
            const count = document.querySelectorAll(`.creation-card--${type}`).length;
            total += count;
            const statEl = document.querySelector(`.creations-stat-card--${type} .creations-stat-card__value`);
            if (statEl) statEl.textContent = count;
        }
        const totalEl = document.querySelector('.creations-stat-card--total .creations-stat-card__value');
        if (totalEl) totalEl.textContent = total;
    }

    /**
     * Affiche une notification (Motion / `app:notification`).
     */
    showNotification(message, type = 'info') {
        if (typeof window.appNotify === 'function') {
            window.appNotify(message, type);
            return;
        }

        document.dispatchEvent(
            new CustomEvent('app:notification', {
                bubbles: true,
                detail: { message, type },
            }),
        );
    }
}

