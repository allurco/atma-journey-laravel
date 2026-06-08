<x-pages::settings.layout :heading="__('Integrações')" :subheading="__('Conecte suas fontes de leads ao Atma')">
    @php($inputClasses = 'w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 font-mono text-sm')
    @php($btnClasses = 'flex-shrink-0 rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100')

    <div class="space-y-6" x-data="{ revealed: false }">
        @if ($regenerated)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Segredo regenerado. Atualize-o nas suas integrações — o segredo anterior parou de funcionar.
            </div>
        @endif

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">URL do webhook de leads</label>
            <div class="flex gap-2">
                <input type="text" readonly value="{{ $webhookUrl }}" x-ref="url" class="{{ $inputClasses }}" />
                <button type="button" @click="navigator.clipboard.writeText($refs.url.value)" class="{{ $btnClasses }}">Copiar</button>
            </div>
            <p class="mt-1.5 text-xs text-slate-400">POST com os campos <code>name</code>, <code>phone</code> (e opcionalmente <code>email</code>, <code>source</code>, <code>external_id</code>).</p>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Segredo</label>
            <div class="flex gap-2">
                <input :type="revealed ? 'text' : 'password'" readonly value="{{ $secret }}" x-ref="secret" class="{{ $inputClasses }}" />
                <button type="button" @click="revealed = ! revealed" class="{{ $btnClasses }}" x-text="revealed ? 'Ocultar' : 'Revelar'"></button>
                <button type="button" @click="navigator.clipboard.writeText($refs.secret.value)" class="{{ $btnClasses }}">Copiar</button>
            </div>
            <p class="mt-1.5 text-xs text-slate-400">Envie no cabeçalho <code>X-Webhook-Secret</code>.</p>
        </div>

        <div class="border-t border-slate-100 pt-5">
            <button type="button" wire:click="regenerate"
                wire:confirm="Regenerar o segredo invalida o atual imediatamente. Suas integrações precisarão do novo segredo. Continuar?"
                class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-medium text-rose-600 transition-colors hover:bg-rose-50">
                Regenerar segredo
            </button>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
            <p class="text-sm font-medium text-slate-700">Como conectar</p>
            <p class="mt-1 text-xs text-slate-500">
                Cole a URL e o segredo no seu formulário do site, Meta Lead Ads, Zapier ou CRM.
                Cada lead recebido vira um paciente no topo do funil (Primeiro Contato), sem duplicar quem já existe.
            </p>
        </div>
    </div>
</x-pages::settings.layout>
