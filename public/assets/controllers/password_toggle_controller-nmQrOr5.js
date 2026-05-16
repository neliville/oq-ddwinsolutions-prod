import { Controller } from '@hotwired/stimulus';

/**
 * Affiche / masque un champ mot de passe (pages auth).
 */
export default class extends Controller {
    static targets = ['input', 'iconShow', 'iconHide', 'toggle'];

    connect() {
        this.updateIcons();
    }

    toggle(event) {
        event.preventDefault();
        const input = this.inputTarget;
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        if (this.hasToggleTarget) {
            this.toggleTarget.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            this.toggleTarget.setAttribute(
                'aria-label',
                isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe',
            );
        }

        this.updateIcons();
        input.focus();
    }

    updateIcons() {
        if (!this.hasIconShowTarget || !this.hasIconHideTarget) {
            return;
        }

        const isPassword = this.inputTarget.type === 'password';
        this.iconShowTarget.classList.toggle('hidden', !isPassword);
        this.iconHideTarget.classList.toggle('hidden', isPassword);
    }
}
