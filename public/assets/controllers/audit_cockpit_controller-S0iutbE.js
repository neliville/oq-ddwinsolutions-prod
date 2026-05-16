import { Controller } from '@hotwired/stimulus';

/**
 * Cockpit audit : filtres, recherche sur cartes (graphiques via `chart` Stimulus).
 */
export default class extends Controller {
    static targets = ['card', 'filterBtn', 'searchInput'];

    connect() {
        this._activeFilter = 'all';
    }

    filter(event) {
        const btn = event.currentTarget;
        const key = btn.dataset.filter || 'all';
        this._activeFilter = key;
        this.filterBtnTargets.forEach((b) => {
            const on = (b.dataset.filter || 'all') === key;
            b.classList.toggle('bg-primary', on);
            b.classList.toggle('text-primary-foreground', on);
            b.classList.toggle('border-primary', on);
            b.classList.toggle('border-border', !on);
            b.classList.toggle('bg-background', !on);
        });
        this._applyVisibility();
    }

    search() {
        this._applyVisibility();
    }

    _applyVisibility() {
        const q = (this.hasSearchInputTarget ? this.searchInputTarget.value : '').trim().toLowerCase();
        this.cardTargets.forEach((el) => {
            const f = el.dataset.filter || '';
            const hay = (el.dataset.search || '').toLowerCase();
            const matchFilter = this._activeFilter === 'all' || f === this._activeFilter;
            const matchSearch = q === '' || hay.includes(q);
            el.classList.toggle('hidden', !(matchFilter && matchSearch));
        });
    }
}
