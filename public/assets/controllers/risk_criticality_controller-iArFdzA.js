import { Controller } from '@hotwired/stimulus';

const BADGE_CLASSES = {
    low: 'border-sky-500/40 bg-sky-500/10 text-sky-800 dark:text-sky-300',
    medium: 'border-amber-500/40 bg-amber-500/10 text-amber-800 dark:text-amber-300',
    critical: 'border-rose-500/40 bg-rose-500/10 text-rose-800 dark:text-rose-300',
};

const ZONE_CLASSES = {
    low: 'risk-heatmap-preview__cell--low',
    medium: 'risk-heatmap-preview__cell--medium',
    high: 'risk-heatmap-preview__cell--high',
};

/**
 * Preview criticité G×P×D + position matrice (formulaire création risque).
 */
export default class extends Controller {
    static targets = [
        'severity',
        'probability',
        'detection',
        'status',
        'scoreDisplay',
        'badgeWrap',
        'capaAlert',
        'cell',
    ];

    static values = {
        mediumThreshold: Number,
        criticalThreshold: Number,
    };

    connect() {
        this._onInput = () => {
            this.#syncScaleDisplays();
            this.#refresh();
        };
        this.#syncScaleDisplays();
        this.#refresh();
        this.element.addEventListener('input', this._onInput);
        this.element.addEventListener('change', this._onInput);
    }

    #syncScaleDisplays() {
        this.element.querySelectorAll('.risk-scale-field__range').forEach((range) => {
            const display = this.element.querySelector(`[data-risk-scale-display="${range.id}"]`);
            if (display) {
                display.textContent = range.value === '' ? '—' : range.value;
            }
            range.setAttribute('aria-valuenow', range.value);
        });
    }

    disconnect() {
        this.element.removeEventListener('input', this._onInput);
        this.element.removeEventListener('change', this._onInput);
    }

    #readScale(name) {
        const el = this[`${name}Target`] ?? this.element.querySelector(`[data-risk-criticality-target="${name}"]`);
        if (!el) {
            return null;
        }
        const raw = el.value ?? el.getAttribute('value');
        if (raw === '' || raw === null || raw === undefined) {
            return null;
        }
        const n = parseInt(String(raw), 10);
        return Number.isFinite(n) ? n : null;
    }

    #computeScore(s, p, d) {
        if (s === null || p === null || d === null) {
            return null;
        }
        return s * p * d;
    }

    #presentLevel(score) {
        if (score === null) {
            return { level: null, label: '—' };
        }
        if (score >= this.criticalThresholdValue) {
            return { level: 'critical', label: 'Critique' };
        }
        if (score >= this.mediumThresholdValue) {
            return { level: 'medium', label: 'Modéré' };
        }
        return { level: 'low', label: 'Faible' };
    }

    #normalize(v) {
        return Math.max(1, Math.min(5, v));
    }

    #zoneForCell(p, s) {
        const sum = this.#normalize(p) + this.#normalize(s);
        if (sum <= 4) {
            return 'low';
        }
        if (sum <= 7) {
            return 'medium';
        }
        return 'high';
    }

    #refresh() {
        const s = this.#readScale('severity');
        const p = this.#readScale('probability');
        const d = this.#readScale('detection');
        const score = this.#computeScore(s, p, d);
        const { level, label } = this.#presentLevel(score);

        if (this.hasScoreDisplayTarget) {
            this.scoreDisplayTarget.textContent = score === null ? '—' : String(score);
        }

        if (this.hasBadgeWrapTarget) {
            const base = 'inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium';
            if (level === null) {
                this.badgeWrapTarget.innerHTML = `<span class="${base} border-border bg-muted/40 text-muted-foreground">Renseignez G, P et D</span>`;
            } else {
                this.badgeWrapTarget.innerHTML = `<span class="${base} ${BADGE_CLASSES[level] ?? BADGE_CLASSES.low}">${label}</span>`;
            }
        }

        if (this.hasCapaAlertTarget) {
            const statusEl = this.element.querySelector('[data-risk-criticality-target="status"]');
            const status = statusEl?.value ?? '';
            const show = score !== null && score >= this.criticalThresholdValue && status === 'sous_surveillance';
            this.capaAlertTarget.classList.toggle('hidden', !show);
        }

        if (this.hasCellTarget) {
            this.cellTargets.forEach((cell) => {
                const cp = parseInt(cell.dataset.p, 10);
                const cs = parseInt(cell.dataset.s, 10);
                const zone = this.#zoneForCell(cp, cs);
                cell.className = `risk-criticality-panel__cell ${ZONE_CLASSES[zone] ?? ZONE_CLASSES.low}`;
                const active = s !== null && p !== null && this.#normalize(p) === cp && this.#normalize(s) === cs;
                cell.classList.toggle('risk-criticality-panel__cell--active', active);
                cell.textContent = active ? '●' : '';
            });
        }
    }
}
