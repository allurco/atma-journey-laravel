<div class="flex flex-col gap-6">
    <div class="text-center">
        <h1 class="text-xl font-semibold text-slate-800">Aceitar convite</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $clinicName }}</p>
    </div>

    @if ($valid)
        <form wire:submit="accept" class="flex flex-col gap-5">
            <p class="text-sm text-slate-600">Olá, <span class="font-medium text-slate-800">{{ $userName }}</span>. Defina uma senha para ativar seu acesso.</p>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Senha</label>
                <input type="password" wire:model="password" autocomplete="new-password"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40" />
                @error('password') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Confirmar senha</label>
                <input type="password" wire:model="passwordConfirmation" autocomplete="new-password"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40" />
                @error('passwordConfirmation') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white transition-all hover:bg-teal-700">
                Ativar acesso
            </button>
        </form>
    @else
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            Este convite é inválido, já foi utilizado ou expirou. Solicite um novo convite à sua clínica.
        </div>
        <a href="{{ route('login') }}" class="text-center text-sm font-medium text-teal-600 hover:text-teal-700">Ir para o login</a>
    @endif
</div>
