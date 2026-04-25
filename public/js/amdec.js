(function () {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const defaultEntries = [
        {
            process: 'Assemblage moteur',
            failure: 'Serrage incorrect des boulons',
            effect: 'Fuite d\'huile, panne moteur',
            cause: 'Clé dynamométrique mal calibrée',
            actions: 'Calibration hebdomadaire, procédure de contrôle croisé',
            gravity: 8,
            occurrence: 4,
            detection: 3,
        },
        {
            process: 'Contrôle qualité',
            failure: 'Pièce défectueuse non détectée',
            effect: 'Retour client, réclamation',
            cause: 'Manque de formation inspecteur',
            actions: 'Formation renforcée, double contrôle visuel',
            gravity: 7,
            occurrence: 3,
            detection: 6,
        },
        {
            process: 'Livraison produit',
            failure: 'Emballage endommagé',
            effect: 'Produit abîmé à réception',
            cause: 'Emballage inadapté aux contraintes logistiques',
            actions: 'Changement d\'emballage, tests de chute réguliers',
            gravity: 5,
            occurrence: 6,
            detection: 2,
        },
    ];

    const state = {
        subject: '',
        entries: [],
    };

    const elements = {
        subject: () => document.getElementById('amdecSubject'),
        entries: () => document.getElementById('amdecEntries'),
        resultsWrapper: () => document.getElementById('amdecResults'),
        tableBody: () => document.getElementById('amdecTableBody'),
        totalCount: () => document.getElementById('amdecTotalCount'),
        criticalCount: () => document.getElementById('amdecCriticalCount'),
        averageNpr: () => document.getElementById('amdecAverageNpr'),
        insights: () => document.getElementById('amdecInsights'),
        hiddenId: () => document.getElementById('amdecAnalysisId'),
    };

    const routes = () => window.amdecRoutes || {};

    const notify = (message, type = 'info') => {
        if (typeof Toastify === 'undefined') {
            console.log(`[${type}]`, message);
            return;
        }

        const colors = {
            success: 'linear-gradient(to right, #10b981, #34d399)',
            error: 'linear-gradient(to right, #ef4444, #dc2626)',
            warning: 'linear-gradient(to right, #f59e0b, #f97316)',
            info: 'linear-gradient(to right, #6366f1, #3b82f6)',
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

    const computeMetrics = (entry) => {
        const g = clamp(Number(entry.gravity) || 1, 1, 10);
        const o = clamp(Number(entry.occurrence) || 1, 1, 10);
        const d = clamp(Number(entry.detection) || 1, 1, 10);
        const npr = g * o * d;
        let priority = 'Faible';

        if (npr > 125) {
            priority = 'Élevée';
        } else if (npr > 50) {
            priority = 'Moyenne';
        }

        return { g, o, d, npr, priority };
    };

    const createEmptyEntry = () => ({
        process: '',
        failure: '',
        effect: '',
        cause: '',
        actions: '',
        gravity: 5,
        occurrence: 5,
        detection: 5,
    });

    const ensureEntries = () => {
        if (!Array.isArray(state.entries) || !state.entries.length) {
            state.entries = [createEmptyEntry()];
        }
    };

    const renderEntries = () => {
        const container = elements.entries();
        if (!container) {
            return;
        }

        ensureEntries();

        const html = state.entries
            .map((entry, index) => {
                const { npr, priority } = computeMetrics(entry);
                const badgeClass =
                    priority === 'Élevée'
                        ? 'amdec-entry__badge--high'
                        : priority === 'Moyenne'
                          ? 'amdec-entry__badge--medium'
                          : 'amdec-entry__badge--low';

                return `
                <div class="amdec-entry" data-index="${index}">
                    <div class="amdec-entry__grid">
                        <div class="amdec-entry__group" data-field="process">
                            <label>Fonction / Processus</label>
                            <input type="text" value="${entry.process ?? ''}" placeholder="Ex : Assemblage moteur" data-field="process">
                        </div>
                        <div class="amdec-entry__group" data-field="failure">
                            <label>Mode de défaillance</label>
                            <textarea data-field="failure" placeholder="Ex : Serrage incorrect">${entry.failure ?? ''}</textarea>
                        </div>
                        <div class="amdec-entry__group" data-field="effect">
                            <label>Effet de la défaillance</label>
                            <textarea data-field="effect" placeholder="Conséquences observées">${entry.effect ?? ''}</textarea>
                        </div>
                        <div class="amdec-entry__group" data-field="cause">
                            <label>Cause probable</label>
                            <textarea data-field="cause" placeholder="Origine identifiée">${entry.cause ?? ''}</textarea>
                        </div>
                        <div class="amdec-entry__group amdec-entry__group--full" data-field="actions">
                            <label>Actions préventives / correctives</label>
                            <textarea data-field="actions" placeholder="Mesures proposées">${entry.actions ?? ''}</textarea>
                        </div>
                    </div>

                    <div class="amdec-entry__ratings">
                        <div class="amdec-entry__group">
                            <label>Gravité (1-10)</label>
                            <input type="number" min="1" max="10" value="${entry.gravity ?? 5}" data-field="gravity">
                        </div>
                        <div class="amdec-entry__group">
                            <label>Occurrence (1-10)</label>
                            <input type="number" min="1" max="10" value="${entry.occurrence ?? 5}" data-field="occurrence">
                        </div>
                        <div class="amdec-entry__group">
                            <label>Détection (1-10)</label>
                            <input type="number" min="1" max="10" value="${entry.detection ?? 5}" data-field="detection">
                        </div>
                    </div>

                    <div class="amdec-entry__npr">
                        <div class="d-flex align-items-center justify-content-between">
                            <span>Indice NPR</span>
                            <span class="amdec-entry__badge ${badgeClass}" data-role="priority">${priority}</span>
                        </div>
                        <div class="npr-value" data-role="npr">${npr}</div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove" data-action="remove">
                            <i class="fas fa-trash-alt me-2"></i>Supprimer
                        </button>
                    </div>
                </div>`;
            })
            .join('');

        container.innerHTML = html;
    };

    const syncStateField = (index, field, value) => {
        if (!state.entries[index]) {
            return;
        }

        if (['gravity', 'occurrence', 'detection'].includes(field)) {
            state.entries[index][field] = clamp(parseInt(value, 10) || 1, 1, 10);
        } else {
            state.entries[index][field] = value;
        }

        updateEntryMetricsDisplay(index);
    };

    const updateEntryMetricsDisplay = (index) => {
        const container = elements.entries();
        if (!container) return;
        const entryEl = container.querySelector(`.amdec-entry[data-index="${index}"]`);
        const entry = state.entries[index];
        if (!entryEl || !entry) return;

        const { npr, priority } = computeMetrics(entry);
        const nprEl = entryEl.querySelector('[data-role="npr"]');
        const badgeEl = entryEl.querySelector('[data-role="priority"]');

        if (nprEl) {
            nprEl.textContent = npr;
        }

        if (badgeEl) {
            badgeEl.textContent = priority;
            badgeEl.classList.remove('amdec-entry__badge--low', 'amdec-entry__badge--medium', 'amdec-entry__badge--high');

            if (priority === 'Élevée') {
                badgeEl.classList.add('amdec-entry__badge--high');
            } else if (priority === 'Moyenne') {
                badgeEl.classList.add('amdec-entry__badge--medium');
            } else {
                badgeEl.classList.add('amdec-entry__badge--low');
            }
        }
    };

    const addListeners = () => {
        const entriesContainer = elements.entries();
        if (!entriesContainer) {
            return;
        }

        entriesContainer.addEventListener('input', (event) => {
            const target = event.target;
            const wrapper = target.closest('.amdec-entry');
            if (!wrapper) return;
            const index = Number(wrapper.dataset.index);
            const field = target.dataset.field;
            if (!field) return;

            syncStateField(index, field, target.value);
        });

        entriesContainer.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-action="remove"]');
            if (!button) return;
            const wrapper = button.closest('.amdec-entry');
            if (!wrapper) return;
            const index = Number(wrapper.dataset.index);

            if (state.entries.length === 1) {
                notify('Vous devez conserver au moins un mode de défaillance.', 'warning');
                return;
            }

            state.entries.splice(index, 1);
            renderEntries();
        });
    };

    const showResults = (value) => {
        const wrapper = elements.resultsWrapper();
        if (!wrapper) return;
        wrapper.style.display = value ? 'block' : 'none';
        if (value) {
            wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    const buildDataset = () => {
        const dataset = state.entries
            .map((entry) => {
                const metrics = computeMetrics(entry);
                if (!(entry.process || entry.failure)) {
                    return null;
                }

                return {
                    process: entry.process.trim(),
                    failure: entry.failure.trim(),
                    effect: entry.effect.trim(),
                    cause: entry.cause.trim(),
                    actions: entry.actions.trim(),
                    gravity: metrics.g,
                    occurrence: metrics.o,
                    detection: metrics.d,
                    npr: metrics.npr,
                    priority: metrics.priority,
                };
            })
            .filter(Boolean);

        return dataset;
    };

    const renderTable = (rows) => {
        const tbody = elements.tableBody();
        if (!tbody) return;

        tbody.innerHTML = rows
            .map(
                (item) => `
            <tr>
                <td>${item.process || '-'}</td>
                <td>${item.failure || '-'}</td>
                <td>${item.effect || '-'}</td>
                <td>${item.cause || '-'}</td>
                <td><strong>${item.gravity}</strong></td>
                <td><strong>${item.occurrence}</strong></td>
                <td><strong>${item.detection}</strong></td>
                <td><strong style="color:${item.npr > 125 ? '#ef4444' : '#4f46e5'};">${item.npr}</strong></td>
                <td>
                    <span class="amdec-entry__badge ${
                        item.priority === 'Élevée'
                            ? 'amdec-entry__badge--high'
                            : item.priority === 'Moyenne'
                              ? 'amdec-entry__badge--medium'
                              : 'amdec-entry__badge--low'
                    }">${item.priority}</span>
                </td>
                <td>${item.actions || '-'}</td>
            </tr>`
            )
            .join('');
    };

    const renderSummary = (rows) => {
        elements.totalCount().textContent = rows.length;
        const critical = rows.filter((item) => item.npr > 125).length;
        elements.criticalCount().textContent = critical;
        const average =
            rows.length > 0
                ? Math.round(rows.reduce((sum, item) => sum + item.npr, 0) / rows.length)
                : 0;
        elements.averageNpr().textContent = average;
    };

    const renderInsights = (rows) => {
        const container = elements.insights();
        if (!container) return;

        if (!rows.length) {
            container.innerHTML = '';
            return;
        }

        const critical = rows.filter((item) => item.npr > 125);
        const medium = rows.filter((item) => item.npr > 50 && item.npr <= 125);
        const highGravity = rows.filter((item) => item.gravity >= 8);
        const poorDetection = rows.filter((item) => item.detection >= 7);

        let html = '<h3>💡 Analyse et recommandations</h3><ul>';

        if (critical.length) {
            html += `<li><strong>⚠️ ${critical.length} risque(s) critique(s)</strong> nécessitent une action immédiate :</li>`;
            html += '<ul>';
            critical.slice(0, 3).forEach((item) => {
                html += `<li>${item.process || 'Processus'} – ${item.failure || 'Défaillance'} (NPR: ${item.npr})</li>`;
            });
            html += '</ul>';
        }

        if (medium.length) {
            html += `<li><strong>⚡ ${medium.length} risque(s) de priorité moyenne</strong> à traiter à court terme.</li>`;
        }

        if (highGravity.length) {
            html += `<li><strong>🔴 ${highGravity.length} défaillance(s) à forte gravité</strong> (G ≥ 8) — agissez en priorité sur l'occurrence ou la détection.</li>`;
        }

        if (poorDetection.length) {
            html += `<li><strong>🔍 ${poorDetection.length} défaillance(s) difficiles à détecter</strong> (D ≥ 7) — renforcez vos contrôles.</li>`;
        }

        html += '<li><strong>📝 Plan d\'action :</strong> ciblez d\'abord les NPR les plus élevés en réduisant G, O ou en améliorant D.</li>';
        html += '</ul>';

        container.innerHTML = html;
    };

    const generateAmdecReport = () => {
        const dataset = buildDataset();
        if (!dataset.length) {
            notify('Ajoutez au moins un mode de défaillance avec une fonction et une description.', 'warning');
            return;
        }

        dataset.sort((a, b) => b.npr - a.npr);
        renderTable(dataset);
        renderSummary(dataset);
        renderInsights(dataset);
        showResults(true);
        notify('Analyse AMDEC générée avec succès.', 'success');
    };

    const exportAmdec = (format) => {
        const dataset = buildDataset();
        if (!dataset.length) {
            notify('Ajoutez au moins un mode de défaillance avant d\'exporter.', 'warning');
            return;
        }

        const sortedDataset = [...dataset].sort((a, b) => b.npr - a.npr);
        renderTable(sortedDataset);
        renderSummary(sortedDataset);
        renderInsights(sortedDataset);
        showResults(true);

        const exportDate = new Date();
        const exportLocale = exportDate.toLocaleString('fr-FR');
        const titleText = state.subject && state.subject.trim()
            ? state.subject.trim()
            : 'Analyse AMDEC';
        const descriptionText = 'Analyse des modes de défaillance, de leurs effets et de leur criticité (NPR).';
        const totalEntries = sortedDataset.length;
        const criticalCount = sortedDataset.filter((item) => item.npr > 125).length;
        const averageNpr = totalEntries
            ? Math.round(sortedDataset.reduce((sum, item) => sum + item.npr, 0) / totalEntries)
            : 0;
        const topRisk = sortedDataset[0] || null;

        if (format === 'json') {
            const payload = {
                metadata: {
                    title: titleText,
                    generatedAt: exportDate.toISOString(),
                    exportLocale,
                    tool: 'AMDEC',
                    version: '1.0',
                    source: 'OUTILS-QUALITÉ',
                },
                analysis: {
                    subject: state.subject || '',
                    totalEntries,
                    criticalCount,
                    averageNpr,
                    topRisk: topRisk
                        ? {
                            process: topRisk.process,
                            failure: topRisk.failure,
                            npr: topRisk.npr,
                            priority: topRisk.priority,
                        }
                        : null,
                    entries: sortedDataset,
                },
                rawState: buildSavePayload(),
            };

            const blob = new Blob([JSON.stringify(payload, null, 2)], {
                type: 'application/json',
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `analyse-amdec-${Date.now()}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            notify('Export JSON généré.', 'success');
            if (typeof window.trackExport === 'function') {
                window.trackExport('amdec', 'JSON', { totalEntries, criticalCount, averageNpr });
            }
            return;
        }

        const summaryLines = [
            `Modes analysés : ${totalEntries} · NPR moyen : ${averageNpr}`,
            `NPR critiques (>125) : ${criticalCount} · Risque principal : ${topRisk ? (topRisk.failure || 'Non renseigné') : 'N/A'}`,
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
            ctx.fillText(descriptionText.substring(0, 150), finalCanvas.width / 2, headerHeight - 2);

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

        if (format === 'pdf' || format === 'png' || format === 'jpeg') {
            const { jsPDF } = window.jspdf || {};
            if (format === 'pdf' && !jsPDF) {
                notify('La bibliothèque jsPDF n\'est pas disponible.', 'error');
                return;
            }

            const node = elements.resultsWrapper();
            html2canvas(node, { scale: 2 })
                .then((canvas) => {
                    const exportCanvas = buildExportCanvas(canvas);

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
                        pdf.save(`analyse-amdec-${Date.now()}.pdf`);
                        notify('Export PDF généré.', 'success');
                        if (typeof window.trackExport === 'function') {
                            window.trackExport('amdec', 'PDF', { totalEntries, criticalCount, averageNpr });
                        }
                    } else {
                        const mime = format === 'jpeg' ? 'image/jpeg' : 'image/png';
                        const dataUrl = exportCanvas.toDataURL(mime, 0.95);
                        const link = document.createElement('a');
                        link.href = dataUrl;
                        link.download = `analyse-amdec-${Date.now()}.${format === 'jpeg' ? 'jpg' : 'png'}`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        notify(`Export ${format === 'jpeg' ? 'JPEG' : 'PNG'} généré.`, 'success');
                        if (typeof window.trackExport === 'function') {
                            window.trackExport('amdec', format.toUpperCase(), { totalEntries, criticalCount, averageNpr });
                        }
                    }
                })
                .catch((error) => {
                    console.error(error);
                    notify('Erreur lors de la génération de l\'export.', 'error');
                });
        }
    };

    const buildSavePayload = () => ({
        subject: state.subject || null,
        entries: state.entries.map((entry) => ({
            process: entry.process || '',
            failure: entry.failure || '',
            effect: entry.effect || '',
            cause: entry.cause || '',
            actions: entry.actions || '',
            gravity: clamp(Number(entry.gravity) || 1, 1, 10),
            occurrence: clamp(Number(entry.occurrence) || 1, 1, 10),
            detection: clamp(Number(entry.detection) || 1, 1, 10),
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
        return window.amdecAppConfig?.currentAnalysisId ?? null;
    };

    const setCurrentAnalysisId = (id) => {
        const hidden = elements.hiddenId();
        const normalized = id ? Number(id) : null;
        if (hidden) {
            hidden.value = normalized ? String(normalized) : '';
        }
        const config = window.amdecAppConfig || {};
        config.currentAnalysisId = normalized;
        config.analysisId = normalized;
        window.amdecAppConfig = config;
    };

    const saveAmdec = async () => {
        if (!window.amdecAppConfig?.isAuthenticated) {
            notify('Connectez-vous pour sauvegarder vos analyses.', 'warning');
            const loginRoute = routes().login || '/login';
            window.location.href = loginRoute;
            return;
        }

        const payload = buildSavePayload();
        const dataset = buildDataset();
        if (!dataset.length) {
            notify('Ajoutez au moins un mode de défaillance avant de sauvegarder.', 'warning');
            return;
        }

        const requestBody = {
            title:
                (payload.subject && payload.subject.trim()) ||
                `Analyse AMDEC - ${new Date().toLocaleDateString('fr-FR')}`,
            subject: payload.subject,
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

            notify(data.message || 'Analyse AMDEC sauvegardée.', 'success');
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

    const loadAmdec = async (id, { silent = false } = {}) => {
        try {
            const baseUrl = routes().get;
            const requestUrl = baseUrl.replace(/0$/, String(id));
            const payload = await fetchJson(requestUrl);

            if (!payload.success || !payload.data) {
                throw new Error(payload.message || 'Analyse introuvable');
            }

            const data = payload.data.content || {};
            state.subject = data.subject || '';
            state.entries = Array.isArray(data.entries) && data.entries.length ? data.entries : [createEmptyEntry()];

            if (elements.subject()) {
                elements.subject().value = state.subject || '';
            }

            renderEntries();
            setCurrentAnalysisId(payload.data.id);
            showResults(false);

            if (!silent) {
                notify('Analyse AMDEC chargée.', 'success');
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

    const _openAmdecModal = async (modalElement) => {
        let ctrl = null;
        if (window.Stimulus && typeof window.Stimulus.getControllerForElementAndIdentifier === 'function') {
            try { ctrl = window.Stimulus.getControllerForElementAndIdentifier(modalElement, 'bootstrap-modal'); } catch (e) {}
        }
        if (ctrl && typeof ctrl.show === 'function') { ctrl.show(); return; }
        const bsLib = window.bootstrap || await new Promise(r => { if (window.bootstrap) r(window.bootstrap); else setTimeout(() => r(window.bootstrap), 100); });
        if (bsLib?.Modal) new bsLib.Modal(modalElement).show();
    };

    const _closeAmdecModal = async (modalElement) => {
        let ctrl = null;
        if (window.Stimulus && typeof window.Stimulus.getControllerForElementAndIdentifier === 'function') {
            try { ctrl = window.Stimulus.getControllerForElementAndIdentifier(modalElement, 'bootstrap-modal'); } catch (e) {}
        }
        if (ctrl && typeof ctrl.hide === 'function') { ctrl.hide(); return; }
        const bsLib = window.bootstrap;
        if (bsLib?.Modal?.getInstance) bsLib.Modal.getInstance(modalElement)?.hide();
    };

    const deleteAmdecAnalysis = async (id, event) => {
        event.stopPropagation();
        const confirmed = await (window.showConfirmationModal
            ? window.showConfirmationModal({ title: 'Supprimer l\'analyse', message: 'Supprimer cette analyse ? Cette action est irréversible.', confirmText: 'Supprimer', type: 'danger' })
            : Promise.resolve(window.confirm('Supprimer cette analyse ?')));
        if (!confirmed) return;
        try {
            const url = routes().delete.replace(/\/0$/, '/' + id);
            const response = await fetch(url, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Erreur lors de la suppression');
            notify('Analyse supprimée.', 'success');
            openAmdecSaved();
        } catch (error) {
            notify('Erreur : ' + error.message, 'error');
        }
    };

    const openAmdecSaved = async () => {
        if (!window.amdecAppConfig?.isAuthenticated) {
            notify('Connectez-vous pour accéder à vos analyses.', 'warning');
            return;
        }

        try {
            const payload = await fetchJson(routes().list);
            const analyses = payload.data || [];

            const modalElement = document.getElementById('amdecLoadModal');
            if (!modalElement) {
                notify('Le modal n\'est pas disponible.', 'error');
                return;
            }

            const listContainer = modalElement.querySelector('#amdecAnalysesList');
            if (!listContainer) return;

            if (!analyses.length) {
                listContainer.innerHTML = `
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                        <p class="mb-2">Aucune analyse sauvegardée.</p>
                        <button type="button" class="btn btn-primary btn-sm" onclick="newAmdecAnalysis()">
                            <i class="fas fa-plus me-1"></i>Créer une nouvelle analyse
                        </button>
                    </div>`;
            } else {
                listContainer.innerHTML = analyses.map((analysis) => `
                    <div class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-3 py-2" style="cursor:pointer;" data-analysis-id="${analysis.id}">
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-center min-w-0">
                                <h6 class="mb-0 text-truncate">${analysis.title || 'Sans titre'}</h6>
                                <small class="text-muted ms-2 flex-shrink-0">${analysis.updatedAt ? new Date(analysis.updatedAt).toLocaleDateString('fr-FR') : new Date(analysis.createdAt).toLocaleDateString('fr-FR')}</small>
                            </div>
                            <p class="mb-0 text-muted small text-truncate">${analysis.subject || 'Analyse AMDEC'}</p>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm flex-shrink-0" title="Supprimer" onclick="deleteAmdecAnalysis(${analysis.id}, event)" aria-label="Supprimer cette analyse">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                `).join('');

                listContainer.querySelectorAll('[data-analysis-id]').forEach((item) => {
                    item.addEventListener('click', async (e) => {
                        if (e.target.closest('button')) return;
                        await loadAmdec(item.dataset.analysisId);
                        await _closeAmdecModal(modalElement);
                    });
                });
            }

            let modalFooter = modalElement.querySelector('.modal-footer');
            if (!modalFooter) {
                modalFooter = document.createElement('div');
                modalFooter.className = 'modal-footer';
                modalFooter.innerHTML = `<button type="button" class="btn btn-primary btn-sm" onclick="newAmdecAnalysis()"><i class="fas fa-plus me-2"></i>Nouvelle analyse</button>`;
                modalElement.querySelector('.modal-content').appendChild(modalFooter);
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();

            await _openAmdecModal(modalElement);
        } catch (error) {
            console.error(error);
            notify(error.message || 'Erreur lors du chargement des analyses.', 'error');
        }
    };

    const autoLoadLatestAmdec = async () => {
        if (!window.amdecAppConfig?.isAuthenticated) {
            return;
        }

        const config = window.amdecAppConfig;
        if (config.analysisId) {
            await loadAmdec(config.analysisId, { silent: true });
            return;
        }

        try {
            const payload = await fetchJson(routes().list);
            const analyses = payload.data || [];
            if (!analyses.length) {
                return;
            }
            await loadAmdec(analyses[0].id, { silent: true });
        } catch (error) {
            console.error('Impossible de charger automatiquement l\'analyse AMDEC :', error);
        }
    };

    const resetAmdecForm = () => {
        state.subject = '';
        state.entries = [createEmptyEntry()];
        if (elements.subject()) {
            elements.subject().value = '';
        }
        renderEntries();
        showResults(false);
        setCurrentAnalysisId(null);
        notify('Formulaire réinitialisé.', 'info');
    };

    const newAmdecAnalysis = () => {
        state.subject = 'Exemple de processus';
        state.entries = defaultEntries.map((entry) => ({ ...entry }));
        if (elements.subject()) {
            elements.subject().value = state.subject;
        }
        renderEntries();
        showResults(false);
        setCurrentAnalysisId(null);
        notify('Modèle AMDEC chargé pour vous guider.', 'info');
    };

    const addAmdecEntry = () => {
        state.entries.push(createEmptyEntry());
        renderEntries();
        notify('Mode de défaillance ajouté.', 'success');
    };

    const updateAmdecSubject = (value) => {
        state.subject = value;
    };

    window.generateAmdecReport = generateAmdecReport;
    window.exportAmdec = exportAmdec;
    window.saveAmdec = saveAmdec;
    window.openAmdecSaved = openAmdecSaved;
    window.deleteAmdecAnalysis = deleteAmdecAnalysis;
    window.addAmdecEntry = addAmdecEntry;
    window.resetAmdecForm = resetAmdecForm;
    window.newAmdecAnalysis = newAmdecAnalysis;
    window.updateAmdecSubject = updateAmdecSubject;

    document.addEventListener('DOMContentLoaded', () => {
        state.subject = '';
        state.entries = defaultEntries.map((entry) => ({ ...entry }));

        if (elements.subject()) {
            elements.subject().value = state.subject;
        }

        renderEntries();
        addListeners();
        showResults(false);

        if (window.amdecAppConfig?.isAuthenticated) {
            autoLoadLatestAmdec();
        }
    });
})();


