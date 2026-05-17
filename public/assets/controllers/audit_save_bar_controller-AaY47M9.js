/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

/**
 * Barre d’enregistrement cockpit audit : état dirty, raccourci clavier, scroll prev/next,
 * enregistrement + navigation (fetch + redirection avec ancre).
 */
export default class extends Controller {
    static targets = [
        'status',
        'statusDirty',
        'statusIdle',
        'primarySave',
        'barDesktop',
        'barMobile',
        'shortcutModWin',
        'shortcutModMac',
    ];
    static values = {
        formId: { type: String, default: 'audit-chapter-form' },
    };

    connect() {
        this._dirty = false;
        this._onKey = this._onKey.bind(this);
        this._onBeforeUnload = this._onBeforeUnload.bind(this);
        this._onInput = this._onInput.bind(this);
        this._onTurboLoad = this._onTurboLoad.bind(this);
        this._onTurboSubmitEnd = this._onTurboSubmitEnd.bind(this);

        window.addEventListener('keydown', this._onKey, true);
        window.addEventListener('beforeunload', this._onBeforeUnload);
        document.addEventListener('turbo:load', this._onTurboLoad);
        document.addEventListener('turbo:submit-end', this._onTurboSubmitEnd);

        const form = this.form;
        if (form) {
            form.addEventListener('input', this._onInput, true);
            form.addEventListener('change', this._onInput, true);
        }

        this._syncStatus();
        this._scrollToHash();
        this._applyOsShortcutHint();
    }

    disconnect() {
        window.removeEventListener('keydown', this._onKey, true);
        window.removeEventListener('beforeunload', this._onBeforeUnload);
        document.removeEventListener('turbo:load', this._onTurboLoad);
        document.removeEventListener('turbo:submit-end', this._onTurboSubmitEnd);
        const form = this.form;
        if (form) {
            form.removeEventListener('input', this._onInput, true);
            form.removeEventListener('change', this._onInput, true);
        }
    }

    get form() {
        return document.getElementById(this.formIdValue);
    }

    /** Cartes exigence visibles (hors .hidden du filtre cockpit) */
    visibleCards() {
        return Array.from(document.querySelectorAll('.audit-req-card')).filter((el) => !el.classList.contains('hidden'));
    }

    prevRequirement() {
        this._navRelative(-1);
    }

    nextRequirement() {
        this._navRelative(1);
    }

    _navRelative(delta) {
        const cards = this.visibleCards();
        if (cards.length === 0) {
            return;
        }
        const active = document.activeElement?.closest?.('.audit-req-card');
        let idx = active ? cards.indexOf(active) : -1;
        if (idx < 0) {
            idx = delta > 0 ? 0 : cards.length - 1;
        } else {
            idx = Math.min(cards.length - 1, Math.max(0, idx + delta));
        }
        const target = cards[idx];
        if (!target) {
            return;
        }
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        const verdict = target.querySelector(`select[id^="verdict-"]`);
        if (verdict instanceof HTMLElement) {
            verdict.focus({ preventScroll: true });
            return;
        }
        const focusable = target.querySelector('textarea, input, button, [href]');
        if (focusable instanceof HTMLElement) {
            focusable.focus({ preventScroll: true });
        }
    }

    _onInput() {
        this._dirty = true;
        this._syncStatus();
    }

    _onTurboLoad() {
        this._dirty = false;
        this._syncStatus();
        this._scrollToHash();
    }

    _onTurboSubmitEnd(event) {
        const submission = event.detail?.formSubmission;
        const formEl = submission?.formElement;
        if (!formEl || formEl.id !== this.formIdValue) {
            return;
        }
        if (event.detail.success) {
            this._dirty = false;
            this._syncStatus();
        }
    }

    _syncStatus() {
        const dirty = this._dirty;
        this.statusDirtyTargets.forEach((el) => el.classList.toggle('hidden', !dirty));
        this.statusIdleTargets.forEach((el) => el.classList.toggle('hidden', dirty));
        this.element.dispatchEvent(
            new CustomEvent('audit-save-bar:dirty', {
                bubbles: true,
                detail: { dirty },
            }),
        );
    }

    _onBeforeUnload(e) {
        if (this._dirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    }

    _inScope(el) {
        if (!(el instanceof Node)) {
            return false;
        }
        return this.element.contains(el) || !!this.form?.contains(el);
    }

    /**
     * Raccourci d’enregistrement : Ctrl/⌘ + Entrée (UX-safe, pattern SaaS standard
     * Slack/GitHub/Linear/Notion). N’interfère ni avec le navigateur ni avec la
     * frappe libre dans les <textarea> (Enter seul reste un saut de ligne).
     */
    _onKey(e) {
        const isEnter = e.key === 'Enter' || e.code === 'Enter' || e.code === 'NumpadEnter';
        if (!isEnter || !(e.ctrlKey || e.metaKey) || e.shiftKey || e.altKey) {
            return;
        }
        if (!this._inScope(document.activeElement)) {
            return;
        }
        const form = this.form;
        if (!form) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        const saveBtn =
            this.primarySaveTargets.find((b) => b.offsetParent !== null) ?? this.primarySaveTargets[0];
        if (saveBtn) {
            saveBtn.click();
        } else {
            form.requestSubmit();
        }
    }

    /**
     * POST en fetch puis redirection (permet d’ajouter #ancre vers l’exigence suivante).
     */
    async saveAndContinue() {
        const form = this.form;
        if (!form) {
            return;
        }
        const cards = this.visibleCards();
        const active = document.activeElement?.closest?.('.audit-req-card');
        let nextHash = '';
        if (active) {
            const idx = cards.indexOf(active);
            const next = cards[idx + 1];
            if (next?.id) {
                nextHash = `#${next.id}`;
            }
        } else if (cards[0]?.id) {
            nextHash = `#${cards[0].id}`;
        }

        const fd = new FormData(form);
        const action = form.getAttribute('action') || window.location.pathname;
        try {
            const res = await fetch(action, {
                method: 'POST',
                body: fd,
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                redirect: 'manual',
            });
            if (res.status === 0 || res.type === 'opaqueredirect') {
                window.location.assign(`${window.location.pathname}${window.location.search}${nextHash}`);
                return;
            }
            const loc = res.headers.get('Location');
            if (loc && (res.status === 302 || res.status === 303 || res.status === 301)) {
                const url = new URL(loc, window.location.origin);
                const hash = url.hash || nextHash || '';
                window.location.assign(`${url.pathname}${url.search}${hash}`);
                return;
            }
            if (res.ok) {
                window.location.assign(`${window.location.pathname}${window.location.search}${nextHash}`);
                return;
            }
        } catch (_) {
            /* fallback navigate */
        }
        form.requestSubmit();
    }

    /**
     * Bascule l’affichage des keycaps Ctrl ↔ ⌘ selon la plateforme. Sans JS,
     * on reste sur Ctrl par défaut (fallback sûr).
     */
    _applyOsShortcutHint() {
        const ua = (navigator.userAgent || navigator.platform || '').toLowerCase();
        const isMac = /(mac|iphone|ipad|ipod)/.test(ua);
        if (!isMac) {
            return;
        }
        this.shortcutModWinTargets.forEach((el) => el.classList.add('hidden'));
        this.shortcutModMacTargets.forEach((el) => el.classList.remove('hidden'));
    }

    _scrollToHash() {
        const { hash } = window.location;
        if (!hash || hash.length < 2) {
            return;
        }
        const el = document.querySelector(hash);
        if (el instanceof HTMLElement) {
            el.scrollIntoView({ block: 'start' });
        }
    }
}
