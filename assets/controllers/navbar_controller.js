import { Controller } from '@hotwired/stimulus';

/**
 * Controller Stimulus pour gérer l'ouverture/fermeture du menu mobile
 * sans dépendre de Bootstrap JS.
 */
export default class extends Controller {
    static targets = ['toggle', 'menu'];
    static values = {
        breakpoint: { type: Number, default: 992 }
    };

    connect() {
        if (!this.hasToggleTarget || !this.hasMenuTarget) {
            return;
        }

        this.openClass = 'show';
        this.handleResize = this.handleResize.bind(this);
        this.handleMenuClick = this.handleMenuClick.bind(this);

        this.toggleTarget.setAttribute('aria-expanded', this.isOpen ? 'true' : 'false');

        this.menuClickHandler = this.handleMenuClick.bind(this);

        this.menuTarget.addEventListener('click', this.menuClickHandler);
        window.addEventListener('resize', this.handleResize, { passive: true });

        this.handleResize();
    }

    disconnect() {
        if (!this.hasToggleTarget || !this.hasMenuTarget) {
            return;
        }

        this.menuTarget.removeEventListener('click', this.menuClickHandler);
        window.removeEventListener('resize', this.handleResize);
    }

    toggle(event) {
        event.preventDefault();
        this.isOpen ? this.closeMenu() : this.openMenu();
    }

    handleMenuClick(event) {
        const link = event.target.closest('a');
        if (!link) {
            return;
        }

        if (window.innerWidth < this.breakpointValue) {
            this.closeMenu();
        }
    }

    handleResize() {
        if (window.innerWidth >= this.breakpointValue) {
            this.openMenu({ forceDesktop: true });
            this.menuTarget.style.display = '';
            this.menuTarget.style.maxHeight = '';
        } else if (!this.isOpen) {
            this.resetStyles();
            this.toggleTarget.setAttribute('aria-expanded', 'false');
        }
    }

    openMenu({ forceDesktop = false } = {}) {
        this.menuTarget.classList.add(this.openClass);
        if (!forceDesktop) {
            this.menuTarget.style.display = 'block';
            this.menuTarget.style.maxHeight = `${this.menuTarget.scrollHeight}px`;
        } else {
            this.menuTarget.style.display = '';
            this.menuTarget.style.maxHeight = '';
        }
        this.toggleTarget.setAttribute('aria-expanded', 'true');
    }

    closeMenu() {
        this.menuTarget.classList.remove(this.openClass);
        this.resetStyles();
        this.toggleTarget.setAttribute('aria-expanded', 'false');
    }

    resetStyles() {
        this.menuTarget.style.display = '';
        this.menuTarget.style.maxHeight = '';
    }

    get isOpen() {
        return this.menuTarget.classList.contains(this.openClass);
    }
}
