/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import autoAnimate from '@formkit/auto-animate';

/** Animate les mutations DOM du conteneur (listes, filtres, Live Components). */
export default class extends Controller {
    static values = {
        disabled: { type: Boolean, default: false },
    };

    connect() {
        if (this.disabledValue) {
            return;
        }
        this._stop = autoAnimate(this.element, { duration: 220, easing: 'ease-out' });
        this._onLiveDisconnect = () => {
            if (typeof this._stop === 'function') {
                this._stop();
            }
            this._stop = null;
        };
        this.element.addEventListener('live:disconnect', this._onLiveDisconnect);
    }

    disconnect() {
        this.element.removeEventListener('live:disconnect', this._onLiveDisconnect);
        if (typeof this._stop === 'function') {
            this._stop();
        }
        this._stop = null;
    }
}
