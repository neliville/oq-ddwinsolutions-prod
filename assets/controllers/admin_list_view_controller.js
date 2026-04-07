import { Controller } from '@hotwired/stimulus';

const storageKeyFor = (key) => `adminListView:${key}`;

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
            this.tablePanelTarget.classList.toggle('d-none', !isTable);
        }
        if (this.hasCardsPanelTarget) {
            this.cardsPanelTarget.classList.toggle('d-none', isTable);
        }
        if (this.hasTableBtnTarget) {
            this.tableBtnTarget.classList.toggle('btn-primary', isTable);
            this.tableBtnTarget.classList.toggle('btn-outline-secondary', !isTable);
            this.tableBtnTarget.setAttribute('aria-pressed', isTable ? 'true' : 'false');
        }
        if (this.hasCardsBtnTarget) {
            this.cardsBtnTarget.classList.toggle('btn-primary', !isTable);
            this.cardsBtnTarget.classList.toggle('btn-outline-secondary', isTable);
            this.cardsBtnTarget.setAttribute('aria-pressed', (!isTable).toString());
        }
    }
}
