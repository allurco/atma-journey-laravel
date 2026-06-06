/**
 * In-house searchable select (the Pro-gated combobox, built ourselves). Pure
 * Alpine — no external library. `selected` is exposed via x-modelable so a
 * parent's `wire:model` binds straight to it; filtering is client-side over the
 * options the parent passes (which are only loaded while the form is open).
 *
 * @param {{value: string, label: string}[]} options
 * @param {string} placeholder
 */
export default function uiCombobox(options, placeholder = '') {
    return {
        options,
        placeholder,
        open: false,
        search: '',
        selected: null,

        get filtered() {
            const query = this.search.trim().toLowerCase();

            if (query === '') {
                return this.options;
            }

            return this.options.filter((option) => option.label.toLowerCase().includes(query));
        },

        get selectedLabel() {
            const match = this.options.find((option) => String(option.value) === String(this.selected));

            return match ? match.label : '';
        },

        toggle() {
            this.open = ! this.open;

            if (this.open) {
                this.search = '';
                this.$nextTick(() => this.$refs.search?.focus());
            }
        },

        choose(value) {
            this.selected = value;
            this.open = false;
            this.search = '';
        },

        clear() {
            this.selected = null;
            this.search = '';
        },
    };
}
