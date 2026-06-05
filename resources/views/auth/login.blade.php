<x-layouts.guest :title="__('Entrar')">
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
                    Plataforma completa para gerenciar o relacionamento com seus pacientes e aumentar a retenção da sua clínica.
                </p>

                <div class="flex items-center gap-6 text-slate-400 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-teal-400"></div>
                        <span>Dashboard Inteligente</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-teal-400"></div>
                        <span>Pipeline Visual</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right panel — login form --}}
        <div class="flex-1 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <div class="lg:hidden flex items-center gap-3 mb-10 justify-center">
                    <x-atma-logo :size="36" />
                    <h1 class="text-slate-800 text-xl font-semibold tracking-tight">ATMA Journey</h1>
                </div>

                <div class="mb-6">
                    <h2 class="text-slate-800 text-2xl font-semibold mb-2">Bem-vindo de volta</h2>
                    <p class="text-slate-500">Entre com suas credenciais para acessar o sistema.</p>
                </div>

                <x-auth-session-status class="mb-4 text-center text-sm font-medium text-teal-700" :status="session('status')" />

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-2">E-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                            placeholder="seu@email.com"
                            class="w-full px-4 py-3 bg-white border @error('email') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                        @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-sm font-medium text-slate-700">Senha</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-sm text-teal-700 hover:text-teal-800">Esqueceu a senha?</a>
                            @endif
                        </div>
                        <input id="password" name="password" type="password" required
                            placeholder="Sua senha"
                            class="w-full px-4 py-3 bg-white border @error('password') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                        @error('password') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600 select-none">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500/40">
                        Lembrar de mim
                    </label>

                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 bg-teal-600 text-white py-3.5 rounded-xl hover:bg-teal-700 transition-all duration-200 shadow-sm hover:shadow-md">
                        <span class="font-medium">Entrar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.guest>
