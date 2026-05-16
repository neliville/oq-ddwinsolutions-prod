/**
 * Configuration Flatpickr partagée (locale FR + options communes).
 */

export const frenchLocale = {
    firstDayOfWeek: 1,
    weekdays: {
        shorthand: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
        longhand: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
    },
    months: {
        shorthand: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
        longhand: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
    },
};

let stylesLoaded = false;

export async function loadFlatpickrStyles() {
    if (stylesLoaded) {
        return;
    }
    await import('flatpickr/dist/flatpickr.min.css');
    stylesLoaded = true;
}

export async function loadFlatpickr() {
    const module = await import('flatpickr');
    return module.default;
}

/**
 * Options pour un champ date unique (input type=date conservé, affichage d/m/Y).
 *
 * @param {object} overrides
 * @returns {object}
 */
export function getSingleDateOptions(overrides = {}) {
    return {
        locale: frenchLocale,
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        disableMobile: true,
        allowInput: false,
        clickOpens: true,
        ...overrides,
    };
}

/**
 * Options pour le sélecteur de plage (admin analytics).
 *
 * @param {object} overrides
 * @returns {object}
 */
export function getRangeDateOptions(overrides = {}) {
    return {
        mode: 'range',
        locale: frenchLocale,
        dateFormat: 'Y-m-d',
        allowInput: false,
        clickOpens: true,
        disableMobile: true,
        ...overrides,
    };
}

/**
 * @param {HTMLInputElement} input
 * @param {string} isoDate Y-m-d
 */
export function dispatchInputEvents(input) {
    if (!input) {
        return;
    }
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

const flatpickrInstances = new WeakMap();

/**
 * Initialise Flatpickr sur un input type=date (sans Stimulus).
 *
 * @param {HTMLInputElement} input
 * @param {object} overrides Options Flatpickr
 * @returns {Promise<object|null>}
 */
export async function initDateFieldInput(input, overrides = {}) {
    if (!input || input.type !== 'date' || input.dataset.dateFieldInitialized === '1') {
        return flatpickrInstances.get(input) ?? null;
    }

    await loadFlatpickrStyles();
    const Flatpickr = await loadFlatpickr();

    const options = getSingleDateOptions({
        defaultDate: input.value || undefined,
        minDate: input.min || undefined,
        maxDate: input.max || undefined,
        onChange: (selectedDates, dateStr) => {
            if (dateStr) {
                input.value = dateStr;
            } else if (selectedDates.length === 0) {
                input.value = '';
            }
            dispatchInputEvents(input);
        },
        onReady: (_selectedDates, _dateStr, instance) => {
            instance.calendarContainer?.classList.add('flatpickr-oq');
        },
        onOpen: (_selectedDates, _dateStr, instance) => {
            instance.calendarContainer?.classList.add('flatpickr-oq');
        },
        ...overrides,
    });

    const instance = new Flatpickr(input, options);
    input.dataset.dateFieldInitialized = '1';
    flatpickrInstances.set(input, instance);

    const wrapper = input.closest('.oq-date-field');
    wrapper?.classList.add('oq-date-field--ready');

    return instance;
}

/**
 * @param {HTMLInputElement} input
 */
export function destroyDateFieldInput(input) {
    const instance = flatpickrInstances.get(input);
    if (instance) {
        instance.destroy();
        flatpickrInstances.delete(input);
    }
    if (input) {
        delete input.dataset.dateFieldInitialized;
        input.closest('.oq-date-field')?.classList.remove('oq-date-field--ready');
    }
}

/**
 * Initialise tous les champs .oq-date-field dans un sous-arbre (outils 8D, etc.).
 *
 * @param {ParentNode} root
 * @returns {Promise<void>}
 */
export async function scanAndInitDateFields(root = document) {
    const wrappers = root.querySelectorAll?.('.oq-date-field') ?? [];
    const tasks = [];

    wrappers.forEach((wrapper) => {
        const input = wrapper.querySelector('input[type="date"]');
        if (input && input.dataset.dateFieldInitialized !== '1') {
            tasks.push(initDateFieldInput(input));
        }
    });

    await Promise.all(tasks);
}

if (typeof window !== 'undefined') {
    window.oqInitDateFields = scanAndInitDateFields;
}
