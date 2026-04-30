import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal'];
    static values = {
        storageKey: { type: String, default: 'post_save_cta_shown' },
    };

    connect() {
        this._onSaved = this._onSaved.bind(this);
        document.addEventListener('app:analysis:saved', this._onSaved);
    }

    disconnect() {
        document.removeEventListener('app:analysis:saved', this._onSaved);
    }

    _onSaved(event) {
        if (sessionStorage.getItem(this.storageKeyValue)) return;
        sessionStorage.setItem(this.storageKeyValue, '1');

        const modal = this.element.querySelector('#postSaveCtaModal');
        if (!modal || typeof bootstrap === 'undefined') return;

        const detail = event.detail || {};
        const nameEl = this.element.querySelector('[data-post-save-cta-analysis-name]');
        if (nameEl && detail.title) nameEl.textContent = detail.title;

        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
}
