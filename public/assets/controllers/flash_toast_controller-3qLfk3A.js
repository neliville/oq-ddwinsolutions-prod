import { Controller } from '@hotwired/stimulus';

/**
 * Affiche les messages flash Symfony via appNotify / Toastify (layout connecté).
 * L’affichage est différé : flash-toast est souvent placé avant le contrôleur
 * `notifications` dans le DOM ; sans délai, l’événement app:notification part avant l’écouteur.
 */
export default class extends Controller {
    static values = {
        messages: Array,
    };

    connect() {
        this.scheduleShow();
    }

    scheduleShow() {
        if (this._showTimer) {
            clearTimeout(this._showTimer);
        }

        // Macrotâche : laisse Stimulus connecter tous les contrôleurs (dont notifications).
        this._showTimer = window.setTimeout(() => this.showMessages(), 0);
    }

    disconnect() {
        if (this._showTimer) {
            clearTimeout(this._showTimer);
            this._showTimer = null;
        }
    }

    showMessages(attempt = 0) {
        if (typeof window.appNotify !== 'function') {
            if (attempt < 20) {
                window.setTimeout(() => this.showMessages(attempt + 1), 50);
            }
            return;
        }

        if (!Array.isArray(this.messagesValue)) {
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
