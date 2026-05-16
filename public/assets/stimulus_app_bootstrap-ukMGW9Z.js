import { startStimulusApp } from '@symfony/stimulus-bundle';
import Dialog from '@stimulus-components/dialog';
import Sortable from '@stimulus-components/sortable';
import Reveal from '@stimulus-components/reveal';
import CheckboxSelectAll from '@stimulus-components/checkbox-select-all';
import QseAuditDeleteController from './controllers/qse_audit_delete_controller.js';
import QseAuditBoardController from './controllers/qse_audit_board_controller.js';
import DataTableColumnsController from './controllers/data_table_columns_controller.js';
import ChartController from './controllers/chart_controller.js';
import PdcaChartsLazyController from './controllers/pdca_charts_lazy_controller.js';

import AuditCockpitController from './controllers/audit_cockpit_controller.js';
import AuditSaveBarController from './controllers/audit_save_bar_controller.js';

import GsapRevealController from './controllers/motion/gsap_reveal_controller.js';
import GsapStaggerController from './controllers/motion/gsap_stagger_controller.js';
import HomeEntranceController from './controllers/motion/home_entrance_controller.js';
import GsapCounterController from './controllers/motion/gsap_counter_controller.js';
import MotionHoverController from './controllers/motion/motion_hover_controller.js';
import AutoAnimateController from './controllers/motion/auto_animate_controller.js';
import PageTransitionController from './controllers/motion/page_transition_controller.js';
import MotionPremiumController from './controllers/motion/motion_premium_controller.js';
import AccordionController from './controllers/accordion_controller.js';
import RiskCriticalityController from './controllers/risk_criticality_controller.js';
import CapaFormController from './controllers/capa_form_controller.js';

import ToastController from './controllers/interactions/toast_controller.js';
import SidebarInteractionController from './controllers/interactions/sidebar_interaction_controller.js';
import AuditSavePulseController from './controllers/interactions/audit_save_pulse_controller.js';
import PasswordToggleController from './controllers/password_toggle_controller.js';
import PasswordStrengthController from './controllers/password_strength_controller.js';

const app = startStimulusApp();

app.register('dialog', Dialog);
app.register('qse-audit-delete', QseAuditDeleteController);
app.register('qse-audit-board', QseAuditBoardController);
app.register('audit-cockpit', AuditCockpitController);
app.register('audit-save-bar', AuditSaveBarController);
app.register('audit-save-pulse', AuditSavePulseController);
app.register('sortable', Sortable);
app.register('reveal', Reveal);
app.register('checkbox-select-all', CheckboxSelectAll);
app.register('chart', ChartController);
app.register('pdca-charts-lazy', PdcaChartsLazyController);
app.register('data-table-columns', DataTableColumnsController);

app.register('gsap-reveal', GsapRevealController);
app.register('gsap-stagger', GsapStaggerController);
app.register('home-entrance', HomeEntranceController);
app.register('gsap-counter', GsapCounterController);
app.register('motion-hover', MotionHoverController);
app.register('auto-animate', AutoAnimateController);
app.register('page-transition', PageTransitionController);
app.register('motion-premium', MotionPremiumController);
app.register('accordion', AccordionController);
app.register('risk-criticality', RiskCriticalityController);
app.register('capa-form', CapaFormController);

app.register('toast', ToastController);
app.register('sidebar-interaction', SidebarInteractionController);
app.register('password-toggle', PasswordToggleController);
app.register('password-strength', PasswordStrengthController);

window.Stimulus = app;
