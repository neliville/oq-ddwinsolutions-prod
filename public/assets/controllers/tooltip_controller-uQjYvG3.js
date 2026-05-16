import { Controller } from '@hotwired/stimulus';
import { computePosition, autoUpdate, offset, flip, shift, arrow } from '@floating-ui/dom';

/**
 * Tooltip — portal vers body + Floating UI (même stack que hover-card).
 */
export default class extends Controller {
    static values = {
        delayDuration: { type: Number, default: 0 },
        wrapperSelector: String,
        contentSelector: String,
        arrowSelector: String,
    };

    static targets = ['trigger', 'wrapper'];

    connect() {
        this.initialized = false;
        this.openTimeout = null;
        this.closeTimeout = null;
        this.cleanupAutoUpdate = null;
        this.isPortaled = false;

        this.wrapperElement = document.querySelector(this.wrapperSelectorValue);
        this.contentElement = document.querySelector(this.contentSelectorValue);
        this.arrowElement = document.querySelector(this.arrowSelectorValue);

        if (!this.wrapperElement || !this.contentElement || !this.arrowElement) {
            return;
        }

        this.placement = this.wrapperElement.getAttribute('data-side') || 'top';
        this.sideOffset = parseInt(this.wrapperElement.getAttribute('data-side-offset'), 10) || 6;

        this.boundTurboBeforeCache = this.#onTurboBeforeCache.bind(this);
        document.addEventListener('turbo:before-cache', this.boundTurboBeforeCache);

        this.#portalWrapper();
        this.initialized = true;
    }

    disconnect() {
        this.#forceHide();
        this.#clearTimeouts();
        this.#stopAutoUpdate();
        document.removeEventListener('turbo:before-cache', this.boundTurboBeforeCache);
        this.#unportalWrapper();
    }

    wrapperTargetConnected() {
        if (this.wrapperElement && this.isPortaled) {
            this.#unportalWrapper();
            this.wrapperElement = this.wrapperTarget;
            this.contentElement = this.wrapperElement.querySelector('[data-slot="tooltip-content"]');
            this.arrowElement = this.wrapperElement.querySelector('[data-tooltip-target="arrow"]');
            this.#portalWrapper();
        }
    }

    show() {
        if (!this.initialized || !this.hasTriggerTarget) {
            return;
        }

        this.#clearTimeouts();
        const delay = this.delayDurationValue ?? 0;

        this.openTimeout = window.setTimeout(() => {
            this.#open();
            this.openTimeout = null;
        }, delay);
    }

    hide() {
        if (!this.initialized) {
            return;
        }

        this.#clearTimeouts();
        this.closeTimeout = window.setTimeout(() => {
            this.#close();
            this.closeTimeout = null;
        }, 80);
    }

    #open() {
        if (!this.wrapperElement || !this.hasTriggerTarget) {
            return;
        }

        this.wrapperElement.dataset.state = 'open';
        this.contentElement.dataset.state = 'open';

        this.#updatePosition();
        this.#startAutoUpdate();
    }

    #close() {
        if (!this.wrapperElement) {
            return;
        }

        this.#stopAutoUpdate();
        this.wrapperElement.dataset.state = 'closed';
        this.contentElement.dataset.state = 'closed';
        this.wrapperElement.style.left = '';
        this.wrapperElement.style.top = '';
    }

    #forceHide() {
        this.#clearTimeouts();
        if (this.wrapperElement?.dataset.state === 'open') {
            this.#close();
        }
    }

    #portalWrapper() {
        if (this.isPortaled || !this.wrapperElement) {
            return;
        }

        this.placeholder = document.createElement('template');
        this.placeholder.setAttribute('data-tooltip-placeholder', '');
        this.wrapperElement.parentNode?.insertBefore(this.placeholder, this.wrapperElement);

        document.body.appendChild(this.wrapperElement);
        this.wrapperElement.dataset.portaled = 'true';
        this.wrapperElement.dataset.state = 'closed';
        this.isPortaled = true;
    }

    #unportalWrapper() {
        if (!this.isPortaled || !this.wrapperElement) {
            return;
        }

        if (this.placeholder?.parentNode) {
            this.placeholder.parentNode.insertBefore(this.wrapperElement, this.placeholder);
            this.placeholder.remove();
        }

        this.placeholder = null;
        delete this.wrapperElement.dataset.portaled;
        this.isPortaled = false;
    }

    #updatePosition() {
        if (!this.hasTriggerTarget || !this.wrapperElement) {
            return;
        }

        const middleware = [
            offset(this.sideOffset),
            flip({ padding: 8 }),
            shift({ padding: 8 }),
            arrow({ element: this.arrowElement }),
        ];

        computePosition(this.triggerTarget, this.wrapperElement, {
            strategy: 'fixed',
            placement: this.placement,
            middleware,
        }).then(({ x, y, placement, middlewareData }) => {
            Object.assign(this.wrapperElement.style, {
                position: 'fixed',
                left: `${x}px`,
                top: `${y}px`,
            });

            const side = placement.split('-')[0];
            this.wrapperElement.dataset.side = side;
            this.contentElement.dataset.side = side;
            this.arrowElement.dataset.side = side;

            if (middlewareData.arrow) {
                const { x: arrowX, y: arrowY } = middlewareData.arrow;
                Object.assign(this.arrowElement.style, {
                    left: arrowX != null ? `${arrowX}px` : '',
                    top: arrowY != null ? `${arrowY}px` : '',
                });
            }
        });
    }

    #startAutoUpdate() {
        if (!this.hasTriggerTarget || !this.wrapperElement) {
            return;
        }

        this.#stopAutoUpdate();
        this.cleanupAutoUpdate = autoUpdate(this.triggerTarget, this.wrapperElement, () => {
            this.#updatePosition();
        });
    }

    #stopAutoUpdate() {
        if (this.cleanupAutoUpdate) {
            this.cleanupAutoUpdate();
            this.cleanupAutoUpdate = null;
        }
    }

    #onTurboBeforeCache() {
        this.#forceHide();
    }

    #clearTimeouts() {
        if (this.openTimeout) {
            clearTimeout(this.openTimeout);
            this.openTimeout = null;
        }
        if (this.closeTimeout) {
            clearTimeout(this.closeTimeout);
            this.closeTimeout = null;
        }
    }
}
