import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour gérer les modals Bootstrap
 * Utilise une approche simple basée sur style.display (comme le code initial)
 * Compatible avec Symfony Live Components
 * 
 * Documentation Symfony UX Live Component:
 * https://symfony.com/bundles/ux-live-component/current/index.html
 */
export default class extends Controller {
    static values = {
        autoShow: { type: Boolean, default: false }
    };

    /**
     * Watcher pour autoShowValue - ouvre le modal quand autoShow devient true
     */
    autoShowValueChanged(newValue, oldValue) {
        if (newValue === true && oldValue === false) {
            this.show();
        } else if (newValue === false && oldValue === true) {
            this.hide();
        }
    }
    
    connect() {
        // Si autoShow est activé, ouvrir le modal automatiquement
        if (this.autoShowValue) {
            // Utiliser requestAnimationFrame pour s'assurer que le DOM est prêt
            requestAnimationFrame(() => {
                this.show();
            });
        }
        
               // Écouter l'événement browser "modal:close" pour fermer le modal
               this.boundCloseModal = (event) => {
                   const modalId = event.detail?.modalId;
                   if (!modalId || this.element.id === modalId) {
                       console.log('[bootstrap-modal] modal:close event received', {
                           modalId: modalId || 'all',
                           elementId: this.element.id
                       });
                       this.hide();
                   }
               };
               document.addEventListener('modal:close', this.boundCloseModal);
        
        // Écouter l'événement browser "modal:open" pour ouvrir le modal
        this.boundOpenModal = (event) => {
            console.log('[bootstrap-modal] modal:open event received', {
                modalId: event.detail?.modalId,
                elementId: this.element.id,
                detail: event.detail
            });
            const modalId = event.detail?.modalId;
            if (modalId && this.element.id === modalId) {
                console.log('[bootstrap-modal] Modal ID matches, opening modal');
                // Utiliser requestAnimationFrame puis setTimeout pour s'assurer que Live Component a mis à jour le DOM
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        if (this.element && document.contains(this.element)) {
                            console.log('[bootstrap-modal] Opening modal directly');
                            this.show();
                        } else {
                            console.warn('[bootstrap-modal] Element not in DOM yet, waiting...');
                            // Réessayer après un délai supplémentaire
                            setTimeout(() => {
                                if (this.element && document.contains(this.element)) {
                                    console.log('[bootstrap-modal] Opening modal after retry');
                                    this.show();
                                } else {
                                    console.error('[bootstrap-modal] Element still not in DOM after retry');
                                }
                            }, 300);
                        }
                    }, 50);
                });
            } else {
                console.log('[bootstrap-modal] Modal ID does not match', {
                    modalId,
                    elementId: this.element.id
                });
            }
        };
        // Utiliser capture phase pour intercepter l'événement plus tôt
        document.addEventListener('modal:open', this.boundOpenModal, true);
        
               // Écouter les événements Turbo/Live Component pour détecter les mises à jour
               this.boundTurboRender = (event) => {
                   console.log('[bootstrap-modal] Turbo/Live render event received', {
                       type: event.type,
                       detail: event.detail,
                       timestamp: new Date().toISOString()
                   });
                   // Vérifier si le modal doit être ouvert en lisant les props Live
                   // Utiliser requestAnimationFrame puis setTimeout pour s'assurer que Live Component a terminé sa mise à jour
                   requestAnimationFrame(() => {
                       setTimeout(() => {
                           this.checkAndOpenModal();
                       }, 150);
                   });
               };
               document.addEventListener('turbo:render', this.boundTurboRender);
               document.addEventListener('turbo:stream-render', this.boundTurboRender);
               document.addEventListener('live:render', this.boundTurboRender);
               
               // Écouter l'événement live:action:success pour ouvrir le modal après un LiveAction
               this.boundLiveActionSuccess = (event) => {
                   console.log('[bootstrap-modal] Live action success event received', {
                       type: event.type,
                       detail: event.detail,
                       timestamp: new Date().toISOString()
                   });
                   // Vérifier si le modal doit être ouvert après un court délai
                   requestAnimationFrame(() => {
                       setTimeout(() => {
                           this.checkAndOpenModal();
                       }, 200);
                   });
               };
               document.addEventListener('live:action:success', this.boundLiveActionSuccess);
        
        // Écouter les événements de clic sur les boutons qui déclenchent les LiveActions pour ouvrir le modal directement
        // Cette approche contourne le problème où les LiveProps ne sont pas mises à jour après un LiveAction
        this.boundButtonClick = (event) => {
            const target = event.target.closest('[data-live-action-param]');
            if (!target) return;
            
            const actionParam = target.getAttribute('data-live-action-param');
            const modalId = this.element.id;
            
            // Ouvrir le modal directement si c'est une action pour ouvrir le modal de catégorie
            if (modalId === 'categoryModal' && (actionParam === 'openEditCategoryModal' || actionParam === 'openAddCategoryModal')) {
                // Extraire le categoryId depuis l'attribut data-live-action-param-category-id
                const categoryIdAttr = target.getAttribute('data-live-action-param-category-id');
                const categoryId = categoryIdAttr ? parseInt(categoryIdAttr, 10) : null;
                
                console.log('[bootstrap-modal] Opening modal directly after button click', {
                    actionParam,
                    modalId,
                    categoryId
                });
                
                // Attendre un court délai pour que Live Component commence à traiter l'action
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        if (this.element && document.contains(this.element)) {
                            this.updateModalContent(categoryId); // Mettre à jour le contenu avec le categoryId
                            this.show();
                        }
                    }, 300);
                });
            }
            
            // Ouvrir le modal directement si c'est une action pour ouvrir le modal de cause
            if (modalId === 'causeModal' && (actionParam === 'openEditCauseModal' || actionParam === 'openAddCauseModal')) {
                // Extraire le categoryId et causeIndex depuis les attributs
                const causeCategoryIdAttr = target.getAttribute('data-live-action-param-category-id');
                const causeIndexAttr = target.getAttribute('data-live-action-param-cause-index');
                const causeCategoryId = causeCategoryIdAttr ? parseInt(causeCategoryIdAttr, 10) : null;
                const causeIndex = causeIndexAttr !== null ? parseInt(causeIndexAttr, 10) : null;
                
                console.log('[bootstrap-modal] Opening modal directly after button click', {
                    actionParam,
                    modalId,
                    causeCategoryId,
                    causeIndex
                });
                
                // Attendre un court délai pour que Live Component commence à traiter l'action
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        if (this.element && document.contains(this.element)) {
                            this.updateModalContent(null, causeCategoryId, causeIndex); // Mettre à jour le contenu avec les IDs
                            this.show();
                        }
                    }, 300);
                });
            }
        };
        
        // Écouter les clics sur les boutons qui déclenchent les LiveActions
        document.addEventListener('click', this.boundButtonClick, true);
        
        // Écouter les événements Turbo/Live Component pour mettre à jour le contenu du modal après le re-render
        this.boundUpdateContent = (event) => {
            if (this.isVisible()) {
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        this.updateModalContent();
                    }, 100);
                });
            }
        };
        document.addEventListener('turbo:render', this.boundUpdateContent);
        document.addEventListener('turbo:stream-render', this.boundUpdateContent);
        document.addEventListener('live:render', this.boundUpdateContent);
        
        // Écouter aussi les événements Turbo avant le render pour voir ce qui se passe
        this.boundTurboBeforeRender = (event) => {
            console.log('[bootstrap-modal] Turbo before render event received', {
                type: event.type,
                detail: event.detail,
                timestamp: new Date().toISOString()
            });
        };
        document.addEventListener('turbo:before-render', this.boundTurboBeforeRender);
        
        // Écouter les événements Live Component spécifiques
        this.boundLiveComponentUpdate = (event) => {
            console.log('[bootstrap-modal] Live Component update event received', {
                type: event.type,
                detail: event.detail,
                timestamp: new Date().toISOString()
            });
            setTimeout(() => {
                this.checkAndOpenModal();
            }, 150);
        };
        document.addEventListener('live:component:update', this.boundLiveComponentUpdate);
        
        // Écouter aussi les événements Turbo spécifiques pour les Live Components
        this.boundTurboFrameLoad = () => {
            console.log('[bootstrap-modal] Turbo frame load event received');
            setTimeout(() => {
                this.checkAndOpenModal();
            }, 150);
        };
        document.addEventListener('turbo:frame-load', this.boundTurboFrameLoad);
        
        // Utiliser MutationObserver pour surveiller les changements dans data-live-props-value
        const liveComponent = document.querySelector('[data-controller*="live"]');
        if (liveComponent) {
            this.observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'data-live-props-value') {
                        const newValue = mutation.target.getAttribute('data-live-props-value');
                        console.log('[bootstrap-modal] Live props changed detected by MutationObserver', {
                            newValue: newValue,
                            timestamp: new Date().toISOString()
                        });
                        // Utiliser requestAnimationFrame puis setTimeout pour s'assurer que Live Component a terminé
                        requestAnimationFrame(() => {
                            setTimeout(() => {
                                this.checkAndOpenModal();
                            }, 150);
                        });
                    }
                });
            });
            
            this.observer.observe(liveComponent, {
                attributes: true,
                attributeFilter: ['data-live-props-value'],
                childList: true, // Surveiller aussi les changements d'enfants (au cas où le Live Component est remplacé)
                subtree: true // Surveiller aussi les sous-arbres
            });
        }
        
        // Fermer le modal en cliquant sur le backdrop (comme dans le code initial)
        this.boundBackdropClick = (event) => {
            if (event.target === this.element) {
                this.hide();
            }
        };
        this.element.addEventListener('click', this.boundBackdropClick);
        
        // Fermer le modal avec la touche Escape (comme dans le code initial)
        this.boundEscapeKey = (event) => {
            if (event.key === 'Escape' && this.isVisible()) {
                this.hide();
            }
        };
        document.addEventListener('keydown', this.boundEscapeKey);
        
        // Écouter la soumission du formulaire pour mettre à jour data-live-action-param-editing-category-id
        this.boundFormSubmit = (event) => {
            const form = event.target.closest('form');
            if (!form || form.closest('#categoryModal') !== this.element) {
                return;
            }
            
            // Mettre à jour data-live-action-param-editing-category-id depuis le champ caché
            const hiddenInput = form.querySelector('input[name="editingCategoryId"]');
            if (hiddenInput && hiddenInput.value) {
                form.setAttribute('data-live-action-param-editing-category-id', hiddenInput.value);
                console.log('[bootstrap-modal] Updated data-live-action-param-editing-category-id from hidden input', {
                    value: hiddenInput.value,
                    formAction: form.getAttribute('data-live-action-param')
                });
            }
        };
        // Écouter les événements de soumission de formulaire (capture phase pour intercepter avant Live Component)
        document.addEventListener('submit', this.boundFormSubmit, true);
    }

    disconnect() {
        if (this.boundCloseModal) {
            document.removeEventListener('modal:close', this.boundCloseModal);
        }
        if (this.boundOpenModal) {
            document.removeEventListener('modal:open', this.boundOpenModal, true);
        }
        if (this.boundTurboRender) {
            document.removeEventListener('turbo:render', this.boundTurboRender);
            document.removeEventListener('turbo:stream-render', this.boundTurboRender);
            document.removeEventListener('live:render', this.boundTurboRender);
        }
        if (this.boundLiveActionSuccess) {
            document.removeEventListener('live:action:success', this.boundLiveActionSuccess);
        }
        if (this.boundButtonClick) {
            document.removeEventListener('click', this.boundButtonClick, true);
        }
        if (this.boundUpdateContent) {
            document.removeEventListener('turbo:render', this.boundUpdateContent);
            document.removeEventListener('turbo:stream-render', this.boundUpdateContent);
            document.removeEventListener('live:render', this.boundUpdateContent);
        }
        if (this.boundTurboBeforeRender) {
            document.removeEventListener('turbo:before-render', this.boundTurboBeforeRender);
        }
        if (this.boundLiveComponentUpdate) {
            document.removeEventListener('live:component:update', this.boundLiveComponentUpdate);
        }
        if (this.boundTurboFrameLoad) {
            document.removeEventListener('turbo:frame-load', this.boundTurboFrameLoad);
        }
        if (this.boundBackdropClick) {
            this.element.removeEventListener('click', this.boundBackdropClick);
        }
        if (this.boundEscapeKey) {
            document.removeEventListener('keydown', this.boundEscapeKey);
        }
        if (this.boundFormSubmit) {
            document.removeEventListener('submit', this.boundFormSubmit, true);
        }
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    /**
     * Affiche le modal en utilisant style.display (comme dans le code initial)
     */
    show() {
        if (!this.element || !document.contains(this.element)) {
            return;
        }

        // Sauvegarder l'élément qui avait le focus avant l'ouverture du modal
        this.previouslyFocusedElement = document.activeElement;

        // Utiliser l'approche simple du code initial
        this.element.style.display = 'block';

        // Ajouter la classe Bootstrap 'show' pour l'animation
        requestAnimationFrame(() => {
            this.element.classList.add('show');

            // Gérer aria-hidden dynamiquement
            this.element.setAttribute('aria-hidden', 'false');

            // Ajouter le backdrop Bootstrap
            if (!document.body.querySelector('.modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
            // Empêcher le scroll du body
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = '0px';

            // Focus sur le premier élément focusable du modal pour l'accessibilité
            const focusableElements = this.element.querySelectorAll(
                'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }
        });
    }

    /**
     * Cache le modal en utilisant style.display (comme dans le code initial)
     */
    hide() {
        if (!this.element || !document.contains(this.element)) {
            return;
        }

        // Retirer la classe Bootstrap 'show'
        this.element.classList.remove('show');

        // Gérer aria-hidden dynamiquement
        this.element.setAttribute('aria-hidden', 'true');

        // Retirer le backdrop Bootstrap
        const backdrop = document.body.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }

        // Utiliser l'approche simple du code initial
        this.element.style.display = 'none';

        // Restaurer le scroll du body
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        // Restaurer le focus sur l'élément qui était actif avant l'ouverture du modal
        if (this.previouslyFocusedElement && document.contains(this.previouslyFocusedElement)) {
            this.previouslyFocusedElement.focus();
        }
    }

    /**
     * Vérifie si le modal est visible
     */
    isVisible() {
        return this.element && this.element.style.display === 'block';
    }

    /**
     * Met à jour le contenu du modal (titre, champs) en fonction des LiveProps
     * @param {number|null} categoryId - ID de la catégorie à éditer (extrait du bouton cliqué)
     * @param {number|null} causeCategoryId - ID de la catégorie de la cause à éditer
     * @param {number|null} causeIndex - Index de la cause à éditer
     */
    updateModalContent(categoryId = null, causeCategoryId = null, causeIndex = null) {
        if (!this.element || !document.contains(this.element)) {
            return;
        }
        
        // Trouver l'élément Live Component
        const liveComponent = document.querySelector('[data-controller*="live"]');
        if (!liveComponent) {
            return;
        }
        
        // Récupérer les props Live depuis l'attribut data-live-props-value
        const propsValue = liveComponent.getAttribute('data-live-props-value');
        if (!propsValue) {
            return;
        }
        
        try {
            const props = JSON.parse(propsValue);
            const modalId = this.element.id;
            
            // Mettre à jour le modal de catégorie
            if (modalId === 'categoryModal') {
                // Utiliser categoryId passé en paramètre ou depuis les props
                const editingCategoryId = categoryId !== null ? categoryId : (props.editingCategoryId || null);
                
                // Trouver la catégorie correspondante
                let editingCategory = null;
                if (editingCategoryId !== null && props.categories && Array.isArray(props.categories)) {
                    // Chercher par ID (peut être un nombre ou une chaîne)
                    editingCategory = props.categories.find(cat => {
                        const catId = typeof cat.id === 'string' ? parseInt(cat.id, 10) : cat.id;
                        const searchId = typeof editingCategoryId === 'string' ? parseInt(editingCategoryId, 10) : editingCategoryId;
                        return catId === searchId;
                    });
                    
                    console.log('[bootstrap-modal] updateModalContent - categoryModal', {
                        editingCategoryId,
                        editingCategory,
                        categoriesCount: props.categories.length,
                        categories: props.categories.map(cat => ({ id: cat.id, name: cat.name }))
                    });
                }
                
                const titleElement = this.element.querySelector('#categoryModalTitle');
                const form = this.element.querySelector('form');
                const selectElement = this.element.querySelector('#categorySelect');
                const inputElement = this.element.querySelector('#categoryName');
                const customGroupElement = this.element.querySelector('#customCategoryGroup');
                
                if (titleElement) {
                    titleElement.textContent = editingCategoryId ? 'Modifier la catégorie' : 'Ajouter une catégorie';
                }
                
                if (form) {
                    const actionParam = editingCategoryId ? 'updateCategory' : 'addCategory';
                    form.setAttribute('data-live-action-param', actionParam);
                    
                    // Ajouter ou mettre à jour le champ caché editingCategoryId si on édite
                    if (editingCategoryId) {
                        let hiddenInput = form.querySelector('input[name="editingCategoryId"]');
                        if (!hiddenInput) {
                            hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'editingCategoryId';
                            form.insertBefore(hiddenInput, form.firstChild);
                        }
                        hiddenInput.value = editingCategoryId.toString();
                    } else {
                        // Supprimer le champ caché si on ajoute une nouvelle catégorie
                        const hiddenInput = form.querySelector('input[name="editingCategoryId"]');
                        if (hiddenInput) {
                            hiddenInput.remove();
                        }
                    }
                }
                
                // Préremplir les champs si on édite une catégorie
                if (editingCategory) {
                    if (selectElement) {
                        // Ajouter les catégories disponibles au select si elles n'existent pas déjà
                        const availableCategories = ['PERSONNEL', 'MATÉRIELS', 'MESURE', 'MACHINES', 'MÉTHODES', 'ENVIRONNEMENT', 'MANAGEMENT'];
                        const existingOptions = Array.from(selectElement.options).map(opt => opt.value);
                        
                        // Ajouter les catégories disponibles qui ne sont pas déjà dans le select
                        availableCategories.forEach(catName => {
                            if (!existingOptions.includes(catName)) {
                                const option = document.createElement('option');
                                option.value = catName;
                                option.textContent = catName;
                                // Insérer avant l'option "custom"
                                const customOption = selectElement.querySelector('option[value="custom"]');
                                if (customOption) {
                                    selectElement.insertBefore(option, customOption);
                                } else {
                                    selectElement.appendChild(option);
                                }
                            }
                        });
                        
                        // Vérifier si la catégorie est dans la liste des catégories standard
                        const isStandard = availableCategories.includes(editingCategory.name);
                        
                        if (isStandard) {
                            selectElement.value = editingCategory.name;
                            if (customGroupElement) {
                                customGroupElement.style.display = 'none';
                            }
                        } else {
                            selectElement.value = 'custom';
                            if (customGroupElement) {
                                customGroupElement.style.display = 'block';
                            }
                        }
                    }
                    
                    if (inputElement) {
                        inputElement.value = editingCategory.name;
                    }
                } else {
                    // Ajouter les catégories disponibles au select si elles n'existent pas déjà (pour l'ajout)
                    if (selectElement) {
                        const availableCategories = ['PERSONNEL', 'MATÉRIELS', 'MESURE', 'MACHINES', 'MÉTHODES', 'ENVIRONNEMENT', 'MANAGEMENT'];
                        const existingOptions = Array.from(selectElement.options).map(opt => opt.value);
                        
                        // Ajouter les catégories disponibles qui ne sont pas déjà dans le select
                        availableCategories.forEach(catName => {
                            if (!existingOptions.includes(catName)) {
                                const option = document.createElement('option');
                                option.value = catName;
                                option.textContent = catName;
                                // Insérer avant l'option "custom"
                                const customOption = selectElement.querySelector('option[value="custom"]');
                                if (customOption) {
                                    selectElement.insertBefore(option, customOption);
                                } else {
                                    selectElement.appendChild(option);
                                }
                            }
                        });
                        
                        selectElement.value = '';
                    }
                    if (inputElement) {
                        inputElement.value = '';
                    }
                    if (customGroupElement) {
                        customGroupElement.style.display = 'block';
                    }
                }
            }
            
            // Mettre à jour le modal de cause
            if (modalId === 'causeModal') {
                // Utiliser causeCategoryId et causeIndex passés en paramètre ou depuis les props
                const editingCauseCategoryId = causeCategoryId !== null ? causeCategoryId : (props.editingCauseCategoryId || null);
                const editingCauseIndex = causeIndex !== null ? causeIndex : (props.editingCauseIndex !== null && props.editingCauseIndex !== undefined ? props.editingCauseIndex : null);
                
                // Trouver la cause correspondante
                let editingCause = null;
                if (editingCauseCategoryId !== null && editingCauseIndex !== null && props.categories) {
                    const category = props.categories.find(cat => cat.id === editingCauseCategoryId);
                    if (category && category.causes && category.causes[editingCauseIndex]) {
                        editingCause = category.causes[editingCauseIndex];
                    }
                }
                
                const titleElement = this.element.querySelector('#causeModalTitle');
                const form = this.element.querySelector('form');
                const inputElement = this.element.querySelector('#causeName');
                
                if (titleElement) {
                    titleElement.textContent = (editingCauseIndex !== null && editingCauseIndex !== undefined) ? 'Modifier la cause' : 'Ajouter une cause';
                }
                
                if (form) {
                    const actionParam = (editingCauseIndex !== null && editingCauseIndex !== undefined) ? 'updateCause' : 'addCause';
                    form.setAttribute('data-live-action-param', actionParam);
                }
                
                // Préremplir le champ si on édite une cause
                if (inputElement && editingCause) {
                    inputElement.value = editingCause;
                } else if (inputElement) {
                    // Réinitialiser le champ si on ajoute une nouvelle cause
                    inputElement.value = '';
                }
            }
        } catch (e) {
            console.warn('[bootstrap-modal] Error parsing Live props for modal content update:', e);
        }
    }

    /**
     * Vérifie les props Live et ouvre le modal si nécessaire
     */
    checkAndOpenModal() {
        // Trouver l'élément Live Component
        const liveComponent = document.querySelector('[data-controller*="live"]');
        if (!liveComponent) {
            console.debug('[bootstrap-modal] No Live Component found');
            return;
        }
        
        // Récupérer les props Live depuis l'attribut data-live-props-value
        const propsValue = liveComponent.getAttribute('data-live-props-value');
        if (!propsValue) {
            console.debug('[bootstrap-modal] No Live props found');
            return;
        }
        
        try {
            const props = JSON.parse(propsValue);
            const modalId = this.element.id;
            
            console.log('[bootstrap-modal] Checking modal', {
                modalId,
                props,
                elementExists: !!this.element,
                isVisible: this.isVisible(),
                editingCategoryId: props.editingCategoryId,
                showCategoryModal: props.showCategoryModal
            });
            
                   // Vérifier si le modal doit être ouvert pour les catégories
                   if (modalId === 'categoryModal') {
                       const hasEditingId = props.editingCategoryId !== null && props.editingCategoryId !== undefined;
                       const showModal = props.showCategoryModal === true;
                       const shouldOpen = hasEditingId || showModal;
                       
                       console.log('[bootstrap-modal] Category modal check', {
                           hasEditingId,
                           showModal,
                           shouldOpen,
                           isVisible: this.isVisible(),
                           editingCategoryId: props.editingCategoryId,
                           showCategoryModal: props.showCategoryModal
                       });
                       
                       if (shouldOpen && !this.isVisible()) {
                           console.log('[bootstrap-modal] Opening category modal from props check');
                           this.updateModalContent(); // Mettre à jour le contenu avant d'ouvrir
                           this.show();
                       } else if (!shouldOpen && this.isVisible()) {
                           console.log('[bootstrap-modal] Closing category modal from props check');
                           // Fermer le modal immédiatement
                           this.hide();
                       }
                   }
            
            // Vérifier si le modal doit être ouvert pour les causes
            if (modalId === 'causeModal') {
                const hasEditingCategoryId = props.editingCauseCategoryId !== null && props.editingCauseCategoryId !== undefined;
                const hasEditingIndex = props.editingCauseIndex !== null && props.editingCauseIndex !== undefined;
                const showModal = props.showCauseModal === true;
                const shouldOpen = hasEditingCategoryId || hasEditingIndex || showModal;
                
                console.log('[bootstrap-modal] Cause modal check', {
                    hasEditingCategoryId,
                    hasEditingIndex,
                    showModal,
                    shouldOpen,
                    isVisible: this.isVisible()
                });
                
                       if (shouldOpen && !this.isVisible()) {
                           console.log('[bootstrap-modal] Opening cause modal from props check');
                           this.updateModalContent(); // Mettre à jour le contenu avant d'ouvrir
                           this.show();
                       } else if (!shouldOpen && this.isVisible()) {
                           console.log('[bootstrap-modal] Closing cause modal from props check');
                           this.hide();
                       }
            }
        } catch (e) {
            console.warn('[bootstrap-modal] Error parsing Live props:', e);
        }
    }
}
