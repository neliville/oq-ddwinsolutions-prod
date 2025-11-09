import { Controller } from '@hotwired/stimulus';

const FOCUSABLE_SELECTOR = [
    'a[href]:not([tabindex="-1"])',
    'button:not([disabled])',
    '[role="menuitem"]',
    '[tabindex]:not([tabindex="-1"])'
].join(',');

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['button', 'menu'];

    connect() {
        this.isOpen = false;
        this.handleOutsideClick = this.handleOutsideClick.bind(this);
        this.handleKeydown = this.handleKeydown.bind(this);
        this.handleTurboBeforeCache = this.handleTurboBeforeCache.bind(this);

        document.addEventListener('turbo:before-cache', this.handleTurboBeforeCache);

        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-haspopup', 'true');
            this.buttonTarget.setAttribute('aria-expanded', 'false');
        }

        if (this.hasMenuTarget) {
            this.menuTarget.setAttribute('role', 'menu');
            this.menuTarget.setAttribute('hidden', '');
        }
    }

    disconnect() {
        document.removeEventListener('turbo:before-cache', this.handleTurboBeforeCache);
        this.removeListeners();
    }

    toggle(event) {
        event.preventDefault();
        this.isOpen ? this.close() : this.open();
    }

    openWithKeyboard(event) {
        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
            return;
        }

        event.preventDefault();
        if (!this.isOpen) {
            this.open();
        }

        if (event.key === 'ArrowDown') {
            this.focusFirstItem();
        } else {
            const items = this.focusableItems();
            if (items.length) {
                items[items.length - 1].focus({ preventScroll: true });
            }
        }
    }

    open() {
        if (!this.hasMenuTarget || this.isOpen) {
            return;
        }

        this.menuTarget.removeAttribute('hidden');
        this.menuTarget.classList.add('show');
        this.menuTarget.style.display = 'block';

        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-expanded', 'true');
        }

        document.addEventListener('click', this.handleOutsideClick, true);
        document.addEventListener('keydown', this.handleKeydown);

        this.isOpen = true;

        requestAnimationFrame(() => this.focusFirstItem());
    }

    close() {
        if (!this.hasMenuTarget || !this.isOpen) {
            if (this.hasMenuTarget) {
                this.menuTarget.setAttribute('hidden', '');
                this.menuTarget.classList.remove('show');
                this.menuTarget.style.display = '';
            }
            return;
        }

        this.menuTarget.setAttribute('hidden', '');
        this.menuTarget.classList.remove('show');
        this.menuTarget.style.display = '';

        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-expanded', 'false');
        }

        this.removeListeners();
        this.isOpen = false;
    }

    handleOutsideClick(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }

    handleKeydown(event) {
        if (!this.isOpen) {
            return;
        }

        switch (event.key) {
            case 'Escape':
                event.preventDefault();
                this.close();
                if (this.hasButtonTarget) {
                    this.buttonTarget.focus({ preventScroll: true });
                }
                break;
            case 'ArrowDown':
                event.preventDefault();
                this.focusNextItem();
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.focusPreviousItem();
                break;
            case 'Tab':
                this.handleTab(event);
                break;
            default:
                break;
        }
    }

    handleTab(event) {
        const items = this.focusableItems();
        if (!items.length) {
            return;
        }

        const first = items[0];
        const last = items[items.length - 1];

        if (!event.shiftKey && event.target === last) {
            event.preventDefault();
            this.close();
            if (this.hasButtonTarget) {
                this.buttonTarget.focus({ preventScroll: true });
            }
        } else if (event.shiftKey && event.target === first) {
            event.preventDefault();
            this.close();
            if (this.hasButtonTarget) {
                this.buttonTarget.focus({ preventScroll: true });
            }
        }
    }

    handleTurboBeforeCache() {
        this.close();
    }

    focusNextItem() {
        const items = this.focusableItems();
        if (!items.length) {
            return;
        }

        const index = items.indexOf(document.activeElement);
        const nextIndex = index === -1 || index === items.length - 1 ? 0 : index + 1;
        items[nextIndex].focus({ preventScroll: true });
    }

    focusPreviousItem() {
        const items = this.focusableItems();
        if (!items.length) {
            return;
        }

        const index = items.indexOf(document.activeElement);
        const prevIndex = index <= 0 ? items.length - 1 : index - 1;
        items[prevIndex].focus({ preventScroll: true });
    }

    focusFirstItem() {
        const items = this.focusableItems();
        if (items.length) {
            items[0].focus({ preventScroll: true });
        }
    }

    focusableItems() {
        if (!this.hasMenuTarget) {
            return [];
        }

        return Array.from(this.menuTarget.querySelectorAll(FOCUSABLE_SELECTOR))
            .filter((el) => !el.hasAttribute('disabled') && !el.getAttribute('aria-disabled'));
    }

    removeListeners() {
        document.removeEventListener('click', this.handleOutsideClick, true);
        document.removeEventListener('keydown', this.handleKeydown);
    }
}

