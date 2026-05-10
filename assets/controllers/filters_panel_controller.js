import { Controller } from '@hotwired/stimulus';

/**
 * Panneau de filtres repliable (Tailwind .hidden), sans Bootstrap Collapse.
 */
export default class extends Controller {
    static targets = ['panel', 'toggle'];
    static values = {
        open: Boolean,
    };

    connect() {
        const panel = this.panelTarget;
        if (panel) {
            this.openValue = !panel.classList.contains('hidden');

            const savedState = localStorage.getItem('logsFiltersOpen');
            if (savedState === 'true' && panel.classList.contains('hidden')) {
                this.open();
            }
        }

        this.element.addEventListener('turbo:frame-load', this.handleFrameLoad.bind(this));
        this.updateToggleButton(this.openValue);
    }

    updateToggleButton(isOpen) {
        const toggle = this.toggleTarget;
        if (toggle) {
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            const icon = toggle.querySelector('i[data-lucide]');
            if (icon && typeof lucide !== 'undefined') {
                icon.setAttribute('data-lucide', isOpen ? 'filter-x' : 'filter');
                lucide.createIcons();
            }
        }
    }

    disconnect() {
        this.element.removeEventListener('turbo:frame-load', this.handleFrameLoad.bind(this));
    }

    handleFrameLoad() {
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
        if (!panel || this.openValue) {
            return;
        }
        panel.classList.remove('hidden');
        this.openValue = true;
        this.updateToggleButton(true);
        localStorage.setItem('logsFiltersOpen', 'true');
    }

    close() {
        const panel = this.panelTarget;
        if (!panel || !this.openValue) {
            return;
        }
        panel.classList.add('hidden');
        this.openValue = false;
        this.updateToggleButton(false);
        localStorage.setItem('logsFiltersOpen', 'false');
    }

    preventAutoClose(event) {
        event.stopPropagation();
    }
}
