<x-layouts.guest title="Iniciar sesión">
    <main class="flex min-h-dvh">
        <section class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-[#0B1629] p-12 lg:flex" aria-labelledby="product-heading">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(59,130,246,.08),transparent_50%),radial-gradient(circle_at_80%_20%,rgba(139,92,246,.08),transparent_50%)]"></div>
            <div class="relative">
                <div class="mb-12 flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-blue-600 text-base font-bold text-white">SD</span><span class="text-xl font-bold text-white">SkillDiagnóstico IA</span></div>
                <h1 id="product-heading" class="mb-4 text-4xl font-bold leading-tight text-white">Transforma el talento de tu equipo en decisiones inteligentes.</h1>
                <p class="mb-12 text-lg leading-relaxed text-slate-400">Plataforma de diagnóstico de habilidades blandas para organizaciones, impulsada por inteligencia artificial.</p>
                <div class="space-y-4">
                    <x-auth-feature icon="brain" title="Evaluación de habilidades" description="Preguntas seleccionadas según cargo y eje organizacional" />
                    <x-auth-feature icon="chart" title="Diagnóstico organizacional" description="Visualiza brechas en los ejes Operativo, Misional y Estratégico" />
                    <x-auth-feature icon="target" title="Planes de mejoramiento" description="Actividades personalizadas para cada colaborador" />
                    <x-auth-feature icon="shield" title="Privacidad y seguridad" description="Datos protegidos y confidenciales en todo momento" />
                </div>
            </div>
            <div class="relative mt-10 flex flex-wrap gap-4 text-xs text-slate-400">
                <span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-blue-500"></i>Operativo</span>
                <span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-emerald-500"></i>Misional</span>
                <span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-violet-500"></i>Estratégico</span>
            </div>
        </section>
        <section class="flex flex-1 items-center justify-center bg-slate-50 p-5 sm:p-8" aria-labelledby="login-heading">
            <div class="w-full max-w-sm">
                <div class="mb-6 flex items-center justify-center gap-3 lg:hidden"><span class="grid size-10 place-items-center rounded-xl bg-blue-600 font-bold text-white">SD</span><span class="font-bold">SkillDiagnóstico IA</span></div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
                    <header class="mb-8"><h2 id="login-heading" class="mb-1 text-2xl font-bold text-slate-900">Bienvenido</h2><p class="text-sm text-slate-500">Ingresa a tu cuenta para continuar</p></header>
                    @if (session('status'))<p class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700" role="status">{{ session('status') }}</p>@endif
                    <form method="POST" action="{{ route('login.store') }}" class="space-y-5" data-login-form>
                        @csrf
                        <div>
                            <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Correo electrónico</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" inputmode="email" maxlength="255" required autofocus aria-describedby="email-error" @if($errors->has('email')) aria-invalid="true" @endif class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30" placeholder="tu@empresa.com">
                            @error('email')<p id="email-error" class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Contraseña</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" autocomplete="current-password" maxlength="1024" required aria-describedby="password-error" @if($errors->has('password')) aria-invalid="true" @endif class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 pr-12 text-sm text-slate-800 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30" placeholder="••••••••">
                                <button type="button" class="password-toggle absolute right-2 top-1/2 grid size-8 -translate-y-1/2 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Mostrar contraseña" aria-controls="password" aria-pressed="false"><svg class="password-eye size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="password-eye-off hidden size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 3 18 18M9 5a11 11 0 0 1 3 0c7 0 10 7 10 7a20 20 0 0 1-3 4M6 6a20 20 0 0 0-4 6s3 7 10 7a11 11 0 0 0 5-1"/></svg></button>
                            </div>
                            @error('password')<p id="password-error" class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-600"><input name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-4 accent-blue-600">Recordarme</label>
                            <button type="button" id="recover-password" class="text-xs font-medium text-blue-600 hover:text-blue-800">¿Olvidaste tu contraseña?</button>
                        </div>
                        <p id="recovery-feedback" hidden class="text-xs leading-5 text-slate-600" role="status"></p>
                        <button type="submit" class="w-full rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-70">Iniciar sesión</button>
                    </form>
                    <p class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-400">¿Necesitas ayuda? <a href="mailto:{{ config('softskills.support_email') }}" class="text-blue-600 hover:underline">Contáctanos</a></p>
                </div>
            </div>
        </section>
    </main>
</x-layouts.guest>
