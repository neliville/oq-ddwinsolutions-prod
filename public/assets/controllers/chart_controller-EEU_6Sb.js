import { Controller } from '@hotwired/stimulus';

/**
 * Façade graphiques ApexCharts (import dynamique).
 * `kind` : admin-dashboard | analytics | audit-cockpit | user-dashboard | admin-growth | pdca-cockpit
 */
export default class extends Controller {
    static values = {
        kind: { type: String, default: 'admin-dashboard' },
        config: { type: Object, default: {} },
    };

    async connect() {
        this._teardown = null;
        const kind = this.kindValue;
        const config = this.configValue && typeof this.configValue === 'object' ? this.configValue : {};

        // Laisser le layout Tailwind appliquer les hauteurs avant mesure ApexCharts
        await new Promise((resolve) => {
            requestAnimationFrame(() => requestAnimationFrame(resolve));
        });

        try {
            const mod = await import('./charts/apex_adapter.js');
            this._teardown = mod.mount(kind, this.element, config);
        } catch (e) {
            console.error('[chart]', e);
        }
    }

    disconnect() {
        if (typeof this._teardown === 'function') {
            this._teardown();
        }
        this._teardown = null;
    }
}
