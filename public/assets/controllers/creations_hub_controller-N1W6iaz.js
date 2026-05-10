import { Controller } from '@hotwired/stimulus';

/**
 * Hub Mes créations : filtre par type d'outil et recherche par titre (côté client).
 */
export default class extends Controller {
    static targets = ['section', 'card', 'filterBtn', 'searchInput', 'emptyState'];

    connect() {
        this.activeFilter = 'all';
        this.syncFilterUi();
        this.applyVisibility();
    }

    setFilter(event) {
        const filter = event.params?.filter ?? event.currentTarget?.dataset?.filter;
        if (!filter) {
            return;
        }
        this.activeFilter = filter;
        this.syncFilterUi();
        this.applyVisibility();
    }

    search() {
        this.applyVisibility();
    }

    resetFilters() {
        this.activeFilter = 'all';
        if (this.hasSearchInputTarget) {
            this.searchInputTarget.value = '';
        }
        this.syncFilterUi();
        this.applyVisibility();
        if (this.hasSearchInputTarget) {
            this.searchInputTarget.focus();
        }
    }

    syncFilterUi() {
        if (!this.hasFilterBtnTarget) {
            return;
        }
        this.filterBtnTargets.forEach((btn) => {
            const f = btn.dataset.filter;
            const pressed = f === this.activeFilter;
            btn.setAttribute('aria-pressed', pressed ? 'true' : 'false');
            btn.dataset.active = pressed ? 'true' : 'false';
        });
    }

    normalizedQuery() {
        if (!this.hasSearchInputTarget) {
            return '';
        }
        return this.searchInputTarget.value.trim().toLowerCase();
    }

    applyVisibility() {
        const q = this.normalizedQuery();
        let anyCardVisible = false;

        this.sectionTargets.forEach((section) => {
            const kind = section.dataset.creationKind || '';
            const typeMatches = this.activeFilter === 'all' || this.activeFilter === kind;

            const cards = this.cardTargets.filter((c) => section.contains(c));
            let visibleInSection = 0;

            cards.forEach((card) => {
                const haystack = (card.dataset.searchText || '').toLowerCase();
                const textMatches = q === '' || haystack.includes(q);
                const show = typeMatches && textMatches;
                card.hidden = !show;
                card.setAttribute('aria-hidden', show ? 'false' : 'true');
                if (show) {
                    visibleInSection += 1;
                    anyCardVisible = true;
                }
            });

            const showSection = typeMatches && visibleInSection > 0;
            section.hidden = !showSection;
            section.setAttribute('aria-hidden', showSection ? 'false' : 'true');
        });

        if (this.hasEmptyStateTarget) {
            this.emptyStateTarget.hidden = anyCardVisible;
        }
    }
}
