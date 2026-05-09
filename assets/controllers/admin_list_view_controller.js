import { Controller } from '@hotwired/stimulus';

const storageKeyFor = (key) => `adminListView:${key}`;

const ACTIVE_BTN = 'bg-primary text-primary-foreground';
const INACTIVE_BTN = 'border border-border text-foreground bg-transparent';

export default class extends Controller {
    static values = {
        storageKey: { type: String, default: 'default' },
    };

    static targets = ['tablePanel', 'cardsPanel', 'tableBtn', 'cardsBtn'];

    connect() {
        this._readMode();
        this._apply();
    }

    setTable() {
        this.mode = 'table';
        this._persist();
        this._apply();
    }

    setCards() {
        this.mode = 'cards';
        this._persist();
        this._apply();
    }

    _readMode() {
        const raw = localStorage.getItem(storageKeyFor(this.storageKeyValue));
        this.mode = raw === 'cards' || raw === 'table' ? raw : 'table';
    }

    _persist() {
        localStorage.setItem(storageKeyFor(this.storageKeyValue), this.mode);
    }

    _apply() {
        const isTable = this.mode === 'table';
        if (this.hasTablePanelTarget) {
            this.tablePanelTarget.classList.toggle('hidden', !isTable);
        }
        if (this.hasCardsPanelTarget) {
            this.cardsPanelTarget.classList.toggle('hidden', isTable);
        }
        if (this.hasTableBtnTarget) {
            this._setActiveBtn(this.tableBtnTarget, isTable);
            this.tableBtnTarget.setAttribute('aria-pressed', isTable ? 'true' : 'false');
        }
        if (this.hasCardsBtnTarget) {
            this._setActiveBtn(this.cardsBtnTarget, !isTable);
            this.cardsBtnTarget.setAttribute('aria-pressed', (!isTable).toString());
        }
    }

    _setActiveBtn(btn, active) {
        if (active) {
            btn.classList.add(...ACTIVE_BTN.split(' '));
            btn.classList.remove(...INACTIVE_BTN.split(' '));
        } else {
            btn.classList.remove(...ACTIVE_BTN.split(' '));
            btn.classList.add(...INACTIVE_BTN.split(' '));
        }
    }
}
