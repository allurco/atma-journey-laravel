<div class="min-h-screen flex bg-[#FAFAF8]">
    {{-- Left panel — branding --}}
    <div class="hidden lg:flex lg:w-1/2 bg-slate-900 relative overflow-hidden items-center justify-center">
        <div class="absolute inset-0 bg-gradient-to-br from-teal-600/20 via-transparent to-emerald-600/10"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl translate-y-1/3 -translate-x-1/4"></div>

        <div class="relative z-10 px-16 max-w-lg">
            <div class="flex items-center gap-3 mb-8">
                <x-atma-logo :size="44" class="drop-shadow-[0_8px_24px_rgba(13,148,136,0.4)]" />
                <div>
                    <h1 class="text-white text-2xl font-semibold tracking-tight">ATMA Journey</h1>
                    <p class="text-teal-300/70 text-sm">Retenção de Pacientes</p>
                </div>
            </div>

            <p class="text-slate-300 text-lg leading-relaxed mb-6">
                Crie a conta da sua clínica e acompanhe a jornada de cada paciente — do primeiro contato ao pós-tratamento.
            </p>

            <div class="flex items-center gap-6 text-slate-400 text-sm mb-10">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-teal-400"></div>
                    <span>Pipeline Visual</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-teal-400"></div>
                    <span>Agenda &amp; Prontuário</span>
                </div>
            </div>

            <div class="rounded-2xl border border-amber-400/30 bg-gradient-to-br from-amber-500/10 to-teal-500/5 backdrop-blur-sm p-5">
                <span class="text-amber-400 text-[11px] font-bold tracking-[0.15em] uppercase">14 dias grátis</span>
                <p class="text-slate-300 text-sm mt-2 leading-relaxed">
                    Sua clínica ganha um endereço próprio e um banco de dados isolado. Sem cartão de crédito.
                </p>
            </div>
        </div>
    </div>

    {{-- Right panel — signup form --}}
    <div class="flex-1 flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <div class="lg:hidden flex items-center gap-3 mb-10 justify-center">
                <x-atma-logo :size="36" />
                <h1 class="text-slate-800 text-xl font-semibold tracking-tight">ATMA Journey</h1>
            </div>

            <div class="mb-6">
                <h2 class="text-slate-800 text-2xl font-semibold mb-2">Crie a conta da sua clínica</h2>
                <p class="text-slate-500">Leva menos de um minuto para começar.</p>
            </div>

            <form wire:submit="register" class="space-y-5">
                <div>
                    <label for="clinic_name" class="block text-sm font-medium text-slate-700 mb-2">Nome da clínica</label>
                    <input id="clinic_name" type="text" wire:model="clinic_name" required autofocus
                        placeholder="Clínica Bem-Estar"
                        class="w-full px-4 py-3 bg-white border @error('clinic_name') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                    @error('clinic_name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="slug" class="block text-sm font-medium text-slate-700 mb-2">Endereço da clínica</label>
                    <div class="flex items-stretch rounded-xl border @error('slug') border-rose-300 @else border-slate-200 @enderror bg-white overflow-hidden transition-all focus-within:ring-2 focus-within:ring-teal-500/40 focus-within:border-teal-500">
                        <input id="slug" type="text" wire:model="slug" required
                            placeholder="sua-clinica"
                            class="flex-1 min-w-0 px-4 py-3 bg-transparent focus:outline-none text-slate-800 placeholder-slate-400" />
                        <span class="flex items-center px-3 bg-slate-50 text-slate-400 text-sm border-l border-slate-200">.atma.test</span>
                    </div>
                    @error('slug') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="pt-1 border-t border-slate-100"></div>

                <div>
                    <label for="admin_name" class="block text-sm font-medium text-slate-700 mb-2">Seu nome</label>
                    <input id="admin_name" type="text" wire:model="admin_name" required
                        placeholder="Dra. Maria Silva"
                        class="w-full px-4 py-3 bg-white border @error('admin_name') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                    @error('admin_name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="admin_email" class="block text-sm font-medium text-slate-700 mb-2">E-mail</label>
                    <input id="admin_email" type="email" wire:model="admin_email" required
                        placeholder="seu@email.com"
                        class="w-full px-4 py-3 bg-white border @error('admin_email') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                    @error('admin_email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="admin_password" class="block text-sm font-medium text-slate-700 mb-2">Senha</label>
                    <input id="admin_password" type="password" wire:model="admin_password" required
                        placeholder="Mínimo de 8 caracteres"
                        class="w-full px-4 py-3 bg-white border @error('admin_password') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                    @error('admin_password') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="admin_password_confirmation" class="block text-sm font-medium text-slate-700 mb-2">Confirme a senha</label>
                    <input id="admin_password_confirmation" type="password" wire:model="admin_password_confirmation" required
                        placeholder="Repita a senha"
                        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="register"
                    class="w-full flex items-center justify-center gap-2 bg-teal-600 text-white py-3.5 rounded-xl hover:bg-teal-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md">
                    <svg wire:loading wire:target="register" class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                    </svg>
                    <span class="font-medium" wire:loading.remove wire:target="register">Criar clínica</span>
                    <span class="font-medium" wire:loading wire:target="register">Criando...</span>
                </button>

                <p class="text-center text-sm text-slate-500">
                    Já tem uma conta? Acesse pelo endereço da sua clínica.
                </p>
            </form>
        </div>
    </div>
</div>
