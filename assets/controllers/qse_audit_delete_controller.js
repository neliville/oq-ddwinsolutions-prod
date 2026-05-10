import { Controller } from '@hotwired/stimulus';

const DELETE_URL_SENTINEL = '999999001';

/**
 * Dialogue unique de suppression d’audit : remplit le formulaire puis ouvre <dialog>.
 */
export default class extends Controller {
    static targets = ['dialog', 'form', 'token', 'title', 'lead', 'capaWarning', 'capaCount'];
    static values = { deleteUrlTemplate: String };

    open(event) {
        const btn = event.currentTarget;
        const id = btn.getAttribute('data-audit-id');
        const capaCount = parseInt(btn.getAttribute('data-capa-count') || '0', 10) || 0;
        const label = btn.getAttribute('data-audit-label') || '';
        const token = btn.getAttribute('data-csrf-token') || '';
        if (!id || !this.hasFormTarget || !this.hasDialogTarget) {
            return;
        }
        const tpl = this.deleteUrlTemplateValue || '';
        const actionUrl = tpl.includes(DELETE_URL_SENTINEL)
            ? tpl.replace(DELETE_URL_SENTINEL, id)
            : tpl.replace(/\/\d+\/delete(?:\?.*)?$/, `/${id}/delete`);
        this.formTarget.action = actionUrl;
        if (this.hasTokenTarget) {
            this.tokenTarget.value = token;
        }
        if (this.hasTitleTarget) {
            this.titleTarget.textContent = `Supprimer l’audit « ${label} » ?`;
        }
        if (this.hasLeadTarget) {
            this.leadTarget.textContent =
                'Cette opération supprime définitivement l’audit, les évaluations et constats associés en base.';
        }
        if (this.hasCapaWarningTarget && this.hasCapaCountTarget) {
            if (capaCount > 0) {
                this.capaWarningTarget.hidden = false;
                this.capaCountTarget.textContent = String(capaCount);
            } else {
                this.capaWarningTarget.hidden = true;
            }
        }
        this.dialogTarget.showModal();
    }

    close() {
        if (this.hasDialogTarget) {
            this.dialogTarget.close();
        }
    }
}
