(function () {
    const defaultState = {
        subject: '',
        qui: '',
        quoi: '',
        ou: '',
        quand: '',
        comment: '',
        combien: '',
        pourquoi: '',
    };

    const sampleState = {
        subject: 'Retards de livraison aux clients',
        qui: 'Les équipes logistiques (3 préparateurs), le responsable expéditions (Jean Dupont), les transporteurs, et les clients B2B (principalement secteur automobile).',
        quoi: 'Retards systématiques de 2-3 jours sur les livraisons promises aux clients, avec non-respect des délais annoncés lors de la prise de commande.',
        ou: 'Principalement sur l\'entrepôt de Lyon (zone de préparation de commandes), mais aussi au niveau du quai d\'expédition. Moins de problèmes sur l\'entrepôt de Marseille.',
        quand: 'Depuis 3 mois, surtout en fin de mois lors des pics d\'activité. Les retards surviennent principalement les lundis et vendredis. Durée moyenne du retard : 2,5 jours.',
        comment: 'Les commandes s\'accumulent dans le système WMS, la préparation prend du retard à cause d\'un manque de coordination entre les équipes. Les colis sont ensuite mis en attente sur le quai faute de place dans les camions.',
        combien: '35% des commandes en retard (150 commandes/semaine sur 430), coût estimé à 15 000 € / mois en pénalités contractuelles, 8 réclamations clients formelles ce mois-ci.',
        pourquoi: 'Causes identifiées : manque de personnel (-2 personnes depuis 4 mois), processus de préparation inefficace (pas de priorisation), mauvaise communication avec les transporteurs, pics d\'activité non anticipés, système informatique instable.',
    };

    const state = { ...defaultState };

    const elements = {
        subject: () => document.getElementById('qqoqccpSubject'),
        qui: () => document.getElementById('qqoqccpQui'),
        quoi: () => document.getElementById('qqoqccpQuoi'),
        ou: () => document.getElementById('qqoqccpOu'),
        quand: () => document.getElementById('qqoqccpQuand'),
        comment: () => document.getElementById('qqoqccpComment'),
        combien: () => document.getElementById('qqoqccpCombien'),
        pourquoi: () => document.getElementById('qqoqccpPourquoi'),
        results: () => document.getElementById('qqoqccpResults'),
        report: () => document.getElementById('qqoqccpReportContainer'),
        reportTitle: () => document.getElementById('qqoqccpReportTitle'),
        reportDate: () => document.getElementById('qqoqccpReportDate'),
        reportSubject: () => document.getElementById('qqoqccpReportSubject'),
        reportQui: () => document.getElementById('qqoqccpReportQui'),
        reportQuoi: () => document.getElementById('qqoqccpReportQuoi'),
        reportOu: () => document.getElementById('qqoqccpReportOu'),
        reportQuand: () => document.getElementById('qqoqccpReportQuand'),
        reportComment: () => document.getElementById('qqoqccpReportComment'),
        reportCombien: () => document.getElementById('qqoqccpReportCombien'),
        reportPourquoi: () => document.getElementById('qqoqccpReportPourquoi'),
        reportSummary: () => document.getElementById('qqoqccpReportSummary'),
        analysisIdInput: () => document.getElementById('qqoqccpAnalysisId'),
    };

    function clone(obj) {
        return JSON.parse(JSON.stringify(obj));
    }

    function isToastAvailable() {
        return typeof Toastify !== 'undefined';
    }

    function notify(message, type = 'info') {
        if (!isToastAvailable()) {
            console.log(`[${type}] ${message}`);
            return;
        }

        const colors = {
            success: 'linear-gradient(to right, #00b09b, #96c93d)',
            error: 'linear-gradient(to right, #ff6b6b, #ee5a6f)',
            warning: 'linear-gradient(to right, #fbbf24, #f97316)',
            info: 'linear-gradient(to right, #60a5fa, #1d4ed8)',
        };

        Toastify({
            text: message,
            duration: 3500,
            close: true,
            gravity: 'top',
            position: 'right',
            backgroundColor: colors[type] || colors.info,
        }).showToast();
    }

    function syncInputsFromState() {
        Object.entries(elements).forEach(([key, getter]) => {
            if (typeof state[key] === 'undefined') {
                return;
            }
            const el = getter();
            if (el) {
                el.value = state[key];
            }
        });
    }

    function sanitizeFieldName(field) {
        return ['subject', 'qui', 'quoi', 'ou', 'quand', 'comment', 'combien', 'pourquoi'].includes(field);
    }

    function updateQqoqccpField(field, value) {
        if (!sanitizeFieldName(field)) {
            return;
        }
        state[field] = value;
    }

    function setCurrentAnalysisId(newId) {
        const hiddenInput = elements.analysisIdInput();
        const normalizedId = newId ? Number(newId) : null;

        if (hiddenInput) {
            hiddenInput.value = normalizedId ? String(normalizedId) : '';
        }

        const config = window.qqoqccpAppConfig || {};
        config.currentAnalysisId = normalizedId;
        config.analysisId = normalizedId;
        window.qqoqccpAppConfig = config;
    }

    function getCurrentAnalysisId() {
        const hiddenInput = elements.analysisIdInput();
        if (hiddenInput && hiddenInput.value) {
            const parsed = parseInt(hiddenInput.value, 10);
            if (!Number.isNaN(parsed)) {
                return parsed;
            }
        }
        return window.qqoqccpAppConfig?.currentAnalysisId ?? null;
    }

    function applyState(newState) {
        Object.assign(state, defaultState, newState || {});
        syncInputsFromState();
    }

    function hideResults() {
        const wrapper = elements.results();
        if (wrapper) {
            wrapper.style.display = 'none';
        }
    }

    function showResults() {
        const wrapper = elements.results();
        if (wrapper) {
            wrapper.style.display = 'block';
        }
    }

    function formatDateLabel(date = new Date()) {
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function ensureReportGenerated() {
        if (!state.subject.trim()) {
            throw new Error('Le sujet de l\'analyse est requis pour générer le rapport.');
        }

        const missingFields = Object.entries(state)
            .filter(([key, value]) => key !== 'subject' && !String(value || '').trim())
            .map(([key]) => key);

        if (missingFields.length) {
            throw new Error('Merci de compléter toutes les questions QQOQCCP avant de générer le rapport.');
        }
    }

    function injectReportContent() {
        elements.reportTitle().textContent = `Rapport QQOQCCP : ${state.subject}`;
        elements.reportDate().textContent = `Généré le ${formatDateLabel()}`;
        elements.reportSubject().textContent = state.subject;
        elements.reportQui().textContent = state.qui;
        elements.reportQuoi().textContent = state.quoi;
        elements.reportOu().textContent = state.ou;
        elements.reportQuand().textContent = state.quand;
        elements.reportComment().textContent = state.comment;
        elements.reportCombien().textContent = state.combien;
        elements.reportPourquoi().textContent = state.pourquoi;

        const summaryNode = elements.reportSummary();
        if (summaryNode) {
            summaryNode.innerHTML = '';
            const list = document.createElement('ul');
            list.className = 'qqoqccp-report__summary-list';

            const summaryItems = [
                `Acteurs clés : ${state.qui}`,
                `Nature du sujet : ${state.quoi}`,
                `Localisation : ${state.ou}`,
                `Temporalité : ${state.quand}`,
                `Processus impliqués : ${state.comment}`,
                `Ressources et impacts : ${state.combien}`,
                `Motivations ou causes : ${state.pourquoi}`,
            ];

            summaryItems.forEach((item) => {
                const li = document.createElement('li');
                li.textContent = item;
                list.appendChild(li);
            });

            summaryNode.appendChild(list);
        }
    }

    async function generateQqoqccpReport() {
        try {
            ensureReportGenerated();
            injectReportContent();
            showResults();
            elements.report().scrollIntoView({ behavior: 'smooth', block: 'start' });
            notify('Rapport QQOQCCP généré avec succès.', 'success');
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    function resetQqoqccpForm() {
        applyState(defaultState);
        hideResults();
        setCurrentAnalysisId(null);
        notify('Formulaire QQOQCCP réinitialisé.', 'info');
    }

    function newQqoqccpAnalysis() {
        applyState(sampleState);
        hideResults();
        setCurrentAnalysisId(null);
        notify('Nouvelle analyse QQOQCCP prête. Exemple chargé pour vous guider.', 'info');
    }

    function exportQqoqccp(format) {
        let answersSummary = {};
        try {
            ensureReportGenerated();
            injectReportContent();
            showResults();

            answersSummary = {
                qui: state.qui.trim(),
                quoi: state.quoi.trim(),
                ou: state.ou.trim(),
                quand: state.quand.trim(),
                comment: state.comment.trim(),
                combien: state.combien.trim(),
                pourquoi: state.pourquoi.trim(),
            };
        } catch (error) {
            notify(error.message, 'error');
            return;
        }

        const exportDate = new Date();
        const exportLocale = exportDate.toLocaleString('fr-FR');
        const titleText = state.subject && state.subject.trim()
            ? state.subject.trim()
            : 'Analyse QQOQCCP';
        const descriptionText = 'Synthèse QQOQCCP – Qui ? Quoi ? Où ? Quand ? Comment ? Combien ? Pourquoi ?';
        const totalCharacters = Object.values(answersSummary).reduce((sum, text) => sum + text.length, 0);
        const totalWords = Object.values(answersSummary).reduce((sum, text) => {
            if (!text) {
                return sum;
            }
            const words = text.trim().split(/\s+/);
            return sum + (words[0] === '' ? 0 : words.length);
        }, 0);
        const longestEntry = Object.entries(answersSummary)
            .map(([key, value]) => ({ key, value, length: value.length }))
            .sort((a, b) => b.length - a.length)[0] || { key: 'qui', value: '', length: 0 };

        if (format === 'json') {
            const payload = {
                metadata: {
                    title: titleText,
                    generatedAt: exportDate.toISOString(),
                    exportLocale,
                    tool: 'QQOQCCP',
                    version: '1.0',
                    source: 'OUTILS-QUALITÉ',
                },
                analysis: {
                    subject: state.subject || '',
                    responses: answersSummary,
                    stats: {
                        totalCharacters,
                        totalWords,
                        longestAnswer: {
                            key: longestEntry.key,
                            length: longestEntry.length,
                            preview: longestEntry.value.slice(0, 120),
                        },
                    },
                },
                rawState: clone(state),
            };

            const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `analyse-qqoqccp-${Date.now()}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            notify('Export JSON téléchargé.', 'success');
            if (typeof window.trackExport === 'function') {
                window.trackExport('qqoqccp', 'JSON', { totalCharacters, totalWords });
            }
            return;
        }

        const summaryLines = [
            `Questions renseignées : 7 / 7 · Total caractères : ${totalCharacters}`,
            `Volume de texte : ${totalWords} mot(s) · Réponse la plus dense : ${longestEntry.key.toUpperCase()}`,
        ];

        const buildExportCanvas = (capturedCanvas) => {
            const padding = 56;
            const headerHeight = 108;
            const footerHeight = 88;
            const finalCanvas = document.createElement('canvas');
            finalCanvas.width = capturedCanvas.width + padding * 2;
            finalCanvas.height = capturedCanvas.height + padding * 2 + headerHeight + footerHeight;
            const ctx = finalCanvas.getContext('2d');

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, finalCanvas.width, finalCanvas.height);

            ctx.textAlign = 'center';
            ctx.fillStyle = '#1f2937';
            ctx.font = 'bold 30px Inter, sans-serif';
            ctx.fillText(titleText, finalCanvas.width / 2, headerHeight / 2 + 8);

            ctx.font = '16px Inter, sans-serif';
            ctx.fillStyle = '#475569';
            ctx.fillText(`Exporté le ${exportLocale}`, finalCanvas.width / 2, headerHeight - 24);

            ctx.font = '14px Inter, sans-serif';
            ctx.fillStyle = '#334155';
            ctx.fillText(descriptionText.substring(0, 140), finalCanvas.width / 2, headerHeight - 2);

            const contentOffsetY = headerHeight + padding;
            ctx.drawImage(capturedCanvas, padding, contentOffsetY);

            ctx.save();
            ctx.translate(finalCanvas.width / 2, contentOffsetY + capturedCanvas.height / 2);
            ctx.rotate(-Math.PI / 6);
            ctx.font = '26px Inter, sans-serif';
            ctx.fillStyle = 'rgba(148, 163, 184, 0.18)';
            ctx.fillText('OUTILS-QUALITÉ', 0, 0);
            ctx.restore();

            const summaryStart = contentOffsetY + capturedCanvas.height + padding;
            ctx.textAlign = 'center';
            ctx.fillStyle = '#1f2937';
            ctx.font = '15px Inter, sans-serif';
            ctx.fillText(summaryLines[0], finalCanvas.width / 2, summaryStart);

            ctx.fillStyle = '#475569';
            ctx.font = '14px Inter, sans-serif';
            ctx.fillText(summaryLines[1], finalCanvas.width / 2, summaryStart + 24);

            ctx.fillStyle = '#94a3b8';
            ctx.font = '12px Inter, sans-serif';
            ctx.fillText('© OUTILS-QUALITÉ - www.outils-qualite.com', finalCanvas.width / 2, finalCanvas.height - footerHeight / 2);

            return finalCanvas;
        };

        if (format === 'pdf' || format === 'jpeg' || format === 'png') {
            const reportNode = elements.report();
            if (!reportNode) {
                notify('Impossible de trouver le rapport à exporter.', 'error');
                return;
            }

            const { jsPDF } = window.jspdf || {};
            if (format === 'pdf' && !jsPDF) {
                notify('La bibliothèque jsPDF n\'est pas chargée.', 'error');
                return;
            }

            html2canvas(reportNode, { scale: 2 })
                .then((canvas) => {
                    const exportCanvas = buildExportCanvas(canvas);

                    if (format === 'pdf') {
                        const pdf = new jsPDF('portrait');
                        const pageWidth = pdf.internal.pageSize.getWidth();
                        const pageHeight = pdf.internal.pageSize.getHeight();
                        const imgData = exportCanvas.toDataURL('image/png', 0.95);
                        const ratio = Math.min(pageWidth / exportCanvas.width, pageHeight / exportCanvas.height);
                        const imgWidth = exportCanvas.width * ratio;
                        const imgHeight = exportCanvas.height * ratio;
                        const marginX = (pageWidth - imgWidth) / 2;
                        const marginY = (pageHeight - imgHeight) / 2;
                        pdf.addImage(imgData, 'PNG', marginX, marginY, imgWidth, imgHeight);
                        pdf.save(`analyse-qqoqccp-${Date.now()}.pdf`);
                        notify('Export PDF généré.', 'success');
                        if (typeof window.trackExport === 'function') {
                            window.trackExport('qqoqccp', 'PDF', { totalCharacters, totalWords });
                        }
                    } else {
                        const mime = format === 'jpeg' ? 'image/jpeg' : 'image/png';
                        const dataUrl = exportCanvas.toDataURL(mime, 0.95);
                        const link = document.createElement('a');
                        link.href = dataUrl;
                        link.download = `analyse-qqoqccp-${Date.now()}.${format === 'jpeg' ? 'jpg' : 'png'}`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        notify(`Export ${format === 'jpeg' ? 'JPEG' : 'PNG'} généré.`, 'success');
                        if (typeof window.trackExport === 'function') {
                            window.trackExport('qqoqccp', format.toUpperCase(), { totalCharacters, totalWords });
                        }
                    }
                })
                .catch((error) => {
                    console.error(error);
                    notify('Erreur lors de la génération de l’export.', 'error');
                });
        }
    }

    async function saveQqoqccp() {
        const routes = window.qqoqccpRoutes || {};
        if (!routes.save) {
            notify('Route de sauvegarde QQOQCCP introuvable.', 'error');
            return;
        }
        if (!window.qqoqccpAppConfig?.isAuthenticated) {
            notify('Vous devez être connecté pour sauvegarder.', 'warning');
            window.location.href = routes.login ?? '/login';
            return;
        }

        try {
            ensureReportGenerated();
        } catch (error) {
            notify(error.message, 'error');
            return;
        }

        const payload = {
            title: state.subject || `Analyse QQOQCCP - ${new Date().toLocaleDateString('fr-FR')}`,
            subject: state.subject || null,
            content: clone(state),
        };

        const existingId = getCurrentAnalysisId();
        if (existingId) {
            payload.id = existingId;
        }

        const response = await fetch(routes.save, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Erreur lors de la sauvegarde QQOQCCP.');
        }

        if (data.data?.id) {
            setCurrentAnalysisId(data.data.id);
        }

        notify(data.message || 'Analyse QQOQCCP sauvegardée avec succès.', 'success');
    }

    async function loadQqoqccp(id, { silent = false } = {}) {
        const routes = window.qqoqccpRoutes || {};
        if (!routes.get) {
            if (!silent) {
                notify('Route de chargement QQOQCCP introuvable.', 'error');
            }
            return null;
        }

        try {
            const requestUrl = routes.get.replace(/0$/, String(id));
            const response = await fetch(requestUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json();

            if (!response.ok || !payload.success || !payload.data) {
                throw new Error(payload.message || 'Analyse QQOQCCP introuvable.');
            }

            applyState(payload.data.content || {});
            setCurrentAnalysisId(payload.data.id);
            hideResults();

            if (!silent) {
                notify('Analyse QQOQCCP chargée.', 'success');
            }

            return payload.data;
        } catch (error) {
            console.error(error);
            if (!silent) {
                notify(error.message || 'Erreur lors du chargement QQOQCCP.', 'error');
            }
            return null;
        }
    }

    async function getBootstrapLib() {
        if (window.bootstrap?.Modal) {
            return window.bootstrap;
        }
        if (typeof window.bootstrapReady === 'function') {
            return await window.bootstrapReady();
        }
        return window.bootstrap || null;
    }

    async function renderSavedAnalysesModal(analyses) {
        const existing = document.getElementById('qqoqccpLoadModal');
        if (existing) {
            existing.remove();
        }

        const modalHtml = `
            <div class="modal fade" id="qqoqccpLoadModal" tabindex="-1" aria-labelledby="qqoqccpLoadModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="qqoqccpLoadModalLabel">Mes analyses QQOQCCP</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="list-group">
                                ${analyses.map((analysis) => `
                                    <button type="button" class="list-group-item list-group-item-action" data-analysis-id="${analysis.id}">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">${analysis.title || 'Sans titre'}</h6>
                                            <small>${analysis.updatedAt ? new Date(analysis.updatedAt).toLocaleDateString('fr-FR') : new Date(analysis.createdAt).toLocaleDateString('fr-FR')}</small>
                                        </div>
                                        <p class="mb-1 text-muted small">${analysis.subject || 'Sujet non renseigné'}</p>
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modalElement = document.getElementById('qqoqccpLoadModal');
        const bootstrapLib = await getBootstrapLib();
        if (!bootstrapLib?.Modal) {
            notify('Le module d’interface Bootstrap n’est pas disponible pour afficher vos analyses.', 'error');
            modalElement.remove();
            return;
        }

        modalElement.querySelectorAll('[data-analysis-id]').forEach((button) => {
            button.addEventListener('click', async (event) => {
                const selectedId = event.currentTarget.getAttribute('data-analysis-id');
                await loadQqoqccp(selectedId);
                const bootstrapModal = bootstrapLib.Modal.getInstance(modalElement);
                bootstrapModal?.hide();
            });
        });

        const modal = new bootstrapLib.Modal(modalElement);
        modal.show();
    }

    async function openQqoqccpSaved() {
        if (!window.qqoqccpAppConfig?.isAuthenticated) {
            notify('Connectez-vous pour accéder à vos analyses.', 'warning');
            return;
        }

        const routes = window.qqoqccpRoutes || {};
        if (!routes.list) {
            notify('Route de récupération des analyses QQOQCCP introuvable.', 'error');
            return;
        }

        try {
            const response = await fetch(routes.list, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json();
            const analyses = payload.data || [];

            if (!analyses.length) {
                notify('Aucune analyse QQOQCCP sauvegardée.', 'info');
                return;
            }

            await renderSavedAnalysesModal(analyses);
        } catch (error) {
            console.error(error);
            notify('Erreur lors du chargement de vos analyses QQOQCCP.', 'error');
        }
    }

    async function autoLoadLatestQqoqccp() {
        if (!window.qqoqccpAppConfig?.isAuthenticated) {
            return;
        }

        const config = window.qqoqccpAppConfig;
        if (config.analysisId) {
            await loadQqoqccp(config.analysisId, { silent: true });
            return;
        }

        const routes = window.qqoqccpRoutes || {};
        if (!routes.list) {
            return;
        }

        try {
            const response = await fetch(routes.list, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json();
            if (!payload.data || !payload.data.length) {
                return;
            }

            const latest = payload.data[0];
            if (latest.content) {
                applyState(latest.content);
                setCurrentAnalysisId(latest.id);
            } else {
                await loadQqoqccp(latest.id, { silent: true });
            }
        } catch (error) {
            console.error('Erreur lors du chargement automatique QQOQCCP :', error);
        }
    }

    function initialiseForm() {
        applyState(sampleState);
        hideResults();
    }

    document.addEventListener('DOMContentLoaded', () => {
        initialiseForm();

        if (window.qqoqccpAppConfig?.isAuthenticated) {
            autoLoadLatestQqoqccp();
        }
    });

    window.qqoqccpData = state;
    window.updateQqoqccpField = updateQqoqccpField;
    window.generateQqoqccpReport = generateQqoqccpReport;
    window.resetQqoqccpForm = resetQqoqccpForm;
    window.newQqoqccpAnalysis = newQqoqccpAnalysis;
    window.exportQqoqccp = exportQqoqccp;
    window.saveQqoqccp = saveQqoqccp;
    window.openQqoqccpSaved = openQqoqccpSaved;
    window.loadQqoqccpAnalysis = loadQqoqccp;
})();


