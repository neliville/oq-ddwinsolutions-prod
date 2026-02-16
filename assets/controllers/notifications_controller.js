import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur pour gérer les notifications Toastify
 */
export default class extends Controller {
    static values = {
        message: String,
        type: String,
    };

    connect() {
        this.loadToastify();
        this.boundOnNotification = this.onNotificationEvent.bind(this);
        document.addEventListener('app:notification', this.boundOnNotification);
    }

    disconnect() {
        document.removeEventListener('app:notification', this.boundOnNotification);
    }

    onNotificationEvent(event) {
        const { message, type = 'info' } = event.detail || {};
        if (message) {
            this.show(message, type);
        }
    }
    
    show(event) {
        // Méthode appelée depuis data-action
        if (event && event.type === 'click') {
            event.preventDefault();
            const message = event.currentTarget.dataset.notificationsMessageValue || this.messageValue;
            const type = event.currentTarget.dataset.notificationsTypeValue || this.typeValue || 'info';
            if (message) {
                this.displayNotification(message, type);
            }
        } else if (typeof event === 'string') {
            // Méthode appelée directement avec message
            const message = event;
            const type = arguments[1] || 'info';
            this.displayNotification(message, type);
        }
    }
    
    displayNotification(event) {
        // Récupérer le message depuis l'élément qui a déclenché l'événement
        const element = event?.currentTarget || event?.target || this.element;
        const message = element?.dataset?.notificationsMessageValue || this.messageValue;
        const type = element?.dataset?.notificationsTypeValue || this.typeValue || 'info';
        
        if (message) {
            this.loadToastify().then(() => {
                if (window.Toastify) {
                    // Palette alignée sur les notifications Ishikawa (création, export PDF)
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
                        className: 'toastify-notification',
                    }).showToast();
                }
            });
        }
    }

    async loadToastify() {
        if (window.Toastify) {
            return Promise.resolve();
        }

        // Charger Toastify depuis CDN si pas déjà chargé
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

    show(message, type = 'info') {
        this.loadToastify().then(() => {
            if (window.Toastify) {
                // Palette alignée sur les notifications Ishikawa (création, export PDF)
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
                    className: 'toastify-notification',
                }).showToast();
            }
        });
    }

    success(message) {
        this.show(message, 'success');
    }

    error(message) {
        this.show(message, 'error');
    }

    warning(message) {
        this.show(message, 'warning');
    }

    info(message) {
        this.show(message, 'info');
    }
}

