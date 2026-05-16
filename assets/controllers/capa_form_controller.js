import { Controller } from '@hotwired/stimulus';

const PRIORITY_LABELS = {
    haute: 'Haute',
    moyenne: 'Moyenne',
    basse: 'Basse',
};

/**
 * Garde-fous UX fiche CAPA (clôture, annulation, sync panneau contextuel).
 */
export default class extends Controller {
    static targets = [
        'form',
        'effectivenessVerification',
        'cancelButton',
        'priority',
        'dueAt',
        'contextPriority',
        'contextDueAt',
    ];

    connect() {
        this.#bindSubmitGuard();
        this.#syncContextFromFields();
        this.priorityTarget?.addEventListener('change', () => this.#syncPriority());
        this.dueAtTarget?.addEventListener('change', () => this.#syncDueAt());
        this.dueAtTarget?.addEventListener('input', () => this.#syncDueAt());
    }

    disconnect() {
        if (this._onSubmit) {
            this.formTarget?.removeEventListener('submit', this._onSubmit);
        }
    }

    #bindSubmitGuard() {
        if (!this.hasFormTarget) {
            return;
        }
        this._onSubmit = (event) => {
            const submitter = event.submitter;
            const action = submitter?.getAttribute('name') === 'workflow_action'
                ? submitter.value
                : '';

            if (action === 'cancel' && !window.confirm('Annuler cette CAPA ? Cette action est réservée aux statuts en cours de traitement.')) {
                event.preventDefault();
                return;
            }

            if (action === 'close' && this.hasEffectivenessVerificationTarget) {
                const value = this.effectivenessVerificationTarget.value.trim();
                if (value === '') {
                    event.preventDefault();
                    this.effectivenessVerificationTarget.setAttribute('aria-invalid', 'true');
                    this.effectivenessVerificationTarget.focus({ preventScroll: false });
                    this.effectivenessVerificationTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        };
        this.formTarget.addEventListener('submit', this._onSubmit);
    }

    #syncContextFromFields() {
        this.#syncPriority();
        this.#syncDueAt();
    }

    #syncPriority() {
        if (!this.hasPriorityTarget || !this.hasContextPriorityTarget) {
            return;
        }
        const raw = this.priorityTarget.value;
        const label = raw ? (PRIORITY_LABELS[raw] ?? raw) : '—';
        this.contextPriorityTarget.textContent = `Priorité : ${label}`;
    }

    #syncDueAt() {
        if (!this.hasDueAtTarget || !this.hasContextDueAtTarget) {
            return;
        }
        const raw = this.dueAtTarget.value;
        if (!raw) {
            this.contextDueAtTarget.textContent = 'Non définie';
            return;
        }
        const parts = raw.split('-');
        if (parts.length === 3) {
            this.contextDueAtTarget.textContent = `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
    }
}
