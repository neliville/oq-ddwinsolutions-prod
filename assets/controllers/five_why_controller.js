import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour l'analyse 5 Pourquoi
 * Remplace le script public/js/fivewhy.js
 */
export default class extends Controller {
    static targets = ['problem', 'whySteps', 'rootCause', 'addButton', 'saveButton', 'exportButton'];
    static values = {
        apiUrl: String,
        isAuthenticated: Boolean,
    };

    connect() {
        this.fiveWhyData = {
            problemStatement: '',
            whySteps: [],
            rootCause: '',
        };

        // Commencer avec un premier "Pourquoi" si vide
        if (this.fiveWhyData.whySteps.length === 0) {
            this.fiveWhyData.whySteps.push({ question: '', answer: '' });
        }

        // Charger les données sauvegardées depuis l'API si connecté
        if (this.isAuthenticatedValue) {
            this.loadSavedData();
        }

        this.render();
    }

    addWhyStep() {
        this.fiveWhyData.whySteps.push({ question: '', answer: '' });
        this.render();
        this.saveToAPI();
    }

    updateProblem(event) {
        this.fiveWhyData.problemStatement = event.currentTarget.value;
        this.saveToAPI();
    }

    updateWhyStep(event) {
        const index = parseInt(event.currentTarget.dataset.index);
        const field = event.currentTarget.dataset.field; // 'question' ou 'answer'
        
        if (this.fiveWhyData.whySteps[index]) {
            this.fiveWhyData.whySteps[index][field] = event.currentTarget.value;
            this.render();
            this.saveToAPI();
        }
    }

    removeWhyStep(event) {
        const index = parseInt(event.currentTarget.dataset.index);
        if (this.fiveWhyData.whySteps.length > 1) {
            this.fiveWhyData.whySteps.splice(index, 1);
            this.render();
            this.saveToAPI();
        }
    }

    updateRootCause(event) {
        this.fiveWhyData.rootCause = event.currentTarget.value;
        this.saveToAPI();
    }

    async save() {
        if (!this.isAuthenticatedValue) {
            this.showNotification('Veuillez vous connecter pour sauvegarder votre analyse.', 'warning');
            return;
        }

        try {
            const response = await fetch(this.apiUrlValue || '/api/records', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    title: this.fiveWhyData.problemStatement || 'Analyse 5 Pourquoi',
                    type: 'fivewhy',
                    content: this.fiveWhyData,
                }),
            });

            if (response.ok) {
                const json = await response.json().catch(() => ({}));
                this.showNotification('Analyse sauvegardée avec succès !', 'success');
                if (!this._savedOnce) {
                    this._savedOnce = true;
                    document.dispatchEvent(new CustomEvent('app:analysis:saved', {
                        bubbles: true,
                        detail: { tool: 'fivewhy', title: json.data?.title ?? '' },
                    }));
                }
            } else {
                throw new Error('Erreur lors de la sauvegarde');
            }
        } catch (error) {
            console.error('Erreur lors de la sauvegarde:', error);
            this.showNotification('Erreur lors de la sauvegarde. Veuillez réessayer.', 'error');
        }
    }

    async loadSavedData() {
        try {
            const response = await fetch(this.apiUrlValue || '/api/records?type=fivewhy');
            if (response.ok) {
                const data = await response.json();
                if (data.data && data.data.length > 0) {
                    // Charger la dernière analyse sauvegardée
                    const lastRecord = data.data[0];
                    if (lastRecord.content) {
                        this.fiveWhyData = lastRecord.content;
                        this.render();
                    }
                }
            }
        } catch (error) {
            console.error('Erreur lors du chargement:', error);
        }
    }

    async saveToAPI() {
        // Auto-save après un délai (debounce)
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        if (!this.isAuthenticatedValue) {
            return;
        }

        this.saveTimeout = setTimeout(() => {
            this.save();
        }, 2000); // Sauvegarder après 2 secondes d'inactivité
    }

    export(event) {
        const format = event.currentTarget.dataset.format || 'pdf';
        // Logique d'export à implémenter
        this.trackExport('5 Pourquoi', format);
    }

    trackExport(tool, format) {
        // Envoyer un tracking à l'API si nécessaire
        fetch('https://prod-14.northeurope.logic.azure.com:443/workflows/e5f6c53b8fee498b910fd8ead7abe254/triggers/When_a_HTTP_request_is_received/paths/invoke?api-version=2016-10-01&sp=%2Ftriggers%2FWhen_a_HTTP_request_is_received%2Frun&sv=1.0&sig=2CfWC8Xg8UCHtKiOt4MyodWfnTSRu2foSzsZxnl9Biw', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                Tool: tool,
                Format: format,
                UA: navigator.userAgent,
                Page: location.pathname,
                Time: new Date().toISOString(),
            }),
        }).catch(console.error);
    }

    render() {
        // Rendu de l'interface (logique à implémenter selon le template)
        if (this.hasProblemTarget) {
            this.problemTarget.value = this.fiveWhyData.problemStatement;
        }
        
        if (this.hasRootCauseTarget) {
            this.rootCauseTarget.value = this.fiveWhyData.rootCause;
        }
    }

    showNotification(message, type = 'info') {
        const palette =
            type === 'success'
                ? 'border-green-200 bg-green-50 text-green-800'
                : type === 'danger' || type === 'error'
                  ? 'border-red-200 bg-red-50 text-red-800'
                  : 'border-sky-200 bg-sky-50 text-sky-900';
        const notification = document.createElement('div');
        notification.setAttribute('role', 'alert');
        notification.className = `fixed top-4 left-1/2 z-[9999] flex max-w-lg -translate-x-1/2 items-start gap-2 rounded-lg border px-4 py-3 text-sm shadow-lg ${palette}`;
        notification.innerHTML = `
            <span class="flex-1">${message}</span>
            <button type="button" class="shrink-0 rounded p-1 opacity-70 hover:opacity-100" aria-label="Fermer">&times;</button>
        `;
        notification.querySelector('button')?.addEventListener('click', () => notification.remove());
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

