<x-layouts.guest title="Iniciar sesión">
    <main class="grid min-h-dvh lg:grid-cols-[minmax(0,1.12fr)_minmax(26rem,.88fr)]">
        <section class="auth-hero relative hidden min-h-dvh overflow-hidden bg-[#05122f] px-[clamp(2.5rem,5vw,5.5rem)] py-9 text-white lg:flex lg:flex-col" aria-labelledby="product-heading">
            <x-auth-hero-visual />

            <div class="relative z-10">
                <x-application-logo light />
            </div>

            <div class="relative z-10 flex flex-1 flex-col justify-center py-8 xl:py-12">
                <div class="max-w-[35rem]">
                    <div class="inline-flex items-center gap-2 rounded-full border border-violet-400/30 bg-violet-400/10 px-4 py-2 text-[.68rem] font-semibold uppercase tracking-[.18em] text-violet-100 backdrop-blur-sm">
                        <svg class="size-4 text-violet-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="m12 2 1.6 5.4L19 9l-5.4 1.6L12 16l-1.6-5.4L5 9l5.4-1.6L12 2ZM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15Z"/>
                        </svg>
                        IA al servicio del talento
                    </div>

                    <h1 id="product-heading" class="mt-6 text-[clamp(2.25rem,4vw,4.15rem)] font-bold leading-[1.03] tracking-[-.045em]">
                        Transforma el talento<br>
                        de tu equipo en<br>
                        <span class="bg-gradient-to-r from-cyan-300 via-blue-400 to-violet-400 bg-clip-text text-transparent">decisiones inteligentes</span>
                    </h1>
                    <p class="mt-5 max-w-[31rem] text-sm leading-6 text-blue-100/70 xl:text-base xl:leading-7">
                        Plataforma de diagnóstico de habilidades blandas impulsada por inteligencia artificial para organizaciones que buscan desarrollar equipos de alto rendimiento.
                    </p>
                </div>

                <div class="mt-8 grid max-w-[33rem] gap-5 xl:mt-10 xl:gap-6">
                    <x-auth-feature
                        icon="brain"
                        title="Evaluación adaptativa con IA"
                        description="Preguntas personalizadas según cargo y rol para obtener resultados precisos y accionables."
                    />
                    <x-auth-feature
                        icon="chart"
                        title="Diagnóstico organizacional"
                        description="Visualiza brechas en los ejes Operativo, Misional y Estratégico con analíticas avanzadas."
                        tone="cyan"
                    />
                    <x-auth-feature
                        icon="target"
                        title="Planes de mejoramiento"
                        description="Actividades personalizadas para cada colaborador y seguimiento del progreso."
                        tone="violet"
                    />
                    <x-auth-feature
                        icon="shield"
                        title="Privacidad y seguridad"
                        description="Datos protegidos y confidenciales en todo momento con los más altos estándares."
                        tone="violet"
                    />
                </div>
            </div>

            <div class="pointer-events-none absolute right-[7%] top-[45%] z-10 hidden xl:block">
                <div class="auth-metric-card w-52 -rotate-2">
                    <div class="flex items-center justify-between text-[.6rem] text-blue-100/70"><span>Índice organizacional</span><span>×</span></div>
                    <div class="mt-3 flex items-center gap-4">
                        <div class="grid size-16 place-items-center rounded-full border-[7px] border-blue-400 border-r-violet-400 text-center shadow-[0_0_25px_rgba(35,129,255,.35)]">
                            <span class="text-lg font-semibold leading-none">82<small class="block text-[.45rem] font-normal text-blue-100/60">/100</small></span>
                        </div>
                        <div><p class="text-xs font-medium">Nivel alto</p><p class="mt-1 text-[.58rem] text-emerald-300">▲ 12%</p></div>
                    </div>
                </div>
                <div class="auth-metric-card ml-10 mt-3 w-60 rotate-1">
                    <div class="flex items-center justify-between text-[.6rem] text-blue-100/70"><span>Brechas por eje</span><span>×</span></div>
                    <div class="mt-3 grid gap-2 text-[.58rem]">
                        <div class="grid grid-cols-[4rem_1fr_2rem] items-center gap-2"><span>Operativo</span><i class="h-1.5 rounded-full bg-gradient-to-r from-blue-500 to-cyan-300"></i><span>78%</span></div>
                        <div class="grid grid-cols-[4rem_1fr_2rem] items-center gap-2"><span>Misional</span><i class="h-1.5 w-[65%] rounded-full bg-gradient-to-r from-emerald-500 to-cyan-300"></i><span>65%</span></div>
                        <div class="grid grid-cols-[4rem_1fr_2rem] items-center gap-2"><span>Estratégico</span><i class="h-1.5 w-[84%] rounded-full bg-gradient-to-r from-violet-500 to-cyan-300"></i><span>84%</span></div>
                    </div>
                </div>
            </div>

            <div class="relative z-10 flex items-center justify-center gap-8 border-t border-blue-200/15 pt-5 text-[.68rem] text-blue-100/70 xl:gap-10">
                <span class="uppercase tracking-[.18em] text-blue-100/50">Ejes de evaluación</span>
                <span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-blue-400 shadow-[0_0_12px_#2d7cff]"></i>Operativo</span>
                <span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-emerald-400 shadow-[0_0_12px_#34d399]"></i>Misional</span>
                <span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-violet-400 shadow-[0_0_12px_#a78bfa]"></i>Estratégico</span>
            </div>
        </section>

        <section class="auth-panel relative flex min-h-dvh flex-col overflow-hidden bg-[#f7f9fd] px-5 py-6 sm:px-8 lg:px-[clamp(2rem,5vw,5rem)] lg:py-10" aria-labelledby="login-heading">
            <div class="relative z-10 mx-auto flex w-full max-w-[31rem] flex-1 flex-col">
                <x-application-logo compact class="mb-8 lg:hidden" />

                <div class="flex flex-1 items-center py-4">
                    <div class="auth-login-card w-full rounded-[1.35rem] border border-white/80 bg-white/95 p-6 shadow-[0_24px_70px_rgba(34,59,110,.14)] backdrop-blur sm:p-9 lg:p-10">
                        <header>
                            <div class="flex items-center gap-2">
                                <h2 id="login-heading" class="text-3xl font-bold tracking-[-.035em] text-[#07132f] sm:text-[2.25rem]">Bienvenido</h2>
                                <svg class="size-7 text-sky-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="m9 1 1.7 5.3L16 8l-5.3 1.7L9 15l-1.7-5.3L2 8l5.3-1.7L9 1Zm9 9 1.2 3.8L23 15l-3.8 1.2L18 20l-1.2-3.8L13 15l3.8-1.2L18 10Z"/>
                                </svg>
                            </div>
                            <p class="mt-2 text-sm text-slate-500 sm:text-base">Ingresa a tu cuenta para continuar</p>
                        </header>

                        @if (session('status'))
                            <div class="mt-6 flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                                <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                                <p>{{ session('status') }}</p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}" class="mt-7 grid gap-5" novalidate>
                            @csrf

                            <div class="grid gap-2">
                                <label for="email" class="text-[.72rem] font-bold uppercase tracking-[.03em] text-slate-700">Correo electrónico</label>
                                <div class="relative">
                                    <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value="{{ old('email') }}"
                                        autocomplete="username"
                                        inputmode="email"
                                        maxlength="255"
                                        required
                                        autofocus
                                        aria-describedby="email-error"
                                        @class([
                                            'auth-input w-full rounded-xl border bg-white py-3.5 pr-4 pl-12 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
                                            'border-red-400' => $errors->has('email'),
                                            'border-slate-200' => ! $errors->has('email'),
                                        ])
                                        placeholder="nombre@empresa.com"
                                    >
                                </div>
                                @error('email')
                                    <p id="email-error" class="text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid gap-2">
                                <label for="password" class="text-[.72rem] font-bold uppercase tracking-[.03em] text-slate-700">Contraseña</label>
                                <div class="relative">
                                    <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2"/></svg>
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        autocomplete="current-password"
                                        maxlength="1024"
                                        required
                                        aria-describedby="password-error"
                                        @class([
                                            'auth-input w-full rounded-xl border bg-white py-3.5 pr-12 pl-12 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10',
                                            'border-red-400' => $errors->has('password'),
                                            'border-slate-200' => ! $errors->has('password'),
                                        ])
                                        placeholder="Ingresa tu contraseña"
                                    >
                                    <button type="button" class="password-toggle absolute right-3 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Mostrar contraseña" aria-controls="password" aria-pressed="false">
                                        <svg class="password-eye size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                        <svg class="password-eye-off hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 3 18 18M10.5 6.2A10.6 10.6 0 0 1 12 6c6 0 9.5 6 9.5 6a14 14 0 0 1-2.1 2.8M6.2 6.2A15.8 15.8 0 0 0 2.5 12s3.5 6 9.5 6a9.9 9.9 0 0 0 3.2-.5M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                                    </button>
                                </div>
                                @error('password')
                                    <p id="password-error" class="text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                                <label class="flex cursor-pointer items-center gap-2 text-slate-600">
                                    <input name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-4 rounded border-slate-300 accent-blue-600">
                                    <span>Recordarme</span>
                                </label>
                                <span class="font-medium text-blue-600" title="La recuperación de contraseña se habilitará en la siguiente fase">¿Olvidaste tu contraseña?</span>
                            </div>

                            <button type="submit" class="group mt-1 flex min-h-14 items-center justify-center gap-3 rounded-xl bg-gradient-to-r from-blue-600 via-blue-600 to-[#165dff] px-6 text-sm font-semibold text-white shadow-[0_14px_28px_rgba(37,99,235,.25)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_18px_34px_rgba(37,99,235,.32)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 active:translate-y-0 disabled:pointer-events-none disabled:opacity-70">
                                <span>Iniciar sesión</span>
                                <svg class="size-5 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"/></svg>
                            </button>
                        </form>

                        <p class="mt-9 text-center text-xs text-slate-500">
                            ¿Necesitas ayuda?
                            <a href="mailto:{{ config('softskills.support_email') }}" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline">Contáctanos</a>
                        </p>
                    </div>
                </div>

                <footer class="relative z-10 grid justify-items-center gap-3 py-5 text-center text-[.7rem] text-slate-500">
                    <p class="flex items-center gap-2">
                        <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.8 8.1 7 10 4.2-1.9 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                        Tu información está protegida
                    </p>
                    <p><span>Política de privacidad</span><span class="px-2" aria-hidden="true">·</span><span>Términos de uso</span></p>
                </footer>
            </div>
        </section>
    </main>
</x-layouts.guest>
