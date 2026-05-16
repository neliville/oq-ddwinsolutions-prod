import { Controller } from '@hotwired/stimulus';

const RULES = [
    { key: 'length', test: (value) => value.length >= 8 },
    { key: 'lower', test: (value) => /[a-z]/.test(value) },
    { key: 'upper', test: (value) => /[A-Z]/.test(value) },
    { key: 'digit', test: (value) => /\d/.test(value) },
];

const STRENGTH_LABELS = ['', 'Faible', 'Correct', 'Bon', 'Excellent'];

/**
 * Feedback visuel de force du mot de passe (aligné sur la regex serveur).
 */
export default class extends Controller {
    static targets = ['input', 'bar', 'label', 'rule'];

    connect() {
        this.onInput = this.evaluate.bind(this);
        this.inputTarget.addEventListener('input', this.onInput);
        this.evaluate();
    }

    disconnect() {
        this.inputTarget.removeEventListener('input', this.onInput);
    }

    evaluate() {
        const value = this.inputTarget.value;
        let score = 0;

        RULES.forEach((rule) => {
            const valid = rule.test(value);
            if (valid) {
                score += 1;
            }

            const ruleEl = this.ruleTargets.find((el) => el.dataset.rule === rule.key);
            if (ruleEl) {
                ruleEl.classList.toggle('auth-password-rule--valid', valid);
                ruleEl.setAttribute('aria-checked', valid ? 'true' : 'false');
            }
        });

        if (this.hasBarTarget) {
            const percent = value.length === 0 ? 0 : (score / RULES.length) * 100;
            this.barTarget.style.width = `${percent}%`;

            let level = 'weak';
            if (score === 4 && value.length >= 12) {
                level = 'excellent';
            } else if (score >= 3) {
                level = 'good';
            } else if (score >= 2) {
                level = 'fair';
            }
            this.barTarget.dataset.level = level;
        }

        if (this.hasLabelTarget) {
            this.labelTarget.textContent =
                value.length === 0 ? '' : STRENGTH_LABELS[score] ?? STRENGTH_LABELS[1];
        }
    }
}
