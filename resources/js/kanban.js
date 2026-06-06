import Sortable from 'sortablejs';

/**
 * Alpine component bridging SortableJS drag-and-drop to the Livewire pipeline
 * board. Alpine owns the drag DOM; Livewire owns the state — on drop we call
 * `$wire.moveCard(cardId, stage)` (the slice-2 action) and let the server
 * re-render. Sortable is re-initialised after each morph so its instances never
 * point at stale DOM.
 */
export default function kanban() {
    return {
        sortables: [],

        init() {
            this.boot();

            if (window.Livewire) {
                window.Livewire.hook('morphed', ({ el }) => {
                    if (el === this.$root) {
                        this.boot();
                    }
                });
            }
        },

        boot() {
            this.sortables.forEach((sortable) => sortable.destroy());
            this.sortables = [];

            this.$root.querySelectorAll('[data-stage-list]').forEach((list) => {
                this.sortables.push(
                    Sortable.create(list, {
                        group: 'pipeline',
                        animation: 150,
                        ghostClass: 'opacity-40',
                        // Use the mouse-driven fallback instead of native HTML5
                        // drag so behaviour is consistent across desktop + touch.
                        forceFallback: true,
                        fallbackTolerance: 3,
                        onEnd: (event) => {
                            if (event.from === event.to) {
                                return;
                            }

                            this.$wire.moveCard(
                                Number(event.item.dataset.cardId),
                                event.to.dataset.stage,
                            );
                        },
                    }),
                );
            });
        },
    };
}
