import { Controller } from '@hotwired/stimulus';
import {
    destroyDateFieldInput,
    initDateFieldInput,
    scanAndInitDateFields,
} from '../js/flatpickr/config.js';

/**
 * Champ date unique — Flatpickr altInput (affichage d/m/Y, valeur Y-m-d sur input natif).
 */
export default class extends Controller {
    static targets = ['input'];

    static values = {
        min: String,
        max: String,
        compact: { type: Boolean, default: false },
        allowInput: { type: Boolean, default: false },
    };

    connect() {
        this.initPromise = this.#init();
    }

    async disconnect() {
        if (this.initPromise) {
            await this.initPromise.catch(() => {});
        }
        this.#destroy();
    }

    async #init() {
        if (!this.hasInputTarget) {
            return;
        }

        const input = this.inputTarget;

        if (this.compactValue) {
            this.element.classList.add('oq-date-field--compact');
        }

        this.flatpickr = await initDateFieldInput(input, {
            minDate: this.hasMinValue && this.minValue !== '' ? this.minValue : undefined,
            maxDate: this.hasMaxValue && this.maxValue !== '' ? this.maxValue : undefined,
            allowInput: this.allowInputValue,
        });
    }

    #destroy() {
        if (this.hasInputTarget) {
            destroyDateFieldInput(this.inputTarget);
        }
        this.flatpickr = null;
    }
}

/**
 * Ré-attache les contrôleurs date-field dans un sous-arbre (outils 8D dynamiques).
 *
 * @param {ParentNode} root
 */
export function scanDateFieldsIn(root = document) {
    return scanAndInitDateFields(root);
}

if (typeof window !== 'undefined') {
    window.oqInitDateFields = scanDateFieldsIn;
}
