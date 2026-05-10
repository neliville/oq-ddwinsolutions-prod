import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour le rendu du diagramme Ishikawa sur Canvas HTML5
 * Remplace le rendu SVG/DIV par Canvas pour de meilleures performances
 */
export default class extends Controller {
    static targets = ['canvas'];
    static values = {
        diagramData: { type: Object, default: {} }, // Données du diagramme
        width: { type: Number, default: 1400 },
        height: { type: Number, default: 800 }
    };

    connect() {
        console.log('Ishikawa Canvas controller connecté');
        
        this.ctx = null;
        this.spineY = this.heightValue / 2;
        this.spineStartX = 100; // Commence plus à gauche
        this.spineEndX = this.widthValue - 220; // Se termine avant la boîte problème
        this.problemBoxX = this.widthValue - 200;
        this.problemBoxY = this.spineY;
        
        // Initialiser le canvas
        this.initializeCanvas();
        
        // Écouter les événements de mise à jour du diagramme
        this.boundUpdateHandler = this.handleDiagramUpdate.bind(this);
        document.addEventListener('ishikawa:diagram-updated', this.boundUpdateHandler);
        
        // Écouter les événements Live Component pour les mises à jour
        this.boundLiveUpdate = this.handleLiveUpdate.bind(this);
        document.addEventListener('live:update', this.boundLiveUpdate);
        
        // Écouter les événements de rendu Live Component (quand le DOM est mis à jour)
        this.boundLiveRender = this.handleLiveRender.bind(this);
        document.addEventListener('live:render', this.boundLiveRender);
        
        // Charger les données initiales depuis les valeurs Stimulus
        this.loadInitialDataFromValues();
        
        // Attendre un peu pour que le Live Component soit complètement initialisé
        setTimeout(() => {
            this.draw();
        }, 100);
        
        // Écouter les événements de drag
        this.boundCategoryDrag = this.handleCategoryDrag.bind(this);
        this.boundCauseDrag = this.handleCauseDrag.bind(this);
        this.element.addEventListener('category-dragging', this.boundCategoryDrag);
        this.element.addEventListener('cause-dragging', this.boundCauseDrag);
    }

    disconnect() {
        if (this.boundUpdateHandler) {
            document.removeEventListener('ishikawa:diagram-updated', this.boundUpdateHandler);
        }
        if (this.boundLiveUpdate) {
            document.removeEventListener('live:update', this.boundLiveUpdate);
        }
        if (this.boundLiveRender) {
            document.removeEventListener('live:render', this.boundLiveRender);
        }
        if (this.boundCategoryDrag) {
            this.element.removeEventListener('category-dragging', this.boundCategoryDrag);
        }
        if (this.boundCauseDrag) {
            this.element.removeEventListener('cause-dragging', this.boundCauseDrag);
        }
    }

    handleLiveRender(event) {
        // Quand le Live Component rend le DOM, recharger les données et redessiner
        setTimeout(() => {
            this.loadInitialData();
            this.draw();
        }, 50);
    }

    handleLiveUpdate(event) {
        // Mettre à jour les données depuis le Live Component
        if (event.detail && event.detail.data) {
            // Récupérer les données depuis l'élément Live Component
            const liveComponent = this.element.closest('[data-live-id]');
            if (liveComponent) {
                // Les données peuvent être dans event.detail.data ou extraites du DOM
                const problem = event.detail.data.problem || this.diagramDataValue?.problem;
                const categories = event.detail.data.categories || this.diagramDataValue?.categories;
                
                // S'assurer que les catégories ont les propriétés Canvas nécessaires
                if (categories && Array.isArray(categories)) {
                    categories.forEach(category => {
                        if (!category.spineX) category.spineX = 200;
                        if (category.angle === undefined) category.angle = 0;
                        if (!category.branchLength) category.branchLength = 150;
                    });
                }
                
                this.diagramDataValue = {
                    problem: problem || 'Problème à résoudre',
                    categories: categories || []
                };
                this.draw();
            }
        }
    }

    handleCategoryDrag(event) {
        // Mettre à jour temporairement la position de la catégorie pendant le drag
        if (event.detail && this.diagramDataValue && this.diagramDataValue.categories) {
            const category = this.diagramDataValue.categories.find(cat => cat.id === event.detail.categoryId);
            if (category) {
                category.spineX = event.detail.spineX;
                category.angle = event.detail.angle;
                category.branchLength = event.detail.branchLength;
                this.draw();
            }
        }
    }

    handleCauseDrag(event) {
        // Pour les causes, on peut ajuster leur position
        // Cette fonctionnalité peut être ajoutée plus tard si nécessaire
    }

    initializeCanvas() {
        if (!this.hasCanvasTarget) {
            console.warn('Canvas target introuvable');
            return;
        }

        const canvas = this.canvasTarget;
        
        // Définir la taille du canvas
        canvas.width = this.widthValue;
        canvas.height = this.heightValue;
        
        // Obtenir le contexte 2D
        this.ctx = canvas.getContext('2d');
        
        // Les données seront chargées après un délai pour laisser le Live Component s'initialiser
    }

    loadInitialDataFromValues() {
        // Charger les données depuis le script JSON dans le DOM
        const jsonScript = document.getElementById('diagramDataJson');
        if (jsonScript) {
            try {
                const parsed = JSON.parse(jsonScript.textContent);
                
                if (parsed && (parsed.problem || parsed.categories)) {
                    // S'assurer que toutes les catégories ont les propriétés Canvas
                    if (parsed.categories && Array.isArray(parsed.categories)) {
                        parsed.categories.forEach((category, index) => {
                            if (!category.spineX) {
                                category.spineX = 200 + (index * 200);
                            }
                            if (category.angle === undefined) {
                                category.angle = index % 2 === 0 ? -30 : 30;
                            }
                            if (!category.branchLength) {
                                category.branchLength = 150;
                            }
                        });
                    }
                    this.diagramDataValue = parsed;
                    return;
                }
            } catch (e) {
                console.error('Erreur lors du parsing des données diagramData:', e);
            }
        }
        
        // Fallback : charger depuis le DOM
        this.loadInitialDataFromDOM();
    }

    loadInitialDataFromDOM() {
        // Récupérer les données depuis le Live Component via l'élément parent
        const liveComponent = this.element.closest('[data-live-id]');
        if (liveComponent) {
            const componentElement = liveComponent;
            
            // Chercher les données dans les éléments du DOM
            const problemInput = componentElement.querySelector('#problemInput');
            const problemText = problemInput ? problemInput.value : 'Problème à résoudre';
            
            // Récupérer les catégories depuis les cartes de catégories
            const categoryCards = componentElement.querySelectorAll('.category-card');
            const categories = [];
            
            categoryCards.forEach((card, index) => {
                const categoryId = parseInt(card.dataset.categoryId) || (index + 1);
                const categoryName = card.dataset.categoryName || '';
                
                // Pour les causes, on devra les récupérer depuis les événements Live ou les données cachées
                const category = {
                    id: categoryId,
                    name: categoryName,
                    causes: [], // Sera rempli par les événements Live Component
                    spineX: 200 + (index * 200),
                    angle: index % 2 === 0 ? -30 : 30,
                    branchLength: 150
                };
                
                categories.push(category);
            });
            
            // Si on a des données, les utiliser
            if (categories.length > 0 || problemText) {
                this.diagramDataValue = {
                    problem: problemText,
                    categories: categories
                };
                return;
            }
        }
        
        // Si pas de données, initialiser avec des valeurs par défaut
        if (!this.diagramDataValue || !this.diagramDataValue.categories || this.diagramDataValue.categories.length === 0) {
            this.diagramDataValue = {
                problem: 'Problème à résoudre',
                categories: []
            };
        }
    }

    loadInitialData() {
        // Méthode de compatibilité qui appelle loadInitialDataFromValues
        this.loadInitialDataFromValues();
    }

    handleDiagramUpdate(event) {
        if (event.detail && event.detail.diagramData) {
            this.diagramDataValue = event.detail.diagramData;
        }
        this.draw();
    }

    /**
     * Dessine l'ensemble du diagramme
     */
    draw() {
        if (!this.ctx) {
            this.initializeCanvas();
            if (!this.ctx) {
                return;
            }
        }

        // Effacer le canvas
        this.ctx.clearRect(0, 0, this.widthValue, this.heightValue);
        
        // Dessiner le fond
        this.drawBackground();
        
        // Dessiner la spine
        this.drawSpine();
        
        // Dessiner la boîte du problème
        this.drawProblemBox();
        
        // Dessiner les catégories et leurs causes
        if (this.diagramDataValue && this.diagramDataValue.categories) {
            this.diagramDataValue.categories.forEach((category) => {
                this.drawCategory(category);
            });
        }
    }

    /**
     * Dessine le fond du diagramme
     */
    drawBackground() {
        const gradient = this.ctx.createLinearGradient(0, 0, this.widthValue, this.heightValue);
        gradient.addColorStop(0, '#fafbff');
        gradient.addColorStop(1, '#f0f4ff');
        
        this.ctx.fillStyle = gradient;
        this.ctx.fillRect(0, 0, this.widthValue, this.heightValue);
    }

    /**
     * Dessine la colonne vertébrale (spine) horizontale
     */
    drawSpine() {
        this.ctx.strokeStyle = '#000000'; // Noir comme dans l'image de référence
        this.ctx.lineWidth = 4;
        this.ctx.lineCap = 'round';
        
        this.ctx.beginPath();
        this.ctx.moveTo(this.spineStartX, this.spineY);
        this.ctx.lineTo(this.spineEndX, this.spineY);
        this.ctx.stroke();
    }

    /**
     * Dessine la boîte du problème à droite (tête de poisson)
     */
    drawProblemBox() {
        const problem = this.diagramDataValue?.problem || 'Problème à résoudre';
        const boxWidth = 180;
        const boxHeight = 70;
        // Positionner la boîte à droite, connectée à la spine
        const x = this.spineEndX + 20; // Un peu à droite de la fin de la spine
        const y = this.problemBoxY - boxHeight / 2;
        const radius = 8;

        // Dessiner la flèche de la spine vers la boîte
        this.ctx.strokeStyle = '#000000'; // Noir
        this.ctx.lineWidth = 4;
        this.ctx.beginPath();
        this.ctx.moveTo(this.spineEndX, this.spineY);
        this.ctx.lineTo(x, this.spineY);
        this.ctx.stroke();

        // Ombre
        this.ctx.shadowColor = 'rgba(0, 0, 0, 0.2)';
        this.ctx.shadowBlur = 10;
        this.ctx.shadowOffsetX = 0;
        this.ctx.shadowOffsetY = 4;

        // Fond de la boîte - rouge
        this.ctx.fillStyle = '#dc3545';
        this.roundRect(x, y, boxWidth, boxHeight, radius);
        this.ctx.fill();

        // Bordure
        this.ctx.strokeStyle = '#c82333';
        this.ctx.lineWidth = 3;
        this.roundRect(x, y, boxWidth, boxHeight, radius);
        this.ctx.stroke();

        // Réinitialiser l'ombre
        this.ctx.shadowColor = 'transparent';
        this.ctx.shadowBlur = 0;
        this.ctx.shadowOffsetX = 0;
        this.ctx.shadowOffsetY = 0;

        // Texte du problème
        this.ctx.fillStyle = '#ffffff';
        this.ctx.font = 'bold 14px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        
        // Découper le texte en plusieurs lignes si nécessaire
        const words = problem.split(' ');
        const lines = [];
        let currentLine = '';
        
        words.forEach(word => {
            const testLine = currentLine + word + ' ';
            const metrics = this.ctx.measureText(testLine);
            if (metrics.width > boxWidth - 20 && currentLine !== '') {
                lines.push(currentLine);
                currentLine = word + ' ';
            } else {
                currentLine = testLine;
            }
        });
        lines.push(currentLine);

        // Dessiner les lignes de texte
        const lineHeight = 18;
        const startY = y + boxHeight / 2 - ((lines.length - 1) * lineHeight) / 2;
        lines.forEach((line, index) => {
            this.ctx.fillText(line.trim(), x + boxWidth / 2, startY + index * lineHeight);
        });
        
        // Réinitialiser l'ombre pour les autres éléments
        this.ctx.shadowColor = 'transparent';
        this.ctx.shadowBlur = 0;
        this.ctx.shadowOffsetX = 0;
        this.ctx.shadowOffsetY = 0;
    }

    /**
     * Dessine une catégorie avec son arête
     */
    drawCategory(category) {
        if (!category.spineX || category.angle === undefined || !category.branchLength) {
            return;
        }

        const spineX = category.spineX;
        const angle = category.angle * (Math.PI / 180); // Convertir en radians
        const branchLength = category.branchLength;

        // Calculer la position de la catégorie
        const categoryX = spineX + Math.cos(angle) * branchLength;
        const categoryY = this.spineY + Math.sin(angle) * branchLength;

        // Dessiner la ligne de connexion (arête) - ligne solide avec la couleur de la catégorie
        const colors = {
            'PERSONNEL': '#dc3545',
            'MATÉRIELS': '#20c997',
            'MESURE': '#0dcaf0',
            'MACHINES': '#ffc107',
            'MÉTHODES': '#6f42c1',
            'ENVIRONNEMENT': '#198754',
            'MANAGEMENT': '#fd7e14'
        };
        const categoryColor = colors[category.name] || '#6c757d';
        
        this.ctx.strokeStyle = categoryColor; // Utiliser la couleur de la catégorie
        this.ctx.lineWidth = 3; // Ligne plus épaisse
        this.ctx.setLineDash([]); // Ligne solide, pas de tirets
        
        this.ctx.beginPath();
        this.ctx.moveTo(spineX, this.spineY);
        this.ctx.lineTo(categoryX, categoryY);
        this.ctx.stroke();

        // Couleur de la catégorie (déjà définie plus haut)

        // Dessiner le label de la catégorie
        const labelWidth = 120;
        const labelHeight = 40;
        const labelX = categoryX - labelWidth / 2;
        const labelY = categoryY - labelHeight / 2;
        const labelRadius = 6;

        // Ombre
        this.ctx.shadowColor = 'rgba(0, 0, 0, 0.1)';
        this.ctx.shadowBlur = 5;
        this.ctx.shadowOffsetX = 0;
        this.ctx.shadowOffsetY = 2;

        // Fond du label
        this.ctx.fillStyle = categoryColor;
        this.roundRect(labelX, labelY, labelWidth, labelHeight, labelRadius);
        this.ctx.fill();

        // Bordure
        this.ctx.strokeStyle = this.adjustBrightness(categoryColor, -20);
        this.ctx.lineWidth = 2;
        this.roundRect(labelX, labelY, labelWidth, labelHeight, labelRadius);
        this.ctx.stroke();

        // Réinitialiser l'ombre
        this.ctx.shadowColor = 'transparent';
        this.ctx.shadowBlur = 0;
        this.ctx.shadowOffsetX = 0;
        this.ctx.shadowOffsetY = 0;

        // Texte de la catégorie
        this.ctx.fillStyle = '#ffffff';
        this.ctx.font = 'bold 12px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        this.ctx.fillText(category.name, categoryX, categoryY);

        // Dessiner les causes (angle est déjà en radians)
        this.drawCauses(category, categoryX, categoryY, angle, categoryColor);
    }

    /**
     * Dessine les causes attachées à une catégorie
     * Les causes sont des sous-branches HORIZONTALES (parallèles à la spine) partant de la branche de catégorie
     * IMPORTANT: Les causes doivent partir de la branche diagonale, pas du label de catégorie
     * @param {Object} category - L'objet catégorie avec ses propriétés
     * @param {number} categoryX - Position X du label de catégorie (non utilisé ici)
     * @param {number} categoryY - Position Y du label de catégorie (non utilisé ici)
     * @param {number} angleRad - Angle en RADIANS (pas en degrés) de la branche de catégorie
     * @param {string} categoryColor - Couleur de la catégorie (non utilisé ici)
     */
    drawCauses(category, categoryX, categoryY, angleRad, categoryColor) {
        if (!category.causes || category.causes.length === 0) {
            return;
        }

        const spineX = category.spineX;
        const branchLength = category.branchLength;
        // angleRad est déjà en radians, pas besoin de conversion

        // Les causes sont HORIZONTALES (parallèles à la spine)
        // Elles partent de la branche de catégorie et s'étendent horizontalement vers la droite
        const causeSpacing = 30; // Espacement vertical entre les causes
        const causeLength = 110; // Longueur horizontale des branches de causes
        const totalHeight = (category.causes.length - 1) * causeSpacing;
        
        // Positionner les causes le long de la branche de la catégorie
        // Les causes sont réparties le long de la branche (environ 50-70% de la branche)
        const branchProgress = 0.6; // Position à 60% de la branche
        const branchPointX = spineX + Math.cos(angleRad) * (branchLength * branchProgress);
        const branchPointY = this.spineY + Math.sin(angleRad) * (branchLength * branchProgress);
        
        // Les causes sont disposées verticalement autour du point sur la branche
        const startY = branchPointY - totalHeight / 2;

        category.causes.forEach((cause, index) => {
            const causeY = startY + index * causeSpacing;
            
            // Les causes partent horizontalement de la branche (toujours vers la droite)
            const causeEndX = branchPointX + causeLength;

            // Ligne de connexion de la cause - ligne HORIZONTALE partant de la branche
            this.ctx.strokeStyle = '#87ceeb'; // Bleu clair comme dans l'image de référence
            this.ctx.lineWidth = 2;
            this.ctx.setLineDash([]); // Ligne solide
            
            this.ctx.beginPath();
            this.ctx.moveTo(branchPointX, causeY); // Point sur la branche de catégorie
            this.ctx.lineTo(causeEndX, causeY); // Point horizontal (ligne horizontale)
            this.ctx.stroke();

            // Boîte de la cause - style gris clair comme dans l'image de référence
            const causeBoxWidth = 160;
            const causeBoxHeight = 26;
            // Positionner la boîte à la fin de la ligne horizontale (vers la droite)
            const causeBoxX = causeEndX;
            const causeBoxY = causeY - causeBoxHeight / 2;
            const causeRadius = 4;

            // Fond de la boîte - gris clair comme dans l'image
            this.ctx.fillStyle = '#f0f0f0'; // Gris très clair
            this.roundRect(causeBoxX, causeBoxY, causeBoxWidth, causeBoxHeight, causeRadius);
            this.ctx.fill();

            // Bordure - gris clair
            this.ctx.strokeStyle = '#d0d0d0'; // Gris clair
            this.ctx.lineWidth = 1.5;
            this.roundRect(causeBoxX, causeBoxY, causeBoxWidth, causeBoxHeight, causeRadius);
            this.ctx.stroke();

            // Texte de la cause
            this.ctx.fillStyle = '#000000'; // Noir pour meilleure lisibilité
            this.ctx.font = '10px Arial';
            this.ctx.textAlign = 'center';
            this.ctx.textBaseline = 'middle';
            
            // Tronquer le texte si trop long
            let causeText = cause;
            if (typeof cause === 'object') {
                causeText = cause.data || cause.text || '';
            }
            
            const maxWidth = causeBoxWidth - 10;
            const metrics = this.ctx.measureText(causeText);
            if (metrics.width > maxWidth) {
                // Tronquer intelligemment
                let truncated = causeText;
                while (this.ctx.measureText(truncated + '...').width > maxWidth && truncated.length > 0) {
                    truncated = truncated.substring(0, truncated.length - 1);
                }
                causeText = truncated + '...';
            }
            
            this.ctx.fillText(causeText, causeEndX + causeBoxWidth / 2, causeY);
        });
    }

    /**
     * Méthode utilitaire pour dessiner un rectangle arrondi
     */
    roundRect(x, y, width, height, radius) {
        this.ctx.beginPath();
        this.ctx.moveTo(x + radius, y);
        this.ctx.lineTo(x + width - radius, y);
        this.ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
        this.ctx.lineTo(x + width, y + height - radius);
        this.ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
        this.ctx.lineTo(x + radius, y + height);
        this.ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
        this.ctx.lineTo(x, y + radius);
        this.ctx.quadraticCurveTo(x, y, x + radius, y);
        this.ctx.closePath();
    }

    /**
     * Méthode utilitaire pour ajuster la luminosité d'une couleur
     */
    adjustBrightness(color, percent) {
        // Convertir hex en RGB
        const hex = color.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);

        // Ajuster la luminosité
        const newR = Math.max(0, Math.min(255, r + (r * percent / 100)));
        const newG = Math.max(0, Math.min(255, g + (g * percent / 100)));
        const newB = Math.max(0, Math.min(255, b + (b * percent / 100)));

        // Convertir en hex
        return '#' + [newR, newG, newB].map(x => {
            const hex = Math.round(x).toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        }).join('');
    }

    /**
     * Exporte le diagramme en PDF
     */
    exportPDF() {
        if (!this.hasCanvasTarget) return;

        const { jsPDF } = window.jspdf;
        if (!jsPDF) {
            console.error('jsPDF non disponible');
            return;
        }

        const canvas = this.canvasTarget;
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'a4'
        });

        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();
        const imgWidth = canvas.width;
        const imgHeight = canvas.height;
        const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
        const imgX = (pdfWidth - imgWidth * ratio) / 2;
        const imgY = (pdfHeight - imgHeight * ratio) / 2;

        pdf.addImage(imgData, 'PNG', imgX, imgY, imgWidth * ratio, imgHeight * ratio);

        const currentDate = new Date().toLocaleDateString('fr-FR');
        pdf.setFontSize(8);
        pdf.setTextColor(150, 150, 150);
        pdf.text('Généré par OUTILS-QUALITÉ - www.outils-qualite.com', 10, pdfHeight - 5);

        const filename = `ishikawa-${Date.now()}.pdf`;
        pdf.save(filename);
    }

    /**
     * Exporte le diagramme en JPEG
     */
    exportJPEG() {
        if (!this.hasCanvasTarget) return;

        const canvas = this.canvasTarget;
        const imgData = canvas.toDataURL('image/jpeg', 0.9);
        
        const link = document.createElement('a');
        link.download = `ishikawa-${Date.now()}.jpg`;
        link.href = imgData;
        link.click();
    }

    /**
     * Exporte le diagramme en JSON
     */
    exportJSON() {
        const exportData = {
            metadata: {
                tool: "Diagramme d'Ishikawa",
                version: '2.0',
                exportDate: new Date().toISOString(),
                source: 'OUTILS-QUALITÉ',
            },
            diagram: this.diagramDataValue || {}
        };

        const dataStr = JSON.stringify(exportData, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `ishikawa-${Date.now()}.json`;
        link.click();
        URL.revokeObjectURL(url);
    }

    /**
     * Méthode publique pour mettre à jour les données du diagramme
     */
    updateDiagramData(diagramData) {
        this.diagramDataValue = diagramData;
        this.draw();
    }

    /**
     * Méthode publique pour redessiner le diagramme
     */
    redraw() {
        this.draw();
    }
}

