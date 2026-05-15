import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

/**
 * Multi-graphiques analytics (traffic, sharing, …) depuis un JSON `{ charts: [...] }`.
 * Chaque entrée : ref, kind, labels, values, datasetLabel?, fill?, colorVariant? ('primary'|'secondary').
 */
export default class extends Controller {
    static values = {
        config: Object,
    };

    connect() {
        this.charts = [];
        const list = this.configValue?.charts;
        if (!Array.isArray(list)) {
            return;
        }
        list.forEach((spec) => this._render(spec));
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

    _css(name, fallback) {
        const raw = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return raw || fallback;
    }

    _palette() {
        const colors = [];
        for (let i = 1; i <= 5; i += 1) {
            const v = this._css(`--chart-palette-${i}`, '');
            if (v) {
                colors.push(v);
            }
        }
        return colors.length ? colors : ['#6366f1', '#06b6d4', '#f59e0b', '#10b981', '#a855f7'];
    }

    _theme(variant) {
        const secondary = variant === 'secondary';
        return {
            primary: this._css('--chart-primary', '#4f46e5'),
            secondary: this._css('--chart-secondary', '#0891b2'),
            muted: this._css('--chart-muted-fg', '#64748b'),
            grid: this._css('--chart-grid', 'rgba(148, 163, 184, 0.22)'),
            fillPrimary: this._css('--chart-fill-primary', 'rgba(79, 70, 229, 0.12)'),
            fillSecondary: this._css('--chart-fill-secondary', 'rgba(8, 145, 178, 0.18)'),
            stroke: secondary ? this._css('--chart-secondary', '#0891b2') : this._css('--chart-primary', '#4f46e5'),
            fill: secondary ? this._css('--chart-fill-secondary', 'rgba(8, 145, 178, 0.18)') : this._css('--chart-fill-primary', 'rgba(79, 70, 229, 0.12)'),
        };
    }

    _canvasRef(ref) {
        const safe = String(ref).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        return this.element.querySelector(`[data-analytics-charts-ref="${safe}"]`);
    }

    _render(spec) {
        if (!spec?.ref || !spec?.kind) {
            return;
        }
        const canvas = this._canvasRef(spec.ref);
        if (!canvas) {
            return;
        }
        const labels = spec.labels || [];
        const values = spec.values ?? spec.data ?? [];
        if (!labels.length || !values.length) {
            return;
        }

        const variant = spec.colorVariant === 'secondary' ? 'secondary' : 'primary';
        const t = this._theme(variant);
        const label = spec.datasetLabel || '';

        const tooltipPlugin = {
            callbacks: {
                title: (items) => items.map((i) => i.label).join(' '),
                label: (ctx) => ` ${ctx.formattedValue}`,
            },
        };

        const legendPlugin =
            spec.kind === 'doughnut' || spec.kind === 'polarArea'
                ? { display: true, position: 'bottom', labels: { color: t.muted, boxWidth: 10 } }
                : { display: false };

        let chart;
        if (spec.kind === 'line') {
            const fill = Boolean(spec.fill);
            chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label,
                            data: values,
                            borderColor: t.stroke,
                            backgroundColor: fill ? t.fill : t.stroke,
                            tension: 0.32,
                            fill,
                            borderWidth: 2,
                            pointRadius: fill ? 0 : 3,
                            pointHoverRadius: fill ? 4 : 5,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    scales: {
                        x: {
                            ticks: { color: t.muted, maxRotation: 0 },
                            grid: { color: fill ? 'transparent' : t.grid },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: t.muted, precision: 0 },
                            grid: { color: t.grid },
                        },
                    },
                    plugins: { legend: legendPlugin, tooltip: tooltipPlugin },
                },
            });
        } else if (spec.kind === 'bar') {
            const fill = variant === 'secondary' ? t.fillSecondary : t.fillPrimary;
            const stroke = variant === 'secondary' ? t.secondary : t.primary;
            chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label,
                            data: values,
                            backgroundColor: fill,
                            borderColor: stroke,
                            borderRadius: 6,
                            borderWidth: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            ticks: { color: t.muted, maxRotation: 0 },
                            grid: { display: false },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: t.muted, precision: 0 },
                            grid: { color: t.grid },
                        },
                    },
                    plugins: { legend: legendPlugin, tooltip: tooltipPlugin },
                },
            });
        } else if (spec.kind === 'doughnut' || spec.kind === 'polarArea') {
            const palette = this._palette();
            const bg = labels.map((_, i) => palette[i % palette.length]);
            const ringBg = this._css('--chart-ring-bg', '#ffffff');

            chart = new Chart(canvas, {
                type: spec.kind,
                data: {
                    labels,
                    datasets: [
                        {
                            label,
                            data: values,
                            backgroundColor: bg,
                            borderColor: labels.map(() => ringBg),
                            borderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: legendPlugin, tooltip: tooltipPlugin },
                },
            });
        }

        if (chart) {
            this.charts.push(chart);
        }
    }
}
