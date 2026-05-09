import { startStimulusApp } from '@symfony/stimulus-bundle';
import Dialog from '@stimulus-components/dialog';
import Sortable from '@stimulus-components/sortable';
import Reveal from '@stimulus-components/reveal';
import CheckboxSelectAll from '@stimulus-components/checkbox-select-all';

const app = startStimulusApp();

app.register('dialog', Dialog);
app.register('sortable', Sortable);
app.register('reveal', Reveal);
app.register('checkbox-select-all', CheckboxSelectAll);

window.Stimulus = app;
