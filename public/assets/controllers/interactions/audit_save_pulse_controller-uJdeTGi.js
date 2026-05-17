/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { animate } from 'motion';
import { prefersReducedMotion } from '../../motion/reduced-motion.js';
import { SPRING_SNAPPY } from '../../motion/tokens.js';

/** Pulse discret sur le bouton principal d’enregistrement quand l’audit est « dirty ». */
export default class extends Controller {
    static targets = ['primarySave'];

    connect() {
        this._onDirty = this._onDirty.bind(this);
        document.addEventListener('audit-save-bar:dirty', this._onDirty);
    }

    disconnect() {
        document.removeEventListener('audit-save-bar:dirty', this._onDirty);
    }

    _onDirty(e) {
        const dirty = Boolean(e.detail?.dirty);
        if (!dirty || prefersReducedMotion()) {
            return;
        }
        this.primarySaveTargets.forEach((btn) => {
            if (!btn) {
                return;
            }
            animate(
                btn,
                { boxShadow: ['0 0 0 0 rgba(99,102,241,0.35)', '0 0 0 10px rgba(99,102,241,0)'] },
                { duration: 0.65, easing: 'ease-out' },
            );
            animate(btn, { scale: [1, 1.02, 1] }, { type: 'spring', stiffness: SPRING_SNAPPY.stiffness, damping: SPRING_SNAPPY.damping });
        });
    }
}
