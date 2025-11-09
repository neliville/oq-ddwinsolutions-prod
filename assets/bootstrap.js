import { startStimulusApp } from '@symfony/stimulus-bundle';
import Dialog from '@stimulus-components/dialog';
import Sortable from '@stimulus-components/sortable';
import Reveal from '@stimulus-components/reveal';

import BootstrapModalController from './controllers/bootstrap_modal_controller.js';
import CsrfProtectionController from './controllers/csrf_protection_controller.js';
import DeleteConfirmationController from './controllers/delete_confirmation_controller.js';
import DraggableController from './controllers/draggable_controller.js';
import DropdownBasicController from './controllers/dropdown_basic_controller.js';
import FiveWhyController from './controllers/five_why_controller.js';
import HelloController from './controllers/hello_controller.js';
import IshikawaCanvasController from './controllers/ishikawa_canvas_controller.js';
import IshikawaController from './controllers/ishikawa_controller.js';
import IshikawaDragController from './controllers/ishikawa_drag_controller.js';
import LiveComponentController from './controllers/live_component_controller.js';
import ProfileDropdownController from './controllers/profile_dropdown_controller.js';

const app = startStimulusApp();

app.register('dialog', Dialog);
app.register('sortable', Sortable);
app.register('reveal', Reveal);

app.register('bootstrap-modal', BootstrapModalController);
app.register('csrf-protection', CsrfProtectionController);
app.register('delete-confirmation', DeleteConfirmationController);
app.register('draggable', DraggableController);
app.register('dropdown-basic', DropdownBasicController);
app.register('five-why', FiveWhyController);
app.register('hello', HelloController);
app.register('ishikawa-canvas', IshikawaCanvasController);
app.register('ishikawa', IshikawaController);
app.register('ishikawa-drag', IshikawaDragController);
app.register('live-component', LiveComponentController);
app.register('profile-dropdown', ProfileDropdownController);

window.Stimulus = app;
