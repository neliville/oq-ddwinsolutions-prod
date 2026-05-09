import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal'];
    static values = {
        storageKey: { type: String, default: 'post_save_cta_shown' },
    };

    connect() {
        this._onSaved = this._onSaved.bind(this);
        this._onBeforeCache = this._onBeforeCache.bind(this);
        document.addEventListener('app:analysis:saved', this._onSaved);
        document.addEventListener('turbo:before-cache', this._onBeforeCache);
    }

    disconnect() {
        document.removeEventListener('app:analysis:saved', this._onSaved);
        document.removeEventListener('turbo:before-cache', this._onBeforeCache);
    }

    _onBeforeCache() {
        const modal = this.element.querySelector('#postSaveCtaModal');
        if (!modal) return;
        modal.classList.remove('show');
        modal.style.display = '';
        const backdrop = document.querySelector('.tw-backdrop');
        if (backdrop) backdrop.remove();
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    _onSaved(event) {
        if (sessionStorage.getItem(this.storageKeyValue)) return;
        sessionStorage.setItem(this.storageKeyValue, '1');

        const modal = this.element.querySelector('#postSaveCtaModal');
        if (!modal) return;

        const detail = event.detail || {};
        const nameEl = this.element.querySelector('[data-post-save-cta-analysis-name]');
        if (nameEl && detail.title) nameEl.textContent = detail.title;

        document.dispatchEvent(new CustomEvent('modal:open', { detail: { modalId: 'postSaveCtaModal' } }));
    }
}
