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
            ? document.querySelector(`[data-bs-target="#${modalId}"]`)
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
                    const modalController = this.application.getControllerForElementAndIdentifier(modal, 'bootstrap-modal');
                    if (modalController && typeof modalController.hide === 'function') {
                        modalController.hide();
                    } else if (window.bootstrap) {
                        const bsModal = window.bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
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
     * Charge Toastify depuis CDN si nécessaire
     */
    async loadToastify() {
        if (window.Toastify) {
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            // CSS
            if (!document.querySelector('link[href*="toastify"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css';
                document.head.appendChild(link);
            }

            // JS
            if (!window.Toastify) {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/toastify-js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            } else {
                resolve();
            }
        });
    }

    /**
     * Affiche une notification cohérente avec celles utilisées pour Ishikawa / exports PDF.
     * Utilise en priorité le contrôleur Stimulus `notifications` s'il est présent,
     * puis Toastify avec les mêmes couleurs que les autres pages.
     */
    showNotification(message, type = 'info') {
        // 1) Si un contrôleur `notifications` est présent sur la page, l'utiliser
        const notificationsElement = document.querySelector('[data-controller*="notifications"]');
        if (notificationsElement && this.application) {
            const notificationsController = this.application.getControllerForElementAndIdentifier(
                notificationsElement,
                'notifications'
            );

            if (notificationsController && typeof notificationsController.show === 'function') {
                notificationsController.show(message, type);
                return;
            }
        }

        // 2) Fallback direct Toastify avec la même palette qu'Ishikawa (création, export PDF)
        // Charger Toastify si nécessaire
        this.loadToastify().then(() => {
            if (typeof window.Toastify !== 'undefined') {
                // Palette exactement identique à celle utilisée dans ishikawa.js
                const colors = {
                    success: '#2ecc71',
                    error: '#e74c3c',
                    warning: '#f39c12',
                    info: '#3498db',
                };

                window.Toastify({
                    text: message,
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    backgroundColor: colors[type] || colors.info,
                    stopOnFocus: true,
                    callback: function () {
                        // Callback optionnel pour nettoyer
                    }
                }).showToast();
                return;
            }

            // 3) Derniers fallback vers les helpers globaux existants
            if (window.showNotification) {
                window.showNotification(message, type);
            } else if (window.showToast) {
                window.showToast(message, type);
            } else {
                // Ne jamais utiliser alert() - utiliser console.error à la place
                console.error('Notification non affichée:', message);
            }
        }).catch(() => {
            // En cas d'erreur de chargement Toastify, utiliser les fallbacks
            if (window.showNotification) {
                window.showNotification(message, type);
            } else if (window.showToast) {
                window.showToast(message, type);
            } else {
                console.error('Notification non affichée:', message);
            }
        });
    }
}

