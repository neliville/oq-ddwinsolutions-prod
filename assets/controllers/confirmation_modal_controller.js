import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour gérer les modals de confirmation
 * Remplace les confirm() natifs par des modals Bootstrap
 */
export default class extends Controller {
    static targets = ['message'];
    static values = {
        confirmCallback: String,
        cancelCallback: String
    };

    connect() {
        // Stocker les callbacks dans l'élément pour y accéder depuis l'extérieur
        if (this.hasConfirmCallbackValue) {
            this.element.dataset.confirmCallback = this.confirmCallbackValue;
        }
        if (this.hasCancelCallbackValue) {
            this.element.dataset.cancelCallback = this.cancelCallbackValue;
        }
    }

    onConfirmed(event) {
        event.preventDefault();
        event.stopPropagation();

        // Récupérer le callback depuis l'attribut data ou la valeur
        const callbackName = this.element.dataset.confirmCallback || this.confirmCallbackValue;
        
        if (callbackName && window[callbackName] && typeof window[callbackName] === 'function') {
            window[callbackName]();
        }

        // Résoudre la Promise si elle existe
        if (this.element.dataset.confirmPromiseResolve) {
            const resolve = window[this.element.dataset.confirmPromiseResolve];
            if (typeof resolve === 'function') {
                resolve(true);
                delete window[this.element.dataset.confirmPromiseResolve];
            }
        }

        // Fermer le modal
        const modal = this.element.closest('.modal');
        if (modal) {
            const modalController = this.application.getControllerForElementAndIdentifier(modal, 'bootstrap-modal');
            if (modalController && typeof modalController.hide === 'function') {
                modalController.hide();
            }
        }
    }

    onCancelled(event) {
        event.preventDefault();
        event.stopPropagation();

        // Résoudre la Promise avec false si elle existe
        if (this.element.dataset.confirmPromiseResolve) {
            const resolve = window[this.element.dataset.confirmPromiseResolve];
            if (typeof resolve === 'function') {
                resolve(false);
                delete window[this.element.dataset.confirmPromiseResolve];
            }
        }

        // Fermer le modal
        const modal = this.element.closest('.modal');
        if (modal) {
            const modalController = this.application.getControllerForElementAndIdentifier(modal, 'bootstrap-modal');
            if (modalController && typeof modalController.hide === 'function') {
                modalController.hide();
            }
        }
    }
}

