import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/**
 * Contrôleur Stimulus pour le drag & drop sur le canvas Ishikawa
 * Gère le déplacement des catégories et causes sur le canvas
 */
export default class extends Controller {
    static values = {
        categoryId: { type: Number, default: null },
        canvasWidth: { type: Number, default: 1400 },
        canvasHeight: { type: Number, default: 800 },
        spineY: { type: Number, default: 400 }
    };

    connect() {
        this.isDragging = false;
        this.dragType = null; // 'category' ou 'cause'
        this.dragCategoryId = null;
        this.dragCauseIndex = null;
        this.startX = 0;
        this.startY = 0;
        this.initialSpineX = 0;
        this.initialAngle = 0;
        this.initialBranchLength = 0;
        
        // Bind les méthodes pour pouvoir les retirer plus tard
        this.boundMouseMove = this.handleMouseMove.bind(this);
        this.boundMouseUp = this.handleMouseUp.bind(this);
    }

    disconnect() {
        this.stopDrag();
    }

    /**
     * Démarre le drag d'une catégorie
     */
    startCategoryDrag(event) {
        event.preventDefault();
        event.stopPropagation();
        
        this.isDragging = true;
        this.dragType = 'category';
        this.dragCategoryId = this.categoryIdValue;
        
        const canvas = this.element;
        const rect = canvas.getBoundingClientRect();
        this.startX = event.clientX - rect.left;
        this.startY = event.clientY - rect.top;
        
        // Trouver la catégorie actuelle pour obtenir ses propriétés
        const category = this.getCategoryData(this.dragCategoryId);
        if (category) {
            this.initialSpineX = category.spineX || 200;
            this.initialAngle = category.angle || 0;
            this.initialBranchLength = category.branchLength || 150;
        }
        
        // Ajouter les listeners
        document.addEventListener('mousemove', this.boundMouseMove);
        document.addEventListener('mouseup', this.boundMouseUp);
        
        canvas.style.cursor = 'grabbing';
    }

    /**
     * Démarre le drag d'une cause
     */
    startCauseDrag(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const causeIndex = parseInt(event.currentTarget.dataset.causeIndex);
        if (isNaN(causeIndex)) return;
        
        this.isDragging = true;
        this.dragType = 'cause';
        this.dragCategoryId = this.categoryIdValue;
        this.dragCauseIndex = causeIndex;
        
        const canvas = this.element;
        const rect = canvas.getBoundingClientRect();
        this.startX = event.clientX - rect.left;
        this.startY = event.clientY - rect.top;
        
        // Ajouter les listeners
        document.addEventListener('mousemove', this.boundMouseMove);
        document.addEventListener('mouseup', this.boundMouseUp);
        
        canvas.style.cursor = 'grabbing';
    }

    /**
     * Gère le mouvement de la souris pendant le drag
     */
    handleMouseMove(event) {
        if (!this.isDragging) return;
        
        const canvas = this.element;
        const rect = canvas.getBoundingClientRect();
        const currentX = event.clientX - rect.left;
        const currentY = event.clientY - rect.top;
        
        const deltaX = currentX - this.startX;
        const deltaY = currentY - this.startY;
        
        if (this.dragType === 'category') {
            this.handleCategoryDrag(deltaX, deltaY);
        } else if (this.dragType === 'cause') {
            this.handleCauseDrag(deltaX, deltaY);
        }
    }

    /**
     * Gère le drag d'une catégorie
     */
    handleCategoryDrag(deltaX, deltaY) {
        // Calculer la nouvelle position sur la spine
        let newSpineX = this.initialSpineX + deltaX;
        newSpineX = Math.max(150, Math.min(newSpineX, this.canvasWidthValue - 250));
        
        // Calculer le nouvel angle basé sur la position Y
        const angleRad = Math.atan2(deltaY, deltaX);
        let newAngle = angleRad * (180 / Math.PI);
        
        // Limiter l'angle entre -80 et 80 degrés
        newAngle = Math.max(-80, Math.min(80, newAngle));
        
        // Calculer la nouvelle longueur de branche
        const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
        let newBranchLength = Math.max(100, Math.min(250, this.initialBranchLength + distance / 2));
        
        // Émettre un événement pour mettre à jour visuellement
        this.dispatch('category-dragging', {
            detail: {
                categoryId: this.dragCategoryId,
                spineX: newSpineX,
                angle: newAngle,
                branchLength: newBranchLength
            }
        });
        
        // Mettre à jour les valeurs temporaires
        this.currentSpineX = newSpineX;
        this.currentAngle = newAngle;
        this.currentBranchLength = newBranchLength;
    }

    /**
     * Gère le drag d'une cause
     */
    handleCauseDrag(deltaX, deltaY) {
        // Pour les causes, on peut ajuster leur position le long de la branche
        // Émettre un événement pour mettre à jour visuellement
        this.dispatch('cause-dragging', {
            detail: {
                categoryId: this.dragCategoryId,
                causeIndex: this.dragCauseIndex,
                deltaX: deltaX,
                deltaY: deltaY
            }
        });
    }

    /**
     * Gère la fin du drag
     */
    handleMouseUp(event) {
        if (!this.isDragging) return;
        
        if (this.dragType === 'category' && this.currentSpineX !== undefined) {
            // Sauvegarder la position finale via Live Component
            this.saveCategoryPosition(
                this.dragCategoryId,
                this.currentSpineX,
                this.currentAngle,
                this.currentBranchLength
            );
        }
        
        this.stopDrag();
    }

    /**
     * Arrête le drag
     */
    stopDrag() {
        this.isDragging = false;
        this.dragType = null;
        this.dragCategoryId = null;
        this.dragCauseIndex = null;
        
        // Retirer les listeners
        document.removeEventListener('mousemove', this.boundMouseMove);
        document.removeEventListener('mouseup', this.boundMouseUp);
        
        const canvas = this.element;
        if (canvas) {
            canvas.style.cursor = 'default';
        }
    }

    /**
     * Sauvegarde la position d'une catégorie via Live Component
     */
    async saveCategoryPosition(categoryId, spineX, angle, branchLength) {
        try {
            const liveComponent = this.element.closest('[data-live-id]');
            if (liveComponent) {
                const component = await getComponent(liveComponent);
                await component.action('updateCategoryPosition', {
                    categoryId: categoryId.toString(),
                    spineX: Math.round(spineX),
                    angle: Math.round(angle * 10) / 10, // Arrondir à 1 décimale
                    branchLength: Math.round(branchLength)
                });
            }
        } catch (error) {
            console.error('Erreur lors de la sauvegarde de la position:', error);
        }
    }

    /**
     * Récupère les données d'une catégorie depuis le Live Component
     */
    getCategoryData(categoryId) {
        // Cette méthode sera appelée par le contrôleur ishikawa_controller
        // qui a accès aux données du diagramme
        return null;
    }

    /**
     * Méthode publique pour définir les données des catégories
     */
    setCategoriesData(categories) {
        this.categoriesData = categories;
    }

    /**
     * Récupère les données d'une catégorie depuis le cache local
     */
    getCategoryData(categoryId) {
        if (!this.categoriesData) return null;
        return this.categoriesData.find(cat => cat.id === categoryId);
    }
}

