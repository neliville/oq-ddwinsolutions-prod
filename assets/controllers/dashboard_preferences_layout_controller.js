import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/**
 * Liste triable des widgets dashboard (onglet préférences).
 */
export default class extends Controller {
    static targets = ['list', 'orderInput'];

    connect() {
        if (!this.hasListTarget || !this.hasOrderInputTarget) {
            return;
        }

        this.sortable = Sortable.create(this.listTarget, {
            handle: '[data-drag-handle]',
            animation: 150,
            onEnd: () => this.syncOrder(),
        });
    }

    disconnect() {
        if (this.sortable) {
            this.sortable.destroy();
            this.sortable = null;
        }
    }

    syncOrder() {
        const ids = [...this.listTarget.querySelectorAll('[data-widget-id]')]
            .map((el) => el.dataset.widgetId)
            .filter(Boolean);
        this.orderInputTarget.value = ids.join(',');
    }
}
