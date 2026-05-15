import { Controller } from '@hotwired/stimulus';

const DELETE_URL_SENTINEL = '999999001';

/**
 * Dialogue unique de suppression d’audit : remplit le formulaire puis ouvre <dialog>.
 */
export default class extends Controller {
    static targets = ['dialog', 'form', 'token', 'title', 'lead', 'capaWarning', 'capaCount'];
    static values = { deleteUrlTemplate: String };

    connect() {
        this.activeTrigger = null;
        this.boundPageShowReset = () => this.resetDialogState();
        this.resetDialogState();
        requestAnimationFrame(() => this.resetDialogState());
        window.addEventListener('pageshow', this.boundPageShowReset);
        this.consumePendingNotification();
    }

    disconnect() {
        if (this.boundPageShowReset) {
            window.removeEventListener('pageshow', this.boundPageShowReset);
        }
    }

    open(event) {
        const btn = event.currentTarget;
        const id = btn.getAttribute('data-audit-id');
        const explicitDeleteUrl = btn.getAttribute('data-delete-url') || '';
        const capaCount = parseInt(btn.getAttribute('data-capa-count') || '0', 10) || 0;
        const label = btn.getAttribute('data-audit-label') || '';
        const token = btn.getAttribute('data-csrf-token') || '';
        if (!id || !this.hasFormTarget || !this.hasDialogTarget) {
            return;
        }
        this.activeTrigger = btn;
        const tpl = this.deleteUrlTemplateValue || '';
        const actionUrl = explicitDeleteUrl || (tpl.includes(DELETE_URL_SENTINEL)
            ? tpl.replace(DELETE_URL_SENTINEL, id)
            : tpl.replace(/\/\d+\/delete(?:\?.*)?$/, `/${id}/delete`));
        if (!actionUrl) {
            this.dispatchNotification('URL de suppression introuvable pour cet audit.', 'error');
            return;
        }
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
        this.dialogTarget.removeAttribute('hidden');
        this.dialogTarget.showModal();
        const autofocusTarget = this.dialogTarget.querySelector('button, [href], input, select, textarea');
        if (autofocusTarget instanceof HTMLElement) {
            requestAnimationFrame(() => autofocusTarget.focus());
        }
    }

    async submit(event) {
        event.preventDefault();

        if (!this.hasFormTarget) {
            return;
        }

        const submitButton = event.submitter instanceof HTMLElement
            ? event.submitter
            : this.formTarget.querySelector('button[type="submit"]');
        const originalLabel = submitButton?.innerHTML ?? null;

        if (submitButton) {
            submitButton.setAttribute('disabled', 'disabled');
        }

        try {
            const response = await fetch(this.formTarget.action, {
                method: this.formTarget.method || 'POST',
                body: new FormData(this.formTarget),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            const payload = this.parseResponsePayload(await response.text(), response);
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Erreur lors de la suppression de l’audit.');
            }

            const row = this.activeTrigger?.closest('tr');
            this.close();

            const message = payload.message || 'L’audit a été supprimé.';
            const redirectUrl = payload.redirect || window.location.pathname;
            row?.remove();

            const remainingRows = row?.parentElement?.querySelectorAll('tr').length ?? 0;
            if (remainingRows === 0) {
                this.storePendingNotification(message, 'success');
                window.location.assign(redirectUrl);
                return;
            }

            this.dispatchNotification(message, 'success');
            this.activeTrigger = null;
        } catch (error) {
            this.dispatchNotification(error.message || 'Erreur lors de la suppression de l’audit.', 'error');
        } finally {
            if (submitButton) {
                submitButton.removeAttribute('disabled');
                if (originalLabel !== null) {
                    submitButton.innerHTML = originalLabel;
                }
            }
        }
    }

    close(event) {
        if (event) {
            event.preventDefault();
        }
        this.resetDialogState();
    }

    backdropClose(event) {
        if (!this.hasDialogTarget || event.target !== this.dialogTarget) {
            return;
        }

        this.close(event);
    }

    dispatchNotification(message, type = 'info') {
        document.dispatchEvent(new CustomEvent('app:notification', {
            detail: { message, type },
        }));
    }

    storePendingNotification(message, type = 'info') {
        window.sessionStorage.setItem('qse-audit-delete-notification', JSON.stringify({ message, type }));
    }

    consumePendingNotification() {
        const raw = window.sessionStorage.getItem('qse-audit-delete-notification');
        if (!raw) {
            return;
        }

        window.sessionStorage.removeItem('qse-audit-delete-notification');

        try {
            const { message, type } = JSON.parse(raw);
            if (message) {
                requestAnimationFrame(() => this.dispatchNotification(message, type || 'success'));
            }
        } catch (_error) {
            // Ignore malformed session payloads.
        }
    }

    resetDialogState() {
        if (!this.hasDialogTarget) {
            return;
        }

        if (this.dialogTarget.open) {
            this.dialogTarget.close();
        }

        this.dialogTarget.setAttribute('hidden', 'hidden');

        if (this.hasFormTarget) {
            this.formTarget.removeAttribute('action');
        }

        if (this.hasTokenTarget) {
            this.tokenTarget.value = '';
        }

        if (this.hasLeadTarget) {
            this.leadTarget.textContent = '';
        }

        if (this.hasCapaWarningTarget) {
            this.capaWarningTarget.hidden = true;
        }

        if (this.hasCapaCountTarget) {
            this.capaCountTarget.textContent = '0';
        }

        this.activeTrigger = null;
    }

    parseResponsePayload(rawBody, response) {
        const trimmed = String(rawBody ?? '').trim();

        try {
            return JSON.parse(rawBody ?? '{}');
        } catch (_error) {
            if (trimmed.startsWith('<!--') && trimmed.includes('No route found for')) {
                return {
                    success: false,
                    message: 'La suppression a appelé une URL invalide. La route de suppression a ete securisee, rechargez la page puis reessayez.',
                    status: response.status,
                };
            }

            if (trimmed.startsWith('<!') || trimmed.toLowerCase().startsWith('<html') || trimmed.startsWith('<!--')) {
                return {
                    success: false,
                    message: response.status === 403
                        ? 'Votre session a expire ou le jeton de suppression est invalide. Rechargez la page puis reessayez.'
                        : 'Le serveur a renvoye une page HTML au lieu d une reponse de suppression. Rechargez la page puis reessayez.',
                    status: response.status,
                };
            }

            return {
                success: false,
                message: 'Reponse serveur illisible lors de la suppression de l audit.',
                status: response.status,
            };
        }
    }
}
