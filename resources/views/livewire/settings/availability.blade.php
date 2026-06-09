<x-pages::settings.layout :heading="__('Disponibilidade')" :subheading="__('Arraste no calendário para criar um turno; ao soltar, você pode copiá-lo para vários dias.')">
    <div class="space-y-4">
        {{-- Controls: doctor + day --}}
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-56">
                <x-ui.combobox label="Médico" wire:model.live="selectedDoctorId" :options="$doctors"
                    placeholder="Selecione um médico" :error="$errors->first('selectedDoctorId')" />
            </div>
            <div class="flex items-center gap-2">
                <x-ui.button variant="secondary" type="button" wire:click="previousDay" aria-label="Dia anterior">&larr;</x-ui.button>
                <div class="w-40"><x-ui.date-picker wire:model.live="date" /></div>
                <x-ui.button variant="secondary" type="button" wire:click="nextDay" aria-label="Próximo dia">&rarr;</x-ui.button>
                <x-ui.button variant="secondary" type="button" wire:click="today">Hoje</x-ui.button>
            </div>
        </div>
        <p class="text-xs capitalize text-slate-400">{{ $dayLabel }}</p>

        {{-- 24h vertical canvas --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            @if ($selectedDoctorId === null)
                <p class="py-12 text-center text-sm text-slate-400">Selecione um médico para definir a disponibilidade.</p>
            @else
                <p class="mb-3 text-xs text-slate-400">Arraste de cima para baixo para desenhar um turno · clique no × para remover.</p>
                <div class="flex">
                    {{-- Hour ruler --}}
                    <div class="w-12 flex-shrink-0 select-none">
                        @for ($h = 0; $h < 24; $h++)
                            <div class="relative pr-2 text-right text-[10px] text-slate-400" style="height: {{ $hourPx }}px">
                                <span class="absolute -top-1.5 right-2">{{ sprintf('%02d:00', $h) }}</span>
                            </div>
                        @endfor
                    </div>

                    {{-- Draw area --}}
                    <div class="relative flex-1 cursor-crosshair border-l border-slate-200 select-none"
                        style="height: {{ 24 * $hourPx }}px"
                        x-data="{
                            hourPx: {{ $hourPx }},
                            dragging: false,
                            rectTop: 0,
                            startY: 0,
                            curY: 0,
                            begin(e) {
                                const r = this.$el.getBoundingClientRect();
                                this.rectTop = r.top;
                                this.startY = this.clampY(e.clientY - r.top);
                                this.curY = this.startY;
                                this.dragging = true;
                            },
                            move(e) {
                                if (!this.dragging) return;
                                this.curY = this.clampY(e.clientY - this.rectTop);
                            },
                            end() {
                                if (!this.dragging) return;
                                this.dragging = false;
                                const a = this.snap(Math.min(this.startY, this.curY));
                                const b = this.snap(Math.max(this.startY, this.curY));
                                if (b - a < 15) return;
                                $wire.openDraw(this.clock(a), this.clock(b));
                            },
                            clampY(y) { return Math.max(0, Math.min(24 * this.hourPx, y)); },
                            snap(y) { const raw = (y / this.hourPx) * 60; return Math.max(0, Math.min(1440, Math.round(raw / 15) * 15)); },
                            clock(m) { const h = Math.floor(m / 60), mm = m % 60; return String(h).padStart(2,'0') + ':' + String(mm).padStart(2,'0'); },
                            get a() { return this.snap(Math.min(this.startY, this.curY)); },
                            get b() { return this.snap(Math.max(this.startY, this.curY)); },
                            get previewTop() { return this.a * this.hourPx / 60; },
                            get previewHeight() { return (this.b - this.a) * this.hourPx / 60; },
                            get previewLabel() { return this.clock(this.a) + '–' + this.clock(this.b); },
                        }"
                        @mousedown.prevent="begin($event)"
                        @mousemove.window="move($event)"
                        @mouseup.window="end()">

                        {{-- Hour gridlines --}}
                        @for ($h = 0; $h < 24; $h++)
                            <div class="absolute inset-x-0 border-b border-slate-100" style="top: {{ $h * $hourPx }}px; height: {{ $hourPx }}px"></div>
                        @endfor

                        {{-- Existing shifts --}}
                        @foreach ($shiftBlocks as $block)
                            <div wire:key="block-{{ $block['id'] }}"
                                class="absolute inset-x-1 overflow-hidden rounded-lg bg-teal-600 px-2 py-1 text-[11px] text-white shadow-sm"
                                style="top: {{ $block['top'] }}px; height: {{ $block['height'] }}px">
                                <div class="flex items-start justify-between gap-1">
                                    <span class="font-medium">{{ $block['label'] }}</span>
                                    <button type="button" wire:click="removeShift({{ $block['id'] }})"
                                        class="text-teal-100 transition-colors hover:text-white" title="Remover" aria-label="Remover disponibilidade">&times;</button>
                                </div>
                            </div>
                        @endforeach

                        {{-- Live drag preview --}}
                        <div x-show="dragging" x-cloak
                            class="pointer-events-none absolute inset-x-1 rounded-lg bg-teal-400/40 ring-2 ring-teal-500"
                            :style="`top: ${previewTop}px; height: ${previewHeight}px`">
                            <span class="absolute left-2 top-0.5 text-[10px] font-semibold text-teal-800" x-text="previewLabel"></span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Copy modal --}}
    @if ($showCopyModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:key="copy-modal">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-slate-800">Nova disponibilidade</h2>
                <p class="mt-1 text-sm text-slate-500">Turno das {{ $drawStartTime }} às {{ $drawEndTime }}</p>

                <form wire:submit="saveDraw" class="mt-4 space-y-4">
                    <div>
                        <p class="mb-2 text-sm font-medium text-slate-700">Copiar para outros dias</p>
                        <div class="grid grid-cols-2 gap-4">
                            <x-ui.date-picker label="De" wire:model="copyFromDate" :error="$errors->first('copyFromDate')" />
                            <x-ui.date-picker label="Até" wire:model="copyToDate" :error="$errors->first('copyToDate')" />
                        </div>
                        @error('copyToDate') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                        @error('drawEndTime') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="skipWeekends"
                            class="rounded border-slate-300 text-teal-600 focus:ring-2 focus:ring-teal-500/40" />
                        Pular fins de semana
                    </label>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-ui.button variant="secondary" type="button" wire:click="cancelDraw">Cancelar</x-ui.button>
                        <x-ui.button type="submit">Criar</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-pages::settings.layout>
