import { Controller } from '@hotwired/stimulus';
import { computePosition, autoUpdate, offset, flip, shift, arrow } from '@floating-ui/dom';

/**
 * HoverCard — portal vers <body> + positionnement Floating UI (flip/shift/arrow).
 */
export default class extends Controller {
    static values = {
        openDelay: { type: Number, default: 0 },
        closeDelay: { type: Number, default: 0 },
        placement: { type: String, default: 'top' },
        offset: { type: Number, default: 8 },
        interactive: { type: Boolean, default: false },
        arrow: { type: Boolean, default: true },
    };

    connect() {
        this.openTimeout = null;
        this.closeTimeout = null;
        this.cleanupAutoUpdate = null;
        this.placeholder = null;
        this.isPortaled = false;
        this.panelHovered = false;

        this.element.dataset.state = 'closed';

        this.panel = this.element.querySelector('[data-slot="hover-card-content"]');
        this.arrowEl = this.panel?.querySelector('[data-hover-card-target="arrow"]') ?? null;

        this.boundEscape = this.#onEscape.bind(this);
        this.boundTurboBeforeCache = this.#onTurboBeforeCache.bind(this);
        this.boundPanelEnter = this.#onPanelEnter.bind(this);
        this.boundPanelLeave = this.#onPanelLeave.bind(this);

        document.addEventListener('turbo:before-cache', this.boundTurboBeforeCache);
    }

    disconnect() {
        this.#forceClose();
        this.#clearTimeouts();
        document.removeEventListener('turbo:before-cache', this.boundTurboBeforeCache);
        document.removeEventListener('keydown', this.boundEscape);
        this.#detachPanelListeners();
    }

    show() {
        this.#clearTimeouts();
        this.openTimeout = window.setTimeout(() => {
            this.#open();
            this.openTimeout = null;
        }, this.openDelayValue);
    }

    hide(event) {
        if (this.interactiveValue && event?.relatedTarget) {
            const related = event.relatedTarget;
            if (this.panel?.contains(related) || this.#getReferenceElement()?.contains(related)) {
                return;
            }
        }

        this.#clearTimeouts();
        this.closeTimeout = window.setTimeout(() => {
            if (this.interactiveValue && this.panelHovered) {
                return;
            }
            this.#close();
            this.closeTimeout = null;
        }, this.closeDelayValue);
    }

    #open() {
        if (!this.panel || this.element.dataset.state === 'open') {
            return;
        }

        this.#portalPanel();
        this.element.dataset.state = 'open';
        this.panel.dataset.state = 'open';

        this.#updatePosition();
        this.#startAutoUpdate();

        document.addEventListener('keydown', this.boundEscape);

        if (this.interactiveValue) {
            this.panel.addEventListener('mouseenter', this.boundPanelEnter);
            this.panel.addEventListener('mouseleave', this.boundPanelLeave);
        }
    }

    #close() {
        if (this.element.dataset.state !== 'open') {
            return;
        }

        this.#stopAutoUpdate();
        document.removeEventListener('keydown', this.boundEscape);
        this.#detachPanelListeners();

        this.element.dataset.state = 'closed';
        if (this.panel) {
            this.panel.dataset.state = 'closed';
            this.panel.style.left = '';
            this.panel.style.top = '';
        }

        this.#unportalPanel();
        this.panelHovered = false;
    }

    #forceClose() {
        this.#clearTimeouts();
        if (this.element.dataset.state === 'open') {
            this.#close();
        } else if (this.isPortaled) {
            this.#unportalPanel();
        }
    }

    #portalPanel() {
        if (this.isPortaled || !this.panel) {
            return;
        }

        this.placeholder = document.createElement('template');
        this.placeholder.setAttribute('data-hover-card-placeholder', '');
        this.panel.parentNode.insertBefore(this.placeholder, this.panel);

        document.body.appendChild(this.panel);
        this.panel.dataset.portaled = 'true';
        this.isPortaled = true;
    }

    #unportalPanel() {
        if (!this.isPortaled || !this.panel) {
            return;
        }

        if (this.placeholder?.parentNode) {
            this.placeholder.parentNode.insertBefore(this.panel, this.placeholder);
            this.placeholder.remove();
        }

        this.placeholder = null;
        delete this.panel.dataset.portaled;
        this.isPortaled = false;
    }

    #updatePosition() {
        const reference = this.#getReferenceElement();
        if (!reference || !this.panel) {
            return;
        }

        const middleware = [
            offset(this.offsetValue),
            flip({ padding: 8 }),
            shift({ padding: 8 }),
        ];

        if (this.arrowValue && this.arrowEl) {
            middleware.push(arrow({ element: this.arrowEl }));
        }

        computePosition(reference, this.panel, {
            strategy: 'fixed',
            placement: this.placementValue,
            middleware,
        }).then(({ x, y, placement, middlewareData }) => {
            Object.assign(this.panel.style, {
                position: 'fixed',
                left: `${x}px`,
                top: `${y}px`,
            });

            const side = placement.split('-')[0];
            this.panel.dataset.side = side;

            if (middlewareData.arrow && this.arrowEl) {
                const { x: arrowX, y: arrowY } = middlewareData.arrow;
                Object.assign(this.arrowEl.style, {
                    left: arrowX != null ? `${arrowX}px` : '',
                    top: arrowY != null ? `${arrowY}px` : '',
                });
                this.arrowEl.dataset.side = side;
            }
        });
    }

    #startAutoUpdate() {
        const reference = this.#getReferenceElement();
        if (!reference || !this.panel) {
            return;
        }

        this.#stopAutoUpdate();
        this.cleanupAutoUpdate = autoUpdate(reference, this.panel, () => {
            this.#updatePosition();
        });
    }

    #stopAutoUpdate() {
        if (this.cleanupAutoUpdate) {
            this.cleanupAutoUpdate();
            this.cleanupAutoUpdate = null;
        }
    }

    #getReferenceElement() {
        const explicit = this.element.querySelector('[data-slot="hover-card-trigger"]');
        if (explicit) {
            return explicit.matches('[data-slot="hover-card-trigger"]')
                ? explicit
                : explicit.querySelector('[data-slot="hover-card-trigger"]') ?? explicit;
        }

        for (const child of this.element.children) {
            if (child === this.panel || child.matches('[data-hover-card-placeholder]')) {
                continue;
            }
            if (child.matches('template[data-hover-card-placeholder]')) {
                continue;
            }
            return child;
        }

        return this.element;
    }

    #onEscape(event) {
        if (event.key === 'Escape') {
            this.#forceClose();
        }
    }

    #onTurboBeforeCache() {
        this.#forceClose();
    }

    #onPanelEnter() {
        this.panelHovered = true;
        this.#clearTimeouts();
    }

    #onPanelLeave(event) {
        this.panelHovered = false;
        this.hide(event);
    }

    #detachPanelListeners() {
        if (!this.panel) {
            return;
        }
        this.panel.removeEventListener('mouseenter', this.boundPanelEnter);
        this.panel.removeEventListener('mouseleave', this.boundPanelLeave);
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
