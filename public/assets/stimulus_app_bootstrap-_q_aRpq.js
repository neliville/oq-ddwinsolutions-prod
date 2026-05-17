import { startStimulusApp } from '@symfony/stimulus-bundle';
import Dialog from '@stimulus-components/dialog';
import Sortable from '@stimulus-components/sortable';
import Reveal from '@stimulus-components/reveal';
import CheckboxSelectAll from '@stimulus-components/checkbox-select-all';
import AccordionController from './controllers/accordion_controller.js';
import PasswordToggleController from './controllers/password_toggle_controller.js';
import PasswordStrengthController from './controllers/password_strength_controller.js';

/**
 * Layer 1 — Core Stimulus.
 * Contrôleurs projet : lazy via `stimulusFetch` (voir assets/controllers.json + en-têtes fichiers).
 * Motion premium : `motion/bootstrap-premium.js` si `data-page-motion="true"`.
 */
const app = startStimulusApp();

app.register('dialog', Dialog);
app.register('sortable', Sortable);
app.register('reveal', Reveal);
app.register('checkbox-select-all', CheckboxSelectAll);
app.register('accordion', AccordionController);
app.register('password-toggle', PasswordToggleController);
app.register('password-strength', PasswordStrengthController);

window.Stimulus = app;
