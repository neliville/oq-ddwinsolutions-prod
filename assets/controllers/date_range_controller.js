import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour gérer un date range picker avec Flatpickr
 * 
 * Usage:
 * <div data-controller="date-range" 
 *      data-date-range-form-value="#logsFilters form"
 *      data-date-range-turbo-frame-value="navigation-logs-frame">
 *   <input type="text" data-date-range-target="input" readonly>
 * </div>
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
        // Attendre que le DOM soit prêt
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
            const flatpickrModule = await import('flatpickr');
            const Flatpickr = flatpickrModule.default;
            
            // Importer le CSS
            await import('flatpickr/dist/flatpickr.min.css');
            
            // Valeurs par défaut depuis les inputs cachés ou les valeurs
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

            // Configuration Flatpickr
            const options = {
                mode: 'range',
                dateFormat: 'Y-m-d',
                defaultDate: defaultDate,
                allowInput: false,
                clickOpens: true,
                locale: {
                    firstDayOfWeek: 1,
                    weekdays: {
                        shorthand: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
                        longhand: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
                    },
                    months: {
                        shorthand: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                        longhand: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
                    },
                },
                onChange: (selectedDates, dateStr, instance) => {
                    this.handleDateChange(selectedDates, dateStr);
                },
            };

            this.flatpickr = new Flatpickr(this.inputTarget, options);

            // Mettre à jour l'affichage initial si des dates sont déjà sélectionnées
            if (defaultDateFrom && defaultDateTo) {
                this.updateInputDisplay(defaultDateFrom, defaultDateTo);
            } else {
                // Si aucune date n'est sélectionnée, afficher un placeholder
                this.inputTarget.placeholder = 'Sélectionner une période';
            }

            // Ajouter des presets de dates rapides après un court délai
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

    handleDateChange(selectedDates, dateStr) {
        if (selectedDates.length === 2) {
            const dateFrom = selectedDates[0].toISOString().split('T')[0];
            const dateTo = selectedDates[1].toISOString().split('T')[0];
            
            // Mettre à jour l'affichage
            this.updateInputDisplay(dateFrom, dateTo);
            
            // Mettre à jour les inputs cachés
            const dateFromInput = this.getDateFromInput();
            const dateToInput = this.getDateToInput();
            
            if (dateFromInput) {
                dateFromInput.value = dateFrom;
            }
            if (dateToInput) {
                dateToInput.value = dateTo;
            }

            // Soumettre automatiquement le formulaire via Turbo Frame après un court délai
            setTimeout(() => {
                this.submitForm();
            }, 100);
        } else if (selectedDates.length === 1) {
            // Une seule date sélectionnée, attendre la deuxième
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
        // Trouver le formulaire dans le Turbo Frame
        const turboFrame = this.turboFrameValue 
            ? document.querySelector(`turbo-frame#${this.turboFrameValue}`)
            : null;
        
        const form = turboFrame 
            ? turboFrame.querySelector('form#logsFiltersForm') 
            : (this.formValue ? document.querySelector(this.formValue) : this.element.closest('form'));
        
        if (form && turboFrame) {
            // Créer une URL avec les paramètres du formulaire
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            
            // Conserver les autres paramètres de l'URL actuelle
            const currentUrl = new URL(window.location.href);
            const currentParams = new URLSearchParams(currentUrl.search);
            
            // Fusionner les paramètres (les nouveaux remplacent les anciens)
            for (const [key, value] of params.entries()) {
                currentParams.set(key, value);
            }
            
            const url = form.action + '?' + currentParams.toString();
            
            // Naviguer dans le Turbo Frame
            turboFrame.src = url;
        } else if (form) {
            // Fallback : soumettre normalement
            form.requestSubmit();
        }
    }

    addPresets() {
        // Attendre que le calendrier soit rendu
        const checkAndAddPresets = () => {
            const flatpickrContainer = document.querySelector('.flatpickr-calendar');
            if (flatpickrContainer && !flatpickrContainer.querySelector('.flatpickr-presets')) {
                const presetsContainer = document.createElement('div');
                presetsContainer.className = 'flatpickr-presets p-2';
                presetsContainer.style.cssText = 'background: #f8f9fa; border-bottom: 1px solid #dee2e6; display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.25rem;';
                
                const presets = [
                    { label: 'Aujourd\'hui', days: 0 },
                    { label: '7 derniers jours', days: 7 },
                    { label: '30 derniers jours', days: 30 },
                    { label: '3 derniers mois', days: 90 },
                ];

                presets.forEach(preset => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-sm btn-outline-secondary';
                    button.style.cssText = 'font-size: 0.75rem; padding: 0.25rem 0.5rem; white-space: nowrap;';
                    button.textContent = preset.label;
                    button.addEventListener('click', (e) => {
                        e.stopPropagation();
                        e.preventDefault();
                        this.setPresetDate(preset.days);
                    });
                    presetsContainer.appendChild(button);
                });

                // Insérer au début du conteneur
                const firstChild = flatpickrContainer.firstChild;
                if (firstChild) {
                    flatpickrContainer.insertBefore(presetsContainer, firstChild);
                } else {
                    flatpickrContainer.appendChild(presetsContainer);
                }
            } else if (!flatpickrContainer) {
                // Réessayer après un court délai si le calendrier n'est pas encore rendu
                setTimeout(checkAndAddPresets, 100);
            }
        };
        
        // Attendre un peu pour que Flatpickr rende le calendrier
        setTimeout(checkAndAddPresets, 200);
    }

    setPresetDate(days) {
        const today = new Date();
        const startDate = new Date(today);
        startDate.setDate(today.getDate() - days);
        
        if (days === 0) {
            // Aujourd'hui seulement
            this.flatpickr.setDate([today, today], true);
        } else {
            this.flatpickr.setDate([startDate, today], true);
        }
    }
}

