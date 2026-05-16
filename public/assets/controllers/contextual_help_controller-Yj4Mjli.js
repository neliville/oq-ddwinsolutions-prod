import { Controller } from '@hotwired/stimulus';

/**
 * Aide contextuelle : survol desktop, clic sur icône « ? » sur écrans tactiles.
 */
export default class extends Controller {
    static targets = ['trigger', 'panel'];

    static values = {
        variant: { type: String, default: 'sidebar' },
        trigger: { type: String, default: 'wrap' },
        panelId: String,
    };

    connect() {
        this.hoverCard = this.element.querySelector('[data-controller~="hover-card"]');
        this.finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        this.outsideClickHandler = this.#onOutsideClick.bind(this);

        if (!this.finePointer && this.triggerValue === 'icon') {
            this.#disableHoverOnRoot();
        }
    }

    disconnect() {
        document.removeEventListener('click', this.outsideClickHandler);
    }

    toggle(event) {
        if (this.finePointer && this.triggerValue !== 'icon') {
            return;
        }
        event.preventDefault();
        event.stopPropagation();

        const open = this.hoverCard?.dataset.state === 'open';
        if (open) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        if (!this.hoverCard) {
            return;
        }
        const controller = this.application.getControllerForElementAndIdentifier(
            this.hoverCard,
            'hover-card',
        );
        if (controller?.show) {
            controller.show();
        } else {
            this.hoverCard.dataset.state = 'open';
            if (this.hasPanelTarget) {
                this.panelTarget.dataset.state = 'open';
            }
        }

        const btn = this.#iconButton();
        if (btn) {
            btn.setAttribute('aria-expanded', 'true');
        }
        document.addEventListener('click', this.outsideClickHandler);
    }

    close() {
        if (!this.hoverCard) {
            return;
        }
        const controller = this.application.getControllerForElementAndIdentifier(
            this.hoverCard,
            'hover-card',
        );
        if (controller?.hide) {
            controller.hide();
        } else {
            this.hoverCard.dataset.state = 'closed';
            if (this.hasPanelTarget) {
                this.panelTarget.dataset.state = 'closed';
            }
        }

        const btn = this.#iconButton();
        if (btn) {
            btn.setAttribute('aria-expanded', 'false');
        }
        document.removeEventListener('click', this.outsideClickHandler);
    }

    #onOutsideClick(event) {
        if (this.#isInsideComponent(event.target)) {
            return;
        }
        this.close();
    }

    #isInsideComponent(target) {
        if (!(target instanceof Node)) {
            return false;
        }
        if (this.element.contains(target)) {
            return true;
        }
        if (this.hasPanelTarget && this.panelTarget.contains(target)) {
            return true;
        }
        const panel = document.getElementById(this.panelIdValue);
        if (panel?.contains(target)) {
            return true;
        }
        return false;
    }

    #disableHoverOnRoot() {
        if (!this.hoverCard) {
            return;
        }
        this.hoverCard.removeAttribute('data-action');
    }

    #iconButton() {
        if (!this.hasTriggerTarget) {
            return null;
        }
        const el = this.triggerTarget;
        return el.matches('button') ? el : el.querySelector('button[data-slot="hover-card-trigger"]');
    }
}
