import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/**
 * Contrôleur Stimulus pour appeler les actions Live Component
 * Utilisé pour les boutons qui déclenchent des actions Live Component
 */
export default class extends Controller {
    static values = {
        action: String,
        categoryId: Number,
        causeIndex: Number,
        categoryName: String,
        causeName: String,
    };

    async callAction(event) {
        event.preventDefault();
        
        try {
            // Trouver le composant Live Component parent
            const liveComponent = this.element.closest('[data-live-id]');
            if (!liveComponent) {
                console.error('Composant Live Component non trouvé');
                return;
            }
            
            const component = await getComponent(liveComponent);
            
            // Construire les paramètres selon l'action
            let params = {};
            
            switch (this.actionValue) {
                case 'openEditCategoryModal':
                    params = { categoryId: this.categoryIdValue };
                    break;
                case 'deleteCategory':
                    params = { categoryId: this.categoryIdValue };
                    break;
                case 'openAddCauseModal':
                    params = { categoryId: this.categoryIdValue };
                    break;
                case 'openEditCauseModal':
                    params = {
                        categoryId: this.categoryIdValue,
                        causeIndex: this.causeIndexValue
                    };
                    break;
                case 'deleteCause':
                    params = {
                        categoryId: this.categoryIdValue,
                        causeIndex: this.causeIndexValue
                    };
                    break;
                case 'addCategory':
                    // Récupérer le nom depuis le select ou l'input
                    const categoryModal = this.element.closest('.modal');
                    const categorySelect = categoryModal?.querySelector('#categorySelect');
                    const categoryNameInput = categoryModal?.querySelector('#categoryName');
                    
                    if (categorySelect?.value === 'custom' && categoryNameInput?.value) {
                        params = { categoryName: categoryNameInput.value };
                    } else if (categorySelect?.value && categorySelect.value !== 'custom') {
                        params = { categoryName: categorySelect.value };
                    }
                    break;
                case 'updateCategory':
                    // Récupérer le nom depuis le select ou l'input
                    const updateCategoryModal = this.element.closest('.modal');
                    const updateCategorySelect = updateCategoryModal?.querySelector('#categorySelect');
                    const updateCategoryNameInput = updateCategoryModal?.querySelector('#categoryName');
                    
                    if (updateCategorySelect?.value === 'custom' && updateCategoryNameInput?.value) {
                        params = { categoryName: updateCategoryNameInput.value };
                    } else if (updateCategorySelect?.value && updateCategorySelect.value !== 'custom') {
                        params = { categoryName: updateCategorySelect.value };
                    }
                    break;
                case 'addCause':
                    // Récupérer le nom depuis l'input
                    const causeModal = this.element.closest('.modal');
                    const causeNameInput = causeModal?.querySelector('#causeName');
                    if (causeNameInput?.value) {
                        params = { causeName: causeNameInput.value };
                    }
                    break;
                case 'updateCause':
                    // Récupérer le nom depuis l'input
                    const updateCauseModal = this.element.closest('.modal');
                    const updateCauseNameInput = updateCauseModal?.querySelector('#causeName');
                    if (updateCauseNameInput?.value) {
                        params = { causeName: updateCauseNameInput.value };
                    }
                    break;
                // Actions sans paramètres
                case 'openAddCategoryModal':
                case 'closeCategoryModal':
                case 'closeCauseModal':
                case 'resetAllCauses':
                case 'save':
                    params = {};
                    break;
            }
            
            // Appeler l'action
            await component.action(this.actionValue, params);
            
            // Après l'action, initialiser les modals Bootstrap si nécessaire
            if (this.actionValue === 'openAddCategoryModal' || this.actionValue === 'openEditCategoryModal' ||
                this.actionValue === 'openAddCauseModal' || this.actionValue === 'openEditCauseModal') {
                // Attendre que le Live Component ait mis à jour le DOM
                setTimeout(() => {
                    const modalId = (this.actionValue.includes('Category')) ? 'categoryModal' : 'causeModal';
                    const modalElement = liveComponent.querySelector(`#${modalId}`);
                    if (modalElement && window.bootstrap) {
                        // Vérifier si le modal n'est pas déjà initialisé
                        let modal = window.bootstrap.Modal.getInstance(modalElement);
                        if (!modal) {
                            modal = new window.bootstrap.Modal(modalElement, {
                                backdrop: 'static',
                                keyboard: false
                            });
                        }
                        modal.show();
                    }
                }, 200);
            }
            
            // Fermer les modals si nécessaire
            if (this.actionValue === 'closeCategoryModal' || this.actionValue === 'closeCauseModal') {
                setTimeout(() => {
                    const modalId = (this.actionValue.includes('Category')) ? 'categoryModal' : 'causeModal';
                    const modalElement = liveComponent.querySelector(`#${modalId}`);
                    if (modalElement && window.bootstrap) {
                        const modal = window.bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                    }
                }, 50);
            }
            
        } catch (error) {
            console.error('Erreur lors de l\'appel de l\'action Live Component:', error);
        }
    }
}
