import { Controller } from '@hotwired/stimulus';
import { getGsapRuntime } from '../motion/gsap.js';

export default class extends Controller {
    static targets = ['modal'];
    static values = {
        storageKey: { type: String, default: 'post_save_cta_shown' },
    };

    connect() {
        this._onSaved = this._onSaved.bind(this);
        this._onBeforeCache = this._onBeforeCache.bind(this);
        this._onModalOpen = this._onModalOpen.bind(this);
        document.addEventListener('app:analysis:saved', this._onSaved);
        document.addEventListener('turbo:before-cache', this._onBeforeCache);
        document.addEventListener('modal:open', this._onModalOpen);
    }

    disconnect() {
        document.removeEventListener('app:analysis:saved', this._onSaved);
        document.removeEventListener('turbo:before-cache', this._onBeforeCache);
        document.removeEventListener('modal:open', this._onModalOpen);
    }

    async _onModalOpen(event) {
        if (event.detail?.modalId !== 'postSaveCtaModal') {
            return;
        }
        const modal = document.getElementById('postSaveCtaModal');
        if (!modal) {
            return;
        }
        const { gsap } = getGsapRuntime();
        gsap.fromTo(modal, { autoAlpha: 0.92, scale: 0.98 }, { autoAlpha: 1, scale: 1, duration: 0.28, ease: 'power2.out' });
    }

    _onBeforeCache() {
        const modal = this.element.querySelector('#postSaveCtaModal');
        if (!modal) return;
        /* Ne pas utiliser style.display = '' : cela retire le display:none inline du Twig et le div
         * repasse en display:block par défaut → flash visible au turbo:before-cache (ex. en quittant /amdec). */
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';
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
