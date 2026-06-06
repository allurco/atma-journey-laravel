<x-pages::settings.layout :heading="__('Clínica')" :subheading="__('Dados e identidade da sua clínica')">
    @php($inputClasses = 'w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500')

    <form wire:submit="save" class="max-w-xl space-y-4">
        @if ($saved)
            <div class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
                Dados da clínica atualizados.
            </div>
        @endif

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Logo</label>
            <div class="flex items-center gap-4">
                <div class="flex h-20 w-20 flex-shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="Prévia do logo" class="h-full w-full object-contain" />
                    @elseif ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo da clínica" class="h-full w-full object-contain" />
                    @else
                        <x-atma-logo class="h-8 w-8 opacity-40" />
                    @endif
                </div>
                @if ($canManage)
                    <div class="space-y-1.5">
                        <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="block text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100" />
                        <p class="text-xs text-slate-400">PNG, JPG, SVG ou WEBP — até 2&nbsp;MB.</p>
                        <div wire:loading wire:target="logo" class="text-xs text-slate-400">Enviando…</div>
                        @error('logo') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        @if ($logoUrl)
                            <button type="button" wire:click="removeLogo" class="text-xs text-rose-600 hover:text-rose-700">Remover logo</button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div>
            <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Nome da clínica</label>
            <input id="name" type="text" wire:model="name" @disabled(! $canManage) class="{{ $inputClasses }}" />
            @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="cnpj" class="mb-2 block text-sm font-medium text-slate-700">CNPJ</label>
            <input id="cnpj" type="text" wire:model="cnpj" placeholder="00.000.000/0001-00" @disabled(! $canManage) class="{{ $inputClasses }}" />
            @error('cnpj') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">E-mail</label>
                <input id="email" type="email" wire:model="email" placeholder="contato@clinica.com" @disabled(! $canManage) class="{{ $inputClasses }}" />
                @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">Telefone</label>
                <input id="phone" type="text" wire:model="phone" placeholder="(11) 99999-0000" @disabled(! $canManage) class="{{ $inputClasses }}" />
                @error('phone') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="address" class="mb-2 block text-sm font-medium text-slate-700">Endereço</label>
            <input id="address" type="text" wire:model="address" placeholder="Rua, número, bairro, cidade" @disabled(! $canManage) class="{{ $inputClasses }}" />
            @error('address') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        @if ($canManage)
            <button type="submit" class="px-4 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-all font-medium">
                Salvar alterações
            </button>
        @endif
    </form>
</x-pages::settings.layout>
