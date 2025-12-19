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
        console.log('onConfirmed appelé', event);
        
        // Empêcher la propagation de l'événement pour éviter que le modal se ferme trop tôt
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Récupérer l'URL depuis le bouton cliqué si disponible
        if (!this.url && event?.target) {
            this.url = event.target.dataset.deleteConfirmationUrlValue;
            this.method = event.target.dataset.deleteConfirmationMethodValue || 'DELETE';
            this.redirect = event.target.dataset.deleteConfirmationRedirectValue;
        }

        if (!this.url) {
            console.error('URL non fournie pour la suppression');
            return;
        }

        console.log('URL de suppression:', this.url, 'Méthode:', this.method);

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

            console.log('Réponse de suppression:', { ok: response.ok, status: response.status, data });

            if (response.ok && data.success) {
                // Fermer le modal Bootstrap
                const modal = this.element.closest('.modal');
                if (modal) {
                    const modalController = this.application.getControllerForElementAndIdentifier(modal, 'bootstrap-modal');
                    if (modalController && typeof modalController.hide === 'function') {
                        modalController.hide();
                    }
                }

                // Vérifier si on est sur la page mes-créations - plusieurs méthodes pour être sûr
                const pathname = window.location.pathname;
                const hasCreationsPageClass = document.querySelector('.creations-page') !== null || 
                                             document.querySelector('main.creations-page') !== null;
                const isCreationsPage = pathname.includes('/mes-creations') || 
                                       pathname.includes('mes-creations') ||
                                       hasCreationsPageClass;

                console.log('Suppression réussie. Détails:', {
                    isCreationsPage,
                    pathname,
                    hasCreationsPageClass,
                    redirect: this.redirect
                });

                // Supprimer l'élément du DOM ou rediriger
                if (this.redirect) {
                    console.log('Redirection vers:', this.redirect);
                    window.location.href = this.redirect;
                } else if (isCreationsPage) {
                    // Sur la page mes-créations, toujours recharger pour mettre à jour les statistiques
                    console.log('Rechargement de la page mes-créations...');

                    // Afficher une notification cohérente avec Ishikawa / exports PDF
                    this.showNotification(data.message || 'Création supprimée avec succès', 'success');

                    // Attendre un peu pour que le toast soit visible puis recharger
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    // Sur les autres pages, supprimer l'élément du DOM
                    const triggerButton = document.querySelector(`[data-dialog-url-value="${this.url}"]`);
                    if (triggerButton) {
                        // Chercher l'élément à supprimer en remontant dans le DOM
                        const elementToRemove = triggerButton.closest('.creation-card, .card, .list-group-item, .col-md-6, [data-delete-target], .mb-1, article');
                        if (elementToRemove) {
                            elementToRemove.style.transition = 'opacity 0.3s';
                            elementToRemove.style.opacity = '0';
                            setTimeout(() => {
                                elementToRemove.remove();
                                // Vérifier s'il reste des éléments dans la section
                                const section = elementToRemove.closest('.creations-section');
                                if (section) {
                                    const remaining = section.querySelectorAll('.creation-card');
                                    if (remaining.length === 0) {
                                        // Plus de cartes dans cette section, recharger la page pour mettre à jour les statistiques
                                        location.reload();
                                    }
                                } else {
                                    // Vérifier s'il reste des éléments (pour compatibilité avec d'autres pages)
                                    const remaining = document.querySelectorAll('.col-md-6 .card, .list-group-item, .creation-card');
                                    if (remaining.length === 0) {
                                        location.reload();
                                    }
                                }
                            }, 300);
                        } else {
                            // Si on ne trouve pas l'élément, recharger la page
                            location.reload();
                        }
                    } else {
                        location.reload();
                    }

                    // Afficher un message de succès (même style que les autres notifications Toastify)
                    this.showNotification(data.message || 'Élément supprimé avec succès', 'success');
                }
            } else {
                throw new Error(data.message || 'Erreur lors de la suppression');
            }
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            this.showNotification(error.message || 'Erreur lors de la suppression', 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
            
            // Réinitialiser les icônes Lucide
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
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

