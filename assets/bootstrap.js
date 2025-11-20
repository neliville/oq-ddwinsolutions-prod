import { Application } from '@hotwired/stimulus';
import Dialog from '@stimulus-components/dialog';
import Sortable from '@stimulus-components/sortable';
import Reveal from '@stimulus-components/reveal';

import BootstrapModalController from './controllers/bootstrap_modal_controller.js';
import CsrfProtectionController from './controllers/csrf_protection_controller.js';
import DeleteConfirmationController from './controllers/delete_confirmation_controller.js';
import DropdownBasicController from './controllers/dropdown_basic_controller.js';
import ProfileDropdownController from './controllers/profile_dropdown_controller.js';
import NotificationsController from './controllers/notifications_controller.js';

const app = Application.start();

app.register('dialog', Dialog);
app.register('sortable', Sortable);
app.register('reveal', Reveal);

app.register('bootstrap-modal', BootstrapModalController);
app.register('csrf-protection', CsrfProtectionController);
app.register('delete-confirmation', DeleteConfirmationController);
app.register('dropdown-basic', DropdownBasicController);
app.register('profile-dropdown', ProfileDropdownController);
app.register('notifications', NotificationsController);

window.Stimulus = app;

const lazyControllers = [
    {
        name: 'ishikawa',
        selector: '[data-controller~="ishikawa"]',
        loader: () => import('./controllers/ishikawa_controller.js'),
    },
    {
        name: 'ishikawa-canvas',
        selector: '[data-controller~="ishikawa-canvas"]',
        loader: () => import('./controllers/ishikawa_canvas_controller.js'),
    },
    {
        name: 'ishikawa-drag',
        selector: '[data-controller~="ishikawa-drag"]',
        loader: () => import('./controllers/ishikawa_drag_controller.js'),
    },
    {
        name: 'draggable',
        selector: '[data-controller~="draggable"]',
        loader: () => import('./controllers/draggable_controller.js'),
    },
    {
        name: 'five-why',
        selector: '[data-controller~="five-why"]',
        loader: () => import('./controllers/five_why_controller.js'),
    },
    {
        name: 'date-range',
        selector: '[data-controller~="date-range"]',
        loader: () => import('./controllers/date_range_controller.js'),
    },
    {
        name: 'filters-panel',
        selector: '[data-controller~="filters-panel"]',
        loader: () => import('./controllers/filters_panel_controller.js'),
    },
    {
        name: 'hello',
        selector: '[data-controller~="hello"]',
        loader: () => import('./controllers/hello_controller.js'),
    },
];

const registeredLazyControllers = new Set();

function registerLazyController({ name, selector, loader }) {
    if (registeredLazyControllers.has(name)) {
        return;
    }

    if (!document.querySelector(selector)) {
        return;
    }

    loader()
        .then(({ default: controller }) => {
            if (!controller) {
                return;
            }
            app.register(name, controller);
            registeredLazyControllers.add(name);
        })
        .catch((error) => {
            console.error(`[Stimulus] Échec du chargement du contrôleur "${name}"`, error);
        });
}

function hydrateLazyControllers() {
    lazyControllers.forEach(registerLazyController);
}

const runHydration = () => {
    requestIdleCallback
        ? requestIdleCallback(hydrateLazyControllers, { timeout: 500 })
        : setTimeout(hydrateLazyControllers, 50);
};

document.addEventListener('DOMContentLoaded', runHydration);
document.addEventListener('turbo:load', runHydration);
document.addEventListener('turbo:render', runHydration);
