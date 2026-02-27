import Collapse from 'bootstrap/js/dist/collapse';
import Dropdown from 'bootstrap/js/dist/dropdown';
import Modal from 'bootstrap/js/dist/modal';
import Toast from 'bootstrap/js/dist/toast';

const registry = window.bootstrap || {};

registry.Collapse = Collapse;
registry.Dropdown = Dropdown;
registry.Modal = Modal;
registry.Toast = Toast;

window.bootstrap = registry;

if (!window.bootstrapReady) {
  window.bootstrapReady = Promise.resolve(window.bootstrap);
}

/** Initialise les composants Bootstrap via data-bs-toggle (Dropdown, etc.) */
function initBootstrapDataAPI() {
  document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((el) => {
    Dropdown.getOrCreateInstance(el);
  });
}

document.addEventListener('DOMContentLoaded', initBootstrapDataAPI);
document.addEventListener('turbo:load', initBootstrapDataAPI);

export { Collapse, Dropdown, Modal, Toast };
