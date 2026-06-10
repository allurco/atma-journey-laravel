<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
    <head>
        @include('partials.head', ['title' => 'ATMA Journey — nenhum paciente é esquecido'])
    </head>

    <body class="min-h-screen bg-[#FAFAF8] font-sans text-slate-700 antialiased">
        {{-- Top navigation --}}
        <header class="sticky top-0 z-30 border-b border-slate-200/60 bg-[#FAFAF8]/80 backdrop-blur">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="ATMA Journey">
                    <x-atma-logo :size="36" />
                    <span class="text-lg font-semibold tracking-tight text-slate-900">ATMA&nbsp;Journey</span>
                </a>

                <div class="flex items-center gap-3">
                    <a
                        href="#recursos"
                        class="hidden rounded-xl px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-teal-700 sm:inline-flex"
                    >
                        Recursos
                    </a>
                    <a
                        href="{{ route('signup') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[#FAFAF8]"
                    >
                        Comece agora
                    </a>
                </div>
            </nav>
        </header>

        <main>
            {{-- Hero --}}
            <section class="relative overflow-hidden">
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute -top-32 left-1/2 -z-10 h-[36rem] w-[36rem] -translate-x-1/2 rounded-full bg-teal-100/50 blur-3xl"
                ></div>

                <div class="mx-auto max-w-6xl px-6 pt-20 pb-16 text-center sm:pt-28">
                    <span class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-4 py-1.5 text-sm font-medium text-teal-700">
                        A jornada do paciente, agora digital
                    </span>

                    <h1 class="mx-auto mt-8 max-w-3xl text-4xl font-bold leading-tight tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                        Para que <span class="text-teal-600">nenhum paciente é esquecido</span> em nenhuma etapa da jornada.
                    </h1>

                    <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">
                        A plataforma única para clínicas de saúde: retenção e CRM, agenda, prontuário e
                        financeiro reunidos em uma só linha do tempo. Da primeira conversa ao retorno,
                        cada paciente é acompanhado com cuidado.
                    </p>

                    <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <a
                            href="{{ route('signup') }}"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-teal-600 px-7 py-3.5 text-base font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[#FAFAF8] sm:w-auto"
                        >
                            Criar conta da clínica
                        </a>
                        <a
                            href="#recursos"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-7 py-3.5 text-base font-semibold text-slate-700 transition hover:border-teal-300 hover:text-teal-700 sm:w-auto"
                        >
                            Conhecer os recursos
                        </a>
                    </div>

                    <p class="mt-5 text-sm text-slate-500">
                        Sem cartão de crédito. Configure sua clínica em minutos.
                    </p>
                </div>
            </section>

            {{-- Value propositions --}}
            <section id="recursos" class="mx-auto max-w-6xl px-6 py-16 sm:py-20">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                        Toda a jornada do paciente em um só lugar
                    </h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Quatro pilares integrados para que a sua equipe nunca perca um paciente de vista.
                    </p>
                </div>

                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                        $features = [
                            [
                                'title' => 'Retenção e CRM',
                                'description' => 'Funil completo do primeiro contato ao retorno. Nenhum lead esfria sem acompanhamento.',
                                'icon' => 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941',
                            ],
                            [
                                'title' => 'Agenda',
                                'description' => 'Agendamentos, confirmações e lembretes em um calendário pensado para a rotina da clínica.',
                                'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
                            ],
                            [
                                'title' => 'Prontuário',
                                'description' => 'Registros clínicos seguros e organizados, sempre acessíveis no contexto de cada paciente.',
                                'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
                            ],
                            [
                                'title' => 'Financeiro',
                                'description' => 'Orçamentos, recebimentos e visão financeira conectados a cada etapa do atendimento.',
                                'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                            ],
                        ];
                    @endphp

                    @foreach ($features as $feature)
                        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-teal-200 hover:shadow-md">
                            <div class="flex size-12 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}" />
                                </svg>
                            </div>
                            <h3 class="mt-5 text-lg font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $feature['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- Closing CTA --}}
            <section class="mx-auto max-w-6xl px-6 pb-20">
                <div class="relative overflow-hidden rounded-3xl bg-teal-600 px-8 py-14 text-center shadow-lg sm:px-16">
                    <h2 class="mx-auto max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Comece a cuidar de cada paciente até o fim da jornada
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-lg text-teal-50">
                        Crie a conta da sua clínica e leve a jornada do paciente para o dia a dia da equipe.
                    </p>
                    <div class="mt-8">
                        <a
                            href="{{ route('signup') }}"
                            class="inline-flex items-center justify-center rounded-xl bg-white px-7 py-3.5 text-base font-semibold text-teal-700 shadow-sm transition hover:bg-teal-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-teal-600"
                        >
                            Criar conta da clínica
                        </a>
                    </div>
                </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-slate-200/60">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 py-8 sm:flex-row">
                <div class="flex items-center gap-3">
                    <x-atma-logo :size="28" />
                    <span class="text-sm font-semibold text-slate-900">ATMA Journey</span>
                </div>
                <p class="text-sm text-slate-500">
                    &copy; {{ now()->year }} ATMA Journey. Cuidando de cada jornada.
                </p>
            </div>
        </footer>
    </body>
</html>
