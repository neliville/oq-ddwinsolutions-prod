/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

/**
 * Affiche / masque des colonnes de tableau (préférence localStorage).
 * Marquer les <th> et <td> avec data-table-col="identifiant".
 */
export default class extends Controller {
    static values = {
        storageKey: { type: String, default: 'dataTableColumns' },
    };

    connect() {
        this._state = this._load();
        this._syncCheckboxesFromState();
        const cols = new Set();
        this.element.querySelectorAll('[data-table-col]').forEach((el) => {
            const c = el.getAttribute('data-table-col');
            if (c) {
                cols.add(c);
            }
        });
        cols.forEach((col) => {
            const visible = this._state[col] !== false;
            this._setColumnVisible(col, visible);
        });
    }

    toggle(event) {
        const input = event.currentTarget;
        const col = input.dataset.tableColToggle;
        if (!col) {
            return;
        }
        this._state[col] = input.checked;
        this._save();
        this._setColumnVisible(col, input.checked);
    }

    _load() {
        try {
            const raw = localStorage.getItem(this.storageKeyValue);
            if (!raw) {
                return {};
            }
            const parsed = JSON.parse(raw);
            return typeof parsed === 'object' && parsed !== null ? parsed : {};
        } catch {
            return {};
        }
    }

    _save() {
        try {
            localStorage.setItem(this.storageKeyValue, JSON.stringify(this._state));
        } catch {
            /* stockage indisponible */
        }
    }

    _syncCheckboxesFromState() {
        this.element.querySelectorAll('input[type="checkbox"][data-table-col-toggle]').forEach((input) => {
            const col = input.dataset.tableColToggle;
            if (!col) {
                return;
            }
            if (this._state[col] === undefined) {
                this._state[col] = true;
            }
            input.checked = Boolean(this._state[col]);
        });
    }

    _setColumnVisible(col, visible) {
        const safe = col.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        this.element.querySelectorAll(`[data-table-col="${safe}"]`).forEach((el) => {
            el.classList.toggle('hidden', !visible);
        });
    }
}
