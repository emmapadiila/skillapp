<x-layouts.guest title="Iniciar sesión">
    <main class="flex min-h-dvh">
        <section class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-brand-950 p-12 lg:flex" aria-labelledby="product-heading">
            <div class="relative">
                <div class="mb-12 flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-brand-600 text-base font-bold text-white">SD</span><span class="text-xl font-bold text-white">SkillDiagnóstico IA</span></div>
                <h1 id="product-heading" class="mb-4 max-w-md text-4xl font-bold leading-tight text-white">Diagnóstico de habilidades blandas en tres ejes</h1>
                <p class="mb-12 max-w-md text-lg leading-relaxed text-brand-100">Operativo, misional y estratégico. Cada persona responde la prueba de su eje y recibe su resultado explicado en lenguaje sencillo.</p>
                <ul class="space-y-5">
                    <x-auth-feature icon="brain" title="Una prueba por eje" description="Afirmaciones y situaciones según el eje de tu cargo." />
                    <x-auth-feature icon="chart" title="Resultados explicados" description="Cada puntaje indica qué lo subió y qué lo bajó." />
                    <x-auth-feature icon="target" title="Tableros para Talento Humano" description="Brechas por categoría y eje, con filtros por área, cargo y periodo." />
                    <x-auth-feature icon="shield" title="Tus datos protegidos" description="Consentimiento informado según la Ley 1581 de 2012." />
                </ul>
            </div>
        </section>
        <section class="flex flex-1 items-center justify-center bg-slate-50 p-5 sm:p-8" aria-labelledby="login-heading">
            <div class="w-full max-w-sm">
                <div class="mb-6 flex items-center justify-center gap-3 lg:hidden"><span class="grid size-10 place-items-center rounded-xl bg-brand-600 font-bold text-white">SD</span><span class="font-bold">SkillDiagnóstico IA</span></div>
                <p class="-mt-3 mb-6 text-center text-sm text-slate-600 lg:hidden">Diagnóstico de habilidades blandas en tres ejes</p>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
                    <header class="mb-8"><h2 id="login-heading" class="mb-1 text-2xl font-bold text-slate-900">Bienvenido</h2><p class="text-sm text-slate-500">Ingresa a tu cuenta para continuar</p></header>
                    @if (session('status'))<x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>@endif
                    <form method="POST" action="{{ route('login.store') }}" class="space-y-5" data-login-form>
                        @csrf
                        <x-ui.input name="email" label="Correo electrónico" type="email" value="{{ old('email') }}" autocomplete="username" inputmode="email" maxlength="255" required autofocus placeholder="tu@empresa.com" />
                        <x-ui.input name="password" label="Contraseña" type="password" autocomplete="current-password" maxlength="1024" required>
                            <x-slot:trailing>
                                <button type="button" class="password-toggle absolute right-2 top-1/2 grid size-8 -translate-y-1/2 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Mostrar contraseña" aria-controls="password" aria-pressed="false"><svg class="password-eye size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="password-eye-off hidden size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 3 18 18M9 5a11 11 0 0 1 3 0c7 0 10 7 10 7a20 20 0 0 1-3 4M6 6a20 20 0 0 0-4 6s3 7 10 7a11 11 0 0 0 5-1"/></svg></button>
                            </x-slot:trailing>
                        </x-ui.input>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-600"><input name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-4 accent-brand-600">Recordarme</label>
                            @if (config('services.supabase.enabled'))
                            <button type="button" id="recover-password" class="text-xs font-medium text-brand-600 hover:text-brand-800">¿Olvidaste tu contraseña?</button>
                            @endif
                        </div>
                        <p id="recovery-feedback" hidden class="text-xs leading-5 text-slate-600" role="status"></p>
                        <x-ui.button type="submit" class="w-full">Iniciar sesión</x-ui.button>
                    </form>
                    <p class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-500">¿Necesitas ayuda? <a href="mailto:{{ config('softskills.support_email') }}" class="text-brand-600 hover:underline">Contáctanos</a></p>
                </div>
            </div>
        </section>
    </main>
</x-layouts.guest>
