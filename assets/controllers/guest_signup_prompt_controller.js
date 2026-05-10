import { Controller } from '@hotwired/stimulus';

/**
 * Invité : ouvre un dialogue d’incitation à l’inscription une fois par session,
 * au scroll proche du bas de page ou après un délai (premier déclencheur gagne).
 */
export default class extends Controller {
    static values = {
        delayMs: { type: Number, default: 14000 },
        scrollRatio: { type: Number, default: 0.88 },
        storageKey: { type: String, default: 'guest_signup_dialog_dismissed' },
    };

    connect() {
        if (sessionStorage.getItem(this.storageKeyValue)) {
            this.element.remove();
            return;
        }

        this._opened = false;
        this._dialogRoot = this.element.querySelector('[data-controller~="ux-dialog"]');
        this._dialogEl = this._dialogRoot?.querySelector('[data-ux-dialog-target="dialog"]');

        if (!this._dialogRoot || !this._dialogEl) {
            return;
        }

        this._onDialogClose = () => {
            sessionStorage.setItem(this.storageKeyValue, '1');
            this._stopWatching();
        };
        this._dialogEl.addEventListener('close', this._onDialogClose);

        this._boundScroll = this._onScroll.bind(this);
        window.addEventListener('scroll', this._boundScroll, { passive: true });
        this._timer = window.setTimeout(() => this._tryOpen('delay'), this.delayMsValue);
        requestAnimationFrame(() => this._onScroll());
    }

    disconnect() {
        this._stopWatching();
        if (this._dialogEl && this._onDialogClose) {
            this._dialogEl.removeEventListener('close', this._onDialogClose);
        }
    }

    _stopWatching() {
        window.removeEventListener('scroll', this._boundScroll);
        if (this._timer) {
            clearTimeout(this._timer);
            this._timer = null;
        }
    }

    _onScroll() {
        if (this._opened) {
            return;
        }
        const root = document.documentElement;
        const total = root.scrollHeight;
        if (total <= 0) {
            return;
        }
        const visibleBottom = window.scrollY + root.clientHeight;
        const ratio = visibleBottom / total;
        if (ratio >= this.scrollRatioValue) {
            this._tryOpen('scroll');
        }
    }

    _tryOpen() {
        if (this._opened) {
            return;
        }
        this._opened = true;
        this._stopWatching();
        this._openDialog();
    }

    _openDialog() {
        try {
            const c = this.application.getControllerForElementAndIdentifier(this._dialogRoot, 'ux-dialog');
            if (c && typeof c.open === 'function') {
                c.open();
            }
        } catch {
            /* noop */
        }
    }
}
