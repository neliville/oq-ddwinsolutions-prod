(function () {
    const disciplinesMeta = [
        {
            id: 'd0',
            code: 'D0',
            title: 'Préparer la démarche',
            subtitle: 'Décrire le contexte, les ressources et les parties prenantes impliquées',
            fields: [
                {
                    key: 'preparation',
                    type: 'textarea',
                    label: 'Préparation / Contexte initial',
                    placeholder: 'Pourquoi lancer un 8D ? Contexte, clients impactés, ressources identifiées…',
                },
            ],
        },
        {
            id: 'd1',
            code: 'D1',
            title: 'Former l\'équipe pluridisciplinaire',
            subtitle: 'Définir le pilote et les membres impliqués (production, qualité, méthodes, etc.)',
            fields: [
                {
                    key: 'leader',
                    type: 'text',
                    label: 'Pilote / Responsable du 8D',
                    placeholder: 'Ex : Alice Martin — Responsable Qualité',
                },
            ],
            team: true,
        },
        {
            id: 'd2',
            code: 'D2',
            title: 'Décrire précisément le problème',
            subtitle: 'Utilisez QQOQCCP pour une description factuelle et chiffrée',
            fields: [
                {
                    key: 'description',
                    type: 'textarea',
                    label: 'Description détaillée du problème (QQOQCCP)',
                    placeholder: 'Décrivez « quoi », « où », « quand », « combien », « comment », « qui »…',
                },
                {
                    key: 'evidence',
                    type: 'text',
                    label: 'Preuves disponibles (photos, rapports, plaintes…)',
                    placeholder: 'Référencez les documents de preuve collectés',
                },
            ],
        },
        {
            id: 'd3',
            code: 'D3',
            title: 'Définir les actions de containment',
            subtitle: 'Protéger immédiatement le client en attendant les actions définitives',
            fields: [
                {
                    key: 'actions',
                    type: 'textarea',
                    label: 'Actions de containment immédiates',
                    placeholder: 'Arrêt de production, tri 100%, monitoring renforcé…',
                },
                {
                    key: 'date',
                    type: 'date',
                    label: 'Date de mise en œuvre',
                },
                {
                    key: 'verification',
                    type: 'text',
                    label: 'Efficacité constatée ?',
                    placeholder: 'Ex : Oui – Aucun défaut détecté depuis le 15/06',
                },
            ],
        },
        {
            id: 'd4',
            code: 'D4',
            title: 'Identifier les causes racines',
            subtitle: 'Analysez avec 5 Pourquoi, Ishikawa, Gemba…',
            fields: [
                {
                    key: 'causes',
                    type: 'textarea',
                    label: 'Causes racines identifiées',
                    placeholder: 'Listez les causes déterminées (5M, 5 Pourquoi…) avec preuves associées',
                },
                {
                    key: 'method',
                    type: 'text',
                    label: 'Méthodes d\'analyse utilisées',
                    placeholder: 'Ex : 5 Pourquoi, Ishikawa (5M), étude de capabilité…',
                },
            ],
        },
        {
            id: 'd5',
            code: 'D5',
            title: 'Définir les actions correctives',
            subtitle: 'Actions permanentes ciblant chaque cause racine',
            fields: [
                {
                    key: 'actions',
                    type: 'textarea',
                    label: 'Actions correctives définitives',
                    placeholder: 'Décrivez chaque action, le risque traité et le statut',
                },
                {
                    key: 'responsible',
                    type: 'text',
                    label: 'Responsable & échéance',
                    placeholder: 'Ex : Responsable méthodes – 20/06/2025',
                },
            ],
        },
        {
            id: 'd6',
            code: 'D6',
            title: 'Valider l\'efficacité',
            subtitle: 'Mesurez l\'efficience réelle sur le terrain',
            fields: [
                {
                    key: 'validation',
                    type: 'textarea',
                    label: 'Plan de validation',
                    placeholder: 'Contrôles, audits, indicateurs suivis…',
                },
                {
                    key: 'results',
                    type: 'textarea',
                    label: 'Résultats obtenus',
                    placeholder: 'Taux de défauts, feedback client, audits réalisés…',
                },
            ],
        },
        {
            id: 'd7',
            code: 'D7',
            title: 'Prévenir la récurrence',
            subtitle: 'Standardiser et déployer les bonnes pratiques',
            fields: [
                {
                    key: 'prevention',
                    type: 'textarea',
                    label: 'Actions de prévention / standardisation',
                    placeholder: 'Mise à jour procédures, formation, Yokoten…',
                },
                {
                    key: 'documents',
                    type: 'text',
                    label: 'Documents / référentiels mis à jour',
                    placeholder: 'Ex : Procédure SOP-12, AMDEC Process, plan de surveillance…',
                },
            ],
        },
        {
            id: 'd8',
            code: 'D8',
            title: 'Féliciter et capitaliser',
            subtitle: 'Reconnaître l\'équipe et partager les leçons apprises',
            fields: [
                {
                    key: 'recognition',
                    type: 'textarea',
                    label: 'Reconnaissance / communication',
                    placeholder: 'Réunion de restitution, communication interne, récompenses…',
                },
                {
                    key: 'learning',
                    type: 'textarea',
                    label: 'Leçons apprises / capitalisation',
                    placeholder: 'Ce qui a fonctionné, points de vigilance, plan de transfert…',
                },
            ],
        },
    ];

    const defaultState = {
        title: '',
        description: '',
        openDate: '',
        closeDate: '',
        team: [],
        disciplines: disciplinesMeta.reduce((acc, discipline) => {
            acc[discipline.id] = {};
            discipline.fields.forEach((field) => {
                acc[discipline.id][field.key] = field.type === 'date' ? '' : '';
            });
            return acc;
        }, {}),
    };

    const sampleState = {
        title: '8D-2025-017 - Défauts de collage panneau porte',
        description: 'Client : OEM Europe / Produit : Porte avant droite / Réclamation du 04/02/2025 (lot 2401).',
        openDate: new Date().toISOString().split('T')[0],
        closeDate: '',
        team: [
            { name: 'Alice Martin', role: 'Responsable Qualité', expertise: 'Pilotage 8D' },
            { name: 'Thomas Bernard', role: 'Ingénieur Process', expertise: 'Collage & polymérisation' },
            { name: 'Julie Robert', role: 'Technicienne production', expertise: 'Ligne portes' },
        ],
        disciplines: {
            d0: {
                preparation:
                    'Réclamation client majeure sur défaut adhésif (décollage partiel des garnitures). Impact sécurité valeur modérée, image client critique.',
            },
            d1: {
                leader: 'Alice Martin – Responsable Qualité Programme',
            },
            d2: {
                description:
                    'Depuis le 28/01, 6 portes sur 200 présentent un décollement partiel après tests climatiques.\n- Où : Ligne assemblage portes, poste collage n°5.\n- Quand : Série lot 2401 (production du 27/01 au 31/01).\n- Combien : Taux défaut 3% (objectif 0.1%).\n- Conséquence : Risque bruit d\'air + insatisfaction client.\n- Détection : Tests climatiques interne, contrôle final.',
                evidence: 'Rapport contrôle CF-2025-014, photos défauts, réclamation client #REQ-458, relevé process.',
            },
            d3: {
                actions:
                    '1. Tri 100% des pièces en stock (lot 2401 : 600 pièces).\n2. Blocage expéditions vers client.\n3. Contrôle audit collage toutes les 2h.\n4. Validation visuelle renforcée après polymérisation.',
                date: new Date().toISOString().split('T')[0],
                verification: 'Aucun défaut constaté sur productions postérieures au 02/02 lors des contrôles renforcés.',
            },
            d4: {
                causes:
                    'Analyse 5M + 5 Pourquoi :\n- Matière : Colle fournisseur C-894 lot 2401, viscosité limite mais conforme.\n- Méthode : Temps de pression réduit suite à optimisation cadence (3s au lieu de 5s).\n- Main d\'oeuvre : Formation opérateur OK.\n- Machine : Presses collage n°5 et n°6 montrent baisse pression hydraulique (-15%).\nCause racine : Temps de pression insuffisant + pression presse instable suite maintenance incomplète.',
                method: '5 Pourquoi, Ishikawa, relevé process, audit maintenance, tests laboratoire.',
            },
            d5: {
                actions:
                    '1. Rétablir temps de pression à 5s (02/02).\n2. Remise à niveau hydraulique presses + plan maintenance hebdo (03/02).\n3. Installation capteur pression avec alerte (04/02).\n4. Validation collage sur 50 pièces test (05/02).',
                responsible: 'Responsable Méthodes – Plan terminé le 05/02/2025.',
            },
            d6: {
                validation:
                    'Suivi du taux défaut collage sur 4 semaines, tests climatiques sur échantillon quotidien, audit process hebdomadaire.',
                results:
                    'Taux défaut passé de 3% à 0.05% (objectif <0.1%). Aucun retour client au 15/03. Audits process OK.',
            },
            d7: {
                prevention:
                    'Mise à jour instruction collage (IN-CLG-08), formation opérateurs, ajout contrôle pression automatique, intégration surveillance dans AMDEC Process.',
                documents: 'IN-CLG-08 revC, AMDEC-Process Porte rev4, plan de surveillance QLP-12.',
            },
            d8: {
                recognition:
                    'Réunion de clôture 22/03 avec direction, communication interne, remerciements officiels équipe 8D.',
                learning:
                    'Leçons : Toujours valider cadence vs paramètres process, surveiller pression presses via capteurs, importance monitoring proactif.',
            },
        },
    };

    const state = JSON.parse(JSON.stringify(defaultState));

    const elements = {
        title: () => document.getElementById('eightdTitle'),
        description: () => document.getElementById('eightdContext'),
        openedAt: () => document.getElementById('eightdOpenedAt'),
        closedAt: () => document.getElementById('eightdClosedAt'),
        disciplines: () => document.getElementById('eightdDisciplines'),
        results: () => document.getElementById('eightdResults'),
        progressBar: () => document.getElementById('eightdProgressBar'),
        timeline: () => document.getElementById('eightdTimeline'),
        summaryTitle: () => document.getElementById('eightdSummaryTitle'),
        summaryList: () => document.getElementById('eightdSummaryList'),
        hiddenId: () => document.getElementById('eightdAnalysisId'),
    };

    const routes = () => window.eightDRoutes || {};

    const notify = (message, type = 'info') => {
        if (typeof Toastify === 'undefined') {
            console.log(`[${type}]`, message);
            return;
        }

        const colors = {
            success: 'linear-gradient(to right, #16a34a, #22c55e)',
            error: 'linear-gradient(to right, #ef4444, #dc2626)',
            warning: 'linear-gradient(to right, #f97316, #f59e0b)',
            info: 'linear-gradient(to right, #6366f1, #8b5cf6)',
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

    const deepClone = (value) => JSON.parse(JSON.stringify(value));

    const resetState = () => {
        Object.assign(state, deepClone(defaultState));
    };

    const applyState = (payload) => {
        resetState();

        if (!payload) {
            return;
        }

        state.title = payload.title || '';
        state.description = payload.description || '';
        state.openDate = payload.openDate || '';
        state.closeDate = payload.closeDate || '';
        state.team = Array.isArray(payload.team) ? payload.team.map((member) => ({
            name: member.name || '',
            role: member.role || '',
            expertise: member.expertise || '',
        })) : [];

        if (payload.disciplines) {
            Object.entries(payload.disciplines).forEach(([key, values]) => {
                if (!state.disciplines[key]) {
                    state.disciplines[key] = {};
                }
                Object.assign(state.disciplines[key], values);
            });
        }
    };

    const renderTeamMembers = () => {
        const container = document.querySelector('[data-team-list]');
        if (!container) return;

        if (!state.team.length) {
            state.team.push({ name: '', role: '', expertise: '' });
        }

        container.innerHTML = state.team
            .map(
                (member, index) => `
                <div class="eightd-member-row" data-team-index="${index}">
                    <input type="text" data-team-field="name" placeholder="Nom" value="${member.name ?? ''}">
                    <input type="text" data-team-field="role" placeholder="Fonction" value="${member.role ?? ''}">
                    <input type="text" data-team-field="expertise" placeholder="Expertise" value="${member.expertise ?? ''}">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove" data-team-action="remove" aria-label="Supprimer ce membre">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `
            )
            .join('');
    };

    const renderDisciplines = () => {
        const container = elements.disciplines();
        if (!container) return;

        container.innerHTML = disciplinesMeta
            .map((discipline) => {
                const fieldsHtml = discipline.fields
                    .map((field) => {
                        const value = state.disciplines[discipline.id][field.key] ?? '';
                        const helper = field.helper ? `<span class="helper">${field.helper}</span>` : '';
                        const input =
                            field.type === 'textarea'
                                ? `<textarea id="eightd-${discipline.id}-${field.key}" data-discipline="${discipline.id}" data-field="${field.key}" placeholder="${field.placeholder || ''}">${value}</textarea>`
                                : `<input type="${field.type === 'date' ? 'date' : 'text'}" id="eightd-${discipline.id}-${field.key}" data-discipline="${discipline.id}" data-field="${field.key}" placeholder="${field.placeholder || ''}" value="${value}">`;

                        return `
                            <div class="eightd-form-group">
                                <label for="eightd-${discipline.id}-${field.key}">${field.label}</label>
                                ${helper}
                                ${input}
                            </div>
                        `;
                    })
                    .join('');

                const teamHtml = discipline.team
                    ? `
                        <div class="eightd-form-group">
                            <label>Membres de l'équipe</label>
                            <span class="helper">Précisez nom, fonction et expertise de chaque membre.</span>
                            <div class="eightd-team-list" data-team-list></div>
                            <div class="eightd-add-member-wrapper">
                                <button type="button" class="btn btn-outline-success btn-sm" data-team-action="add">
                                    <i class="fas fa-user-plus me-2"></i>Ajouter un membre
                                </button>
                            </div>
                        </div>
                    `
                    : '';

                return `
                    <section class="eightd-discipline" data-discipline-wrapper="${discipline.id}">
                        <div class="eightd-discipline__header">
                            <div class="eightd-discipline__badge">${discipline.code}</div>
                            <div class="eightd-discipline__title">
                                <h3>${discipline.title}</h3>
                                <p>${discipline.subtitle}</p>
                            </div>
                        </div>
                        ${fieldsHtml}
                        ${teamHtml}
                    </section>
                `;
            })
            .join('');

        renderTeamMembers();
    };

    const updateGeneralInputs = () => {
        if (elements.title()) {
            elements.title().value = state.title;
        }
        if (elements.description()) {
            elements.description().value = state.description;
        }
        if (elements.openedAt()) {
            elements.openedAt().value = state.openDate;
        }
        if (elements.closedAt()) {
            elements.closedAt().value = state.closeDate;
        }
    };

    const renderAll = () => {
        renderDisciplines();
        updateGeneralInputs();
    };

    const updateEightDField = (field, value) => {
        state[field] = value;
    };

    const updateEightDDiscipline = (disciplineId, field, value) => {
        if (!state.disciplines[disciplineId]) {
            state.disciplines[disciplineId] = {};
        }
        state.disciplines[disciplineId][field] = value;
    };

    const addEightDTeamMember = () => {
        state.team.push({ name: '', role: '', expertise: '' });
        renderTeamMembers();
    };

    const removeEightDTeamMember = (index) => {
        if (state.team.length <= 1) {
            notify('L\'équipe doit contenir au moins un membre.', 'warning');
            return;
        }
        state.team.splice(index, 1);
        renderTeamMembers();
    };

    const showResults = (flag) => {
        const wrapper = elements.results();
        if (!wrapper) return;
        wrapper.style.display = flag ? 'block' : 'none';
        if (flag) {
            wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    const flattenText = (text, limit = 220) => {
        if (!text) return '';
        const cleaned = text.replace(/\s+/g, ' ').trim();
        return cleaned.length > limit ? `${cleaned.slice(0, limit)}…` : cleaned;
    };

    const requiredKeys = [
        ['d1', 'leader'],
        ['d2', 'description'],
        ['d3', 'actions'],
        ['d4', 'causes'],
        ['d5', 'actions'],
        ['d6', 'validation'],
        ['d7', 'prevention'],
        ['d8', 'recognition'],
    ];

    const computeProgress = () => {
        const completed = requiredKeys.filter(([discipline, field]) => {
            const value = state.disciplines?.[discipline]?.[field];
            return Boolean(value && value.toString().trim().length);
        }).length;

        return { completed, total: requiredKeys.length };
    };

    const generateEightDReport = () => {
        if (!state.title.trim()) {
            notify('Renseignez au minimum le titre du problème (onglet Informations générales).', 'warning');
            return;
        }

        const { completed, total } = computeProgress();
        const progress = total ? Math.round((completed / total) * 100) : 0;
        const progressBar = elements.progressBar();
        progressBar.style.width = `${progress}%`;
        progressBar.textContent = `${progress}%`;

        const timeline = elements.timeline();
        const summaryList = elements.summaryList();
        const summaryTitle = elements.summaryTitle();

        timeline.innerHTML = '';

        const pushTimeline = (title, content) => {
            if (!content) return;
            const item = document.createElement('div');
            item.className = 'eightd-timeline__item';
            item.innerHTML = `
                <h4>${title}</h4>
                <p>${flattenText(content)}</p>
            `;
            timeline.appendChild(item);
        };

        pushTimeline('D0 – Préparation', state.disciplines.d0.preparation);
        pushTimeline(`D1 – Équipe (${state.team.length} membre(s))`, [
            state.disciplines.d1.leader ? `Pilote : ${state.disciplines.d1.leader}` : '',
            state.team.length ? state.team.map((member) => `${member.name || 'Membre'} – ${member.role || 'Fonction'}`).join(', ') : '',
        ].filter(Boolean).join(' | '));
        pushTimeline('D2 – Description du problème', state.disciplines.d2.description);
        pushTimeline('D3 – Containment', state.disciplines.d3.actions);
        pushTimeline('D4 – Causes racines', state.disciplines.d4.causes);
        pushTimeline('D5 – Actions correctives', state.disciplines.d5.actions);
        pushTimeline('D6 – Validation', state.disciplines.d6.results || state.disciplines.d6.validation);
        pushTimeline('D7 – Prévention / standardisation', state.disciplines.d7.prevention);
        pushTimeline('D8 – Capitalisation', state.disciplines.d8.learning || state.disciplines.d8.recognition);

        summaryTitle.textContent = progress === 100
            ? '🎉 Félicitations ! Votre 8D est complet.'
            : `⚠️ Rapport 8D en cours (${progress}% complété)`;

        const listItems = [
            `<strong>Référence :</strong> ${state.title || 'Non renseignée'}`,
            `<strong>Date d'ouverture :</strong> ${state.openDate ? new Date(state.openDate).toLocaleDateString('fr-FR') : 'Non renseignée'}`,
            `<strong>Date de clôture :</strong> ${state.closeDate ? new Date(state.closeDate).toLocaleDateString('fr-FR') : 'En cours'}`,
            `<strong>Pilote :</strong> ${state.disciplines.d1.leader || 'Non désigné'}`,
            `<strong>Équipe :</strong> ${state.team.length} membre(s)`,
            `<strong>Disciplines complétées :</strong> ${completed}/${total}`,
        ];

        if (state.disciplines.d6.results) {
            listItems.push(`<strong>Résultats :</strong> ${flattenText(state.disciplines.d6.results, 120)}`);
        }

        summaryList.innerHTML = listItems.map((item) => `<li>${item}</li>`).join('');

        showResults(true);
        notify('Rapport 8D généré.', 'success');
    };

    const buildSavePayload = () => ({
        title: state.title || '',
        description: state.description || '',
        openDate: state.openDate || '',
        closeDate: state.closeDate || '',
        team: state.team.map((member) => ({
            name: member.name || '',
            role: member.role || '',
            expertise: member.expertise || '',
        })),
        disciplines: deepClone(state.disciplines),
    });

    const getCurrentAnalysisId = () => {
        const hidden = elements.hiddenId();
        if (hidden && hidden.value) {
            const parsed = parseInt(hidden.value, 10);
            if (!Number.isNaN(parsed)) {
                return parsed;
            }
        }
        return window.eightDAppConfig?.currentAnalysisId ?? null;
    };

    const setCurrentAnalysisId = (id) => {
        const hidden = elements.hiddenId();
        const normalized = id ? Number(id) : null;
        if (hidden) {
            hidden.value = normalized ? String(normalized) : '';
        }
        const config = window.eightDAppConfig || {};
        config.currentAnalysisId = normalized;
        config.analysisId = normalized;
        window.eightDAppConfig = config;
    };

    const saveEightD = async () => {
        if (!window.eightDAppConfig?.isAuthenticated) {
            notify('Connectez-vous pour sauvegarder vos analyses 8D.', 'warning');
            const loginRoute = routes().login || '/login';
            window.location.href = loginRoute;
            return;
        }

        if (!state.title.trim()) {
            notify('Ajoutez un titre pour votre rapport 8D avant de sauvegarder.', 'warning');
            return;
        }

        const payload = buildSavePayload();
        const body = {
            title: payload.title || `Analyse 8D - ${new Date().toLocaleDateString('fr-FR')}`,
            description: payload.description || null,
            content: payload,
        };

        const existingId = getCurrentAnalysisId();
        if (existingId) {
            body.id = existingId;
        }

        try {
            const response = await fetch(routes().save, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Erreur lors de la sauvegarde');
            }

            if (data.data?.id) {
                setCurrentAnalysisId(data.data.id);
            }

            notify(data.message || 'Analyse 8D sauvegardée.', 'success');
        } catch (error) {
            console.error(error);
            notify(`Erreur lors de la sauvegarde : ${error.message}`, 'error');
        }
    };

    const exportEightD = (format) => {
        if (!state.title.trim()) {
            notify('Renseignez le titre de l\'analyse 8D avant d\'exporter.', 'warning');
            return;
        }

        const payload = buildSavePayload();
        const { completed, total } = computeProgress();
        const progress = total ? Math.round((completed / total) * 100) : 0;
        const teamCount = state.team.length || 0;
        const leader = state.disciplines?.d1?.leader || '';
        const exportDate = new Date();
        const exportLocale = exportDate.toLocaleString('fr-FR');
        const titleText = state.title.trim();
        const descriptionText = payload.description
            ? payload.description.trim()
            : 'Rapport de résolution de problème selon la méthode 8D.';

        if (format === 'json') {
            const jsonPayload = {
                metadata: {
                    title: titleText,
                    generatedAt: exportDate.toISOString(),
                    exportLocale,
                    tool: 'Méthode 8D',
                    version: '1.0',
                    source: 'OUTILS-QUALITÉ',
                },
                analysis: {
                    progress,
                    disciplinesCompleted: completed,
                    disciplinesTotal: total,
                    teamCount,
                    leader,
                    openedAt: payload.openDate || null,
                    closedAt: payload.closeDate || null,
                    summary: descriptionText,
                    content: payload,
                },
            };

            const blob = new Blob([JSON.stringify(jsonPayload, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `analyse-8d-${Date.now()}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            notify('Export JSON généré.', 'success');
            if (typeof window.trackExport === 'function') {
                window.trackExport('methode-8d', 'JSON', { progress, teamCount, leader: leader || 'N/A' });
            }
            return;
        }

        if (elements.results().style.display === 'none') {
            generateEightDReport();
        }

        const summaryLines = [
            `Progression : ${progress}% (${completed}/${total} disciplines complétées)`,
            `Équipe : ${teamCount} membre(s) · Pilote : ${leader || 'Non renseigné'}`,
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
            ctx.fillText(descriptionText.substring(0, 160), finalCanvas.width / 2, headerHeight - 2);

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

            html2canvas(elements.results(), { scale: 2 })
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
                        pdf.save(`analyse-8d-${Date.now()}.pdf`);
                        notify('Export PDF généré.', 'success');
                        if (typeof window.trackExport === 'function') {
                            window.trackExport('methode-8d', 'PDF', { progress, teamCount, leader: leader || 'N/A' });
                        }
                    } else {
                        const mime = format === 'jpeg' ? 'image/jpeg' : 'image/png';
                        const dataUrl = exportCanvas.toDataURL(mime, 0.95);
                        const link = document.createElement('a');
                        link.href = dataUrl;
                        link.download = `analyse-8d-${Date.now()}.${format === 'jpeg' ? 'jpg' : 'png'}`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        notify(`Export ${format === 'jpeg' ? 'JPEG' : 'PNG'} généré.`, 'success');
                        if (typeof window.trackExport === 'function') {
                            window.trackExport('methode-8d', format.toUpperCase(), { progress, teamCount, leader: leader || 'N/A' });
                        }
                    }
                })
                .catch((error) => {
                    console.error(error);
                    notify('Erreur lors de la génération de l\'export.', 'error');
                });
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

    const loadEightD = async (id, { silent = false } = {}) => {
        try {
            const requestUrl = routes().get.replace(/0$/, String(id));
            const payload = await fetchJson(requestUrl);

            if (!payload.success || !payload.data) {
                throw new Error(payload.message || 'Analyse 8D introuvable.');
            }

            const content = payload.data.content || {};
            applyState({
                title: content.title ?? payload.data.title ?? '',
                description: content.description ?? payload.data.description ?? '',
                openDate: content.openDate ?? '',
                closeDate: content.closeDate ?? '',
                team: content.team ?? [],
                disciplines: content.disciplines ?? {},
            });

            renderAll();
            setCurrentAnalysisId(payload.data.id);
            showResults(false);

            if (!silent) {
                notify('Analyse 8D chargée.', 'success');
            }

            return payload.data;
        } catch (error) {
            console.error(error);
            if (!silent) {
                notify(error.message || 'Erreur lors du chargement de l\'analyse 8D.', 'error');
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

    const _openEightDModal = async (modalElement) => {
        let ctrl = null;
        if (window.Stimulus && typeof window.Stimulus.getControllerForElementAndIdentifier === 'function') {
            try { ctrl = window.Stimulus.getControllerForElementAndIdentifier(modalElement, 'bootstrap-modal'); } catch (e) {}
        }
        if (ctrl && typeof ctrl.show === 'function') { ctrl.show(); return; }
        const bsLib = window.bootstrap || await new Promise(r => { if (window.bootstrap) r(window.bootstrap); else setTimeout(() => r(window.bootstrap), 100); });
        if (bsLib?.Modal) new bsLib.Modal(modalElement).show();
    };

    const _closeEightDModal = async (modalElement) => {
        let ctrl = null;
        if (window.Stimulus && typeof window.Stimulus.getControllerForElementAndIdentifier === 'function') {
            try { ctrl = window.Stimulus.getControllerForElementAndIdentifier(modalElement, 'bootstrap-modal'); } catch (e) {}
        }
        if (ctrl && typeof ctrl.hide === 'function') { ctrl.hide(); return; }
        const bsLib = window.bootstrap;
        if (bsLib?.Modal?.getInstance) bsLib.Modal.getInstance(modalElement)?.hide();
    };

    const deleteEightDAnalysis = async (id, event) => {
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
            openEightDSaved();
        } catch (error) {
            notify('Erreur : ' + error.message, 'error');
        }
    };

    const openEightDSaved = async () => {
        if (!window.eightDAppConfig?.isAuthenticated) {
            notify('Connectez-vous pour accéder à vos analyses 8D.', 'warning');
            return;
        }

        try {
            const payload = await fetchJson(routes().list);
            const analyses = payload.data || [];

            const modalElement = document.getElementById('eightDLoadModal');
            if (!modalElement) {
                notify('Le modal n\'est pas disponible.', 'error');
                return;
            }

            const listContainer = modalElement.querySelector('#eightDAnalysesList');
            if (!listContainer) return;

            if (!analyses.length) {
                listContainer.innerHTML = `
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                        <p class="mb-2">Aucune analyse sauvegardée.</p>
                        <button type="button" class="btn btn-primary btn-sm" onclick="newEightDAnalysis()">
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
                            <p class="mb-0 text-muted small text-truncate">${analysis.description || 'Analyse 8D'}</p>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm flex-shrink-0" title="Supprimer" onclick="deleteEightDAnalysis(${analysis.id}, event)" aria-label="Supprimer cette analyse">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                `).join('');

                listContainer.querySelectorAll('[data-analysis-id]').forEach((item) => {
                    item.addEventListener('click', async (e) => {
                        if (e.target.closest('button')) return;
                        await loadEightD(item.dataset.analysisId);
                        await _closeEightDModal(modalElement);
                    });
                });
            }

            let modalFooter = modalElement.querySelector('.modal-footer');
            if (!modalFooter) {
                modalFooter = document.createElement('div');
                modalFooter.className = 'modal-footer';
                modalFooter.innerHTML = `<button type="button" class="btn btn-primary btn-sm" onclick="newEightDAnalysis()"><i class="fas fa-plus me-2"></i>Nouvelle analyse</button>`;
                modalElement.querySelector('.modal-content').appendChild(modalFooter);
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();

            await _openEightDModal(modalElement);
        } catch (error) {
            console.error(error);
            notify(error.message || 'Erreur lors du chargement des analyses.', 'error');
        }
    };

    const autoLoadLatestEightD = async () => {
        if (!window.eightDAppConfig?.isAuthenticated) {
            return;
        }

        const config = window.eightDAppConfig;
        if (config.analysisId) {
            await loadEightD(config.analysisId, { silent: true });
            return;
        }

        try {
            const payload = await fetchJson(routes().list);
            const analyses = payload.data || [];
            if (!analyses.length) {
                return;
            }
            await loadEightD(analyses[0].id, { silent: true });
        } catch (error) {
            console.error('Impossible de charger automatiquement l\'analyse 8D :', error);
        }
    };

    const resetEightDForm = () => {
        resetState();
        renderAll();
        showResults(false);
        setCurrentAnalysisId(null);
        notify('Formulaire 8D réinitialisé.', 'info');
    };

    const newEightDAnalysis = () => {
        applyState(sampleState);
        renderAll();
        showResults(false);
        setCurrentAnalysisId(null);
        notify('Exemple 8D chargé pour vous guider.', 'info');
    };

    const handleGeneralInput = (event) => {
        const target = event.target;
        if (target.matches('#eightdTitle')) {
            updateEightDField('title', target.value);
        } else if (target.matches('#eightdContext')) {
            updateEightDField('description', target.value);
        } else if (target.matches('#eightdOpenedAt')) {
            updateEightDField('openDate', target.value);
        } else if (target.matches('#eightdClosedAt')) {
            updateEightDField('closeDate', target.value);
        }
    };

    const handleDisciplineInput = (event) => {
        const target = event.target;
        const disciplineId = target.dataset.discipline;
        const field = target.dataset.field;

        if (disciplineId && field) {
            updateEightDDiscipline(disciplineId, field, target.value);
        }

        const action = target.dataset.teamAction;
        if (action === 'add') {
            addEightDTeamMember();
        }
    };

    const handleTeamInteraction = (event) => {
        const target = event.target;
        const action = target.dataset.teamAction;
        const wrapper = target.closest('[data-team-index]');

        if (action === 'add') {
            event.preventDefault();
            addEightDTeamMember();
            return;
        }

        if (action === 'remove' && wrapper) {
            const index = Number(wrapper.dataset.teamIndex);
            removeEightDTeamMember(index);
            return;
        }

        const field = target.dataset.teamField;
        if (wrapper && field) {
            const index = Number(wrapper.dataset.teamIndex);
            state.team[index][field] = target.value;
        }
    };

    window.generateEightDReport = generateEightDReport;
    window.exportEightD = exportEightD;
    window.saveEightD = saveEightD;
    window.openEightDSaved = openEightDSaved;
    window.deleteEightDAnalysis = deleteEightDAnalysis;
    window.resetEightDForm = resetEightDForm;
    window.newEightDAnalysis = newEightDAnalysis;
    window.updateEightDField = updateEightDField;

    document.addEventListener('DOMContentLoaded', () => {
        renderAll();
        showResults(false);

        document.addEventListener('input', handleGeneralInput);
        document.addEventListener('input', handleDisciplineInput);
        document.addEventListener('click', handleDisciplineInput);
        document.addEventListener('input', handleTeamInteraction);
        document.addEventListener('click', handleTeamInteraction);

        if (window.eightDAppConfig?.isAuthenticated) {
            autoLoadLatestEightD();
        } else {
            // Charger exemple par défaut pour guider l'utilisateur
            applyState(sampleState);
            renderAll();
            showResults(false);
        }
    });
})();


