import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

/**
 * Graphiques du dashboard admin (données sérialisées depuis AdminDashboardMetricsProvider).
 */
export default class extends Controller {
    static values = {
        config: Object,
    };

    connect() {
        this.charts = [];
        const cfg = this.configValue || {};
        const colors = this._readTheme();
        this._lineChart('chartRegistrations', cfg.registrations, 'Inscriptions par jour', colors, false);
        this._areaChart('chartVisits', cfg.visits, 'Visites pages (jour)', colors);
        this._horizontalBar('chartEngagement', cfg.engagement, 'Activation & usage (7 derniers jours)', colors);
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

    _cssVar(name, fallback) {
        const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return v || fallback;
    }

    _readTheme() {
        return {
            primary: this._cssVar('--chart-primary', '#4f46e5'),
            muted: this._cssVar('--chart-muted-fg', '#64748b'),
            grid: this._cssVar('--chart-grid', 'rgba(148, 163, 184, 0.22)'),
            fillPrimary: this._cssVar('--chart-fill-primary', 'rgba(79, 70, 229, 0.14)'),
        };
    }

    _lineChart(ref, series, label, colors, fill) {
        const el = this.element.querySelector(`[data-admin-dashboard-charts-chart-ref="${ref}"]`);
        if (!el || !series?.labels?.length) {
            return;
        }
        const chart = new Chart(el, {
            type: 'line',
            data: {
                labels: series.labels,
                datasets: [
                    {
                        label,
                        data: series.values,
                        borderColor: colors.primary,
                        backgroundColor: colors.primary,
                        tension: 0.35,
                        fill,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                scales: {
                    x: {
                        ticks: { color: colors.muted, maxRotation: 0 },
                        grid: { color: colors.grid },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: colors.muted, precision: 0 },
                        grid: { color: colors.grid },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => items.map((i) => i.label).join(' '),
                            label: (ctx) => ` ${ctx.formattedValue}`,
                        },
                    },
                },
            },
        });
        this.charts.push(chart);
    }

    _areaChart(ref, series, label, colors) {
        const el = this.element.querySelector(`[data-admin-dashboard-charts-chart-ref="${ref}"]`);
        if (!el || !series?.labels?.length) {
            return;
        }
        const chart = new Chart(el, {
            type: 'line',
            data: {
                labels: series.labels,
                datasets: [
                    {
                        label,
                        data: series.values,
                        borderColor: colors.primary,
                        backgroundColor: colors.fillPrimary,
                        tension: 0.35,
                        fill: true,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                scales: {
                    x: {
                        ticks: { color: colors.muted, maxRotation: 0 },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: colors.muted, precision: 0 },
                        grid: { color: colors.grid },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => items.map((i) => i.label).join(' '),
                            label: (ctx) => ` ${ctx.formattedValue}`,
                        },
                    },
                },
            },
        });
        this.charts.push(chart);
    }

    _horizontalBar(ref, series, label, colors) {
        const el = this.element.querySelector(`[data-admin-dashboard-charts-chart-ref="${ref}"]`);
        if (!el || !series?.labels?.length) {
            return;
        }
        const chart = new Chart(el, {
            type: 'bar',
            data: {
                labels: series.labels,
                datasets: [
                    {
                        label,
                        data: series.values,
                        borderColor: colors.primary,
                        backgroundColor: colors.fillPrimary,
                        borderRadius: 6,
                        borderSkipped: false,
                        barThickness: 14,
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { color: colors.muted, precision: 0 },
                        grid: { color: colors.grid },
                    },
                    y: {
                        ticks: { color: colors.muted },
                        grid: { display: false },
                    },
                },
                plugins: {
                    legend: { display: false },
                },
            },
        });
        this.charts.push(chart);
    }
}
