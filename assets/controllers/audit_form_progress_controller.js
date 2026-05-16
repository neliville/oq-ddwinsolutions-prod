import { Controller } from '@hotwired/stimulus';

/**
 * Score de complétude live pour le formulaire « Nouvel audit ».
 * Cible les champs marqués data-audit-form-progress-target="field".
 */
export default class extends Controller {
    static targets = ['field', 'percent', 'bar'];

    connect() {
        this.update = this.update.bind(this);
        this.bindFieldListeners();
        this.update();
    }

    disconnect() {
        this.unbindFieldListeners();
    }

    bindFieldListeners() {
        this.getTrackedFields().forEach((el) => {
            el.addEventListener('input', this.update);
            el.addEventListener('change', this.update);
        });
    }

    unbindFieldListeners() {
        this.getTrackedFields().forEach((el) => {
            el.removeEventListener('input', this.update);
            el.removeEventListener('change', this.update);
        });
    }

    /** @returns {HTMLElement[]} */
    getTrackedFields() {
        const fields = [...this.fieldTargets];
        const dateInput = this.element.querySelector('#auditedAt');
        if (dateInput instanceof HTMLElement && !fields.includes(dateInput)) {
            fields.push(dateInput);
        }

        return fields;
    }

    update() {
        const fields = this.getTrackedFields();
        if (!fields.length) {
            return;
        }

        let filled = 0;
        fields.forEach((el) => {
            const value = (el.value ?? '').trim();
            const wrapper = el.closest('.audit-form-field-group');
            if (value !== '') {
                filled += 1;
                wrapper?.classList.add('is-filled');
            } else {
                wrapper?.classList.remove('is-filled');
            }
        });

        const pct = Math.round((filled / fields.length) * 100);

        if (this.hasPercentTarget) {
            this.percentTarget.textContent = String(pct);
        }

        if (this.hasBarTarget) {
            this.barTarget.style.width = `${pct}%`;
            const progressbar = this.barTarget.closest('[role="progressbar"]');
            if (progressbar) {
                progressbar.setAttribute('aria-valuenow', String(pct));
            }
        }
    }
}
