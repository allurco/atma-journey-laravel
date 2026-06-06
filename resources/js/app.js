import combobox from './combobox';
import kanban from './kanban';

// Register our in-house Alpine components on Livewire's bundled Alpine. The
// `alpine:init` hook fires before Alpine starts, so they're available to
// `x-data` in the views.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('kanban', kanban);
    window.Alpine.data('uiCombobox', combobox);
});
