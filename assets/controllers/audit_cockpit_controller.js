import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

/**
 * Cockpit audit : filtres, recherche, graphiques (radar + répartition).
 */
export default class extends Controller {
    static targets = ['card', 'filterBtn', 'searchInput'];
    static values = {
        chartConfig: Object,
    };

    connect() {
        this.charts = [];
        this._activeFilter = 'all';
        this._renderCharts();
    }

    disconnect() {
        (this.charts || []).forEach((c) => {
            try {
                c.destroy();
            } catch (_) {
                /* noop */
            }
        });
        this.charts = [];
    }

    filter(event) {
        const btn = event.currentTarget;
        const key = btn.dataset.filter || 'all';
        this._activeFilter = key;
        this.filterBtnTargets.forEach((b) => {
            const on = (b.dataset.filter || 'all') === key;
            b.classList.toggle('bg-primary', on);
            b.classList.toggle('text-primary-foreground', on);
            b.classList.toggle('border-primary', on);
            b.classList.toggle('border-border', !on);
            b.classList.toggle('bg-background', !on);
        });
        this._applyVisibility();
    }

    search() {
        this._applyVisibility();
    }

    _applyVisibility() {
        const q = (this.hasSearchInputTarget ? this.searchInputTarget.value : '').trim().toLowerCase();
        this.cardTargets.forEach((el) => {
            const f = el.dataset.filter || '';
            const hay = (el.dataset.search || '').toLowerCase();
            const matchFilter = this._activeFilter === 'all' || f === this._activeFilter;
            const matchSearch = q === '' || hay.includes(q);
            el.classList.toggle('hidden', !(matchFilter && matchSearch));
        });
    }

    _cssVar(name, fallback) {
        const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return v || fallback;
    }

    _renderCharts() {
        const cfg = this.chartConfigValue || {};
        const radar = cfg.radar;
        const dist = cfg.distribution;
        const primary = this._cssVar('--chart-primary', '#4f46e5');
        const muted = this._cssVar('--chart-muted-fg', '#64748b');
        const grid = this._cssVar('--chart-grid', 'rgba(148, 163, 184, 0.22)');

        const radarEl = this.element.querySelector('[data-audit-cockpit-chart-ref="radar"]');
        if (radarEl && radar?.labels?.length && radar?.values?.length) {
            this.charts.push(
                new Chart(radarEl, {
                    type: 'radar',
                    data: {
                        labels: radar.labels,
                        datasets: [
                            {
                                label: 'Conformité (%)',
                                data: radar.values,
                                borderColor: primary,
                                backgroundColor: this._cssVar('--chart-fill-primary', 'rgba(79, 70, 229, 0.18)'),
                                borderWidth: 2,
                                pointBackgroundColor: primary,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                min: 0,
                                max: 100,
                                ticks: { color: muted, backdropColor: 'transparent' },
                                grid: { color: grid },
                                pointLabels: { color: muted, font: { size: 10 } },
                            },
                        },
                        plugins: { legend: { display: false } },
                    },
                }),
            );
        }

        const distEl = this.element.querySelector('[data-audit-cockpit-chart-ref="distribution"]');
        if (distEl && dist?.labels?.length && dist?.values?.length) {
            const palette = ['#22c55e', '#eab308', '#f97316', '#f97316', '#dc2626', '#94a3b8', '#64748b'];
            this.charts.push(
                new Chart(distEl, {
                    type: 'doughnut',
                    data: {
                        labels: dist.labels,
                        datasets: [
                            {
                                data: dist.values,
                                backgroundColor: dist.labels.map((_, i) => palette[i % palette.length]),
                                borderWidth: 0,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: muted, boxWidth: 10, font: { size: 10 } },
                            },
                        },
                    },
                }),
            );
        }
    }
}
