import { Controller } from '@hotwired/stimulus';
import { animate } from 'motion';
import { prefersReducedMotion } from '../../motion/reduced-motion.js';
import { DURATIONS, SPRING_SNAPPY } from '../../motion/tokens.js';

const TYPE_STYLES = {
    success: 'border-emerald-500/40 bg-emerald-950/90 text-emerald-50',
    error: 'border-red-500/40 bg-red-950/90 text-red-50',
    warning: 'border-amber-500/40 bg-amber-950/90 text-amber-50',
    info: 'border-sky-500/40 bg-slate-900/95 text-slate-50',
};

function normalizeType(type) {
    const v = String(type || 'info');
    if (v === 'danger' || v === 'destructive') {
        return 'error';
    }
    if (v === 'success' || v === 'error' || v === 'warning' || v === 'info') {
        return v;
    }
    return 'info';
}

/**
 * Toasts Motion One (remplace Toastify) — écoute `app:notification` et `window.appNotify`.
 */
export default class extends Controller {
    connect() {
        this._items = [];
        this._onNotify = (e) => {
            const { message, type = 'info' } = e.detail || {};
            if (message) {
                this._show(String(message), normalizeType(type));
            }
        };
        document.addEventListener('app:notification', this._onNotify);
    }

    disconnect() {
        document.removeEventListener('app:notification', this._onNotify);
        this._items.forEach((n) => n.remove());
        this._items = [];
    }

    _show(message, type) {
        const el = document.createElement('div');
        el.setAttribute('role', 'status');
        el.className = `pointer-events-auto max-w-sm rounded-lg border px-4 py-3 text-sm shadow-lg backdrop-blur-sm ${TYPE_STYLES[type] || TYPE_STYLES.info}`;
        el.textContent = message;
        el.style.opacity = '0';
        el.style.transform = 'translateY(-8px) scale(0.98)';
        this.element.appendChild(el);
        this._items.push(el);

        const dismiss = () => {
            animate(
                el,
                { opacity: 0, transform: 'translateY(-6px) scale(0.96)' },
                { duration: prefersReducedMotion() ? 0 : DURATIONS.fast },
            ).finished.then(() => {
                el.remove();
                const i = this._items.indexOf(el);
                if (i >= 0) {
                    this._items.splice(i, 1);
                }
            });
        };

        animate(
            el,
            { opacity: 1, transform: 'translateY(0px) scale(1)' },
            prefersReducedMotion()
                ? { duration: 0 }
                : { type: 'spring', stiffness: SPRING_SNAPPY.stiffness, damping: SPRING_SNAPPY.damping },
        );

        window.setTimeout(dismiss, 3800);
    }
}
