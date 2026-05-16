import ApexCharts from 'apexcharts';

const muted = () => getComputedStyle(document.documentElement).getPropertyValue('--chart-muted-fg').trim() || '#64748b';
const primary = () => getComputedStyle(document.documentElement).getPropertyValue('--chart-primary').trim() || '#4f46e5';
const grid = () => getComputedStyle(document.documentElement).getPropertyValue('--chart-grid').trim() || 'rgba(148, 163, 184, 0.22)';
const fillPrimary = () => getComputedStyle(document.documentElement).getPropertyValue('--chart-fill-primary').trim() || 'rgba(79, 70, 229, 0.14)';

function replaceCanvasWithDiv(canvas) {
    const div = document.createElement('div');
    div.className = `${canvas.className} w-full h-full min-h-0`;
    const h = canvas.closest('[data-chart-wrap]')?.offsetHeight || canvas.offsetHeight || 208;
    div.style.minHeight = `${Math.max(h, 120)}px`;
    canvas.replaceWith(div);
    return div;
}

function mountAdminDashboardApex(el, cfg) {
    const instances = [];

    const line = (ref, series, title) => {
        const canvas = el.querySelector(`[data-admin-dashboard-charts-chart-ref="${ref}"]`);
        if (!canvas || !series?.labels?.length) {
            return;
        }
        const host = replaceCanvasWithDiv(canvas);
        const inst = new ApexCharts(host, {
            chart: { type: 'line', toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
            series: [{ name: title, data: series.values }],
            xaxis: { categories: series.labels, labels: { style: { colors: muted() } } },
            yaxis: { labels: { style: { colors: muted() } } },
            stroke: { curve: 'smooth', width: 2, colors: [primary()] },
            markers: { size: 3, colors: [primary()], strokeWidth: 0 },
            colors: [primary()],
            grid: { borderColor: grid(), strokeDashArray: 4 },
            dataLabels: { enabled: false },
            tooltip: { theme: 'light' },
        });
        inst.render();
        instances.push(inst);
    };

    const area = (ref, series, title) => {
        const canvas = el.querySelector(`[data-admin-dashboard-charts-chart-ref="${ref}"]`);
        if (!canvas || !series?.labels?.length) {
            return;
        }
        const host = replaceCanvasWithDiv(canvas);
        const inst = new ApexCharts(host, {
            chart: { type: 'area', toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
            series: [{ name: title, data: series.values }],
            xaxis: { categories: series.labels, labels: { style: { colors: muted() } } },
            yaxis: { labels: { style: { colors: muted() } } },
            stroke: { curve: 'smooth', width: 2, colors: [primary()] },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100],
                    colorStops: [
                        { offset: 0, color: primary(), opacity: 0.35 },
                        { offset: 100, color: primary(), opacity: 0.02 },
                    ],
                },
            },
            colors: [primary()],
            grid: { borderColor: grid(), strokeDashArray: 4 },
            dataLabels: { enabled: false },
        });
        inst.render();
        instances.push(inst);
    };

    const hbar = (ref, series, title) => {
        const canvas = el.querySelector(`[data-admin-dashboard-charts-chart-ref="${ref}"]`);
        if (!canvas || !series?.labels?.length) {
            return;
        }
        const host = replaceCanvasWithDiv(canvas);
        const inst = new ApexCharts(host, {
            chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '72%' } },
            series: [{ name: title, data: series.values }],
            xaxis: {
                categories: series.labels,
                labels: { style: { colors: muted() } },
            },
            yaxis: { labels: { style: { colors: muted() } } },
            colors: [primary()],
            grid: { borderColor: grid(), strokeDashArray: 4 },
            dataLabels: { enabled: false },
        });
        inst.render();
        instances.push(inst);
    };

    line('chartRegistrations', cfg.registrations, 'Inscriptions');
    area('chartVisits', cfg.visits, 'Visites');
    hbar('chartEngagement', cfg.engagement, 'Activation');

    return () => {
        instances.forEach((i) => {
            try {
                i.destroy();
            } catch (_) {
                /* noop */
            }
        });
    };
}

function mountUserDashboardApex(el, cfg) {
    const instances = [];
    const charts = cfg.charts || [];

    charts.forEach((spec) => {
        const canvas = el.querySelector(`[data-user-dashboard-chart-ref="${spec.ref}"]`);
        if (!canvas) {
            return;
        }
        const host = replaceCanvasWithDiv(canvas);

        if (spec.type === 'donut') {
            const inst = new ApexCharts(host, {
                chart: { type: 'donut', fontFamily: 'inherit', toolbar: { show: false } },
                labels: spec.labels,
                series: spec.series,
                legend: { position: 'bottom', labels: { colors: muted() } },
                colors: spec.colors || ['#6366f1', '#94a3b8'],
                plotOptions: { pie: { donut: { size: '68%' } } },
                dataLabels: { enabled: false },
            });
            inst.render();
            instances.push(inst);
            return;
        }

        if (spec.type === 'bar') {
            const inst = new ApexCharts(host, {
                chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
                plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
                series: [{ name: spec.name || '', data: spec.series }],
                xaxis: { categories: spec.labels, labels: { style: { colors: muted() } } },
                yaxis: { labels: { style: { colors: muted() } } },
                colors: [primary()],
                grid: { borderColor: grid(), strokeDashArray: 4 },
                dataLabels: { enabled: false },
            });
            inst.render();
            instances.push(inst);
        }
    });

    return () => {
        instances.forEach((i) => {
            try {
                i.destroy();
            } catch (_) {
                /* noop */
            }
        });
    };
}

function mountAuditCockpitApex(el, cfg) {
    const instances = [];
    const radar = cfg.radar;
    const dist = cfg.distribution;

    const radarCanvas = el.querySelector('[data-audit-cockpit-chart-ref="radar"]');
    if (radarCanvas && radar?.labels?.length && radar?.values?.length) {
        const host = replaceCanvasWithDiv(radarCanvas);
        const inst = new ApexCharts(host, {
            chart: { type: 'radar', toolbar: { show: false }, fontFamily: 'inherit' },
            series: [{ name: 'Conformité (%)', data: radar.values }],
            xaxis: { categories: radar.labels },
            yaxis: { show: false, max: 100, min: 0 },
            stroke: { width: 2, colors: [primary()] },
            fill: { opacity: 0.25, colors: [primary()] },
            markers: { size: 3, colors: [primary()], strokeWidth: 0 },
            plotOptions: {
                radar: {
                    polygons: {
                        strokeColors: grid(),
                        connectorColors: grid(),
                    },
                },
            },
            colors: [primary()],
        });
        inst.render();
        instances.push(inst);
    }

    const distCanvas = el.querySelector('[data-audit-cockpit-chart-ref="distribution"]');
    if (distCanvas && dist?.labels?.length && dist?.values?.length) {
        const host = replaceCanvasWithDiv(distCanvas);
        const pal = ['#22c55e', '#eab308', '#f97316', '#dc2626', '#94a3b8', '#64748b'];
        const colors = dist.labels.map((_, i) => pal[i % pal.length]);
        const inst = new ApexCharts(host, {
            chart: { type: 'donut', fontFamily: 'inherit', toolbar: { show: false } },
            labels: dist.labels,
            series: dist.values,
            colors,
            legend: { position: 'bottom', fontSize: '11px', labels: { colors: muted() } },
            dataLabels: { enabled: false },
        });
        inst.render();
        instances.push(inst);
    }

    return () => {
        instances.forEach((i) => {
            try {
                i.destroy();
            } catch (_) {
                /* noop */
            }
        });
    };
}

function readPalette() {
    const colors = [];
    for (let i = 1; i <= 5; i += 1) {
        const v = getComputedStyle(document.documentElement).getPropertyValue(`--chart-palette-${i}`).trim();
        if (v) {
            colors.push(v);
        }
    }
    return colors.length ? colors : ['#6366f1', '#06b6d4', '#f59e0b', '#10b981', '#a855f7'];
}

function mountAnalyticsApex(el, configValue) {
    const instances = [];
    const list = configValue?.charts;
    if (!Array.isArray(list)) {
        return () => {};
    }

    const css = (name, fb) => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fb;
    const th = (variant) => ({
        stroke: variant === 'secondary' ? css('--chart-secondary', '#0891b2') : css('--chart-primary', '#4f46e5'),
        fill: variant === 'secondary' ? css('--chart-fill-secondary', 'rgba(8,145,178,0.18)') : css('--chart-fill-primary', 'rgba(79,70,229,0.12)'),
        muted: css('--chart-muted-fg', '#64748b'),
        grid: css('--chart-grid', 'rgba(148, 163, 184, 0.22)'),
    });

    list.forEach((spec) => {
        if (!spec?.ref || !spec?.kind) {
            return;
        }
        const safe = String(spec.ref).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        const canvas = el.querySelector(`[data-analytics-charts-ref="${safe}"]`);
        if (!canvas) {
            return;
        }
        const labels = spec.labels || [];
        const values = spec.values ?? spec.data ?? [];
        if (!labels.length || !values.length) {
            return;
        }

        const variant = spec.colorVariant === 'secondary' ? 'secondary' : 'primary';
        const t = th(variant);
        const datasetLabel = spec.datasetLabel || '';
        const host = replaceCanvasWithDiv(canvas);

        if (spec.kind === 'line') {
            const fill = Boolean(spec.fill);
            const inst = new ApexCharts(host, {
                chart: { type: 'line', toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
                series: [{ name: datasetLabel, data: values }],
                xaxis: { categories: labels, labels: { style: { colors: t.muted } } },
                yaxis: { labels: { style: { colors: t.muted } } },
                stroke: { curve: 'smooth', width: 2, colors: [t.stroke] },
                fill: fill
                    ? {
                          type: 'gradient',
                          gradient: {
                              opacityFrom: 0.35,
                              opacityTo: 0.02,
                              colorStops: [
                                  { offset: 0, color: t.stroke, opacity: 0.35 },
                                  { offset: 100, color: t.stroke, opacity: 0.02 },
                              ],
                          },
                      }
                    : { opacity: 0 },
                colors: [t.stroke],
                grid: { borderColor: t.grid, strokeDashArray: 4 },
                dataLabels: { enabled: false },
            });
            inst.render();
            instances.push(inst);
            return;
        }

        if (spec.kind === 'bar') {
            const inst = new ApexCharts(host, {
                chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
                plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
                series: [{ name: datasetLabel, data: values }],
                xaxis: { categories: labels, labels: { style: { colors: t.muted } } },
                yaxis: { labels: { style: { colors: t.muted } } },
                colors: [t.fill],
                grid: { borderColor: t.grid, strokeDashArray: 4 },
                dataLabels: { enabled: false },
            });
            inst.render();
            instances.push(inst);
            return;
        }

        if (spec.kind === 'doughnut' || spec.kind === 'polarArea') {
            const pal = readPalette();
            const bg = labels.map((_, i) => pal[i % pal.length]);
            const inst = new ApexCharts(host, {
                chart: { type: 'donut', fontFamily: 'inherit', toolbar: { show: false } },
                labels,
                series: values,
                colors: bg,
                legend: { position: 'bottom', labels: { colors: t.muted } },
                plotOptions: { pie: { donut: { size: spec.kind === 'polarArea' ? '55%' : '68%' } } },
                dataLabels: { enabled: false },
            });
            inst.render();
            instances.push(inst);
        }
    });

    return () => {
        instances.forEach((i) => {
            try {
                i.destroy();
            } catch (_) {
                /* noop */
            }
        });
    };
}

function mountPdcaCockpitApex(el, cfg) {
    const instances = [];
    const charts = cfg.charts || [];

    const revealChart = (host) => {
        const wrap = host.closest('[data-chart-wrap]');
        wrap?.querySelector('.pdca-chart-skeleton')?.classList.add('hidden');
        host.classList.remove('opacity-0');
        host.classList.add('opacity-100');
    };

    charts.forEach((spec) => {
        if (!spec?.ref) {
            return;
        }
        const safe = String(spec.ref).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        const canvas = el.querySelector(`[data-pdca-cockpit-chart-ref="${safe}"]`);
        if (!canvas) {
            return;
        }

        if (spec.type === 'donut' && spec.labels?.length && spec.series?.length) {
            const host = replaceCanvasWithDiv(canvas);
            const inst = new ApexCharts(host, {
                chart: { type: 'donut', fontFamily: 'inherit', toolbar: { show: false } },
                labels: spec.labels,
                series: spec.series,
                colors: spec.colors || readPalette(),
                legend: { position: 'bottom', fontSize: '11px', labels: { colors: muted() } },
                plotOptions: { pie: { donut: { size: '68%' } } },
                dataLabels: { enabled: false },
            });
            inst.render().then(() => revealChart(host));
            instances.push(inst);
            return;
        }

        if (spec.type === 'hbar' && spec.labels?.length) {
            const host = replaceCanvasWithDiv(canvas);
            const inst = new ApexCharts(host, {
                chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
                plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '72%' } },
                series: [{ name: spec.name || '', data: spec.series }],
                xaxis: { categories: spec.labels, labels: { style: { colors: muted() } } },
                yaxis: { labels: { style: { colors: muted() } } },
                colors: [primary()],
                grid: { borderColor: grid(), strokeDashArray: 4 },
                dataLabels: { enabled: false },
            });
            inst.render().then(() => revealChart(host));
            instances.push(inst);
            return;
        }

        if (spec.type === 'heatmap' && spec.series?.length) {
            const host = replaceCanvasWithDiv(canvas);
            const inst = new ApexCharts(host, {
                chart: { type: 'heatmap', toolbar: { show: false }, fontFamily: 'inherit' },
                series: spec.series,
                plotOptions: {
                    heatmap: {
                        shadeIntensity: 0.5,
                        radius: 4,
                        colorScale: {
                            ranges: [
                                { from: 0, to: 0, color: '#f1f5f9', name: 'Aucun' },
                                { from: 1, to: 2, color: '#a7f3d0', name: 'Faible' },
                                { from: 3, to: 5, color: '#fbbf24', name: 'Moyen' },
                                { from: 6, to: 99, color: '#dc2626', name: 'Élevé' },
                            ],
                        },
                    },
                },
                dataLabels: { enabled: true, style: { fontSize: '10px' } },
                xaxis: { labels: { style: { colors: muted() } } },
                yaxis: { labels: { style: { colors: muted() } } },
            });
            inst.render().then(() => revealChart(host));
            instances.push(inst);
            return;
        }

        if (spec.type === 'area' && spec.labels?.length) {
            const host = replaceCanvasWithDiv(canvas);
            const multi = Array.isArray(spec.series) && spec.series[0]?.data;
            const series = multi
                ? spec.series.map((s) => ({ name: s.name || '', data: s.data }))
                : [{ name: spec.name || '', data: spec.series }];
            const inst = new ApexCharts(host, {
                chart: { type: 'area', toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
                series,
                xaxis: { categories: spec.labels, labels: { style: { colors: muted() } } },
                yaxis: { labels: { style: { colors: muted() } } },
                stroke: { curve: 'smooth', width: 2 },
                fill: {
                    type: 'gradient',
                    gradient: { opacityFrom: 0.35, opacityTo: 0.05 },
                },
                colors: [primary(), '#06b6d4'],
                grid: { borderColor: grid(), strokeDashArray: 4 },
                legend: { position: 'top', labels: { colors: muted() } },
                dataLabels: { enabled: false },
            });
            inst.render().then(() => revealChart(host));
            instances.push(inst);
            return;
        }

        if (spec.type === 'radar' && spec.labels?.length && spec.values?.length) {
            const host = replaceCanvasWithDiv(canvas);
            const inst = new ApexCharts(host, {
                chart: { type: 'radar', toolbar: { show: false }, fontFamily: 'inherit' },
                series: [{ name: 'Score phase', data: spec.values }],
                xaxis: { categories: spec.labels },
                yaxis: { show: false, max: 100, min: 0 },
                stroke: { width: 2, colors: [primary()] },
                fill: { opacity: 0.2, colors: [primary()] },
                markers: { size: 3, colors: [primary()], strokeWidth: 0 },
                plotOptions: {
                    radar: {
                        polygons: {
                            strokeColors: grid(),
                            connectorColors: grid(),
                        },
                    },
                },
                colors: [primary()],
            });
            inst.render().then(() => revealChart(host));
            instances.push(inst);
        }
    });

    el.dispatchEvent(new CustomEvent('pdca-charts:mounted', { bubbles: true }));

    return () => {
        instances.forEach((i) => {
            try {
                i.destroy();
            } catch (_) {
                /* noop */
            }
        });
    };
}

/** Admin funnel / leads — bar simple */
function mountAdminGrowthApex(el, cfg) {
    const canvas = el.querySelector('[data-admin-growth-chart-ref="main"]');
    if (!canvas || !cfg?.labels?.length) {
        return () => {};
    }
    const host = replaceCanvasWithDiv(canvas);
    const inst = new ApexCharts(host, {
        chart: { type: 'bar', stacked: Boolean(cfg.stacked), toolbar: { show: false }, fontFamily: 'inherit' },
        series: cfg.series || [{ name: 'Valeur', data: cfg.values || [] }],
        xaxis: { categories: cfg.labels, labels: { style: { colors: muted() } } },
        yaxis: { labels: { style: { colors: muted() } } },
        colors: cfg.colors || ['#6366f1', '#06b6d4', '#f59e0b'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '62%' } },
        grid: { borderColor: grid(), strokeDashArray: 4 },
        legend: { position: 'top', labels: { colors: muted() } },
        dataLabels: { enabled: false },
    });
    inst.render();
    return () => {
        try {
            inst.destroy();
        } catch (_) {
            /* noop */
        }
    };
}

/**
 * @param {string} kind
 * @param {HTMLElement} el
 * @param {object} config
 * @returns {() => void}
 */
export function mount(kind, el, config) {
    if (kind === 'admin-dashboard') {
        return mountAdminDashboardApex(el, config);
    }
    if (kind === 'user-dashboard') {
        return mountUserDashboardApex(el, config);
    }
    if (kind === 'audit-cockpit') {
        return mountAuditCockpitApex(el, config);
    }
    if (kind === 'analytics') {
        return mountAnalyticsApex(el, config);
    }
    if (kind === 'admin-growth') {
        return mountAdminGrowthApex(el, config);
    }
    if (kind === 'pdca-cockpit') {
        return mountPdcaCockpitApex(el, config);
    }
    return () => {};
}
