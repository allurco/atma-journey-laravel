<div class="flex flex-col gap-6">
    <div class="text-center">
        <h1 class="text-xl font-semibold text-slate-800">Assinatura de documento</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $clinicName }}</p>
    </div>

    @if ($signed)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-6 text-center">
            <svg class="mx-auto h-10 w-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            <p class="mt-3 text-sm font-medium text-emerald-800">Documento assinado com sucesso.</p>
            <p class="mt-1 text-sm text-emerald-700">Obrigado! Você já pode fechar esta página.</p>
        </div>
    @elseif ($valid)
        <p class="text-sm text-slate-600">Revise o documento <span class="font-medium text-slate-800">{{ $documentTitle }}</span> abaixo e clique em assinar.</p>

        <iframe src="{{ $fileUrl }}" class="h-[60vh] w-full rounded-xl border border-slate-200" title="Documento para assinatura"></iframe>

        <form wire:submit="sign">
            <button type="submit" class="w-full rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white transition-all hover:bg-teal-700">
                Li e concordo — Assinar
            </button>
            <p class="mt-2 text-center text-xs text-slate-400">Ao assinar, registramos data, hora e seu endereço de acesso como comprovação.</p>
        </form>
    @else
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            Este link de assinatura é inválido ou já foi utilizado. Solicite um novo à sua clínica.
        </div>
    @endif
</div>
