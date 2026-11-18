@php
    $collaborator = auth()->user()->role === \App\Enums\UserRole::Collaborator;
    // Grupos del menú: [título del grupo (null = sin título), [[pantalla, texto, ruta, ícono, nota], …]].
    $navigation = $collaborator ? [
        [null, [
            ['inicio', 'Inicio', 'personal.workspace', 'home'],
            ['evaluacion', 'Mi evaluación', 'personal.evaluacion', 'clipboard'],
            ['resultados', 'Mis resultados', 'personal.resultados', 'chart'],
            ['plan', 'Mi plan', 'personal.plan', 'target'],
            ['progreso', 'Mi progreso', 'personal.progreso', 'trend'],
            ['perfil', 'Mi perfil', 'web.profile', 'user'],
            ['notificaciones', 'Notificaciones', 'web.notifications', 'bell'],
        ]],
    ] : [
        ['Tableros', [
            ['tablero-operativo', 'Tablero operativo', 'web.tablero-operativo', 'chart'],
            ['tablero-misional', 'Tablero misional', 'web.tablero-misional', 'chart'],
            ['tablero-estrategico', 'Tablero estratégico', 'web.tablero-estrategico', 'chart'],
        ]],
        ['Personas y pruebas', array_values(array_filter([
            ['colaboradores', 'Colaboradores', 'collaborators.index', 'users'],
            ['evaluaciones', 'Evaluaciones', 'web.evaluaciones', 'clipboard'],
            ['preguntas', 'Banco de preguntas', 'web.preguntas', 'help'],
            ['habilidades', 'Habilidades', 'web.habilidades', 'brain'],
            ['cargos', 'Cargos', 'web.cargos', 'briefcase'],
            ['areas', 'Áreas', 'web.areas', 'building'],
            auth()->user()->isSuperadmin() ? ['empresas', 'Empresas', 'web.empresas', 'building'] : null,
        ]))],
        ['Seguimiento', [
            ['diagnostico', 'Diagnóstico organizacional', 'diagnostics.organizational', 'chart'],
            ['brechas', 'Brechas', 'web.brechas', 'warning'],
            ['planes', 'Planes de mejoramiento', 'web.planes', 'target'],
            ['seguimiento', 'Seguimiento', 'web.seguimiento', 'calendar'],
            ['reportes', 'Reportes', 'web.reportes', 'file'],
            ['recomendaciones', 'Recomendaciones IA', 'web.recomendaciones', 'sparkles', 'No disponible aún'],
        ]],
    ];
    $initialScreen = $screen;
@endphp
<!DOCTYPE html>
<html lang="es" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="api-access-token" content="{{ session('supabase.access_token') }}">
        <meta name="session-refresh-url" content="{{ route('session.refresh') }}">
        <meta name="login-url" content="{{ route('login') }}">
        <meta name="color-scheme" content="light">
        <meta name="theme-color" content="#0b2029">
        <title>SkillDiagnóstico IA</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="workspace min-h-full bg-slate-50 font-sans text-slate-900 antialiased" data-workspace data-screen="{{ $initialScreen }}" data-role="{{ auth()->user()->role->value }}">
        <a href="#workspace-page" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[80] focus:rounded-lg focus:bg-white focus:p-3">Saltar al contenido</a>
        <aside id="admin-sidebar" class="admin-sidebar fixed inset-y-0 left-0 z-40 flex w-60 -translate-x-full flex-col bg-brand-950 text-slate-400 transition-transform lg:translate-x-0" aria-label="Navegación principal">
            <a href="{{ route($collaborator ? 'personal.workspace' : 'workspace') }}" class="flex items-center gap-2.5 border-b border-white/10 p-5">
                <span class="grid size-9 place-items-center rounded-xl bg-brand-600 text-sm font-bold text-white" aria-hidden="true">SD</span>
                <span class="text-[0.9375rem] font-semibold text-white">SkillDiagnóstico IA</span>
            </a>
            <button id="sidebar-close" class="absolute right-2 top-3 grid size-10 place-items-center rounded-lg text-white hover:bg-white/10 lg:hidden" aria-label="Cerrar menú"><span data-icon="close" class="size-5" aria-hidden="true"></span></button>
            <nav class="min-h-0 flex-1 overflow-y-auto px-3 pb-3" aria-label="Secciones">
                @foreach ($navigation as $index => [$group, $items])
                    @if ($group)<p id="nav-group-{{ $index }}" class="px-3 pb-1 pt-5 text-xs font-medium text-slate-400">{{ $group }}</p>@endif
                    <ul class="space-y-0.5 {{ $group ? '' : 'pt-3' }}" @if ($group) aria-labelledby="nav-group-{{ $index }}" @endif>
                        @foreach ($items as $item)
                            @php([$key, $label, $route, $icon] = $item)
                            <li><a href="{{ route($route) }}" data-screen-link="{{ $key }}" class="flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-white/5 hover:text-white">
                                <span data-icon="{{ $icon }}" class="size-4 shrink-0" aria-hidden="true"></span><span class="min-w-0 flex-1 truncate">{{ $label }}</span>
                                @isset($item[4])<span class="shrink-0 rounded-full bg-white/10 px-2 py-0.5 text-[0.6875rem] text-slate-300">{{ $item[4] }}</span>@endisset
                            </a></li>
                        @endforeach
                    </ul>
                @endforeach
            </nav>
            <div class="border-t border-white/10 p-3">
                <a href="{{ route('web.profile') }}" data-screen-link="perfil" class="flex items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-white/5">
                    <span data-user-initials class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    <span class="min-w-0 flex-1"><span data-user-name class="block truncate text-xs font-medium text-white">{{ auth()->user()->name }}</span><span class="block text-xs text-slate-400">{{ $collaborator ? 'Colaborador' : 'Talento humano' }}</span></span>
                    <span data-icon="user" class="size-4 shrink-0" aria-hidden="true"></span>
                </a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-xs hover:bg-white/5 hover:text-white">Cerrar sesión</button></form>
            </div>
        </aside>
        <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-950/45 lg:hidden"></div>
        <main class="min-h-dvh lg:pl-60">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-100 bg-white px-4 shadow-sm sm:px-6">
                <button id="sidebar-open" class="grid size-9 shrink-0 place-items-center rounded-lg border border-slate-200 lg:hidden" aria-expanded="false" aria-controls="admin-sidebar" aria-label="Abrir menú"><span data-icon="menu" class="size-4"></span></button>
                <div class="min-w-0 flex-1"><nav class="mb-0.5 truncate text-xs text-slate-500" aria-label="Ruta"><span>{{ $collaborator ? 'Inicio' : 'Tableros' }}</span><span class="px-1">›</span><span id="workspace-breadcrumb" class="font-medium text-slate-600"></span></nav><h1 id="workspace-title" class="line-clamp-2 text-base font-bold leading-tight sm:truncate sm:text-lg">Cargando…</h1></div>
                <label class="relative hidden md:block"><span class="sr-only">Buscar en esta pantalla</span><span data-icon="search" class="absolute left-3 top-2.5 size-4 text-slate-400"></span><input id="workspace-search" type="search" placeholder="Buscar…" class="w-48 rounded-control border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm"></label>
                <a href="{{ route('web.notifications') }}" data-screen-link="notificaciones" class="relative grid size-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500" aria-label="Notificaciones"><span data-icon="bell" class="size-4"></span><span id="unread-count" hidden class="absolute -right-1 -top-1 rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"></span></a>
                <a href="{{ route('web.profile') }}" data-screen-link="perfil" data-user-initials class="grid size-9 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white" aria-label="Mi perfil">{{ mb_substr(auth()->user()->name, 0, 1) }}</a>
            </header>
            <section id="workspace-page" tabindex="-1" class="mx-auto max-w-[1600px] p-4 outline-none sm:p-6"></section>
        </main>
        <dialog id="workspace-dialog" class="workspace-dialog w-[min(94vw,680px)] rounded-2xl bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-black/50" aria-labelledby="dialog-title">
            <div class="flex items-center justify-between border-b border-slate-100 p-5 sm:p-6"><h2 id="dialog-title" class="font-bold"></h2><button data-dialog-close class="rounded-lg p-2 hover:bg-slate-100" aria-label="Cerrar">×</button></div>
            <div id="dialog-content" class="p-5 sm:p-6"></div>
        </dialog>
        <div id="workspace-toast" hidden role="status" class="fixed left-1/2 top-20 z-[70] sm:bottom-5 sm:top-auto w-max max-w-[90vw] -translate-x-1/2 rounded-xl bg-slate-900 px-5 py-3 text-sm text-white shadow-xl"></div>
        <noscript><p class="fixed bottom-0 right-0 bg-amber-50 p-4 text-sm">Activa JavaScript para consultar los datos y realizar operaciones.</p></noscript>
    </body>
</html>
