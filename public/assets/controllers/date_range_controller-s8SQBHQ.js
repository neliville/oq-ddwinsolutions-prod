import { Controller } from '@hotwired/stimulus';
import {
    frenchLocale,
    getRangeDateOptions,
    loadFlatpickr,
    loadFlatpickrStyles,
} from '../js/flatpickr/config.js';

/**
 * Contrôleur Stimulus pour gérer un date range picker avec Flatpickr
 */
export default class extends Controller {
    static targets = ['input'];

    static values = {
        form: String,
        turboFrame: String,
        dateFrom: String,
        dateTo: String,
    };

    connect() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initializeFlatpickr();
            });
        } else {
            this.initializeFlatpickr();
        }
    }

    disconnect() {
        if (this.flatpickr) {
            this.flatpickr.destroy();
            this.flatpickr = null;
        }
    }

    async initializeFlatpickr() {
        try {
            await loadFlatpickrStyles();
            const Flatpickr = await loadFlatpickr();

            const dateFromInput = this.getDateFromInput();
            const dateToInput = this.getDateToInput();
            const defaultDateFrom = this.dateFromValue || dateFromInput?.value || '';
            const defaultDateTo = this.dateToValue || dateToInput?.value || '';

            let defaultDate = [];
            if (defaultDateFrom && defaultDateTo) {
                defaultDate = [defaultDateFrom, defaultDateTo];
            } else if (defaultDateFrom) {
                defaultDate = [defaultDateFrom];
            }

            const options = getRangeDateOptions({
                defaultDate,
                locale: frenchLocale,
                onChange: (selectedDates) => {
                    this.handleDateChange(selectedDates);
                },
                onReady: (_selectedDates, _dateStr, instance) => {
                    instance.calendarContainer?.classList.add('flatpickr-oq');
                },
                onOpen: (_selectedDates, _dateStr, instance) => {
                    instance.calendarContainer?.classList.add('flatpickr-oq');
                },
            });

            this.flatpickr = new Flatpickr(this.inputTarget, options);

            if (defaultDateFrom && defaultDateTo) {
                this.updateInputDisplay(defaultDateFrom, defaultDateTo);
            } else {
                this.inputTarget.placeholder = 'Sélectionner une période';
            }

            setTimeout(() => this.addPresets(), 200);
        } catch (error) {
            console.error('Erreur lors du chargement de Flatpickr:', error);
        }
    }

    updateInputDisplay(dateFrom, dateTo) {
        if (this.inputTarget && dateFrom && dateTo) {
            const from = new Date(dateFrom);
            const to = new Date(dateTo);
            const formatDate = (date) => {
                return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            };
            this.inputTarget.value = `${formatDate(from)} → ${formatDate(to)}`;
        }
    }

    handleDateChange(selectedDates) {
        if (selectedDates.length === 2) {
            const dateFrom = selectedDates[0].toISOString().split('T')[0];
            const dateTo = selectedDates[1].toISOString().split('T')[0];

            this.updateInputDisplay(dateFrom, dateTo);

            const dateFromInput = this.getDateFromInput();
            const dateToInput = this.getDateToInput();

            if (dateFromInput) {
                dateFromInput.value = dateFrom;
            }
            if (dateToInput) {
                dateToInput.value = dateTo;
            }

            setTimeout(() => {
                this.submitForm();
            }, 100);
        } else if (selectedDates.length === 1) {
            const dateFromInput = this.getDateFromInput();
            if (dateFromInput) {
                dateFromInput.value = selectedDates[0].toISOString().split('T')[0];
            }
        }
    }

    getDateFromInput() {
        const form = this.formValue ? document.querySelector(this.formValue) : this.element.closest('form');
        return form ? form.querySelector('[name="date_from"]') : null;
    }

    getDateToInput() {
        const form = this.formValue ? document.querySelector(this.formValue) : this.element.closest('form');
        return form ? form.querySelector('[name="date_to"]') : null;
    }

    submitForm() {
        const turboFrame = this.turboFrameValue
            ? document.querySelector(`turbo-frame#${this.turboFrameValue}`)
            : null;

        const form = turboFrame
            ? turboFrame.querySelector('form#logsFiltersForm')
            : (this.formValue ? document.querySelector(this.formValue) : this.element.closest('form'));

        if (form && turboFrame) {
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);

            const currentUrl = new URL(window.location.href);
            const currentParams = new URLSearchParams(currentUrl.search);

            for (const [key, value] of params.entries()) {
                if (value) {
                    currentParams.set(key, value);
                } else {
                    currentParams.delete(key);
                }
            }

            const url = form.action + '?' + currentParams.toString();

            this.showNotification('Période mise à jour', 'success');

            turboFrame.src = url;
        } else if (form) {
            form.requestSubmit();
        }
    }

    showNotification(message, type = 'info') {
        if (typeof window.appNotify === 'function') {
            window.appNotify(message, type);
            return;
        }

        document.dispatchEvent(
            new CustomEvent('app:notification', {
                bubbles: true,
                detail: { message, type },
            }),
        );
    }

    addPresets() {
        const checkAndAddPresets = () => {
            const flatpickrContainer = document.querySelector('.flatpickr-calendar.open.flatpickr-oq, .flatpickr-calendar.flatpickr-oq');
            const calendar = flatpickrContainer ?? document.querySelector('.flatpickr-calendar.open');
            if (calendar && !calendar.querySelector('.flatpickr-presets')) {
                const presetsContainer = document.createElement('div');
                presetsContainer.className = 'flatpickr-presets';

                const presets = [
                    { label: "Aujourd'hui", days: 0 },
                    { label: '7 derniers jours', days: 7 },
                    { label: '30 derniers jours', days: 30 },
                    { label: '3 derniers mois', days: 90 },
                ];

                presets.forEach((preset) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = preset.label;
                    button.addEventListener('click', (e) => {
                        e.stopPropagation();
                        e.preventDefault();
                        this.setPresetDate(preset.days);
                    });
                    presetsContainer.appendChild(button);
                });

                const firstChild = calendar.firstChild;
                if (firstChild) {
                    calendar.insertBefore(presetsContainer, firstChild);
                } else {
                    calendar.appendChild(presetsContainer);
                }
            } else if (!calendar) {
                setTimeout(checkAndAddPresets, 100);
            }
        };

        setTimeout(checkAndAddPresets, 200);
    }

    setPresetDate(days) {
        const today = new Date();
        const startDate = new Date(today);
        startDate.setDate(today.getDate() - days);

        if (days === 0) {
            this.flatpickr.setDate([today, today], true);
        } else {
            this.flatpickr.setDate([startDate, today], true);
        }
    }
}
