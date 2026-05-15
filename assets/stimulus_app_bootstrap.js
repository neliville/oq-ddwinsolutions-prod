import { startStimulusApp } from '@symfony/stimulus-bundle';
import Dialog from '@stimulus-components/dialog';
import Sortable from '@stimulus-components/sortable';
import Reveal from '@stimulus-components/reveal';
import CheckboxSelectAll from '@stimulus-components/checkbox-select-all';
import QseAuditDeleteController from './controllers/qse_audit_delete_controller.js';
import AdminDashboardChartsController from './controllers/admin_dashboard_charts_controller.js';
import DataTableColumnsController from './controllers/data_table_columns_controller.js';
import AnalyticsChartsController from './controllers/analytics_charts_controller.js';

import AuditCockpitController from './controllers/audit_cockpit_controller.js';
import AuditSaveBarController from './controllers/audit_save_bar_controller.js';

const app = startStimulusApp();

app.register('dialog', Dialog);
app.register('qse-audit-delete', QseAuditDeleteController);
app.register('audit-cockpit', AuditCockpitController);
app.register('audit-save-bar', AuditSaveBarController);
app.register('sortable', Sortable);
app.register('reveal', Reveal);
app.register('checkbox-select-all', CheckboxSelectAll);
app.register('admin-dashboard-charts', AdminDashboardChartsController);
app.register('data-table-columns', DataTableColumnsController);
app.register('analytics-charts', AnalyticsChartsController);

window.Stimulus = app;
