import { Controller } from '@hotwired/stimulus';

const COLORS = {
    success: '#2ecc71',
    error: '#e74c3c',
    warning: '#f39c12',
    info: '#3498db',
};

/**
 * Contrôleur pour gérer les notifications Toastify
 */
export default class extends Controller {
    static values = {
        message: String,
        type: String,
    };

    connect() {
        this.currentToast = null;
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

    displayNotification(event) {
        const element = event?.currentTarget || event?.target || this.element;
        const message = element?.dataset?.notificationsMessageValue || this.messageValue;
        const type = element?.dataset?.notificationsTypeValue || this.typeValue || 'info';

        if (message) {
            this.show(message, type);
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
        const normalizedType = this.normalizeType(type);
        const text = String(message || '').trim();
        if (!text) {
            return;
        }

        this.loadToastify()
            .then(() => {
                if (!window.Toastify) {
                    return;
                }

                if (this.currentToast && typeof this.currentToast.hideToast === 'function') {
                    this.currentToast.hideToast();
                    this.currentToast = null;
                }

                this.currentToast = window.Toastify({
                    text,
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    backgroundColor: COLORS[normalizedType] || COLORS.info,
                    stopOnFocus: true,
                    className: 'toastify-notification',
                    callback: () => {
                        this.currentToast = null;
                    },
                });
                this.currentToast.showToast();
            })
            .catch(() => {
                if (normalizedType === 'error') {
                    console.error(text);
                    return;
                }

                console.log(text);
            });
    }

    normalizeType(type) {
        const value = String(type || 'info');
        if (value === 'danger' || value === 'destructive') {
            return 'error';
        }

        if (value === 'success' || value === 'error' || value === 'warning' || value === 'info') {
            return value;
        }

        return 'info';
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

