import kanban from './kanban';

// Register the in-house Kanban component on Livewire's bundled Alpine. The
// `alpine:init` hook fires before Alpine starts, so `kanban()` is available to
// `x-data` on the pipeline board.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('kanban', kanban);
});
