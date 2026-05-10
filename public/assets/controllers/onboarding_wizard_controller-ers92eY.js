import { Controller } from '@hotwired/stimulus';

/**
 * Wizard d’onboarding (dashboard) : ouverture du Dialog UX, étapes séquentielles, POST sécurisé.
 */
export default class extends Controller {
    static values = {
        mustOpen: Boolean,
        stepUrl: String,
        skipUrl: String,
        csrf: String,
        totalSteps: { type: Number, default: 6 },
        steps: Array,
    };

    connect() {
        this._uxRoot = this.element.parentElement;
        this._dialogEl = this._uxRoot?.querySelector('[data-ux-dialog-target="dialog"]');
        if (this._dialogEl) {
            this._onCancel = (e) => e.preventDefault();
            this._dialogEl.addEventListener('cancel', this._onCancel);
        }

        if (this.mustOpenValue) {
            requestAnimationFrame(() => this._openDialog());
        }

        this._stepIndex = 0;
        this._renderStep();
    }

    disconnect() {
        if (this._dialogEl && this._onCancel) {
            this._dialogEl.removeEventListener('cancel', this._onCancel);
        }
    }

    onSelectChange() {
        this._syncContinueDisabled();
    }

    async advance(event) {
        if (event) event.preventDefault();
        await this._submitCurrentStep();
    }

    async skip(event) {
        if (event) event.preventDefault();
        if (!this.skipUrlValue) {
            return;
        }
        const skipBtn = this.element.querySelector('[data-onboarding-skip]');
        const continueBtn = this.element.querySelector('[data-onboarding-continue]');
        if (skipBtn) skipBtn.disabled = true;
        if (continueBtn) continueBtn.disabled = true;

        const body = new URLSearchParams();
        body.set('_token', this.csrfValue);

        try {
            const res = await fetch(this.skipUrlValue, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body.toString(),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) {
                throw new Error(data.message || 'Action impossible pour le moment.');
            }
            this._closeDialogWithMessage(
                'Assistant fermé. Complétez votre profil quand vous voulez depuis les préférences (Profil et QHSE).',
            );
        } catch (err) {
            document.dispatchEvent(
                new CustomEvent('app:notification', {
                    detail: { message: err.message || 'Erreur', type: 'error' },
                }),
            );
        } finally {
            if (skipBtn) skipBtn.disabled = false;
            if (continueBtn) continueBtn.disabled = false;
            this._syncContinueDisabled();
        }
    }

    _openDialog() {
        try {
            const c = window.Stimulus?.getControllerForElementAndIdentifier?.(this._uxRoot, 'ux-dialog');
            if (c && typeof c.open === 'function') {
                c.open();
            } else {
                document.getElementById('profileOnboardingOpenStub')?.click();
            }
        } catch (e) {
            document.getElementById('profileOnboardingOpenStub')?.click();
        }
    }

    _currentStepDef() {
        const steps = this.stepsValue || [];
        return steps[this._stepIndex] || null;
    }

    _renderStep() {
        const def = this._currentStepDef();
        const titleEl = this.element.querySelector('[data-onboarding-title]');
        const selectEl = this.element.querySelector('[data-onboarding-select]');
        const continueBtn = this.element.querySelector('[data-onboarding-continue]');
        const formPanel = this.element.querySelector('[data-onboarding-form]');
        const stepIndicator = this.element.querySelector('[data-onboarding-step-indicator]');
        const stepFraction = this.element.querySelector('[data-onboarding-step-fraction]');
        const progressFill = this.element.querySelector('[data-onboarding-progress-fill]');
        const total = this.totalStepsValue || 6;

        if (!def) {
            if (formPanel) formPanel.classList.add('hidden');
            if (continueBtn) continueBtn.classList.add('hidden');
            return;
        }

        if (formPanel) formPanel.classList.remove('hidden');
        if (continueBtn) continueBtn.classList.remove('hidden');
        if (titleEl) titleEl.textContent = def.title;
        if (selectEl) {
            selectEl.innerHTML = '';
            const ph = document.createElement('option');
            ph.value = '';
            ph.textContent = 'Sélectionnez une option';
            ph.disabled = true;
            ph.selected = true;
            selectEl.appendChild(ph);
            (def.options || []).forEach((opt) => {
                const o = document.createElement('option');
                o.value = opt.value;
                o.textContent = opt.label;
                selectEl.appendChild(o);
            });
        }
        this._syncContinueDisabled();

        const current = this._stepIndex + 1;
        if (stepIndicator) {
            stepIndicator.textContent = `Étape ${current} sur ${total}`;
        }
        if (stepFraction) {
            stepFraction.textContent = `${current} / ${total}`;
        }
        if (progressFill) {
            progressFill.style.width = `${(current / total) * 100}%`;
        }
    }

    _syncContinueDisabled() {
        const selectEl = this.element.querySelector('[data-onboarding-select]');
        const continueBtn = this.element.querySelector('[data-onboarding-continue]');
        if (!continueBtn || !selectEl) return;
        continueBtn.disabled = !selectEl.value;
    }

    async _submitCurrentStep() {
        const selectEl = this.element.querySelector('[data-onboarding-select]');
        const continueBtn = this.element.querySelector('[data-onboarding-continue]');
        const def = this._currentStepDef();
        if (!def || !selectEl?.value) return;

        const step = def.step;
        const value = selectEl.value;
        continueBtn.disabled = true;

        const body = new URLSearchParams();
        body.set('_token', this.csrfValue);
        body.set('step', String(step));
        body.set('value', value);

        try {
            const res = await fetch(this.stepUrlValue, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body.toString(),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) {
                throw new Error(data.message || 'Enregistrement impossible');
            }
            if (data.completed) {
                this._closeDialogWithMessage('Votre espace QHSE est personnalisé. Bienvenue !');
                return;
            }
            this._stepIndex += 1;
            this._renderStep();
        } catch (err) {
            document.dispatchEvent(
                new CustomEvent('app:notification', {
                    detail: { message: err.message || 'Erreur', type: 'error' },
                }),
            );
        } finally {
            continueBtn.disabled = false;
            this._syncContinueDisabled();
        }
    }

    _closeDialogWithMessage(message) {
        document.dispatchEvent(
            new CustomEvent('app:notification', {
                detail: { message, type: 'success' },
            }),
        );
        try {
            const c = window.Stimulus?.getControllerForElementAndIdentifier?.(this._uxRoot, 'ux-dialog');
            if (c && typeof c.close === 'function') {
                c.close();
            }
        } catch (e) {
            this._dialogEl?.close();
        }
    }
}
