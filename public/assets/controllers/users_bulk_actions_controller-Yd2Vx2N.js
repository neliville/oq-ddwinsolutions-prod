import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['submit', 'action', 'count'];

    connect() {
        this.refresh();
    }

    refresh() {
        const selectedCount = this.selectedCheckboxes.length;
        const hasAction = this.hasActionTarget && this.actionTarget.value !== '';
        const canSubmit = selectedCount > 0 && hasAction;

        if (this.hasSubmitTarget) {
            this.submitTarget.disabled = !canSubmit;
        }
        if (this.hasCountTarget) {
            this.countTarget.textContent = String(selectedCount);
        }
    }

    get selectedCheckboxes() {
        return this.element.querySelectorAll('[data-checkbox-select-all-target="checkbox"]:checked');
    }
}
