import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour le diagramme Ishikawa
 * Adapté pour travailler avec Canvas HTML5
 * Gère les modals, la sauvegarde et l'export
 */
export default class extends Controller {
    static targets = ['problemInput', 'categoryModal', 'causeModal', 'categorySelect', 'categoryName', 'causeName'];
    static outlets = ['ishikawaCanvas'];
    static values = {
        apiUrl: { type: String, default: '/api/ishikawa' },
        isAuthenticated: { type: Boolean, default: false },
        diagramId: { type: Number, default: null },
    };

    getIshikawaCanvasOutlet() {
        // Essayer d'abord avec l'outlet
        if (this.hasIshikawaCanvasOutlet) {
            return this.ishikawaCanvasOutlet;
        }
        // Fallback : chercher le contrôleur Canvas directement
        const canvasElement = this.element.querySelector('[data-controller*="ishikawa-canvas"]');
        if (canvasElement) {
            return this.application.getControllerForElementAndIdentifier(canvasElement, 'ishikawa-canvas');
        }
        return null;
    }

    connect() {
        console.log('Ishikawa controller connecté');

        // Variables d'état
        this.currentCategoryId = null;
        this.currentCauseIndex = null;
        this.editingCategory = false;
        this.editingCause = false;
        this.saveTimeout = null;
        this.currentToast = null;
        
        // Références aux handlers d'événements pour le nettoyage
        this.categoryModalHiddenHandler = null;
        this.categoryModalShownHandler = null;
        this.causeModalHiddenHandler = null;
        this.causeModalShownHandler = null;
        this.boundTurboLoad = null;
        this.boundTurboBeforeCache = null;
        this.boundLiveUpdate = null;

        // Catégories disponibles
        this.availableCategories = ["MATÉRIELS", "MESURE", "MACHINES", "MÉTHODES", "ENVIRONNEMENT", "PERSONNEL", "MANAGEMENT"];

        // Écouter les événements Turbo
        if (typeof window.Turbo !== 'undefined') {
            this.boundTurboLoad = this.handleTurboLoad.bind(this);
            document.addEventListener('turbo:load', this.boundTurboLoad);
            
            this.boundTurboBeforeCache = this.handleTurboBeforeCache.bind(this);
            document.addEventListener('turbo:before-cache', this.boundTurboBeforeCache);
        }

        // Écouter les événements Live Component pour mettre à jour le canvas
        this.boundLiveUpdate = this.handleLiveUpdate.bind(this);
        document.addEventListener('live:update', this.boundLiveUpdate);
    }

    handleTurboLoad() {
        if (window.location.pathname.includes('/ishikawa')) {
            setTimeout(() => this.ensureInitialization(), 150);
        }
    }

    handleTurboBeforeCache() {
        this.closeAllModals();
    }

    handleLiveUpdate(event) {
        // Mettre à jour le canvas quand les données Live Component changent
        if (this.hasIshikawaCanvasOutlet) {
            const canvasController = this.getIshikawaCanvasOutlet();
            if (canvasController && event.detail && event.detail.data) {
                // Récupérer les données depuis le Live Component
                const diagramData = {
                    problem: event.detail.data.problem,
                    categories: event.detail.data.categories
                };
                canvasController.updateDiagramData(diagramData);
            }
        }
    }

    disconnect() {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        this.closeAllModals();
        
        if (typeof window.Turbo !== 'undefined') {
            if (this.boundTurboLoad) {
                document.removeEventListener('turbo:load', this.boundTurboLoad);
            }
            if (this.boundTurboBeforeCache) {
                document.removeEventListener('turbo:before-cache', this.boundTurboBeforeCache);
            }
        }
        
        if (this.boundLiveUpdate) {
            document.removeEventListener('live:update', this.boundLiveUpdate);
        }
        
        this.currentCategoryId = null;
        this.currentCauseIndex = null;
        this.editingCategory = false;
        this.editingCause = false;
    }

    ensureInitialization() {
        // Vérifier que le canvas est initialisé
        if (this.hasIshikawaCanvasOutlet) {
            const canvasController = this.getIshikawaCanvasOutlet();
            if (canvasController) {
                canvasController.redraw();
            }
        }
    }

    // Gestion du problème
    updateProblem() {
        if (this.hasProblemInputTarget) {
            const problem = this.problemInputTarget.value.trim() || 'Problème non défini';
            
            // Émettre un événement pour mettre à jour le Live Component
            this.dispatch('problem-updated', { detail: { problem } });
            
            // Mettre à jour le canvas
            if (this.hasIshikawaCanvasOutlet) {
                const canvasController = this.getIshikawaCanvasOutlet();
                if (canvasController) {
                    const currentData = canvasController.diagramDataValue || {};
                    currentData.problem = problem;
                    canvasController.updateDiagramData(currentData);
                }
            }
        }
    }

    editProblem() {
        if (this.hasProblemInputTarget) {
            this.problemInputTarget.focus();
            this.problemInputTarget.select();
        }
    }

    // Actions pour les catégories
    addCategory() {
        if (this.canAddMoreCategories()) {
        this.currentCategoryId = null;
        this.editingCategory = true;
        this.openCategoryModal();
        } else {
            this.showNotification('Limite de 10 catégories atteinte', 'error');
        }
    }

    editCategory(event) {
        const categoryId = parseInt(event.currentTarget.dataset.categoryId);
        this.currentCategoryId = categoryId;
        this.editingCategory = true;
        this.openCategoryModal();
    }

    deleteCategory(event) {
        const categoryId = parseInt(event.currentTarget.dataset.categoryId);

        if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie et toutes ses causes ?')) {
            // La suppression sera gérée par le Live Component
            this.dispatch('category-deleted', { detail: { categoryId } });
            this.showNotification('Catégorie supprimée avec succès');
        }
    }

    canAddMoreCategories() {
        // Cette méthode sera appelée par le Live Component
        return true;
    }

    populateCategoryModal() {
        if (!this.hasCategorySelectTarget || !this.hasCategoryNameTarget) return;

        const select = this.categorySelectTarget;
        const customGroup = document.getElementById('customCategoryGroup');
        const categoryName = this.categoryNameTarget;

        select.innerHTML = '<option value="">-- Sélectionner une catégorie --</option>';

        // Récupérer les catégories utilisées depuis le Live Component
        const usedCategories = this.getUsedCategories();

        // Ajouter les catégories disponibles
        this.availableCategories.forEach((cat) => {
            if (!usedCategories.includes(cat) || 
                (this.currentCategoryId && this.getCategoryName(this.currentCategoryId) === cat)) {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = cat;
                select.appendChild(option);
            }
        });

        // Ajouter l'option personnalisée
        const customOption = document.createElement('option');
        customOption.value = 'custom';
        customOption.textContent = 'Catégorie personnalisée...';
        select.appendChild(customOption);

        // Si on édite une catégorie existante
        if (this.currentCategoryId) {
            const currentCategoryName = this.getCategoryName(this.currentCategoryId);
            
            setTimeout(() => {
                if (this.availableCategories.includes(currentCategoryName)) {
                    select.value = currentCategoryName;
                    if (customGroup) customGroup.style.display = 'none';
                    if (categoryName) categoryName.value = '';
                } else {
                    select.value = 'custom';
                    if (categoryName) categoryName.value = currentCategoryName;
                    if (customGroup) customGroup.style.display = 'block';
                }
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }, 10);
        } else {
            select.value = '';
            if (customGroup) customGroup.style.display = 'none';
            if (categoryName) categoryName.value = '';
        }

        // Gérer le changement de sélection
        const oldHandler = select._ishikawaChangeHandler;
        if (oldHandler) {
            select.removeEventListener('change', oldHandler);
        }
        
        select._ishikawaChangeHandler = () => {
            if (select.value === 'custom') {
                if (customGroup) customGroup.style.display = 'block';
                    setTimeout(() => {
                        if (categoryName) categoryName.focus();
                    }, 50);
            } else {
                if (customGroup) customGroup.style.display = 'none';
                    if (categoryName) categoryName.value = '';
            }
        };
        
        select.addEventListener('change', select._ishikawaChangeHandler);
    }

    openCategoryModal() {
        const modalElement = this.hasCategoryModalTarget ? this.categoryModalTarget : document.getElementById('categoryModal');
        
        if (!modalElement) {
            console.error('Modal categoryModal introuvable');
            return;
        }
        
        const title = modalElement.querySelector('#categoryModalTitle');
        if (title) {
            title.textContent = this.currentCategoryId ? 'Modifier la catégorie' : 'Ajouter une catégorie';
        }
        
        if (this.categoryModalHiddenHandler) {
            modalElement.removeEventListener('hidden.bs.modal', this.categoryModalHiddenHandler);
        }
        if (this.categoryModalShownHandler) {
            modalElement.removeEventListener('shown.bs.modal', this.categoryModalShownHandler);
        }
        
        this.populateCategoryModal();
        
        if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
            
            this.categoryModalHiddenHandler = () => {
                this.resetCategoryModal();
            };
            modalElement.addEventListener('hidden.bs.modal', this.categoryModalHiddenHandler, { once: true });
            
            this.categoryModalShownHandler = () => {
                setTimeout(() => {
                    this.populateCategoryModal();
                }, 150);
            };
            modalElement.addEventListener('shown.bs.modal', this.categoryModalShownHandler, { once: true });
            
            modal.show();
        }
    }

    closeCategoryModal() {
        const modalElement = this.hasCategoryModalTarget ? this.categoryModalTarget : document.getElementById('categoryModal');
        if (modalElement && typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
        this.resetCategoryModal();
    }
    
    resetCategoryModal() {
        const modalElement = this.hasCategoryModalTarget ? this.categoryModalTarget : document.getElementById('categoryModal');
        if (modalElement) {
            if (this.categoryModalHiddenHandler) {
                modalElement.removeEventListener('hidden.bs.modal', this.categoryModalHiddenHandler);
                this.categoryModalHiddenHandler = null;
            }
            if (this.categoryModalShownHandler) {
                modalElement.removeEventListener('shown.bs.modal', this.categoryModalShownHandler);
                this.categoryModalShownHandler = null;
            }
        }
        
        const select = this.hasCategorySelectTarget ? this.categorySelectTarget : document.getElementById('categorySelect');
        if (select && select._ishikawaChangeHandler) {
            select.removeEventListener('change', select._ishikawaChangeHandler);
            select._ishikawaChangeHandler = null;
        }
        
        this.currentCategoryId = null;
        this.editingCategory = false;
        
        if (this.hasCategorySelectTarget) {
            this.categorySelectTarget.value = '';
        }
        if (this.hasCategoryNameTarget) {
            this.categoryNameTarget.value = '';
        }
        
        const customGroup = document.getElementById('customCategoryGroup');
        if (customGroup) {
            customGroup.style.display = 'none';
        }
    }

    saveCategoryModal() {
        if (!this.hasCategorySelectTarget || !this.hasCategoryNameTarget) return;

        const select = this.categorySelectTarget;
        const categoryName = this.categoryNameTarget;

        let newName = '';
        if (select.value === 'custom') {
            newName = categoryName.value.trim().toUpperCase();
            if (!newName) {
                this.showNotification('Veuillez saisir un nom de catégorie', 'error');
                return;
            }
        } else if (select.value) {
            newName = select.value;
        } else {
            this.showNotification('Veuillez sélectionner une catégorie', 'error');
            return;
        }

        // Vérifier les doublons
        const usedCategories = this.getUsedCategories();
        if (usedCategories.includes(newName) && (!this.currentCategoryId || this.getCategoryName(this.currentCategoryId) !== newName)) {
            this.showNotification('Cette catégorie existe déjà', 'error');
            return;
        }

        // Émettre un événement pour que le Live Component gère l'ajout/modification
        this.dispatch('category-saved', {
            detail: {
                categoryId: this.currentCategoryId,
                categoryName: newName
            }
        });

        this.closeCategoryModal();
        this.showNotification(this.currentCategoryId ? 'Catégorie modifiée avec succès' : 'Catégorie ajoutée avec succès');
    }

    // Actions pour les causes
    addCause(event) {
        const categoryId = parseInt(event.currentTarget.dataset.categoryId);
        this.currentCategoryId = categoryId;
        this.currentCauseIndex = null;
        this.editingCause = true;
        if (this.hasCauseNameTarget) {
            this.causeNameTarget.value = '';
        }
        this.openCauseModal();
    }

    editCause(event) {
        const categoryId = parseInt(event.currentTarget.dataset.categoryId);
        const causeIndex = parseInt(event.currentTarget.dataset.causeIndex);
        this.currentCategoryId = categoryId;
        this.currentCauseIndex = causeIndex;
        this.editingCause = true;

        const causeText = this.getCauseText(categoryId, causeIndex);
        if (causeText && this.hasCauseNameTarget) {
            this.causeNameTarget.value = causeText;
        }

        this.openCauseModal();
    }

    deleteCause(event) {
        const categoryId = parseInt(event.currentTarget.dataset.categoryId);
        const causeIndex = parseInt(event.currentTarget.dataset.causeIndex);
        
        // Émettre un événement pour que le Live Component gère la suppression
        this.dispatch('cause-deleted', {
            detail: { categoryId, causeIndex }
        });
        
            this.showNotification('Cause supprimée avec succès');
    }

    openCauseModal() {
        const modalElement = this.hasCauseModalTarget ? this.causeModalTarget : document.getElementById('causeModal');
        
        if (!modalElement) {
            console.error('Modal causeModal introuvable');
            return;
        }
        
        const title = modalElement.querySelector('#causeModalTitle');
        if (title) {
            title.textContent = this.currentCauseIndex !== null ? 'Modifier la cause' : 'Ajouter une cause';
        }
        
        if (this.currentCauseIndex !== null && this.hasCauseNameTarget) {
            const causeText = this.getCauseText(this.currentCategoryId, this.currentCauseIndex);
            if (causeText) {
                this.causeNameTarget.value = causeText;
            }
        } else if (this.hasCauseNameTarget) {
            this.causeNameTarget.value = '';
        }
        
        if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
            
            modalElement.addEventListener('hidden.bs.modal', () => {
                this.resetCauseModal();
            }, { once: true });
            
            modal.show();
            
            modalElement.addEventListener('shown.bs.modal', () => {
                if (this.currentCauseIndex !== null && this.hasCauseNameTarget) {
                    const causeText = this.getCauseText(this.currentCategoryId, this.currentCauseIndex);
                    if (causeText) {
                        this.causeNameTarget.value = causeText;
                        setTimeout(() => {
                            this.causeNameTarget.focus();
                            this.causeNameTarget.select();
                        }, 100);
                    }
                } else if (this.hasCauseNameTarget) {
                    this.causeNameTarget.value = '';
                    setTimeout(() => {
                        if (this.hasCauseNameTarget) {
                            this.causeNameTarget.focus();
                        }
                    }, 100);
                }
            }, { once: true });
        }
    }

    closeCauseModal() {
        const modalElement = this.hasCauseModalTarget ? this.causeModalTarget : document.getElementById('causeModal');
        if (modalElement && typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
        this.resetCauseModal();
    }
    
    closeAllModals() {
        if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
            const categoryModal = this.hasCategoryModalTarget ? this.categoryModalTarget : document.getElementById('categoryModal');
            if (categoryModal) {
                const modalInstance = window.bootstrap.Modal.getInstance(categoryModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
            
            const causeModal = this.hasCauseModalTarget ? this.causeModalTarget : document.getElementById('causeModal');
            if (causeModal) {
                const modalInstance = window.bootstrap.Modal.getInstance(causeModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
            
            document.body.classList.remove('modal-open');
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
        }
        
        this.resetCategoryModal();
        this.resetCauseModal();
    }

    resetCauseModal() {
        this.currentCategoryId = null;
        this.currentCauseIndex = null;
        this.editingCause = false;
        if (this.hasCauseNameTarget) {
            this.causeNameTarget.value = '';
        }
    }

    saveCauseModal() {
        if (!this.hasCauseNameTarget) return;

        const causeName = this.causeNameTarget.value.trim();

        if (!causeName) {
            this.showNotification('Veuillez saisir une description de cause', 'error');
            return;
        }

        // Émettre un événement pour que le Live Component gère l'ajout/modification
        this.dispatch('cause-saved', {
            detail: {
                categoryId: this.currentCategoryId,
                causeIndex: this.currentCauseIndex,
                causeName: causeName
            }
        });

        this.closeCauseModal();
        this.showNotification(this.currentCauseIndex !== null ? 'Cause modifiée avec succès' : 'Cause ajoutée avec succès');
    }

    // Fonctions d'export (déléguées au contrôleur Canvas)
    exportPDF() {
        const canvasController = this.getIshikawaCanvasOutlet();
        if (canvasController && typeof canvasController.exportPDF === 'function') {
            canvasController.exportPDF();
        } else {
            console.warn('Contrôleur Canvas non disponible pour l\'export PDF');
        }
    }

    exportJPEG() {
        const canvasController = this.getIshikawaCanvasOutlet();
        if (canvasController && typeof canvasController.exportJPEG === 'function') {
            canvasController.exportJPEG();
        } else {
            console.warn('Contrôleur Canvas non disponible pour l\'export JPEG');
        }
    }

    exportJSON() {
        const canvasController = this.getIshikawaCanvasOutlet();
        if (canvasController && typeof canvasController.exportJSON === 'function') {
            canvasController.exportJSON();
        } else {
            // Fallback : exporter depuis le Live Component
            this.exportJSONFromLiveComponent();
        }
    }

    exportJSONFromLiveComponent() {
        // Récupérer les données depuis le Live Component
        const liveComponent = this.element.closest('[data-live-id]');
        if (liveComponent) {
            // Les données seront disponibles via les événements Live Component
            // Pour l'instant, on utilise une structure par défaut
        const exportData = {
            metadata: {
                tool: "Diagramme d'Ishikawa",
                    version: '2.0',
                exportDate: new Date().toISOString(),
                source: 'OUTILS-QUALITÉ',
            },
            diagram: {
                    problem: this.problemInputTarget?.value || 'Problème non défini',
                    categories: []
            },
        };

        const dataStr = JSON.stringify(exportData, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `ishikawa-${Date.now()}.json`;
        link.click();
        URL.revokeObjectURL(url);

        this.showNotification('Données exportées en JSON');
        }
    }

    // Gestion des événements clavier
    handleKeydown(event) {
        if (event.key === 'Escape') {
            this.closeAllModals();
        }

        if (event.ctrlKey && event.key === 's') {
            event.preventDefault();
            this.exportJSON();
        }
    }

    // Méthodes utilitaires
    getUsedCategories() {
        // Récupérer les catégories utilisées depuis le Live Component ou le canvas
        if (this.hasIshikawaCanvasOutlet) {
            const canvasController = this.getIshikawaCanvasOutlet();
            if (canvasController && canvasController.diagramDataValue && canvasController.diagramDataValue.categories) {
                return canvasController.diagramDataValue.categories.map(cat => cat.name);
            }
        }
        return [];
    }

    getCategoryName(categoryId) {
        if (this.hasIshikawaCanvasOutlet) {
            const canvasController = this.getIshikawaCanvasOutlet();
            if (canvasController && canvasController.diagramDataValue && canvasController.diagramDataValue.categories) {
                const category = canvasController.diagramDataValue.categories.find(cat => cat.id === categoryId);
                return category ? category.name : '';
            }
        }
        return '';
    }

    getCauseText(categoryId, causeIndex) {
        if (this.hasIshikawaCanvasOutlet) {
            const canvasController = this.getIshikawaCanvasOutlet();
            if (canvasController && canvasController.diagramDataValue && canvasController.diagramDataValue.categories) {
                const category = canvasController.diagramDataValue.categories.find(cat => cat.id === categoryId);
                if (category && category.causes && category.causes[causeIndex]) {
                    const cause = category.causes[causeIndex];
                    return typeof cause === 'string' ? cause : (cause.data || cause.text || '');
                }
            }
        }
        return '';
    }

    // Gérer le réordonnancement des catégories via Sortable
    updateCategoryOrder(event) {
        const sortableElement = event.target.closest('[data-controller*="sortable"]');
        if (!sortableElement) return;
        
        // Récupérer tous les éléments de catégories dans leur nouvel ordre
        const categoryCards = sortableElement.querySelectorAll('.category-card');
        const newOrder = [];
        
        categoryCards.forEach((card, index) => {
            const categoryId = parseInt(card.dataset.categoryId);
            if (!isNaN(categoryId)) {
                newOrder.push({
                    id: categoryId,
                    newIndex: index
                });
            }
        });
        
        // Mettre à jour l'ordre via le Live Component
        if (newOrder.length > 0) {
            const liveComponent = this.element.closest('[data-live-id]');
            if (liveComponent) {
                // Appeler l'action Live Component pour réorganiser
                this.updateCategoriesOrder(newOrder);
            }
        }
    }

    async updateCategoriesOrder(newOrder) {
        try {
            const liveComponent = this.element.closest('[data-live-id]');
            if (liveComponent) {
                const { getComponent } = await import('@symfony/ux-live-component');
                const component = await getComponent(liveComponent);
                
                // Appeler l'action Live Component
                await component.action('reorderCategories', {
                    order: newOrder.map(item => item.id.toString())
                });
                
                this.showNotification('Ordre des catégories mis à jour');
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour de l\'ordre:', error);
            this.showNotification('Erreur lors de la mise à jour de l\'ordre', 'error');
        }
    }

    // Notifications avec Toastify
    showNotification(message, type = 'success') {
        const Toastify = window.Toastify;

        if (this.currentToast) {
            this.currentToast.hideToast();
            this.currentToast = null;
        }

        if (typeof Toastify !== 'undefined') {
            const backgroundColor = type === 'success' 
                ? 'linear-gradient(to right, #00b09b, #96c93d)'
                : type === 'error' 
                ? 'linear-gradient(to right, #ff6b6b, #ee5a6f)'
                : type === 'warning'
                ? 'linear-gradient(to right, #ffa500, #ff8c00)'
                : 'linear-gradient(to right, #3498db, #2980b9)';

            this.currentToast = Toastify({
                text: message,
                duration: 3000,
                gravity: 'top',
                position: 'right',
                backgroundColor: backgroundColor,
                stopOnFocus: true,
                callback: () => {
                    this.currentToast = null;
                },
            });
            this.currentToast.showToast();
        } else {
            alert(message);
        }
    }
}
