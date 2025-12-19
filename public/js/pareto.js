(function () {
    const defaultEntries = [
        { name: 'Défaut de soudure', value: 45 },
        { name: 'Retard de livraison', value: 32 },
        { name: 'Erreur de documentation', value: 18 },
        { name: 'Panne machine', value: 12 },
        { name: 'Mauvaise communication', value: 8 },
    ];

    const state = {
        title: '',
        description: '',
        entries: [],
    };

    let chartInstance = null;

    const elements = {
        title: () => document.getElementById('paretoTitle'),
        description: () => document.getElementById('paretoDescription'),
        entries: () => document.getElementById('paretoEntries'),
        resultsWrapper: () => document.getElementById('paretoResults'),
        tableBody: () => document.getElementById('paretoTableBody'),
        insights: () => document.getElementById('paretoInsights'),
        chartCanvas: () => document.getElementById('paretoChart'),
        hiddenId: () => document.getElementById('paretoAnalysisId'),
    };

    const routes = () => window.paretoRoutes || {};

    const notify = (message, type = 'info') => {
        if (typeof Toastify === 'undefined') {
            console.log(`[${type}]`, message);
            return;
        }

        const colors = {
            success: 'linear-gradient(to right, #22c55e, #16a34a)',
            error: 'linear-gradient(to right, #ef4444, #dc2626)',
            warning: 'linear-gradient(to right, #f59e0b, #f97316)',
            info: 'linear-gradient(to right, #0ea5e9, #3b82f6)',
        };

        Toastify({
            text: message,
            duration: 3500,
            close: true,
            gravity: 'top',
            position: 'right',
            backgroundColor: colors[type] || colors.info,
        }).showToast();
    };

    const ensureEntries = () => {
        if (!Array.isArray(state.entries) || !state.entries.length) {
            state.entries = [ { name: '', value: 0 } ];
        }
    };

    const renderEntries = () => {
        const container = elements.entries();
        if (!container) return;

        ensureEntries();

        const html = state.entries
            .map(
                (entry, index) => `
                <div class="pareto-data-entry" data-index="${index}">
                    <input type="text" class="form-control" placeholder="Nom de la cause" data-field="name" value="${entry.name ?? ''}">
                    <input type="number" class="form-control" placeholder="Fréquence" min="0" data-field="value" value="${entry.value ?? ''}">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove" data-action="remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `
            )
            .join('');

        container.innerHTML = html;
    };

    const addListeners = () => {
        const container = elements.entries();
        if (!container) return;

        container.addEventListener('input', (event) => {
            const target = event.target;
            const wrapper = target.closest('.pareto-data-entry');
            if (!wrapper) return;
            const index = Number(wrapper.dataset.index);
            const field = target.dataset.field;
            if (!field || !state.entries[index]) return;

            if (field === 'value') {
                const value = parseFloat(target.value);
                state.entries[index].value = Number.isFinite(value) && value >= 0 ? value : 0;
            } else {
                state.entries[index][field] = target.value;
            }
        });

        container.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-action="remove"]');
            if (!button) return;
            const wrapper = button.closest('.pareto-data-entry');
            if (!wrapper) return;
            const index = Number(wrapper.dataset.index);

            if (state.entries.length === 1) {
                notify('Au moins une cause est nécessaire.', 'warning');
                return;
            }

            state.entries.splice(index, 1);
            renderEntries();
        });
    };

    const buildDataset = () => {
        const dataset = state.entries
            .map((entry) => ({
                name: (entry.name || '').trim(),
                value: Number(entry.value) || 0,
            }))
            .filter((item) => item.name && item.value > 0);

        dataset.sort((a, b) => b.value - a.value);

        const total = dataset.reduce((sum, item) => sum + item.value, 0);
        let cumulative = 0;

        return dataset.map((item, index) => {
            const percentage = total ? (item.value / total) * 100 : 0;
            cumulative += percentage;
            return {
                rank: index + 1,
                name: item.name,
                value: item.value,
                percentage,
                cumulative,
            };
        });
    };

    const renderTable = (rows) => {
        const tbody = elements.tableBody();
        if (!tbody) return;

        tbody.innerHTML = rows
            .map(
                (item) => `
                <tr class="${item.cumulative <= 80 ? 'highlight' : ''}">
                    <td>${item.rank}</td>
                    <td>${item.name}</td>
                    <td>${item.value}</td>
                    <td>${item.percentage.toFixed(2)}%</td>
                    <td>${item.cumulative.toFixed(2)}%</td>
                </tr>
            `
            )
            .join('');
    };

    const renderInsights = (rows) => {
        const container = elements.insights();
        if (!container) return;

        if (!rows.length) {
            container.innerHTML = '';
            return;
        }

        const in80 = rows.filter((item) => item.cumulative <= 80);
        const percentageCauses = (in80.length / rows.length) * 100;

        container.innerHTML = `
            <h3>💡 Analyse et recommandations</h3>
            <ul>
                <li><strong>${in80.length}</strong> cause(s) sur ${rows.length} (${percentageCauses.toFixed(1)}%) représentent 80% des effets.</li>
                <li>Causes prioritaires : <strong>${in80.map((item) => item.name).join(', ') || '-'}</strong></li>
                <li>Concentrez vos actions sur ces causes pour maximiser l’impact.</li>
                <li>Mettez en place un plan d’action progressif en suivant l’ordre de priorité du tableau.</li>
            </ul>
        `;
    };

    const renderChart = (rows) => {
        const canvas = elements.chartCanvas();
        if (!canvas) return;

        const ChartLib = window.Chart;
        if (!ChartLib) {
            console.error('Chart.js n\'est pas chargé.');
            notify('Erreur : Chart.js n’est pas disponible pour générer le graphique.', 'error');
            return;
        }

        if (chartInstance) {
            chartInstance.destroy();
        }

        const labels = rows.map((item) => item.name);
        const values = rows.map((item) => item.value);
        const cumulative = rows.map((item) => item.cumulative);
        const highlightedIndex = rows.length ? rows.length - 1 : -1;

        chartInstance = new ChartLib(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Fréquence',
                        data: values,
                        backgroundColor: 'rgba(14, 165, 233, 0.65)',
                        borderColor: 'rgba(14, 165, 233, 1)',
                        borderWidth: 2,
                        yAxisID: 'y',
                    },
                    {
                        label: '% cumulé',
                        data: cumulative,
                        type: 'line',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        pointRadius: cumulative.map((_, idx) => (idx === highlightedIndex ? 8 : 5)),
                        pointHoverRadius: cumulative.map((_, idx) => (idx === highlightedIndex ? 10 : 6)),
                        pointBackgroundColor: cumulative.map((_, idx) =>
                            idx === highlightedIndex ? 'rgba(236, 72, 153, 1)' : 'rgba(239, 68, 68, 1)'
                        ),
                        pointBorderColor: cumulative.map((_, idx) =>
                            idx === highlightedIndex ? 'rgba(236, 72, 153, 1)' : 'rgba(239, 68, 68, 1)'
                        ),
                        yAxisID: 'y1',
                        tension: 0.35,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Diagramme de Pareto',
                        font: {
                            size: 18,
                            weight: 'bold',
                        },
                    },
                    legend: {
                        display: true,
                        position: 'top',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Fréquence',
                        },
                    },
                    y1: {
                        beginAtZero: true,
                        max: Math.min(110, Math.max(100, Math.ceil((cumulative[cumulative.length - 1] || 100) / 10) * 10 + 10)),
                        position: 'right',
                        title: {
                            display: true,
                            text: '% cumulé',
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                },
            },
        });
    };

    const showResults = (flag) => {
        const wrapper = elements.resultsWrapper();
        if (!wrapper) return;
        wrapper.style.display = flag ? 'block' : 'none';
        if (flag) {
            wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    const generateParetoChart = () => {
        const dataset = buildDataset();
        if (!dataset.length) {
            notify('Ajoutez au moins une cause avec une fréquence positive.', 'warning');
            return;
        }

        renderTable(dataset);
        renderInsights(dataset);
        renderChart(dataset);
        showResults(true);
        notify('Diagramme de Pareto généré.', 'success');
    };

    const exportPareto = (format) => {
        const dataset = buildDataset();
        if (!dataset.length) {
            notify('Ajoutez au moins une cause avec une fréquence positive avant d’exporter.', 'warning');
            return;
        }

        renderTable(dataset);
        renderInsights(dataset);
        renderChart(dataset);
        showResults(true);

        const exportDate = new Date();
        const exportLocale = exportDate.toLocaleString('fr-FR');
        const filenameBase = `analyse-pareto-${Date.now()}`;
        const titleText = state.title && state.title.trim() ? state.title.trim() : 'Analyse Pareto';
        const descriptionText = state.description && state.description.trim()
            ? state.description.trim()
            : 'Analyse de priorisation des causes (méthode 80/20)';
        const copyrightText = '© OUTILS-QUALITÉ - www.outils-qualite.com';
        const causesIn80 = dataset.filter((item) => item.cumulative <= 80);
        const coverage80 = causesIn80.length
            ? causesIn80[causesIn80.length - 1].cumulative
            : dataset[dataset.length - 1]?.cumulative ?? 0;
        const totalFrequency = dataset.reduce((sum, item) => sum + item.value, 0);
        const topCause = dataset[0];

        if (format === 'json') {
            const payload = {
                metadata: {
                    title: titleText,
                    description: descriptionText,
                    generatedAt: exportDate.toISOString(),
                    exportLocale,
                    copyright: copyrightText,
                    tool: 'Analyse Pareto',
                    version: '1.0',
                },
                analysis: {
                    totalEntries: dataset.length,
                    totalFrequency,
                    topCause: topCause
                        ? {
                            name: topCause.name,
                            value: topCause.value,
                            percentage: Number(topCause.percentage.toFixed(2)),
                        }
                        : null,
                    paretoCoverage: {
                        causeCount: causesIn80.length,
                        cumulativePercentage: Number(coverage80.toFixed(2)),
                    },
                    entries: dataset.map((item) => ({
                        name: item.name,
                        value: item.value,
                        percentage: Number(item.percentage.toFixed(2)),
                        cumulative: Number(item.cumulative.toFixed(2)),
                    })),
                },
                rawEntries: state.entries.map((item) => ({
                    name: item.name || '',
                    value: Number(item.value) || 0,
                })),
            };

            const blob = new Blob([JSON.stringify(payload, null, 2)], {
                type: 'application/json',
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${filenameBase}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            notify('Export JSON généré.', 'success');
            return;
        }

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
            ctx.fillText(`Exporté le ${exportLocale}`, finalCanvas.width / 2, headerHeight - 22);

            ctx.font = '14px Inter, sans-serif';
            ctx.fillStyle = '#334155';
            ctx.fillText(descriptionText.substring(0, 120), finalCanvas.width / 2, headerHeight - 4);

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
            ctx.fillText(
                `Causes analysées : ${dataset.length} · Volume total : ${totalFrequency} · Cause principale : ${topCause ? topCause.name : 'N/A'}`,
                finalCanvas.width / 2,
                summaryStart
            );

            ctx.fillStyle = '#475569';
            ctx.font = '14px Inter, sans-serif';
            ctx.fillText(
                `80/20 : ${causesIn80.length} cause(s) couvrent ${coverage80.toFixed(1)}% des effets`,
                finalCanvas.width / 2,
                summaryStart + 24
            );

            ctx.fillStyle = '#94a3b8';
            ctx.font = '12px Inter, sans-serif';
            ctx.fillText(copyrightText, finalCanvas.width / 2, finalCanvas.height - footerHeight / 2);

            return finalCanvas;
        };

        if (format === 'pdf' || format === 'png' || format === 'jpeg') {
            const { jsPDF } = window.jspdf || {};
            if (format === 'pdf' && !jsPDF) {
                notify('La bibliothèque jsPDF n’est pas disponible.', 'error');
                return;
            }

            const wrapper = elements.resultsWrapper();
            html2canvas(wrapper, { scale: 2 })
                .then((capturedCanvas) => {
                    const exportCanvas = buildExportCanvas(capturedCanvas);

                    if (format === 'pdf') {
                        const pdf = new jsPDF('landscape');
                        const pageWidth = pdf.internal.pageSize.getWidth();
                        const pageHeight = pdf.internal.pageSize.getHeight();
                        const imgData = exportCanvas.toDataURL('image/png', 0.95);
                        const ratio = Math.min(pageWidth / exportCanvas.width, pageHeight / exportCanvas.height);
                        const imgWidth = exportCanvas.width * ratio;
                        const imgHeight = exportCanvas.height * ratio;
                        const marginX = (pageWidth - imgWidth) / 2;
                        const marginY = (pageHeight - imgHeight) / 2;
                        pdf.addImage(imgData, 'PNG', marginX, marginY, imgWidth, imgHeight);
                        pdf.save(`${filenameBase}.pdf`);
                        notify('Export PDF généré.', 'success');
                    } else {
                        const mime = format === 'jpeg' ? 'image/jpeg' : 'image/png';
                        const dataUrl = exportCanvas.toDataURL(mime, 0.95);
                        const link = document.createElement('a');
                        link.href = dataUrl;
                        link.download = `${filenameBase}.${format === 'jpeg' ? 'jpg' : 'png'}`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        notify(`Export ${format === 'jpeg' ? 'JPEG' : 'PNG'} généré.`, 'success');
                    }
                })
                .catch((error) => {
                    console.error(error);
                    notify('Erreur lors de la génération de l’export.', 'error');
                });
        }
    };

    const buildSavePayload = () => ({
        title: state.title || null,
        description: state.description || null,
        entries: state.entries.map((item) => ({
            name: item.name || '',
            value: Number(item.value) || 0,
        })),
    });

    const getCurrentAnalysisId = () => {
        const hidden = elements.hiddenId();
        if (hidden && hidden.value) {
            const parsed = parseInt(hidden.value, 10);
            if (!Number.isNaN(parsed)) {
                return parsed;
            }
        }
        return window.paretoAppConfig?.currentAnalysisId ?? null;
    };

    const setCurrentAnalysisId = (id) => {
        const hidden = elements.hiddenId();
        const normalized = id ? Number(id) : null;
        if (hidden) {
            hidden.value = normalized ? String(normalized) : '';
        }
        const config = window.paretoAppConfig || {};
        config.currentAnalysisId = normalized;
        config.analysisId = normalized;
        window.paretoAppConfig = config;
    };

    const savePareto = async () => {
        if (!window.paretoAppConfig?.isAuthenticated) {
            notify('Connectez-vous pour sauvegarder vos analyses.', 'warning');
            const loginRoute = routes().login || '/login';
            window.location.href = loginRoute;
            return;
        }

        const dataset = buildDataset();
        if (!dataset.length) {
            notify('Ajoutez au moins une cause avec une fréquence positive avant de sauvegarder.', 'warning');
            return;
        }

        const payload = buildSavePayload();
        const requestBody = {
            title:
                (payload.title && payload.title.trim()) ||
                `Analyse Pareto - ${new Date().toLocaleDateString('fr-FR')}`,
            description: payload.description,
            content: payload,
        };

        const existingId = getCurrentAnalysisId();
        if (existingId) {
            requestBody.id = existingId;
        }

        try {
            const response = await fetch(routes().save, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(requestBody),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Erreur lors de la sauvegarde');
            }

            if (data.data?.id) {
                setCurrentAnalysisId(data.data.id);
            }

            notify(data.message || 'Analyse Pareto sauvegardée.', 'success');
        } catch (error) {
            console.error(error);
            notify(`Erreur lors de la sauvegarde : ${error.message}`, 'error');
        }
    };

    const fetchJson = async (url) => {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();
        if (!response.ok) {
            const message = data?.message || 'Erreur réseau';
            throw new Error(message);
        }
        return data;
    };

    const loadPareto = async (id, { silent = false } = {}) => {
        try {
            const baseUrl = routes().get;
            const requestUrl = baseUrl.replace(/0$/, String(id));
            const payload = await fetchJson(requestUrl);

            if (!payload.success || !payload.data) {
                throw new Error(payload.message || 'Analyse introuvable');
            }

            const data = payload.data.content || {};
            state.title = data.title || payload.data.title || '';
            state.description = data.description || payload.data.description || '';
            state.entries = Array.isArray(data.entries) && data.entries.length ? data.entries : [ { name: '', value: 0 } ];

            if (elements.title()) {
                elements.title().value = state.title || '';
            }
            if (elements.description()) {
                elements.description().value = state.description || '';
            }

            renderEntries();
            showResults(false);
            setCurrentAnalysisId(payload.data.id);

            if (!silent) {
                notify('Analyse Pareto chargée.', 'success');
            }

            return payload.data;
        } catch (error) {
            console.error(error);
            if (!silent) {
                notify(error.message || 'Erreur lors du chargement.', 'error');
            }
            return null;
        }
    };

    const getBootstrapLib = async () => {
        if (window.bootstrap?.Modal) {
            return window.bootstrap;
        }
        if (typeof window.bootstrapReady === 'function') {
            return await window.bootstrapReady();
        }
        return window.bootstrap || null;
    };

    const openParetoSaved = async () => {
        if (!window.paretoAppConfig?.isAuthenticated) {
            notify('Connectez-vous pour accéder à vos analyses.', 'warning');
            return;
        }

        try {
            const payload = await fetchJson(routes().list);
            const analyses = payload.data || [];

            if (!analyses.length) {
                notify('Aucune analyse Pareto sauvegardée.', 'info');
                return;
            }

            // Trouver le modal existant (créé via le composant Twig)
            const modalElement = document.getElementById('paretoLoadModal');
            if (!modalElement) {
                notify('Le modal n\'est pas disponible.', 'error');
                return;
            }

            // Trouver la liste dans le modal
            const listContainer = modalElement.querySelector('#paretoAnalysesList');
            if (!listContainer) {
                notify('Le conteneur de la liste n\'est pas disponible.', 'error');
                return;
            }

            // Générer le HTML de la liste des analyses
            const listHtml = analyses
                .map(
                    (analysis) => `
                    <button type="button" class="list-group-item list-group-item-action" data-analysis-id="${analysis.id}">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-1">${analysis.title || 'Sans titre'}</h6>
                            <small>${analysis.updatedAt ? new Date(analysis.updatedAt).toLocaleDateString('fr-FR') : new Date(analysis.createdAt).toLocaleDateString('fr-FR')}</small>
                        </div>
                        <p class="mb-0 text-muted small">${analysis.description || 'Analyse Pareto'}</p>
                    </button>
                `
                )
                .join('');

            listContainer.innerHTML = listHtml;

            // Ajouter les event listeners pour charger les analyses
            listContainer.querySelectorAll('[data-analysis-id]').forEach((button) => {
                button.addEventListener('click', async (event) => {
                    const selectedId = event.currentTarget.getAttribute('data-analysis-id');
                    await loadPareto(selectedId);
                    
                    // Fermer le modal via le contrôleur bootstrap-modal
                    let modalController = null;
                    
                    if (window.Stimulus && typeof window.Stimulus.getControllerForElementAndIdentifier === 'function') {
                        try {
                            modalController = window.Stimulus.getControllerForElementAndIdentifier(modalElement, 'bootstrap-modal');
                        } catch (e) {
                            console.warn('Impossible de récupérer le contrôleur Stimulus:', e);
                        }
                    }

                    if (modalController && typeof modalController.hide === 'function') {
                        modalController.hide();
                    } else {
                        // Fallback vers Bootstrap natif
                        const bootstrapLib = await getBootstrapLib();
                        const bootstrapModal = bootstrapLib?.Modal?.getInstance?.(modalElement);
                        bootstrapModal?.hide();
                    }
                });
            });

            // Ouvrir le modal via le contrôleur bootstrap-modal
            // Stimulus est exposé via window.Stimulus (voir assets/bootstrap.js)
            let modalController = null;
            
            if (window.Stimulus && typeof window.Stimulus.getControllerForElementAndIdentifier === 'function') {
                try {
                    modalController = window.Stimulus.getControllerForElementAndIdentifier(modalElement, 'bootstrap-modal');
                } catch (e) {
                    console.warn('Impossible de récupérer le contrôleur Stimulus:', e);
                }
            }

            if (modalController && typeof modalController.show === 'function') {
                modalController.show();
            } else {
                // Fallback vers Bootstrap natif
                const bootstrapLib = await getBootstrapLib();
                if (!bootstrapLib?.Modal) {
                    notify('Le module d\'interface Bootstrap n\'est pas disponible pour afficher vos analyses Pareto.', 'error');
                    return;
                }
                const modalInstance = new bootstrapLib.Modal(modalElement);
                modalInstance.show();
            }
        } catch (error) {
            console.error(error);
            notify(error.message || 'Erreur lors du chargement des analyses.', 'error');
        }
    };

    const autoLoadLatestPareto = async () => {
        if (!window.paretoAppConfig?.isAuthenticated) {
            return;
        }

        const config = window.paretoAppConfig;
        if (config.analysisId) {
            await loadPareto(config.analysisId, { silent: true });
            return;
        }

        try {
            const payload = await fetchJson(routes().list);
            const analyses = payload.data || [];
            if (!analyses.length) {
                return;
            }
            await loadPareto(analyses[0].id, { silent: true });
        } catch (error) {
            console.error('Impossible de charger automatiquement l’analyse Pareto :', error);
        }
    };

    const resetParetoForm = () => {
        state.title = '';
        state.description = '';
        state.entries = [ { name: '', value: 0 } ];

        if (elements.title()) {
            elements.title().value = '';
        }
        if (elements.description()) {
            elements.description().value = '';
        }

        renderEntries();
        showResults(false);
        setCurrentAnalysisId(null);
        chartInstance?.destroy();
        chartInstance = null;
        notify('Formulaire réinitialisé.', 'info');
    };

    const newParetoAnalysis = () => {
        state.title = 'Analyse Pareto - Exemple';
        state.description = 'Exemple de priorisation des causes';
        state.entries = defaultEntries.map((entry) => ({ ...entry }));

        if (elements.title()) {
            elements.title().value = state.title;
        }
        if (elements.description()) {
            elements.description().value = state.description;
        }

        renderEntries();
        showResults(false);
        setCurrentAnalysisId(null);
        chartInstance?.destroy();
        chartInstance = null;
        notify('Exemple Pareto chargé.', 'info');
    };

    const addParetoEntry = () => {
        state.entries.push({ name: '', value: 0 });
        renderEntries();
        notify('Cause ajoutée.', 'success');
    };

    const updateParetoTitle = (value) => {
        state.title = value;
    };

    const updateParetoDescription = (value) => {
        state.description = value;
    };

    window.generateParetoChart = generateParetoChart;
    window.exportPareto = exportPareto;
    window.savePareto = savePareto;
    window.openParetoSaved = openParetoSaved;
    window.resetParetoForm = resetParetoForm;
    window.newParetoAnalysis = newParetoAnalysis;
    window.addParetoEntry = addParetoEntry;
    window.updateParetoTitle = updateParetoTitle;
    window.updateParetoDescription = updateParetoDescription;

    document.addEventListener('DOMContentLoaded', () => {
        resetParetoForm();
        addListeners();

        if (window.paretoAppConfig?.isAuthenticated) {
            autoLoadLatestPareto();
        }
    });
})();


