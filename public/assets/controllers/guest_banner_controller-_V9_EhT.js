import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        delay: { type: Number, default: 4000 },
        storageKey: { type: String, default: 'guest_banner_dismissed' },
    };

    connect() {
        if (sessionStorage.getItem(this.storageKeyValue)) {
            this.element.remove();
            return;
        }
        this._timer = setTimeout(() => this._show(), this.delayValue);
    }

    disconnect() {
        clearTimeout(this._timer);
    }

    dismiss() {
        sessionStorage.setItem(this.storageKeyValue, '1');
        this._hide();
    }

    _show() {
        this.element.classList.add('guest-banner--visible');
    }

    _hide() {
        this.element.classList.add('guest-banner--hiding');
        this.element.addEventListener('transitionend', () => this.element.remove(), { once: true });
    }
}
