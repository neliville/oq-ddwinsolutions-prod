/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

/**
 * Masque les skeletons des graphiques PDCA une fois Apex monté.
 * Utilise IntersectionObserver pour ne pas bloquer le rendu hors viewport.
 */
export default class extends Controller {
    static values = {
        root: { type: Boolean, default: false },
    };

    connect() {
        this._onMounted = () => this._hideSkeletons();
        this.element.addEventListener('pdca-charts:mounted', this._onMounted);

        if (!('IntersectionObserver' in window)) {
            return;
        }

        this._observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('pdca-chart-tile--visible');
                    }
                });
            },
            { rootMargin: '80px 0px', threshold: 0.05 },
        );

        this.element.querySelectorAll('[data-pdca-chart-tile]').forEach((tile) => {
            this._observer.observe(tile);
        });
    }

    disconnect() {
        this.element.removeEventListener('pdca-charts:mounted', this._onMounted);
        this._observer?.disconnect();
        this._observer = null;
    }

    _hideSkeletons() {
        this.element.querySelectorAll('.pdca-chart-skeleton').forEach((sk) => {
            sk.classList.add('hidden');
        });
        this.element.querySelectorAll('[data-pdca-cockpit-chart-ref]').forEach((canvas) => {
            canvas.classList.remove('opacity-0');
            canvas.classList.add('opacity-100');
        });
    }
}
