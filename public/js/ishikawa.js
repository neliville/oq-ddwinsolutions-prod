// Isolation pour éviter les conflits avec Turbo
(function() {
    'use strict';
    
    const isReadonlyMode = window.ishikawaReadonly === true;
    // Variables globales
    let canvas, ctx, problemInput;
    let currentCategory = null;
    let currentCauseIndex = null;
    let isDragging = false;
    let draggedCategory = null;
    let draggedCause = null; // Nouvelle variable pour les causes
    let draggedCauseCategory = null; // Catégorie de la cause draggée
    let dragOffset = { x: 0, y: 0 };
    let selectedColor = '#FF6B6B';
    let hasShownDragHint = false;
    let currentToast = null;

    // Fonction pour afficher les notifications
    function showNotification(message, type = "success") {
        const Toastify = window.Toastify;
        if (typeof Toastify !== "undefined") {
            if (currentToast) {
                currentToast.hideToast();
                currentToast = null;
            }

            const backgroundColor = type === "success"
                ? "#2ecc71"
                : type === "error"
                    ? "#e74c3c"
                    : type === "warning"
                        ? "#f39c12"
                        : "#3498db";

            currentToast = Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor,
                stopOnFocus: true,
                callback: function () {
                    currentToast = null;
                }
            });

            currentToast.showToast();
        } else {
            alert(message);
        }
    }

    const predefinedColors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8',
        '#F06292', '#BA68C8', '#FFD54F', '#81C784', '#64B5F6',
        '#FF8A65', '#A1887F', '#90A4AE', '#4DB6AC', '#AED581'
    ];

    const defaultCategories = [
        {
            name: "Méthodes",
            color: "#FF6B6B",
            spineX: 296.71773738103036,
            angle: 115.53542876905375,
            branchLength: 295.2640601640316,
            causes: [
                { text: "Instructions peu claires", customPosition: { x: 326, y: 728 } },
                { text: "Manque de standardisation", customPosition: { x: 120, y: 567 } },
                { text: "Procédures inadéquates", customPosition: { x: 110, y: 671 } }
            ]
        },
        {
            name: "Milieu",
            color: "#4ECDC4",
            spineX: 792.9873272864937,
            angle: -115.6199376388967,
            branchLength: 320.45377430662285,
            causes: [
                { text: "Éclairage insuffisant", customPosition: { x: 812, y: 287 } },
                { text: "Température inadéquate", customPosition: { x: 559, y: 297 } },
                { text: "Conditions de travail", customPosition: { x: 651, y: 447 } }
            ]
        },
        {
            name: "Management",
            color: "#BA68C8",
            spineX: 1102.6497546515538,
            angle: 119.65443149782831,
            branchLength: 265.8964489849053,
            causes: [
                { text: "Ressources inadéquates", customPosition: { x: 957, y: 536 } },
                { text: "Planification insuffisante", customPosition: { x: 887, y: 609 } },
                { text: "Communication défaillante", customPosition: { x: 1128, y: 679 } }
            ]
        },
        {
            name: "Matériels",
            color: "#FFA07A",
            spineX: 318.7749349570928,
            angle: -113.55238658293477,
            branchLength: 318.52662210048305,
            causes: [
                { text: "Qualité des matières premières", customPosition: { x: 115, y: 314 } },
                { text: "Stockage inadéquat", customPosition: { x: 409, y: 374 } },
                { text: "Spécifications non conformes", customPosition: { x: 152, y: 432 } }
            ]
        },
        {
            name: "Mesure",
            color: "#45B7D1",
            spineX: 734.9263212971041,
            angle: 119.79802725429408,
            branchLength: 278.832306102103,
            causes: [
                { text: "Précision inadéquate", customPosition: { x: 561, y: 552 } },
                { text: "Instruments de mesure défaillants", customPosition: { x: 480, y: 626 } }
            ]
        },
        {
            name: "Machines",
            color: "#64B5F6",
            spineX: 1118.0588435717127,
            angle: -116.21114244166952,
            branchLength: 297.8556120510303,
            causes: [
                { text: "Usure prématurée", customPosition: { x: 1006, y: 399 } },
                { text: "Équipement défaillant", customPosition: { x: 1206, y: 279 } },
                { text: "Maintenance préventive insuffisante", customPosition: { x: 1237, y: 352 } }
            ]
        }
    ];

    const cloneDefaultCategories = () => {
        return defaultCategories.map(cat => ({
            name: cat.name,
            color: cat.color,
            spineX: cat.spineX,
            angle: cat.angle,
            branchLength: cat.branchLength,
            causes: (cat.causes || []).map(cause =>
                typeof cause === 'string' ? { text: cause } : { ...cause }
            )
        }));
    };

    let categories = cloneDefaultCategories();
    let pendingDiagramData = null;
    let pendingProblemText = null;
    let isInitialized = false;
    
    function init() {
         // Éviter les doubles initialisations
         if (isInitialized) {
             console.log('Ishikawa: Déjà initialisé, réinitialisation...');
             // Réinitialiser quand même pour gérer les cas de navigation Turbo
             isInitialized = false;
         }
         
         console.log('Ishikawa: Initialisation...');
        if (window.ishikawaApp) {
            window.ishikawaApp.isReady = false;
        }
         
         canvas = document.getElementById('diagramCanvas');
         problemInput = document.getElementById('problemInput');
         
         if (!canvas) {
             console.error('Ishikawa: Canvas not found');
             return;
         }
         
         // Vérifier si le canvas est déjà initialisé
         if (canvas.dataset.ishikawaInitialized === 'true') {
             console.log('Ishikawa: Canvas déjà initialisé, réinitialisation...');
             // Réinitialiser quand même pour gérer les cas de navigation Turbo
         }
         
         console.log('Ishikawa: Canvas trouvé', canvas);
         
         ctx = canvas.getContext('2d');
         
         if (!ctx) {
             console.error('Ishikawa: Impossible de récupérer le contexte 2D');
             return;
         }
         
         // Marquer le canvas comme initialisé
         canvas.dataset.ishikawaInitialized = 'true';
         
         // Redimensionner le canvas au chargement
         resizeCanvas();
         
         // Redimensionner le canvas lors du redimensionnement de la fenêtre
         // Retirer l'ancien listener s'il existe pour éviter les doublons
         window.removeEventListener('resize', resizeCanvas);
         window.addEventListener('resize', resizeCanvas);
         
         setupCanvasEvents();
         
         if (problemInput) {
             // Retirer l'ancien listener s'il existe pour éviter les doublons
             problemInput.removeEventListener('input', drawDiagram);
             problemInput.addEventListener('input', drawDiagram);
             // S'assurer que le texte est visible
             if (problemInput.value === 'Problème à résoudre') {
                 problemInput.style.color = 'var(--dark-color, #1e293b)';
             }
         }
        
        const appliedPending = applyPendingDiagramData();
        console.log('Ishikawa: Catégories initiales', categories.length);
        if (!appliedPending) {
            updateCategoriesList();
            drawDiagram();
        }
        
        isInitialized = true;
        if (window.ishikawaApp) {
            window.ishikawaApp.isReady = true;
        }
        window.dispatchEvent(new CustomEvent('ishikawa:ready', {
            detail: {
                categories: categories.length
            }
        }));
        console.log('Ishikawa: Initialisation terminée');
    }

    // Gestion d'initialisation avec reprise automatique
    let initAttempts = 0;
    const maxInitAttempts = 50; // Maximum 5 secondes (50 * 100ms)
    
    function tryInit() {
        const canvas = document.getElementById('diagramCanvas');
        if (canvas) {
            console.log('Ishikawa: Canvas trouvé, initialisation...');
            init();
            initAttempts = 0; // Réinitialiser le compteur en cas de succès
        } else {
            initAttempts++;
            if (initAttempts < maxInitAttempts) {
                console.warn(`Ishikawa: Canvas non trouvé, tentative ${initAttempts}/${maxInitAttempts}...`);
                setTimeout(tryInit, 100);
            } else {
                console.error('Ishikawa: Canvas non trouvé après plusieurs tentatives. Vérifiez que le script est chargé après le DOM.');
            }
        }
    }
    
    function initializeApp() {
        // Réinitialiser le compteur à chaque tentative
        initAttempts = 0;
        
        // Attendre un peu pour s'assurer que le DOM est complètement prêt
        if (document.readyState === 'loading') {
            // Le DOM est encore en cours de chargement
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(tryInit, 50);
            });
        } else if (document.readyState === 'interactive' || document.readyState === 'complete') {
            // Le DOM est déjà chargé ou presque
            setTimeout(tryInit, 50);
        } else {
            // Fallback : essayer immédiatement
            tryInit();
        }
    }

    function sanitizeDiagramData(diagramData = {}) {
        const sanitized = {
            problem: typeof diagramData.problem === 'string' ? diagramData.problem : '',
            categories: []
        };

        const rawCategories = Array.isArray(diagramData.categories) ? diagramData.categories : [];

        rawCategories.forEach((rawCategory, index) => {
            if (!rawCategory || typeof rawCategory !== 'object') {
                return;
            }

            const fallback = defaultCategories[index] || {};

            const parseNumber = (value, fallbackValue) => {
                const numeric = Number(value);
                return Number.isFinite(numeric) ? numeric : fallbackValue;
            };

            const category = {
                name: typeof rawCategory.name === 'string' && rawCategory.name.trim() !== ''
                    ? rawCategory.name.trim()
                    : (fallback.name || `Catégorie ${index + 1}`),
                color: typeof rawCategory.color === 'string' && rawCategory.color.trim() !== ''
                    ? rawCategory.color
                    : (fallback.color || predefinedColors[index % predefinedColors.length]),
                spineX: parseNumber(rawCategory.spineX, fallback.spineX ?? (200 + index * 140)),
                angle: parseNumber(rawCategory.angle, fallback.angle ?? (index % 2 === 0 ? 150 : -150)),
                branchLength: parseNumber(rawCategory.branchLength, fallback.branchLength ?? 150),
                causes: []
            };

            const rawCauses = Array.isArray(rawCategory.causes) ? rawCategory.causes : [];

            rawCauses.forEach((rawCause) => {
                if (rawCause === null || rawCause === undefined) {
                    return;
                }

                let text = '';
                let customPosition = null;

                if (typeof rawCause === 'string') {
                    text = rawCause;
                } else if (typeof rawCause === 'object') {
                    if (typeof rawCause.text === 'string') {
                        text = rawCause.text;
                    } else if (typeof rawCause.label === 'string') {
                        text = rawCause.label;
                    } else if (typeof rawCause.description === 'string') {
                        text = rawCause.description;
                    } else if (typeof rawCause.name === 'string') {
                        text = rawCause.name;
                    } else if (typeof rawCause.data === 'string') {
                        text = rawCause.data;
                    }

                    if (rawCause.customPosition && typeof rawCause.customPosition === 'object') {
                        const posX = Number(rawCause.customPosition.x);
                        const posY = Number(rawCause.customPosition.y);
                        if (Number.isFinite(posX) && Number.isFinite(posY)) {
                            customPosition = { x: posX, y: posY };
                        }
                    }
                }

                category.causes.push({
                    text,
                    customPosition
                });
            });

            sanitized.categories.push(category);
        });

        if (sanitized.categories.length === 0) {
            sanitized.categories = cloneDefaultCategories();
        }

        return sanitized;
    }

    function applyDiagramData(diagramData) {
        if (!diagramData || !Array.isArray(diagramData.categories)) {
            return;
        }

        categories = diagramData.categories.map(category => ({
            name: category.name,
            color: category.color,
            spineX: Number.isFinite(category.spineX) ? category.spineX : 200,
            angle: Number.isFinite(category.angle) ? category.angle : 150,
            branchLength: Number.isFinite(category.branchLength) ? category.branchLength : 150,
            causes: (category.causes || []).map(cause => ({
                text: cause.text ?? '',
                customPosition: cause.customPosition && Number.isFinite(cause.customPosition.x) && Number.isFinite(cause.customPosition.y)
                    ? { x: cause.customPosition.x, y: cause.customPosition.y }
                    : null
            }))
        }));

        if (problemInput) {
            problemInput.value = diagramData.problem || '';
        } else {
            pendingProblemText = diagramData.problem || '';
        }

        pendingProblemText = null;

        updateCategoriesList();
        drawDiagram();
    }

    function applyPendingDiagramData() {
        if (problemInput && typeof pendingProblemText === 'string') {
            problemInput.value = pendingProblemText;
            pendingProblemText = null;
        }

        if (pendingDiagramData) {
            const dataToApply = pendingDiagramData;
            pendingDiagramData = null;
            applyDiagramData(dataToApply);
            return true;
        }

        return false;
    }

    function loadDiagramData(diagramData) {
        const sanitizedData = sanitizeDiagramData(diagramData || {});
        pendingDiagramData = sanitizedData;
        pendingProblemText = sanitizedData.problem;

        if (isInitialized && canvas && ctx) {
            const dataToApply = pendingDiagramData;
            pendingDiagramData = null;
            applyDiagramData(dataToApply);
        }
    }

    async function requestConfirmation(options = {}, fallbackMessage = 'Êtes-vous sûr de vouloir continuer ?') {
        // Essayer d'abord avec le modal Bootstrap (si disponible)
        const modalElement = document.getElementById('globalConfirmationModal');
        if (modalElement && window.Stimulus) {
            try {
                const title = options.title || 'Confirmation';
                const message = options.message || fallbackMessage;
                const confirmText = options.confirmText || 'Confirmer';
                const cancelText = options.cancelText || 'Annuler';
                const confirmClass = options.type === 'danger' ? 'btn-danger' : options.type === 'warning' ? 'btn-warning' : 'btn-primary';

                // Mettre à jour le modal
                const messageElement = modalElement.querySelector('[data-confirmation-modal-target="message"]');
                const titleElement = modalElement.querySelector('.modal-title');
                const confirmButton = modalElement.querySelector('button[data-action*="onConfirmed"]');
                const cancelButton = modalElement.querySelector('button.btn-secondary');

                if (messageElement) messageElement.textContent = message;
                if (titleElement) {
                    const icon = titleElement.querySelector('i');
                    titleElement.innerHTML = '';
                    if (icon) titleElement.appendChild(icon);
                    titleElement.appendChild(document.createTextNode(' ' + title));
                }
                if (confirmButton) {
                    confirmButton.textContent = confirmText;
                    confirmButton.className = `btn ${confirmClass}`;
                }
                if (cancelButton) {
                    cancelButton.textContent = cancelText;
                }

                // Stocker la fonction resolve
                return new Promise((resolve) => {
                    const resolveId = 'confirmResolve_' + Date.now();
                    window[resolveId] = resolve;
                    modalElement.dataset.confirmPromiseResolve = resolveId;

                    // Réinitialiser les icônes Lucide
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }

                    // Ouvrir le modal
                    const modalController = window.Stimulus.getControllerForElementAndIdentifier(modalElement, 'bootstrap-modal');
                    if (modalController && typeof modalController.show === 'function') {
                        modalController.show();
                    } else {
                        const bootstrapLib = window.bootstrap;
                        if (bootstrapLib?.Modal) {
                            const modalInstance = new bootstrapLib.Modal(modalElement);
                            modalInstance.show();
                        } else {
                            resolve(window.confirm(message));
                        }
                    }
                });
            } catch (error) {
                console.error('Ishikawa: erreur lors de l\'ouverture du modal de confirmation', error);
            }
        }

        // Fallback vers la fonction globale de main.js
        if (typeof window.showConfirmationModal === 'function') {
            try {
                return await window.showConfirmationModal(options);
            } catch (error) {
                console.error('Ishikawa: erreur lors de l\'ouverture du modal de confirmation', error);
            }
        }

        // Dernier fallback vers confirm() natif
        return window.confirm(options.message || fallbackMessage);
    }

    function ensureExportLibraries({ pdf = false } = {}) {
        if (typeof window.html2canvas !== 'function') {
            throw new Error('La bibliothèque html2canvas n’est pas disponible.');
        }

        if (pdf) {
            const jsPDFCtor = window.jspdf?.jsPDF || window.jspdf?.jspdf?.jsPDF;
            if (typeof jsPDFCtor !== 'function') {
                throw new Error('La bibliothèque jsPDF n’est pas disponible.');
            }
        }
    }

    // Fonction pour redimensionner le canvas de manière responsive
    function resizeCanvas() {
        if (!canvas) return;
        
        const container = canvas.parentElement;
        if (!container) return;
        
        const styles = window.getComputedStyle(container);
        let width = Math.floor(container.clientWidth);
        let height = Math.floor(container.clientHeight);
        
        if (!width || width < 320) {
            width = Math.floor(container.getBoundingClientRect().width) || 320;
        }
        if (!height || height < 300) {
            const minHeight = parseFloat(styles.minHeight);
            if (!isNaN(minHeight) && minHeight > 0) {
                height = Math.floor(minHeight);
            } else {
                height = Math.floor(container.getBoundingClientRect().height);
                if (!height || height < 300) {
                    height = Math.max(400, Math.floor(width * 0.6));
                }
            }
        }
        
        canvas.width = width;
        canvas.height = height;
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        drawDiagram();
    }

    function drawDiagram() {
        if (!canvas || !ctx) {
            console.error('Ishikawa: Canvas ou contexte non disponible pour le dessin');
            return;
        }
        
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const centerY = canvas.height / 2;
        const startX = 100;
        const canvasWidth = canvas.width;
        
        // Dimensions du rectangle du problème adaptatives
        const problemRectWidth = Math.max(220, Math.min(360, canvasWidth * 0.25));
        const problemRectHeight = Math.max(70, Math.min(100, problemRectWidth * 0.28 + 35));
        const problemMarginRight = Math.max(30, canvasWidth * 0.04);
        
        // Position du rectangle du problème
        const problemRectX = canvasWidth - problemRectWidth - problemMarginRight;
        const problemRectY = centerY - problemRectHeight / 2;

        // L'épine se termine à la gauche du rectangle du problème
        const endX = problemRectX;
        // Le centre du rectangle du problème pour le texte
        const problemTextCenterX = problemRectX + problemRectWidth / 2;
        const problemTextCenterY = centerY;

        // Flèche principale (colonne vertébrale)
        ctx.strokeStyle = '#1f2937';
        ctx.lineWidth = 5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.beginPath();
        ctx.moveTo(startX, centerY);
        ctx.lineTo(endX, centerY);
        ctx.stroke();

        // Pointe de flèche
        ctx.beginPath();
        ctx.moveTo(endX, centerY);
        ctx.lineTo(endX - 18, centerY - 12);
        ctx.lineTo(endX - 18, centerY + 12);
        ctx.closePath();
        ctx.fillStyle = '#1f2937';
        ctx.fill();

        // Rectangle du problème (tête de poisson) - Version améliorée
        const problemText = problemInput ? (problemInput.value || "Problème") : "Problème";
        
        // Ombre du rectangle - plus prononcée
        ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
        ctx.shadowBlur = 20;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 8;

        // Dégradé pour le rectangle - rouge
        const gradient = ctx.createLinearGradient(problemRectX, problemRectY, problemRectX + problemRectWidth, problemRectY + problemRectHeight);
        gradient.addColorStop(0, '#ef4444'); // Rouge clair
        gradient.addColorStop(0.5, '#dc2626'); // Rouge moyen
        gradient.addColorStop(1, '#b91c1c'); // Rouge foncé
        
        // Dessiner le rectangle avec coins arrondis
        ctx.fillStyle = gradient;
        roundRect(ctx, problemRectX, problemRectY, problemRectWidth, problemRectHeight, 12);
        ctx.fill();

        // Retirer l'ombre pour le contour
        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 0;

        // Contour du rectangle - plus épais et visible
        ctx.strokeStyle = '#991b1b';
        ctx.lineWidth = 4;
        roundRect(ctx, problemRectX, problemRectY, problemRectWidth, problemRectHeight, 12);
        ctx.stroke();

        // Texte dans le rectangle - adaptatif
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        
        const words = problemText.trim().split(/\s+/);
        let fontSize = Math.min(18, Math.max(12, problemRectHeight * 0.24));
        const minFontSize = 10;
        const maxTextWidth = problemRectWidth - 48;
        let lines = [];
        let lineHeight = fontSize * 1.35;
        let fits = false;
        
        while (!fits && fontSize >= minFontSize) {
            ctx.font = `bold ${fontSize}px Inter, sans-serif`;
            lineHeight = fontSize * 1.35;
            const maxLines = Math.max(1, Math.floor(problemRectHeight / lineHeight));
            lines = [];
            let currentLine = '';
            for (let i = 0; i < words.length; i++) {
                const testLine = currentLine ? `${currentLine} ${words[i]}` : words[i];
                if (ctx.measureText(testLine).width > maxTextWidth && currentLine) {
                    lines.push(currentLine);
                    currentLine = words[i];
                } else {
                    currentLine = testLine;
                }
            }
            if (currentLine) {
                lines.push(currentLine);
            }
            if (lines.length <= maxLines) {
                fits = true;
  } else {
                fontSize -= 1;
            }
        }
        
        if (!fits) {
            ctx.font = `bold ${fontSize}px Inter, sans-serif`;
            lineHeight = fontSize * 1.35;
            const maxLines = Math.max(1, Math.floor(problemRectHeight / lineHeight));
            lines = lines.slice(0, maxLines);
            if (lines.length === maxLines) {
                const last = lines[maxLines - 1];
                if (last) {
                    lines[maxLines - 1] = last.length > 2 ? `${last.slice(0, -2)}…` : `${last}…`;
                }
            }
        }
        
        ctx.shadowColor = 'rgba(0, 0, 0, 0.45)';
        ctx.shadowBlur = 3;
        ctx.shadowOffsetX = 1;
        ctx.shadowOffsetY = 2;
        ctx.fillStyle = '#ffffff';
        
        const totalHeight = lines.length * lineHeight;
        let startY = problemTextCenterY - totalHeight / 2 + lineHeight / 2;
        lines.forEach((line, idx) => {
            if (startY + idx * lineHeight <= problemRectY + problemRectHeight - lineHeight / 2) {
                ctx.fillText(line, problemTextCenterX, startY + idx * lineHeight);
            }
        });
        
        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 0;
        
        // Stocker la zone cliquable du rectangle pour l'interaction
        window.ishikawaApp.problemRectArea = {
            x: problemRectX,
            y: problemRectY,
            width: problemRectWidth,
            height: problemRectHeight,
            centerX: problemTextCenterX,
            centerY: problemTextCenterY
        };

        // Dessiner chaque catégorie
        categories.forEach((category, index) => {
            drawFishboneCategory(category, centerY);
        });
    }

    function roundRect(ctx, x, y, width, height, radius) {
        ctx.beginPath();
        ctx.moveTo(x + radius, y);
        ctx.lineTo(x + width - radius, y);
        ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
        ctx.lineTo(x + width, y + height - radius);
        ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
        ctx.lineTo(x + radius, y + height);
        ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
        ctx.lineTo(x, y + radius);
        ctx.quadraticCurveTo(x, y, x + radius, y);
        ctx.closePath();
    }

    function drawFishboneCategory(category, spineY) {
        const spineX = category.spineX;
        const angleRad = (category.angle * Math.PI) / 180;
        const endX = spineX + category.branchLength * Math.cos(angleRad);
        const endY = spineY + category.branchLength * Math.sin(angleRad);

        ctx.shadowColor = 'rgba(0, 0, 0, 0.1)';
        ctx.shadowBlur = 8;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 3;

        const gradient = ctx.createLinearGradient(spineX, spineY, endX, endY);
        gradient.addColorStop(0, category.color);
        gradient.addColorStop(1, adjustColorBrightness(category.color, -20));
        
        ctx.strokeStyle = gradient;
        ctx.lineWidth = 5;
        ctx.lineCap = 'round';
        ctx.beginPath();
        ctx.moveTo(spineX, spineY);
        ctx.lineTo(endX, endY);
        ctx.stroke();

        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;

        // Nom de la catégorie
        const textOffsetDistance = 30;
        const textX = endX + textOffsetDistance * Math.cos(angleRad);
        const textY = endY + textOffsetDistance * Math.sin(angleRad);
        
        ctx.font = 'bold 16px Inter, sans-serif';
        const textMetrics = ctx.measureText(category.name);
        const textWidth = textMetrics.width;
        const padding = 12;
        
        ctx.shadowColor = 'rgba(0, 0, 0, 0.15)';
        ctx.shadowBlur = 10;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 4;

        ctx.fillStyle = category.color;
        roundRect(ctx, textX - textWidth/2 - padding, textY - 18, textWidth + padding * 2, 36, 8);
        ctx.fill();

        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;
        
        ctx.fillStyle = 'white';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(category.name, textX, textY);

        category.dragArea = {
            x: textX - textWidth/2 - padding,
            y: textY - 18,
            width: textWidth + padding * 2,
            height: 36,
            textX: textX,
            textY: textY
        };

        category.clickArea = category.dragArea;

        // Dessiner les causes avec positions libres
        if (category.causes.length > 0) {
            category.causes.forEach((cause, causeIndex) => {
                // Convertir en objet si c'est une string
                if (typeof cause === 'string') {
                    cause = { text: cause };
                    category.causes[causeIndex] = cause;
                }
                
                let causeBaseX, causeBaseY;
                
                // Position personnalisée (libre) si elle existe
                if (cause.customPosition) {
                    causeBaseX = cause.customPosition.x;
                    causeBaseY = cause.customPosition.y;
  } else {
                    // Position par défaut : horizontalement depuis la branche (comme sur l'image)
                    const ratio = (causeIndex + 1) / (category.causes.length + 1);
                    const baseX = spineX + (endX - spineX) * ratio;
                    const baseY = spineY + (endY - spineY) * ratio;
                    
                    const isUpperBranch = category.angle > 0;
                    const horizontalOffset = 50 + causeIndex * 30;
                    const verticalOffset = 70 + causeIndex * 35;
                    
                    if (isUpperBranch) {
                        causeBaseX = baseX - horizontalOffset;
                        causeBaseY = baseY - verticalOffset;
} else {
                        causeBaseX = baseX - horizontalOffset;
                        causeBaseY = baseY + verticalOffset;
                    }
                    
                    cause.customPosition = { x: causeBaseX, y: causeBaseY };
                }

                // Trouver le point de connexion sur l'arête (le plus proche)
                const dx = endX - spineX;
                const dy = endY - spineY;
                const lineLengthSq = dx * dx + dy * dy;
                
                const t = Math.max(0, Math.min(1, 
                    ((causeBaseX - spineX) * dx + (causeBaseY - spineY) * dy) / lineLengthSq
                ));
                
                const connectionX = spineX + t * dx;
                const connectionY = spineY + t * dy;

                // Ligne de connexion depuis l'arête jusqu'à la cause
                ctx.strokeStyle = adjustColorBrightness(category.color, 20);
                ctx.lineWidth = 3;
                ctx.lineCap = 'round';
                ctx.beginPath();
                ctx.moveTo(connectionX, connectionY);
                ctx.lineTo(causeBaseX, causeBaseY);
                ctx.stroke();

                // Point de connexion sur l'arête (petit)
                ctx.fillStyle = adjustColorBrightness(category.color, -10);
                ctx.beginPath();
                ctx.arc(connectionX, connectionY, 4, 0, Math.PI * 2);
                ctx.fill();

                // Boîte de la cause (draggable)
                const causeText = cause.text || 'Cause';
                ctx.font = '14px Inter, sans-serif';
                const causeTextWidth = ctx.measureText(causeText).width;
                const causePadding = 10;
                const causeBoxWidth = causeTextWidth + causePadding * 2;
                const causeBoxHeight = 32;
                
                // Ombre pour la boîte
                ctx.shadowColor = 'rgba(0, 0, 0, 0.15)';
                ctx.shadowBlur = 8;
                ctx.shadowOffsetX = 0;
                ctx.shadowOffsetY = 3;
                
                // Boîte de cause
                ctx.fillStyle = category.color;
                roundRect(
                    ctx, 
                    causeBaseX - causeBoxWidth/2, 
                    causeBaseY - causeBoxHeight/2, 
                    causeBoxWidth, 
                    causeBoxHeight, 
                    6
                );
                ctx.fill();
                
                // Bordure
                ctx.shadowColor = 'transparent';
                ctx.shadowBlur = 0;
                ctx.strokeStyle = adjustColorBrightness(category.color, -30);
                ctx.lineWidth = 2;
                roundRect(
                    ctx, 
                    causeBaseX - causeBoxWidth/2, 
                    causeBaseY - causeBoxHeight/2, 
                    causeBoxWidth, 
                    causeBoxHeight, 
                    6
                );
                ctx.stroke();
                
                // Texte
                ctx.fillStyle = 'white';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                
                let displayText = causeText;
                if (displayText.length > 30) {
                    displayText = displayText.substring(0, 28) + '...';
                }
                
                ctx.fillText(displayText, causeBaseX, causeBaseY);

                // Zone de clic/drag pour la cause - IMPORTANT : stocker sur l'objet cause
                cause.clickArea = {
                    x: causeBaseX - causeBoxWidth/2,
                    y: causeBaseY - causeBoxHeight/2,
                    width: causeBoxWidth,
                    height: causeBoxHeight,
                    centerX: causeBaseX,
                    centerY: causeBaseY
                };
            });
        }
    }

    function adjustColorBrightness(color, percent) {
        const num = parseInt(color.replace("#",""), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) + amt;
        const G = (num >> 8 & 0x00FF) + amt;
        const B = (num & 0x0000FF) + amt;
        return "#" + (0x1000000 + (R<255?R<1?0:R:255)*0x10000 +
            (G<255?G<1?0:G:255)*0x100 + (B<255?B<1?0:B:255))
            .toString(16).slice(1);
    }

    // Gestion des événements de souris
    function setupCanvasEvents() {
        if (isReadonlyMode) {
            return;
        }
        if (!canvas) return;
        
        canvas.addEventListener('mousedown', (event) => {
            const rect = canvas.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            // Vérifier si on clique sur le rectangle du problème
            if (window.ishikawaApp.problemRectArea) {
                const rectArea = window.ishikawaApp.problemRectArea;
                if (x >= rectArea.x && x <= rectArea.x + rectArea.width &&
                    y >= rectArea.y && y <= rectArea.y + rectArea.height) {
                    // Clic dans le rectangle - positionner le curseur dans le champ
                    if (problemInput) {
                        problemInput.focus();
                        problemInput.select();
                        canvas.style.cursor = 'pointer';
                    }
                    return;
                }
            }

            // D'abord vérifier si on clique sur une cause (boîte complète)
            for (let catIndex = 0; catIndex < categories.length; catIndex++) {
                const category = categories[catIndex];
                
                for (let causeIndex = 0; causeIndex < category.causes.length; causeIndex++) {
                    const cause = category.causes[causeIndex];
                    
                    if (cause.clickArea &&
                        x >= cause.clickArea.x &&
                        x <= cause.clickArea.x + cause.clickArea.width &&
                        y >= cause.clickArea.y &&
                        y <= cause.clickArea.y + cause.clickArea.height) {
                        
                        // Double-clic pour éditer
                        if (event.detail === 2) {
                            currentCategory = catIndex;
                            editCause(causeIndex);
                            return;
                        }
                        
                        // Simple clic pour drag - utiliser le centre de la boîte
                        isDragging = true;
                        draggedCause = causeIndex;
                        draggedCauseCategory = catIndex;
                        dragOffset.x = x - cause.clickArea.centerX;
                        dragOffset.y = y - cause.clickArea.centerY;
                        canvas.classList.add('dragging-cause');
                        
                        // Afficher le hint la première fois
                        if (!hasShownDragHint) {
                            showDragHint();
                            hasShownDragHint = true;
                        }
                        
                        return;
                    }
                }
            }

            // Ensuite vérifier si on clique sur une catégorie
            for (let i = 0; i < categories.length; i++) {
                const category = categories[i];
                if (category.dragArea &&
                    x >= category.dragArea.x &&
                    x <= category.dragArea.x + category.dragArea.width &&
                    y >= category.dragArea.y &&
                    y <= category.dragArea.y + category.dragArea.height) {
                    
                    if (event.detail === 2) {
                        openCategoryModal(i);
                        return;
                    }
                    
                    isDragging = true;
                    draggedCategory = i;
                    dragOffset.x = x - category.dragArea.textX;
                    dragOffset.y = y - category.dragArea.textY;
                    canvas.classList.add('dragging');
                    return;
                }
            }
        });

        canvas.addEventListener('mousemove', (event) => {
            if (!isDragging) {
                const rect = canvas.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                
                let overInteractive = false;
                
                // Check causes
                for (let category of categories) {
                    for (let cause of category.causes) {
                        if (cause.clickArea &&
                            x >= cause.clickArea.x &&
                            x <= cause.clickArea.x + cause.clickArea.width &&
                            y >= cause.clickArea.y &&
                            y <= cause.clickArea.y + cause.clickArea.height) {
                            overInteractive = true;
                            break;
                        }
                    }
                    if (overInteractive) break;
                }
                
                // Check categories
                if (!overInteractive) {
                    for (let category of categories) {
                        if (category.dragArea &&
                            x >= category.dragArea.x &&
                            x <= category.dragArea.x + category.dragArea.width &&
                            y >= category.dragArea.y &&
                            y <= category.dragArea.y + category.dragArea.height) {
                            overInteractive = true;
                            break;
                        }
                    }
                }
                
                // Check rectangle du problème
                if (!overInteractive && window.ishikawaApp.problemRectArea) {
                    const rectArea = window.ishikawaApp.problemRectArea;
                    if (x >= rectArea.x && x <= rectArea.x + rectArea.width &&
                        y >= rectArea.y && y <= rectArea.y + rectArea.height) {
                        overInteractive = true;
                        canvas.style.cursor = 'pointer';
                        return; // Sortir immédiatement pour ne pas changer le curseur
                    }
                }
                
                canvas.style.cursor = overInteractive ? 'grab' : 'default';
                return;
            }

            const rect = canvas.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            // Drag d'une cause - LIBRE partout sur le canvas
            if (draggedCause !== null && draggedCauseCategory !== null) {
                const category = categories[draggedCauseCategory];
                const cause = category.causes[draggedCause];
                
                // Position libre - pas de contrainte
                const newX = x - dragOffset.x;
                const newY = y - dragOffset.y;
                
                // Limites du canvas
                const margin = 50;
                const constrainedX = Math.max(margin, Math.min(canvas.width - margin, newX));
                const constrainedY = Math.max(margin, Math.min(canvas.height - margin, newY));
                
                // Stocker la position personnalisée
                if (typeof cause === 'string') {
                    // Convertir la cause en objet
                    category.causes[draggedCause] = {
                        text: cause,
                        customPosition: { x: constrainedX, y: constrainedY }
                    };
    } else {
                    cause.customPosition = { x: constrainedX, y: constrainedY };
                }
                
                drawDiagram();
                return;
            }

            // Drag d'une catégorie
            if (draggedCategory !== null) {
                const category = categories[draggedCategory];
                const spineY = canvas.height / 2;
                const startX = 100;
                const endX = canvas.width - 150;
                
                const desiredTextX = x - dragOffset.x;
                const desiredTextY = y - dragOffset.y;
                
                const textOffsetDistance = 30;
                const angleRad = (category.angle * Math.PI) / 180;
                const endXText = desiredTextX - textOffsetDistance * Math.cos(angleRad);
                const endYText = desiredTextY - textOffsetDistance * Math.sin(angleRad);
                
                const newSpineX = endXText - category.branchLength * Math.cos(angleRad);
                
                category.spineX = newSpineX;
                
                const dx = endXText - category.spineX;
                const dy = endYText - spineY;
                category.branchLength = Math.sqrt(dx * dx + dy * dy);
                
                category.angle = Math.atan2(dy, dx) * 180 / Math.PI;

                if (category.spineX < startX) category.spineX = startX;
                if (category.spineX > endX) category.spineX = endX;
                if (category.branchLength < 80) category.branchLength = 80;
                if (category.branchLength > 350) category.branchLength = 350;

                drawDiagram();
            }
        });

        canvas.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                draggedCategory = null;
                draggedCause = null;
                draggedCauseCategory = null;
                canvas.classList.remove('dragging');
                canvas.classList.remove('dragging-cause');
            }
        });

        canvas.addEventListener('mouseleave', () => {
            if (isDragging) {
                isDragging = false;
                draggedCategory = null;
                draggedCause = null;
                draggedCauseCategory = null;
                canvas.classList.remove('dragging');
                canvas.classList.remove('dragging-cause');
            }
        });
    }

    // Afficher le hint de drag
    function showDragHint() {
        if (isReadonlyMode) {
            return;
        }
         // Créer le hint si nécessaire
         let hint = document.getElementById('dragHint');
        if (!hint) {
            hint = document.createElement('div');
            hint.id = 'dragHint';
            hint.className = 'drag-hint';
            hint.innerHTML = '<span class="drag-hint__text"></span><button type="button" class="drag-hint__dismiss" aria-label="Fermer l\'astuce">×</button>';
            document.body.appendChild(hint);
        }

        const message = hint.querySelector('.drag-hint__text');
        if (message) {
            message.textContent = '🎯 Glissez les catégories et les causes n\'importe où sur le diagramme !';
        }

        const dismissButton = hint.querySelector('.drag-hint__dismiss');
        if (dismissButton && !dismissButton.dataset.bound) {
            dismissButton.addEventListener('click', dismissDragHint);
            dismissButton.dataset.bound = '1';
        }

        hint.style.display = 'flex';
        hint.setAttribute('data-visible', 'true');
    }
 
     function dismissDragHint() {
         const hint = document.getElementById('dragHint');
         if (hint) {
             hint.style.display = 'none';
             hint.removeAttribute('data-visible');
         }
     }

    // UI Management
    function updateCategoriesList() {
        const list = document.getElementById('categoriesList');
        if (!list) {
            console.error('Ishikawa: categoriesList element not found');
            return;
        }
        
        console.log('Ishikawa: Mise à jour de la liste des catégories', categories.length);
        list.innerHTML = '';
        
        categories.forEach((category, index) => {
            const card = document.createElement('div');
            card.className = 'category-card';

            let actionsSection = '';
            if (!isReadonlyMode) {
                actionsSection = `
                    <div class="category-card__footer">
                        <div class="ishikawa-category-actions">
                            <button class="btn btn-primary btn-icon tooltip" data-tooltip="Éditer" onclick="window.ishikawaApp.openCategoryModal(${index})" aria-label="Éditer la catégorie ${category.name}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="btn btn-danger btn-icon tooltip" data-tooltip="Supprimer" onclick="window.ishikawaApp.deleteCategory(${index})" aria-label="Supprimer la catégorie ${category.name}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }

            card.innerHTML = `
                <div class="category-card__body">
                    <div class="category-color" style="background-color: ${category.color};"></div>
                    <div class="category-info">
                        <span class="category-name">${category.name}</span>
                        <span class="category-count">${category.causes.length} cause(s)</span>
                    </div>
                </div>
                ${actionsSection}
            `;
            list.appendChild(card);
        });
    }

    function initColorPicker() {
        const picker = document.getElementById('colorPicker');
        if (!picker) return;
        
        picker.innerHTML = '';
        
        predefinedColors.forEach(color => {
            const option = document.createElement('div');
            option.className = 'color-option';
            option.style.backgroundColor = color;
            if (color === selectedColor) {
                option.classList.add('active');
            }
            option.onclick = () => {
                document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));
                option.classList.add('active');
                selectedColor = color;
            };
            picker.appendChild(option);
        });
    }

    // Category Management
    function openAddCategoryModal() {
        initColorPicker();
        const input = document.getElementById('newCategoryName');
        if (input) input.value = '';
        const modal = document.getElementById('addCategoryModal');
        if (modal) {
            modal.style.display = 'block';
            // Ajouter la classe modal-open pour masquer les boutons
            const ishikawaPage = document.querySelector('.ishikawa-page');
            if (ishikawaPage) ishikawaPage.classList.add('modal-open');
        }
    }

    function saveNewCategory() {
        const input = document.getElementById('newCategoryName');
        if (!input) return;
        
        const name = input.value.trim();
        
        if (!name) {
            showNotification('Veuillez entrer un nom pour la catégorie.', 'error');
            return;
        }

        const avgSpineX = categories.length > 0 
            ? categories.reduce((sum, cat) => sum + cat.spineX, 0) / categories.length 
            : 400;
        
        const newCategory = {
            name: name,
            color: selectedColor,
            spineX: avgSpineX + 100,
            angle: categories.length % 2 === 0 ? 140 : -140,
            branchLength: 180,
            causes: []
        };

        categories.push(newCategory);
        closeModal('addCategoryModal');
        updateCategoriesList();
        drawDiagram();
        showNotification('Catégorie ajoutée avec succès', 'success');
    }

    function normalizeHexColor(color, fallback = '#FF6B6B') {
        if (!color) {
            return fallback;
        }
        let hex = color.trim();
        if (!hex.startsWith('#')) {
            hex = `#${hex}`;
        }
        const fullMatch = hex.match(/^#([0-9a-f]{6})$/i);
        if (fullMatch) {
            return `#${fullMatch[1].toUpperCase()}`;
        }
        const withAlphaMatch = hex.match(/^#([0-9a-f]{8})$/i);
        if (withAlphaMatch) {
            return `#${withAlphaMatch[1].slice(0, 6).toUpperCase()}`;
        }
        const shortMatch = hex.match(/^#([0-9a-f]{3})$/i);
        if (shortMatch) {
            const chars = shortMatch[1].toUpperCase();
            return `#${chars[0]}${chars[0]}${chars[1]}${chars[1]}${chars[2]}${chars[2]}`;
        }
        const shortAlphaMatch = hex.match(/^#([0-9a-f]{4})$/i);
        if (shortAlphaMatch) {
            const chars = shortAlphaMatch[1].toUpperCase();
            return `#${chars[0]}${chars[0]}${chars[1]}${chars[1]}${chars[2]}${chars[2]}`;
        }
        return fallback;
    }

    function updateCategoryColor(categoryIndex, newColor) {
        if (typeof categoryIndex === 'undefined' || categoryIndex === null) {
            return;
        }
        const category = categories[categoryIndex];
        if (!category) {
            return;
        }

        const normalized = normalizeHexColor(newColor, category.color);
        category.color = normalized;
        updateCategoriesList();
        drawDiagram();
    }

    async function deleteCategory(index) {
        const categoryName = categories[index]?.name || 'cette catégorie';

        const confirmed = await requestConfirmation(
            {
                title: 'Supprimer la catégorie',
                message: `Êtes-vous sûr de vouloir supprimer la catégorie "${categoryName}" et toutes ses causes ? Cette action est irréversible.`,
                type: 'danger',
                confirmText: 'Supprimer',
            },
            `Êtes-vous sûr de vouloir supprimer la catégorie "${categoryName}" ?`
        );

        if (!confirmed) {
            return;
        }

        categories.splice(index, 1);
        updateCategoriesList();
        drawDiagram();
        showNotification('Catégorie supprimée avec succès', 'success');
    }

    function openCategoryModal(categoryIndex) {
        currentCategory = categoryIndex;
        const category = categories[categoryIndex];
        
        // Retirer la classe selected de toutes les cartes
        document.querySelectorAll('.category-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Ajouter la classe selected à la carte correspondante
        const cards = document.querySelectorAll('.category-card');
        if (cards[categoryIndex]) {
            cards[categoryIndex].classList.add('selected');
        }
        
        const input = document.getElementById('categoryNameInput');
        if (input) input.value = category.name;
        displayCauses();
        const modal = document.getElementById('categoryModal');
        if (modal) {
            modal.style.display = 'block';
            // Ajouter la classe modal-open pour masquer les boutons
            const ishikawaPage = document.querySelector('.ishikawa-page');
            if (ishikawaPage) ishikawaPage.classList.add('modal-open');
        }
    }

    function displayCauses() {
        const causesList = document.getElementById('causesList');
        if (!causesList) return;
        
        const category = categories[currentCategory];
        if (!category) return;
        
        if (category.causes.length === 0) {
            causesList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <p>Aucune cause identifiée</p>
                </div>
            `;
            return;
        }
        
        causesList.innerHTML = '';
        category.causes.forEach((cause, index) => {
            const causeItem = document.createElement('div');
            causeItem.className = 'cause-item';
            
            const causeText = typeof cause === 'string' ? cause : cause.text;
            
            causeItem.innerHTML = `
                <span class="cause-text">${causeText}</span>
                <div class="cause-actions">
                    <button class="btn btn-primary btn-icon btn-sm" onclick="window.ishikawaApp.editCause(${index})" aria-label="Éditer la cause">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="btn btn-danger btn-icon btn-sm" onclick="window.ishikawaApp.deleteCause(${index})" aria-label="Supprimer la cause">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </button>
                </div>
            `;
            causesList.appendChild(causeItem);
        });
    }

    function addCause() {
        currentCauseIndex = null;
        const title = document.getElementById('causeModalTitle');
        const input = document.getElementById('causeDescriptionInput');
        const modal = document.getElementById('causeModal');
        
        if (title) title.textContent = '➕ Ajouter une cause';
        if (input) input.value = '';
        if (modal) {
            modal.style.display = 'block';
            // Ajouter la classe modal-open pour masquer les boutons
            const ishikawaPage = document.querySelector('.ishikawa-page');
            if (ishikawaPage) ishikawaPage.classList.add('modal-open');
        }
    }

    function editCause(index) {
        currentCauseIndex = index;
        const cause = categories[currentCategory].causes[index];
        const causeText = typeof cause === 'string' ? cause : cause.text;
        
        // Retirer la classe selected de toutes les causes
        document.querySelectorAll('.cause-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        // Ajouter la classe selected à la cause correspondante
        const causes = document.querySelectorAll('.cause-item');
        if (causes[index]) {
            causes[index].classList.add('selected');
        }
        
        const title = document.getElementById('causeModalTitle');
        const input = document.getElementById('causeDescriptionInput');
        const modal = document.getElementById('causeModal');
        
        if (title) title.textContent = '✏️ Éditer la cause';
        if (input) input.value = causeText;
        if (modal) {
            modal.style.display = 'block';
            // Ajouter la classe modal-open pour masquer les boutons
            const ishikawaPage = document.querySelector('.ishikawa-page');
            if (ishikawaPage) ishikawaPage.classList.add('modal-open');
        }
    }

    async function deleteCause(index) {
        const cause = categories[currentCategory]?.causes[index];
        const causeText = typeof cause === 'string' ? cause : (cause?.text || 'cette cause');

        const confirmed = await requestConfirmation(
            {
                title: 'Supprimer la cause',
                message: `Êtes-vous sûr de vouloir supprimer la cause "${causeText}" ? Cette action est irréversible.`,
                type: 'danger',
                confirmText: 'Supprimer',
            },
            `Êtes-vous sûr de vouloir supprimer la cause "${causeText}" ?`
        );

        if (!confirmed) {
            return;
        }

        categories[currentCategory].causes.splice(index, 1);
        displayCauses();
        updateCategoriesList();
        drawDiagram();
        showNotification('Cause supprimée avec succès', 'success');
    }

    function saveCause() {
        const input = document.getElementById('causeDescriptionInput');
        if (!input) return;
        
        const description = input.value.trim();
        
        if (!description) {
            showNotification('Veuillez entrer une description pour la cause.', 'error');
            return;
  }

  if (currentCauseIndex !== null) {
            const cause = categories[currentCategory].causes[currentCauseIndex];
            if (typeof cause === 'string') {
                categories[currentCategory].causes[currentCauseIndex] = description;
  } else {
                cause.text = description;
            }
        } else {
            categories[currentCategory].causes.push(description);
        }

        closeModal('causeModal');
        
        // Retirer la classe selected de toutes les causes
        document.querySelectorAll('.cause-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        displayCauses();
        updateCategoriesList();
        drawDiagram();
        showNotification(currentCauseIndex !== null ? 'Cause modifiée avec succès' : 'Cause ajoutée avec succès', 'success');
    }

    function saveCategoryChanges() {
        const input = document.getElementById('categoryNameInput');
        if (!input) return;
        
        const newName = input.value.trim();
        
        if (!newName) {
            showNotification('Veuillez entrer un nom pour la catégorie.', 'error');
            return;
        }

        categories[currentCategory].name = newName;
        closeModal('categoryModal');
        updateCategoriesList();
        drawDiagram();
        showNotification('Catégorie modifiée avec succès', 'success');
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
        
        // Vérifier si d'autres modals sont ouverts
        const allModals = document.querySelectorAll('.modal');
        let anyModalOpen = false;
        allModals.forEach(m => {
            if (m.style.display === 'block') {
                anyModalOpen = true;
            }
        });
        
        if (!anyModalOpen) {
            // Retirer la classe modal-open si aucun modal n'est ouvert
            const ishikawaPage = document.querySelector('.ishikawa-page');
            if (ishikawaPage) ishikawaPage.classList.remove('modal-open');
        }
        
        // Retirer la classe selected de toutes les cartes quand on ferme le modal
        if (modalId === 'categoryModal') {
            document.querySelectorAll('.category-card').forEach(card => {
                card.classList.remove('selected');
            });
        }
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
            
            // Vérifier si d'autres modals sont ouverts
            const allModals = document.querySelectorAll('.modal');
            let anyModalOpen = false;
            allModals.forEach(m => {
                if (m.style.display === 'block') {
                    anyModalOpen = true;
                }
            });
            
            if (!anyModalOpen) {
                // Retirer la classe modal-open si aucun modal n'est ouvert
                const ishikawaPage = document.querySelector('.ishikawa-page');
                if (ishikawaPage) ishikawaPage.classList.remove('modal-open');
            }
            
            // Retirer la classe selected de toutes les cartes quand on ferme le modal
            document.querySelectorAll('.category-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Retirer la classe selected de toutes les causes
            document.querySelectorAll('.cause-item').forEach(item => {
                item.classList.remove('selected');
            });
        }
    }

    function trackIshikawaExport(format) {
        if (typeof window.trackExport !== 'function') {
            return;
        }

        const totalCauses = categories.reduce((total, cat) => {
            return total + ((cat.causes || []).length);
        }, 0);

        window.trackExport('ishikawa', format.toUpperCase(), {
            categoryCount: categories.length,
            causeCount: totalCauses,
            problemLength: problemInput ? (problemInput.value || '').length : 0,
        });
    }

    // Export & Reset
    function exportDiagram(format = 'png') {
        if (!canvas) return;

        const filenameBase = 'diagramme-ishikawa-' + Date.now();
        const titleText = (document.querySelector('.page-title')?.innerText || 'Diagramme d\'Ishikawa').trim();
        const exportDate = new Date().toLocaleString('fr-FR');
        const copyrightText = '© OUTILS-QUALITÉ - www.outils-qualite.com';

        if (format === 'png' || format === 'pdf') {
            try {
                ensureExportLibraries({ pdf: format === 'pdf' });
            } catch (error) {
                console.error('Ishikawa: export indisponible', error);
                showNotification(error.message || 'Bibliothèques d’export indisponibles.', 'error');
                return;
            }
        }

        if (format === 'png') {
            const container = document.getElementById('diagramCanvas').parentElement;
            const originalStyle = container.style.cssText;
            container.style.cssText += 'background: white !important; padding: 20px !important; box-shadow: none !important;';

            window.html2canvas(container, {
                scale: 3,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff'
            }).then(canvasCapture => {
                const finalCanvas = document.createElement('canvas');
                const ctx = finalCanvas.getContext('2d');
                const padding = 48;
                const headerHeight = 80;
                const footerHeight = 60;
                finalCanvas.width = canvasCapture.width + padding * 2;
                finalCanvas.height = canvasCapture.height + padding * 2 + headerHeight + footerHeight;

                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, finalCanvas.width, finalCanvas.height);

                ctx.font = '28px Inter, sans-serif';
                ctx.fillStyle = '#1f2937';
                ctx.textAlign = 'center';
                ctx.fillText(titleText, finalCanvas.width / 2, headerHeight / 2 + 12);
                ctx.font = '16px Inter, sans-serif';
                ctx.fillStyle = '#475569';
                ctx.fillText(`Exporté le ${exportDate}`, finalCanvas.width / 2, headerHeight - 12);

                const contentOffsetY = headerHeight + padding;
                ctx.drawImage(canvasCapture, padding, contentOffsetY);

                ctx.font = '16px Arial';
                ctx.fillStyle = 'rgba(0, 0, 0, 0.12)';
                ctx.save();
                ctx.translate(finalCanvas.width / 2, finalCanvas.height / 2);
                ctx.rotate(-Math.PI / 6);
                ctx.textAlign = 'center';
                ctx.fillText('OUTILS-QUALITÉ', 0, 0);
                ctx.restore();

                ctx.font = '14px Inter, sans-serif';
                ctx.fillStyle = '#475569';
                ctx.textAlign = 'center';
                ctx.fillText(`Problème : ${problemInput ? problemInput.value : ''}`.trim().substring(0, 120), finalCanvas.width / 2, finalCanvas.height - footerHeight + 24);
                ctx.font = '12px Inter, sans-serif';
                ctx.fillText(copyrightText, finalCanvas.width / 2, finalCanvas.height - footerHeight / 2);

                const link = document.createElement('a');
                link.download = `${filenameBase}.png`;
                link.href = finalCanvas.toDataURL('image/png', 0.95);
                link.click();
                showNotification('Image PNG exportée avec succès.', 'success');
                trackIshikawaExport('png');
            }).catch(() => {
                showNotification('Erreur lors de la génération du PNG.', 'error');
            }).finally(() => {
                container.style.cssText = originalStyle;
            });
            return;
        }

        if (format === 'json') {
            const data = {
                metadata: {
                    title: titleText,
                    generatedAt: new Date().toISOString(),
                    exportLocale: exportDate,
                    copyright: copyrightText,
                    tool: 'Diagramme d\'Ishikawa',
                    version: '1.0'
                },
                diagram: {
                    problem: problemInput ? problemInput.value : '',
                    categories: categories.map(cat => ({
                        name: cat.name,
                        color: cat.color,
                        spineX: cat.spineX,
                        angle: cat.angle,
                        branchLength: cat.branchLength,
                        causes: (cat.causes || []).map(cause => ({
                            text: typeof cause === 'string' ? cause : cause.text,
                            customPosition: typeof cause === 'object' && cause.customPosition ? cause.customPosition : null
                        }))
                    }))
                }
            };
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${filenameBase}.json`;
            link.click();
            URL.revokeObjectURL(url);
            showNotification('Export JSON terminé.', 'success');
            trackIshikawaExport('json');
            return;
        }

        if (format === 'pdf') {
            const jsPDFCtor = window.jspdf?.jsPDF || window.jspdf?.jspdf?.jsPDF;

            showNotification('Génération du PDF en cours…', 'info');

            const container = document.getElementById('diagramCanvas').parentElement;
            const originalStyle = container.style.cssText;
            container.style.cssText += 'background: white !important; padding: 20px !important; box-shadow: none !important;';

            window.html2canvas(container, {
    scale: 2,
    useCORS: true,
    allowTaint: true,
                backgroundColor: '#ffffff'
            }).then(canvasCapture => {
                const pdf = new jsPDFCtor({ orientation: 'landscape' });
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const marginTop = 22;
                const marginBottom = 18;
                const availableHeight = pageHeight - marginTop - marginBottom;

                const imgData = canvasCapture.toDataURL('image/png');
                const imgWidth = canvasCapture.width;
                const imgHeight = canvasCapture.height;
                const ratio = Math.min(pageWidth / imgWidth, availableHeight / imgHeight);
                const posX = (pageWidth - imgWidth * ratio) / 2;
                const posY = marginTop + (availableHeight - imgHeight * ratio) / 2;

                pdf.addImage(imgData, 'PNG', posX, posY, imgWidth * ratio, imgHeight * ratio);

                pdf.setFont('helvetica', 'bold');
                pdf.setFontSize(16);
                pdf.setTextColor(31, 41, 55);
                pdf.text(titleText, pageWidth / 2, 12, { align: 'center' });
                pdf.setFont('helvetica', 'normal');
                pdf.setFontSize(10);
                pdf.setTextColor(71, 85, 105);
                pdf.text(`Exporté le ${exportDate}`, pageWidth / 2, 18, { align: 'center' });

                pdf.setFontSize(8);
                pdf.setTextColor(150, 150, 150);
                pdf.text(copyrightText, pageWidth / 2, pageHeight - 6, { align: 'center' });

                const problemTextPdf = problemInput ? problemInput.value : '';
                if (problemTextPdf) {
                    pdf.setFontSize(9);
                    pdf.setTextColor(80, 80, 80);
                    pdf.text(`Problème : ${problemTextPdf.substring(0, 110)}${problemTextPdf.length > 110 ? '…' : ''}`, pageWidth / 2, pageHeight - 12, { align: 'center' });
                }

                pdf.save(`${filenameBase}.pdf`);
                showNotification('PDF exporté avec succès.', 'success');
                trackIshikawaExport('pdf');
            }).catch(() => {
                showNotification('Erreur lors de la génération du PDF.', 'error');
            }).finally(() => {
                container.style.cssText = originalStyle;
            });
            return;
        }

        showNotification('Format d\'export inconnu.', 'error');
    }

    async function resetDiagram() {
        return resetCategories();
    }

    async function resetCauses() {
        const confirmed = await requestConfirmation(
            {
                title: 'Réinitialiser les causes',
                message: 'Toutes les causes seront supprimées de chaque catégorie. Souhaitez-vous continuer ?',
                type: 'warning',
                confirmText: 'Supprimer les causes'
            },
            'Toutes les causes seront supprimées. Continuer ?'
        );

        if (!confirmed) {
            return;
        }

        categories.forEach(category => {
            category.causes = [];
        });
        updateCategoriesList();
        drawDiagram();
        showNotification('Toutes les causes ont été supprimées.', 'success');
    }

    async function resetCategories() {
        const confirmed = await requestConfirmation(
            {
                title: 'Réinitialiser les catégories',
                message: 'Les catégories seront restaurées à leur configuration initiale. Les causes par défaut seront également rétablies. Continuer ?',
                type: 'warning',
                confirmText: 'Restaurer'
            },
            'Voulez-vous réinitialiser les catégories ?'
        );

        if (!confirmed) {
            return;
        }

        categories = cloneDefaultCategories();
        updateCategoriesList();
        drawDiagram();
        showNotification('Les catégories ont été restaurées.', 'success');
    }

    async function resetEverything() {
        const confirmed = await requestConfirmation(
            {
                title: 'Tout effacer',
                message: 'Cette action supprimera toutes les catégories, causes et le texte du problème. Voulez-vous continuer ?',
                type: 'danger',
                confirmText: 'Effacer tout'
            },
            'Voulez-vous effacer complètement le diagramme ?'
        );

        if (!confirmed) {
            return;
        }

        categories = [];
        if (problemInput) {
            problemInput.value = '';
        }
        updateCategoriesList();
        drawDiagram();
        showNotification('Le diagramme a été vidé.', 'success');
    }

    // Fonction pour récupérer les données du diagramme (pour la sauvegarde)
    function getDiagramData() {
        return {
            problem: problemInput ? problemInput.value : '',
            categories: categories.map(cat => ({
                name: cat.name,
                color: cat.color,
                spineX: cat.spineX,
                angle: cat.angle,
                branchLength: cat.branchLength,
                causes: cat.causes.map(cause => ({
                    text: typeof cause === 'string' ? cause : cause.text,
                    customPosition: typeof cause === 'object' && cause.customPosition ? cause.customPosition : null
                }))
            }))
        };
    }

    // Exposer les fonctions globales nécessaires
    window.ishikawaApp = {
        openAddCategoryModal,
        saveNewCategory,
        deleteCategory,
        openCategoryModal,
        addCause,
        editCause,
        deleteCause,
        saveCause,
        saveCategoryChanges,
        closeModal,
        exportDiagram,
        resetDiagram,
        getDiagramData,
        resetCauses,
        resetCategories,
        resetEverything,
        updateCategoryColor,
        predefinedColors,
        dismissDragHint,
        loadDiagramData,
        isReady: false
    };

    // Initialiser immédiatement si le script est chargé après le DOM
    if (document.readyState === 'complete') {
        // La page est complètement chargée
        setTimeout(tryInit, 100);
    } else {
        // Attendre que le DOM soit prêt
        initializeApp();
    }

    // Écouter les événements Turbo pour réinitialiser après navigation
    document.addEventListener('turbo:load', () => {
        console.log('Ishikawa: Événement turbo:load détecté, réinitialisation...');
        initAttempts = 0;
        setTimeout(tryInit, 100);
    });
    
    document.addEventListener('turbo:render', () => {
        console.log('Ishikawa: Événement turbo:render détecté, réinitialisation...');
        initAttempts = 0;
        setTimeout(tryInit, 100);
    });
    
    // Écouter aussi l'événement turbo:frame-load pour les frames Turbo
    document.addEventListener('turbo:frame-load', () => {
        console.log('Ishikawa: Événement turbo:frame-load détecté, réinitialisation...');
        initAttempts = 0;
        setTimeout(tryInit, 100);
    });
    
    // Fallback : essayer aussi avec window.load
    window.addEventListener('load', () => {
        console.log('Ishikawa: Événement window.load détecté, vérification...');
        if (!canvas || !ctx) {
            initAttempts = 0;
            setTimeout(tryInit, 100);
        }
    });
})();

