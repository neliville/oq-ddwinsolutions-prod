import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/**
 * Mode personnalisation du dashboard (réordonnancement + masquage, sauvegarde PATCH).
 */
export default class extends Controller {
    static targets = [
        'readView',
        'editView',
        'sortableList',
        'editToggle',
        'saveBtn',
        'cancelBtn',
        'toolbar',
    ];

    static values = {
        patchUrl: String,
        csrfToken: String,
    };

    connect() {
        this.snapshot = null;
    }

    disconnect() {
        if (this.sortable) {
            this.sortable.destroy();
            this.sortable = null;
        }
    }

    startEdit() {
        this.snapshot = this.collectEntries();
        this.readViewTarget.classList.add('hidden');
        this.editViewTarget.classList.remove('hidden');
        this.toolbarTarget.classList.remove('hidden');
        this.editToggleTarget.classList.add('hidden');

        if (!this.sortable && this.hasSortableListTarget) {
            this.sortable = Sortable.create(this.sortableListTarget, {
                handle: '[data-drag-handle]',
                animation: 150,
            });
        }
    }

    cancelEdit() {
        if (this.snapshot) {
            this.restoreEntries(this.snapshot);
        }
        this.endEdit();
    }

    async save() {
        const entries = this.collectEntries();
        const visibleCount = entries.filter((e) => e.visible).length;
        if (visibleCount < 1) {
            if (typeof window.appNotify === 'function') {
                window.appNotify('Au moins un bloc doit rester visible.', 'warning');
            }
            return;
        }

        this.saveBtnTarget.disabled = true;

        try {
            const response = await fetch(this.patchUrlValue, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfTokenValue,
                },
                body: JSON.stringify({ widgets: entries }),
            });

            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Enregistrement impossible.');
            }

            if (typeof window.appNotify === 'function') {
                window.appNotify(data.message || 'Affichage du tableau de bord mis à jour.', 'success');
            }

            window.location.reload();
        } catch (error) {
            if (typeof window.appNotify === 'function') {
                window.appNotify(error.message || 'Erreur lors de l’enregistrement.', 'error');
            }
        } finally {
            this.saveBtnTarget.disabled = false;
        }
    }

    hideWidget(event) {
        const item = event.currentTarget.closest('[data-widget-id]');
        if (!item) {
            return;
        }
        item.dataset.visible = '0';
        item.classList.add('opacity-50');
    }

    showWidget(event) {
        const item = event.currentTarget.closest('[data-widget-id]');
        if (!item) {
            return;
        }
        item.dataset.visible = '1';
        item.classList.remove('opacity-50');
    }

    endEdit() {
        this.readViewTarget.classList.remove('hidden');
        this.editViewTarget.classList.add('hidden');
        this.toolbarTarget.classList.add('hidden');
        this.editToggleTarget.classList.remove('hidden');
    }

    collectEntries() {
        if (!this.hasSortableListTarget) {
            return [];
        }

        return [...this.sortableListTarget.querySelectorAll('[data-widget-id]')].map((el) => ({
            id: el.dataset.widgetId,
            visible: el.dataset.visible !== '0',
        }));
    }

    restoreEntries(entries) {
        if (!this.hasSortableListTarget) {
            return;
        }

        const byId = Object.fromEntries(entries.map((e) => [e.id, e]));
        const items = [...this.sortableListTarget.querySelectorAll('[data-widget-id]')];

        entries.forEach((entry) => {
            const item = items.find((el) => el.dataset.widgetId === entry.id);
            if (!item) {
                return;
            }
            item.dataset.visible = entry.visible ? '1' : '0';
            item.classList.toggle('opacity-50', !entry.visible);
            this.sortableListTarget.appendChild(item);
        });
    }
}
