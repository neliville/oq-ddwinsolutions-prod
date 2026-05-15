import { Controller } from '@hotwired/stimulus';

/**
 * Affiche les messages flash Symfony via appNotify / Toastify (layout connecté).
 */
export default class extends Controller {
    static values = {
        messages: Array,
    };

    connect() {
        this.showMessages();
    }

    showMessages() {
        if (!Array.isArray(this.messagesValue) || typeof window.appNotify !== 'function') {
            return;
        }

        for (const entry of this.messagesValue) {
            const message = entry?.message;
            const type = entry?.type ?? 'info';
            if (message) {
                window.appNotify(String(message), type);
            }
        }
    }
}
