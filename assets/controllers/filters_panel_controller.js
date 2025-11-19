import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur pour gérer le panneau de filtres (ouverture/fermeture manuelle)
 * Empêche la fermeture automatique lors de la soumission du formulaire
 */
export default class extends Controller {
    static targets = ['panel', 'toggle'];
    static values = {
        open: Boolean,
    };

    connect() {
        // Vérifier l'état initial du panneau
        const panel = this.panelTarget;
        if (panel) {
            this.openValue = panel.classList.contains('show');
            
            // Écouter les événements de collapse Bootstrap
            panel.addEventListener('shown.bs.collapse', () => {
                this.openValue = true;
                this.updateToggleButton(true);
                localStorage.setItem('logsFiltersOpen', 'true');
            });
            
            panel.addEventListener('hidden.bs.collapse', () => {
                this.openValue = false;
                this.updateToggleButton(false);
                localStorage.setItem('logsFiltersOpen', 'false');
            });
            
            // Restaurer l'état depuis localStorage
            const savedState = localStorage.getItem('logsFiltersOpen');
            if (savedState === 'true' && !panel.classList.contains('show')) {
                this.open();
            }
        }

        // Écouter les événements Turbo pour préserver l'état
        this.element.addEventListener('turbo:frame-load', this.handleFrameLoad.bind(this));
        
        // Mettre à jour le bouton initial
        this.updateToggleButton(this.openValue);
    }
    
    updateToggleButton(isOpen) {
        const toggle = this.toggleTarget;
        if (toggle) {
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            const icon = toggle.querySelector('i[data-lucide]');
            if (icon && typeof lucide !== 'undefined') {
                // Changer l'icône selon l'état
                icon.setAttribute('data-lucide', isOpen ? 'filter-x' : 'filter');
                lucide.createIcons();
            }
        }
    }

    disconnect() {
        this.element.removeEventListener('turbo:frame-load', this.handleFrameLoad.bind(this));
    }

    handleFrameLoad(event) {
        // Après le rechargement du Turbo Frame, rouvrir le panneau s'il était ouvert
        // Utiliser localStorage pour persister l'état
        const wasOpen = localStorage.getItem('logsFiltersOpen') === 'true';
        if (wasOpen) {
            setTimeout(() => {
                this.open();
            }, 150);
        }
    }

    toggle() {
        if (this.openValue) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        const panel = this.panelTarget;
        if (panel && !this.openValue) {
            // Utiliser Bootstrap Collapse pour ouvrir
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(panel, {
                toggle: false,
            });
            bsCollapse.show();
        }
    }

    close() {
        const panel = this.panelTarget;
        if (panel && this.openValue) {
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(panel, {
                toggle: false,
            });
            bsCollapse.hide();
        }
    }

    // Empêcher la fermeture automatique lors de la soumission
    preventAutoClose(event) {
        // Ne pas fermer automatiquement le panneau
        event.stopPropagation();
    }
}

