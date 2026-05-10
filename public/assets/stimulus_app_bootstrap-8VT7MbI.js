import { startStimulusApp } from '@symfony/stimulus-bundle';
import Dialog from '@stimulus-components/dialog';
import Sortable from '@stimulus-components/sortable';
import Reveal from '@stimulus-components/reveal';
import CheckboxSelectAll from '@stimulus-components/checkbox-select-all';
import QseAuditDeleteController from './controllers/qse_audit_delete_controller.js';

const app = startStimulusApp();

app.register('dialog', Dialog);
app.register('qse-audit-delete', QseAuditDeleteController);
app.register('sortable', Sortable);
app.register('reveal', Reveal);
app.register('checkbox-select-all', CheckboxSelectAll);

window.Stimulus = app;
