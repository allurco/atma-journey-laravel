<div>
    <a href="{{ route('pacientes.show', $patient) }}" wire:navigate class="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
        &larr; Voltar para o paciente
    </a>

    {{-- Header --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex items-start gap-4">
            @if ($patient->photoUrl())
                <img src="{{ $patient->photoUrl() }}" alt="{{ $patient->name }}"
                    class="h-16 w-16 flex-shrink-0 rounded-2xl object-cover" />
            @else
                <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-teal-100 text-xl font-semibold text-teal-700">
                    {{ $patient->initials() }}
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-teal-600">Prontuário</p>
                <h1 class="text-2xl font-semibold text-slate-800">{{ $patient->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Registro clínico do paciente.</p>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mt-6 flex flex-wrap gap-6 border-b border-slate-200">
        <button type="button" wire:click="$set('tab', 'anamnese')"
            @class([
                'pb-3 text-sm font-medium transition-colors',
                'border-b-2 border-teal-500 text-teal-700' => $tab === 'anamnese',
                'text-slate-400 hover:text-slate-600' => $tab !== 'anamnese',
            ])>
            Anamnese
        </button>
        <button type="button" wire:click="$set('tab', 'evolucao')"
            @class([
                'pb-3 text-sm font-medium transition-colors',
                'border-b-2 border-teal-500 text-teal-700' => $tab === 'evolucao',
                'text-slate-400 hover:text-slate-600' => $tab !== 'evolucao',
            ])>
            Evolução
        </button>
        <button type="button" wire:click="$set('tab', 'receitas')"
            @class([
                'pb-3 text-sm font-medium transition-colors',
                'border-b-2 border-teal-500 text-teal-700' => $tab === 'receitas',
                'text-slate-400 hover:text-slate-600' => $tab !== 'receitas',
            ])>
            Receitas
        </button>
        @foreach (['Documentos & Exames'] as $soon)
            <span class="flex items-center gap-1.5 pb-3 text-sm font-medium text-slate-300">
                {{ $soon }}
                <span class="text-[10px] uppercase tracking-wider text-slate-300">em breve</span>
            </span>
        @endforeach
    </div>

    @if ($tab === 'anamnese')
        <div class="mt-6">
            <div class="rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-slate-800">Anamnese</h2>
                    <p class="text-xs text-slate-500">A história clínica do paciente — um registro por paciente, atualizado quando necessário.</p>
                </div>

                @if ($canManage)
                    <form wire:submit="saveAnamnese" class="space-y-5 p-6">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Queixa principal</label>
                            <textarea wire:model="chiefComplaint" rows="2"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40"
                                placeholder="Motivo da consulta"></textarea>
                            @error('chiefComplaint')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">História da doença / evolução</label>
                            <textarea wire:model="history" rows="4"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40"
                                placeholder="Histórico relevante"></textarea>
                            @error('history')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Medicações em uso</label>
                                <textarea wire:model="medications" rows="3"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40"
                                    placeholder="Ex.: Losartana 50mg"></textarea>
                                @error('medications')
                                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Histórico familiar</label>
                                <textarea wire:model="familyHistory" rows="3"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40"
                                    placeholder="Antecedentes familiares relevantes"></textarea>
                                @error('familyHistory')
                                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            @if ($anamneseSaved)
                                <span class="text-sm text-emerald-600">Anamnese salva.</span>
                            @endif
                            <x-ui.button type="submit">Salvar anamnese</x-ui.button>
                        </div>
                    </form>
                @else
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-4 p-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wider text-slate-400">Queixa principal</dt>
                            <dd class="mt-0.5 text-sm text-slate-700">{{ $chiefComplaint ?: '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wider text-slate-400">História da doença / evolução</dt>
                            <dd class="mt-0.5 text-sm text-slate-700">{{ $history ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-slate-400">Medicações em uso</dt>
                            <dd class="mt-0.5 text-sm text-slate-700">{{ $medications ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-slate-400">Histórico familiar</dt>
                            <dd class="mt-0.5 text-sm text-slate-700">{{ $familyHistory ?: '—' }}</dd>
                        </div>
                    </dl>
                @endif
            </div>
        </div>
    @elseif ($tab === 'evolucao')
        <div class="mt-6 space-y-6">
            @if ($canManage)
                <div class="rounded-2xl border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-slate-800">Nova evolução</h2>
                        <p class="text-xs text-slate-500">Registre a evolução clínica do paciente.</p>
                    </div>
                    <form wire:submit="addClinicalNote" class="space-y-4 p-6">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Profissional (opcional)</label>
                            <select wire:model="noteDoctorId"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                                <option value="">—</option>
                                @foreach ($doctors as $doctor)
                                    <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Evolução</label>
                            <textarea wire:model="noteContent" rows="4"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40"
                                placeholder="Evolução clínica, conduta, observações"></textarea>
                            @error('noteContent')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex justify-end">
                            <x-ui.button type="submit">Registrar evolução</x-ui.button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white">
                <h2 class="border-b border-slate-100 px-6 py-4 text-sm font-semibold text-slate-800">Histórico de evolução</h2>
                <div class="divide-y divide-slate-100">
                    @forelse ($clinicalNotes as $note)
                        <div class="px-6 py-4" wire:key="note-{{ $note->id }}">
                            <div class="mb-1 flex items-center justify-between gap-3">
                                <span class="text-sm font-medium text-slate-700">{{ $note->doctor?->name ?? 'Equipe' }}</span>
                                <span class="text-xs text-slate-400">{{ $note->occurred_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="whitespace-pre-line text-sm text-slate-600">{{ $note->content }}</p>
                        </div>
                    @empty
                        <p class="px-6 py-12 text-center text-sm text-slate-400">Nenhuma evolução registrada.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @elseif ($tab === 'receitas')
        <div class="mt-6 space-y-6">
            @if ($canManage)
                <div class="rounded-2xl border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-slate-800">Nova receita</h2>
                        <p class="text-xs text-slate-500">Prescrição assinada por um profissional.</p>
                    </div>
                    <form wire:submit="savePrescription" class="space-y-4 p-6">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Profissional</label>
                            <select wire:model="prescriptionDoctorId"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                                <option value="">Selecione...</option>
                                @foreach ($doctors as $doctor)
                                    <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                                @endforeach
                            </select>
                            @error('prescriptionDoctorId')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-slate-700">Medicamentos</label>
                            @foreach ($prescriptionItems as $i => $item)
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-12" wire:key="presc-item-{{ $i }}">
                                    <input wire:model="prescriptionItems.{{ $i }}.drug" placeholder="Medicamento"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40 sm:col-span-4" />
                                    <input wire:model="prescriptionItems.{{ $i }}.dose" placeholder="Dose"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40 sm:col-span-2" />
                                    <input wire:model="prescriptionItems.{{ $i }}.frequency" placeholder="Frequência"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40 sm:col-span-2" />
                                    <input wire:model="prescriptionItems.{{ $i }}.duration" placeholder="Duração"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40 sm:col-span-3" />
                                    <button type="button" wire:click="removePrescriptionItem({{ $i }})" aria-label="Remover"
                                        class="flex items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-slate-400 transition-colors hover:border-rose-200 hover:text-rose-500 sm:col-span-1">&times;</button>
                                    @error('prescriptionItems.'.$i.'.drug')
                                        <p class="text-sm text-rose-600 sm:col-span-12">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                            <button type="button" wire:click="addPrescriptionItem" class="text-sm font-medium text-teal-600 hover:text-teal-700">+ Adicionar medicamento</button>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Observações (opcional)</label>
                            <textarea wire:model="prescriptionNotes" rows="2"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40"
                                placeholder="Orientações ao paciente"></textarea>
                        </div>

                        <div class="flex justify-end">
                            <x-ui.button type="submit">Emitir receita</x-ui.button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white">
                <h2 class="border-b border-slate-100 px-6 py-4 text-sm font-semibold text-slate-800">Receitas emitidas</h2>
                <div class="divide-y divide-slate-100">
                    @forelse ($prescriptions as $prescription)
                        <div class="px-6 py-4" wire:key="presc-{{ $prescription->id }}">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <span class="text-sm font-medium text-slate-700">{{ $prescription->doctor?->name ?? 'Profissional' }}</span>
                                <span class="text-xs text-slate-400">{{ $prescription->issued_at->format('d/m/Y') }}</span>
                            </div>
                            <ul class="space-y-1">
                                @foreach ($prescription->items as $item)
                                    <li class="text-sm text-slate-600">
                                        <span class="font-medium text-slate-700">{{ $item['drug'] }}</span>@if (! empty($item['dose'])) · {{ $item['dose'] }}@endif @if (! empty($item['frequency'])) · {{ $item['frequency'] }}@endif @if (! empty($item['duration'])) · {{ $item['duration'] }}@endif
                                    </li>
                                @endforeach
                            </ul>
                            @if ($prescription->notes)
                                <p class="mt-2 text-xs text-slate-500">{{ $prescription->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="px-6 py-12 text-center text-sm text-slate-400">Nenhuma receita emitida.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
