import { Controller } from '@hotwired/stimulus';

const ACTION_LABELS = {
    start_audit: 'Préparer un audit',
    create_risk: 'Créer un risque',
    create_capa_draft: 'Ouvrir une CAPA brouillon',
    open_cockpit: 'Explorer le cockpit',
};

/**
 * Wizard d’activation onboarding (dashboard) : étapes context / goal / guided_action / aha / return_reason.
 */
export default class extends Controller {
    static values = {
        mustOpen: Boolean,
        stepUrl: String,
        skipUrl: String,
        csrf: String,
        totalSteps: { type: Number, default: 5 },
        steps: Array,
        contextOptions: Object,
        goalOptions: Object,
        initialStep: String,
        recommendedActionUrls: Object,
    };

    connect() {
        this._uxRoot = this.element.parentElement;
        this._dialogEl = this._uxRoot?.querySelector('[data-ux-dialog-target="dialog"]');
        if (this._dialogEl) {
            this._onCancel = (e) => e.preventDefault();
            this._dialogEl.addEventListener('cancel', this._onCancel);
        }

        this._recommendedAction = null;
        this._stepIndex = this._resolveInitialStepIndex();
        this._populateSelects();
        this._renderStep();

        if (this.mustOpenValue) {
            requestAnimationFrame(() => this._openDialog());
        }
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
        const def = this._currentStepDef();
        if (!def) return;

        if (def.kind === 'aha' || def.kind === 'return_reason') {
            this._stepIndex += 1;
            if (this._stepIndex >= (this.stepsValue || []).length) {
                this._closeDialogWithMessage('Vous pouvez reprendre l’activation depuis le tableau de bord.');
                return;
            }
            this._renderStep();
            return;
        }

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
                'Assistant fermé. Vous pourrez reprendre une première action depuis le tableau de bord.',
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

    _resolveInitialStepIndex() {
        const steps = this.stepsValue || [];
        const initial = this.initialStepValue || 'context';
        const index = steps.findIndex((step) => step.step === initial);
        return index >= 0 ? index : 0;
    }

    _populateSelects() {
        this._fillSelect('[data-onboarding-field="job_function"]', this.contextOptionsValue?.job_function || []);
        this._fillSelect('[data-onboarding-field="company_size"]', this.contextOptionsValue?.company_size || []);
        this._fillSelect('[data-onboarding-field="main_activity"]', this.contextOptionsValue?.main_activity || []);
        this._fillSelect('[data-onboarding-field="piloting_focus"]', this.goalOptionsValue?.piloting_focus || []);
        this._fillSelect('[data-onboarding-field="primary_standard"]', this.goalOptionsValue?.primary_standard || [], true);
    }

    _fillSelect(selector, options, allowEmpty = false) {
        const selectEl = this.element.querySelector(selector);
        if (!selectEl) return;

        selectEl.innerHTML = '';
        if (allowEmpty) {
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'Sans préférence';
            selectEl.appendChild(empty);
        } else {
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Sélectionnez une option';
            placeholder.disabled = true;
            placeholder.selected = true;
            selectEl.appendChild(placeholder);
        }

        options.forEach((opt) => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.label;
            selectEl.appendChild(option);
        });
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
        const descriptionEl = this.element.querySelector('[data-onboarding-step-description]');
        const continueBtn = this.element.querySelector('[data-onboarding-continue]');
        const formPanel = this.element.querySelector('[data-onboarding-form]');
        const stepIndicator = this.element.querySelector('[data-onboarding-step-indicator]');
        const stepFraction = this.element.querySelector('[data-onboarding-step-fraction]');
        const progressFill = this.element.querySelector('[data-onboarding-progress-fill]');
        const total = this.totalStepsValue || 5;

        this.element.querySelectorAll('[data-onboarding-panel]').forEach((panel) => {
            panel.classList.add('hidden');
        });

        if (!def) {
            if (formPanel) formPanel.classList.add('hidden');
            if (continueBtn) continueBtn.classList.add('hidden');
            return;
        }

        if (formPanel) formPanel.classList.remove('hidden');
        if (continueBtn) continueBtn.classList.remove('hidden');
        if (descriptionEl) descriptionEl.textContent = def.description || '';

        const activePanel = this.element.querySelector(`[data-onboarding-panel="${def.kind}"]`);
        if (activePanel) activePanel.classList.remove('hidden');

        if (def.kind === 'guided_action') {
            this._renderGuidedAction();
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

    _renderGuidedAction() {
        const cta = this.element.querySelector('[data-onboarding-guided-action-cta]');
        const copy = this.element.querySelector('[data-onboarding-guided-action-copy]');
        if (!cta) return;

        const action = this._recommendedAction;
        const url = action ? this.recommendedActionUrlsValue?.[action] : null;
        if (!action || !url) {
            cta.hidden = true;
            if (copy) {
                copy.textContent = 'Nous vous proposons une première action alignée sur votre priorité pour nourrir le cockpit.';
            }
            return;
        }

        cta.hidden = false;
        cta.textContent = ACTION_LABELS[action] || 'Lancer ma première action';
        cta.onclick = () => {
            window.location.href = url;
        };
        if (copy) {
            copy.textContent = 'Lancez cette première action pour alimenter votre cockpit. Vous pourrez ensuite revenir au tableau de bord.';
        }
    }

    _syncContinueDisabled() {
        const continueBtn = this.element.querySelector('[data-onboarding-continue]');
        const def = this._currentStepDef();
        if (!continueBtn || !def) return;

        if (def.kind === 'context') {
            continueBtn.disabled = !['job_function', 'company_size', 'main_activity'].every((field) => {
                const select = this.element.querySelector(`[data-onboarding-field="${field}"]`);
                return Boolean(select?.value);
            });
            return;
        }

        if (def.kind === 'goal') {
            const piloting = this.element.querySelector('[data-onboarding-field="piloting_focus"]');
            continueBtn.disabled = !piloting?.value;
            return;
        }

        continueBtn.disabled = false;
    }

    async _submitCurrentStep() {
        const continueBtn = this.element.querySelector('[data-onboarding-continue]');
        const def = this._currentStepDef();
        if (!def || !continueBtn) return;

        const body = new URLSearchParams();
        body.set('_token', this.csrfValue);
        body.set('activation_step', def.step);

        if (def.kind === 'context') {
            body.set('job_function', this._fieldValue('job_function'));
            body.set('company_size', this._fieldValue('company_size'));
            body.set('main_activity', this._fieldValue('main_activity'));
        } else if (def.kind === 'goal') {
            body.set('piloting_focus', this._fieldValue('piloting_focus'));
            const standard = this._fieldValue('primary_standard');
            if (standard) {
                body.set('primary_standard', standard);
            }
        }

        continueBtn.disabled = true;

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

            if (data.recommended_action) {
                this._recommendedAction = data.recommended_action;
            }

            if (data.completed) {
                this._closeDialogWithMessage('Votre première action utile est lancée.');
                return;
            }

            if (data.current_step) {
                const steps = this.stepsValue || [];
                const nextIndex = steps.findIndex((step) => step.step === data.current_step);
                if (nextIndex >= 0) {
                    this._stepIndex = nextIndex;
                } else {
                    this._stepIndex += 1;
                }
            } else {
                this._stepIndex += 1;
            }

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

    _fieldValue(field) {
        return this.element.querySelector(`[data-onboarding-field="${field}"]`)?.value || '';
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
