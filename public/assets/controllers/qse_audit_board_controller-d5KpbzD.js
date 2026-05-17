/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

/** Board audit : dialogue de partage et helpers UI. */
export default class extends Controller {
    static targets = ['shareDialog', 'shareForm', 'shareLead'];

    static values = {
        shareUrlTemplate: String,
    };

    openShare(event) {
        event.preventDefault();
        event.stopPropagation();

        const btn = event.currentTarget;
        const auditId = btn.dataset.auditId;
        const label = btn.dataset.auditLabel || 'cet audit';

        this.closeParentDropdown(btn);

        if (!auditId) {
            this.notify('Identifiant d’audit manquant.', 'error');

            return;
        }

        if (!this.hasShareFormTarget || !this.hasShareDialogTarget) {
            this.notify('Le dialogue de partage n’est pas disponible. Rechargez la page.', 'error');

            return;
        }

        if (this.hasShareUrlTemplateValue) {
            this.shareFormTarget.action = this.shareUrlTemplateValue.replace('999999001', String(auditId));
        }

        const tokenInput = this.shareFormTarget.querySelector('input[name="_token"]');
        if (tokenInput && btn.dataset.shareCsrf) {
            tokenInput.value = btn.dataset.shareCsrf;
        }

        if (this.hasShareLeadTarget) {
            this.shareLeadTarget.textContent = `Partage sécurisé pour « ${label} ».`;
        }

        this.shareDialogTarget.showModal();
        const firstField = this.shareFormTarget.querySelector('input[type="email"], button, select');
        if (firstField instanceof HTMLElement) {
            requestAnimationFrame(() => firstField.focus());
        }
    }

    closeShare(event) {
        if (event) {
            event.preventDefault();
        }
        if (this.hasShareDialogTarget && this.shareDialogTarget.open) {
            this.shareDialogTarget.close();
        }
    }

    backdropCloseShare(event) {
        if (this.hasShareDialogTarget && event.target === this.shareDialogTarget) {
            this.closeShare();
        }
    }

    closeParentDropdown(trigger) {
        const root = trigger?.closest('[data-controller*="dropdown-basic"]');
        if (!root) {
            return;
        }
        const menu = root.querySelector('[data-dropdown-basic-target="menu"]');
        const toggle = root.querySelector('[data-dropdown-basic-target="button"]');
        if (menu) {
            menu.setAttribute('hidden', '');
            menu.classList.remove('show');
            menu.style.display = '';
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    notify(message, type = 'info') {
        document.dispatchEvent(new CustomEvent('app:notification', {
            detail: { message, type },
        }));
    }
}
