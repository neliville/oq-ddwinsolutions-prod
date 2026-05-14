import { Controller } from '@hotwired/stimulus';

const GUIDED_ACTION_FALLBACK = 'start_audit';

/**
 * Wizard d’activation onboarding (dashboard) : contexte, objectif, action guidée.
 */
export default class extends Controller {
    static values = {
        mustOpen: Boolean,
        stepUrl: String,
        skipUrl: String,
        csrf: String,
        capaNewDraftCsrf: String,
        totalSteps: { type: Number, default: 3 },
        steps: Array,
        contextOptions: Object,
        goalOptions: Object,
        initialStep: String,
        recommendedAction: String,
        recommendedActionUrls: Object,
    };

    connect() {
        this._uxRoot = this.element.closest('dialog')?.parentElement ?? this.element.parentElement;
        this._dialogEl = this._uxRoot?.querySelector('[data-ux-dialog-target="dialog"]');
        if (this._dialogEl) {
            this._onCancel = (e) => e.preventDefault();
            this._dialogEl.addEventListener('cancel', this._onCancel);
        }

        this._recommendedAction = this.recommendedActionValue || null;
        this._selectedGuidedAction = null;
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

    onGuidedActionChange(event) {
        const input = event?.currentTarget;
        if (!(input instanceof HTMLInputElement) || !input.checked) {
            return;
        }

        this._selectedGuidedAction = input.value;
        this._updateGuidedActionUi();
    }

    async advance(event) {
        if (event) {
            event.preventDefault();
        }

        const def = this._currentStepDef();
        if (!def) {
            return;
        }

        if (def.kind === 'guided_action') {
            const input = this._selectedGuidedActionInput();
            if (!input) {
                return;
            }

            const url = input.dataset.onboardingActionUrl;
            if (!url) {
                return;
            }

            if (input.dataset.onboardingActionMethod === 'post') {
                await this._submitCapaDraft(url);

                return;
            }

            window.location.href = url;

            return;
        }

        await this._submitCurrentStep();
    }

    async skip(event) {
        if (event) {
            event.preventDefault();
        }

        if (!this.skipUrlValue) {
            return;
        }

        const skipBtn = this.element.querySelector('[data-onboarding-skip]');
        const continueBtn = this.element.querySelector('[data-onboarding-continue]');
        if (skipBtn) {
            skipBtn.disabled = true;
        }
        if (continueBtn) {
            continueBtn.disabled = true;
        }

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
            if (skipBtn) {
                skipBtn.disabled = false;
            }
            if (continueBtn) {
                continueBtn.disabled = false;
            }
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
        const context = this.contextOptionsValue ?? {};
        const goal = this.goalOptionsValue ?? {};

        this._fillSelect('[data-onboarding-field="job_function"]', context.job_function || []);
        this._fillSelect('[data-onboarding-field="company_size"]', context.company_size || []);
        this._fillSelect('[data-onboarding-field="main_activity"]', context.main_activity || []);
        this._fillSelect('[data-onboarding-field="piloting_focus"]', goal.piloting_focus || []);
        this._fillSelect('[data-onboarding-field="primary_standard"]', goal.primary_standard || [], true);
    }

    _fillSelect(selector, options, allowEmpty = false) {
        const selectEl = this.element.querySelector(selector);
        if (!selectEl) {
            return;
        }

        if (!Array.isArray(options) || options.length === 0) {
            return;
        }

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
        const titleEl = this.element.querySelector('[data-onboarding-dialog-title]');
        const continueBtn = this.element.querySelector('[data-onboarding-continue]');
        const formPanel = this.element.querySelector('[data-onboarding-form]');
        const stepIndicator = this.element.querySelector('[data-onboarding-step-indicator]');
        const progressFill = this.element.querySelector('[data-onboarding-progress-fill]');
        const total = this.totalStepsValue || 3;

        this.element.querySelectorAll('[data-onboarding-panel]').forEach((panel) => {
            panel.classList.add('hidden');
        });

        if (!def) {
            if (formPanel) {
                formPanel.classList.add('hidden');
            }
            if (continueBtn) {
                continueBtn.classList.add('hidden');
            }

            return;
        }

        if (formPanel) {
            formPanel.classList.remove('hidden');
        }
        if (continueBtn) {
            continueBtn.classList.remove('hidden');
        }
        if (descriptionEl) {
            descriptionEl.textContent = def.description || '';
        }
        if (titleEl && def.title) {
            titleEl.textContent = def.title;
        }

        const activePanel = this.element.querySelector(`[data-onboarding-panel="${def.kind}"]`);
        if (activePanel) {
            activePanel.classList.remove('hidden');
        }

        if (def.kind === 'guided_action') {
            this._renderGuidedAction();
        } else {
            this._setContinueLabel('Continuer');
        }

        this._syncContinueDisabled();

        const current = this._stepIndex + 1;
        if (stepIndicator) {
            stepIndicator.textContent = `Étape ${current} sur ${total}`;
        }
        if (progressFill) {
            progressFill.style.width = `${(current / total) * 100}%`;
        }
    }

    _renderGuidedAction() {
        const suggested = this._mapRecommendedAction(this._recommendedAction);
        const inputs = this.element.querySelectorAll('[data-onboarding-action-choice]');
        let selected = this._selectedGuidedActionInput();

        inputs.forEach((input) => {
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const shouldSelect = !selected && input.value === suggested;
            input.checked = shouldSelect;
            if (shouldSelect) {
                this._selectedGuidedAction = input.value;
                selected = input;
            }
        });

        if (!selected && inputs.length > 0) {
            const fallback = this.element.querySelector(`[data-onboarding-action-choice][value="${GUIDED_ACTION_FALLBACK}"]`);
            if (fallback instanceof HTMLInputElement) {
                fallback.checked = true;
                this._selectedGuidedAction = fallback.value;
                selected = fallback;
            }
        }

        this._updateGuidedActionUi();
    }

    _mapRecommendedAction(action) {
        if (action === 'create_capa_draft') {
            return 'create_capa_draft';
        }

        if (action === 'create_risk' || action === 'open_cockpit') {
            return 'open_ishikawa';
        }

        return GUIDED_ACTION_FALLBACK;
    }

    _selectedGuidedActionInput() {
        const selected = this.element.querySelector('[data-onboarding-action-choice]:checked');

        return selected instanceof HTMLInputElement ? selected : null;
    }

    _updateGuidedActionUi() {
        const input = this._selectedGuidedActionInput();
        const label = input?.dataset.onboardingActionLabel || 'Lancer ma première action';
        this._setContinueLabel(label);
        this._syncContinueDisabled();
        this._syncGuidedActionIndicators();
    }

    _syncGuidedActionIndicators() {
        this.element.querySelectorAll('[data-onboarding-action-choice]').forEach((choice) => {
            if (!(choice instanceof HTMLInputElement)) {
                return;
            }

            const indicator = choice.closest('label')?.querySelector('[data-onboarding-choice-indicator]');
            if (!indicator) {
                return;
            }

            indicator.classList.toggle('border-primary', choice.checked);
            indicator.classList.toggle('bg-primary', choice.checked);
            indicator.classList.toggle('border-border', !choice.checked);
            indicator.classList.toggle('bg-background', !choice.checked);
        });
    }

    _submitCapaDraft(url) {
        const token = this.capaNewDraftCsrfValue;
        if (!url || !token) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.className = 'hidden';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = token;
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
    }

    _setContinueLabel(label) {
        const labelEl = this.element.querySelector('[data-onboarding-continue-label]');
        if (labelEl) {
            labelEl.textContent = label;
        }
    }

    _syncContinueDisabled() {
        const continueBtn = this.element.querySelector('[data-onboarding-continue]');
        const def = this._currentStepDef();
        if (!continueBtn || !def) {
            return;
        }

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

        if (def.kind === 'guided_action') {
            const input = this._selectedGuidedActionInput();
            continueBtn.disabled = !input || !input.dataset.onboardingActionUrl;

            return;
        }

        continueBtn.disabled = false;
    }

    async _submitCurrentStep() {
        const continueBtn = this.element.querySelector('[data-onboarding-continue]');
        const def = this._currentStepDef();
        if (!def || !continueBtn) {
            return;
        }

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
