@props(['active' => 'dashboard'])

<aside id="admin-sidebar" class="admin-sidebar fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-[#0c1729] text-slate-300 transition-transform duration-200 lg:translate-x-0" aria-label="Navegación principal">
    <div class="flex h-[74px] shrink-0 items-center border-b border-white/10 px-5">
        <a href="{{ route('diagnostics.organizational') }}" class="flex items-center gap-2.5 rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-400" aria-label="SkillDiagnóstico, ir al diagnóstico organizacional">
            <span class="grid size-9 place-items-center rounded-[10px] bg-blue-600 text-sm font-extrabold tracking-tight text-white">SD</span>
            <span class="grid gap-0.5 leading-none"><span class="text-[.88rem] font-bold tracking-tight text-white">SkillDiagnóstico</span><span class="text-[.65rem] font-semibold text-blue-400">● IA</span></span>
        </a>
        <button id="sidebar-close" type="button" class="ml-auto grid size-9 place-items-center rounded-md text-slate-400 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Cerrar menú">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
        </button>
    </div>

    <div class="px-3 pt-5">
        <p class="rounded-md bg-[#12294c] px-3 py-2 text-[.66rem] font-bold uppercase tracking-[.08em] text-blue-400">Vista administrador</p>
    </div>

    <nav class="admin-nav min-h-0 flex-1 overflow-y-auto px-2.5 py-3" aria-label="Secciones">
        <div class="grid gap-1">
            <a href="{{ route('diagnostics.organizational') }}" @class(['admin-nav-link', 'admin-nav-link--active' => $active === 'dashboard']) @if ($active === 'dashboard') aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1V10Z"/></svg><span>Dashboard</span><span class="ml-auto text-base">›</span>
            </a>
            <a href="{{ route('collaborators.index') }}" @class(['admin-nav-link', 'admin-nav-link--active' => $active === 'collaborators']) @if ($active === 'collaborators') aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20M9.5 10.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM16 4a3.5 3.5 0 0 1 0 7m2 3a4 4 0 0 1 3 4v2"/></svg><span>Colaboradores</span><span class="ml-auto text-base">›</span>
            </a>
            @php
                $navigationItems = [
                    ['Evaluaciones', 'clipboard'], ['Habilidades', 'spark'],
                    ['Banco de preguntas', 'help'], ['Cargos', 'briefcase'], ['Diagnóstico organizacional', 'chart'],
                    ['Mapa de calor', 'map'], ['Brechas', 'warning'], ['Recomendaciones IA', 'star'],
                    ['Planes de mejoramiento', 'target'], ['Seguimiento', 'calendar'], ['Reportes', 'file'],
                ];
            @endphp
            @foreach ($navigationItems as [$label, $icon])
                <button type="button" class="admin-nav-link" data-demo-nav="{{ $label }}">
                    @switch($icon)
                        @case('users') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20M9.5 10.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM16 4a3.5 3.5 0 0 1 0 7m2 3a4 4 0 0 1 3 4v2"/></svg> @break
                        @case('clipboard') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M8 10h8M8 14h8M8 18h4"/></svg> @break
                        @case('spark') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3ZM19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16Z"/></svg> @break
                        @case('help') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4.3 1.7c-1 .9-1.8 1.2-1.8 2.8M12 17h.01"/></svg> @break
                        @case('briefcase') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V4h8v3M3 12h18M10 12v2h4v-2"/></svg> @break
                        @case('chart') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19V5M4 19h17M8 15v-4M13 15V7M18 15V9"/></svg> @break
                        @case('map') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z"/><path d="M9 3v15m6-12v15"/></svg> @break
                        @case('warning') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg> @break
                        @case('star') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/></svg> @break
                        @case('target') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg> @break
                        @case('calendar') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg> @break
                        @default <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 3h10l4 4v14H5zM14 3v5h5M8 13h8M8 17h8"/></svg>
                    @endswitch
                    <span>{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </nav>

    <div class="flex shrink-0 items-center gap-3 border-t border-white/10 px-4 py-4">
        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-blue-600 text-xs font-bold text-white">DC</span>
        <span class="min-w-0 flex-1"><span class="block truncate text-xs font-semibold text-white">Daniela Castro</span><span class="mt-0.5 block truncate text-[.68rem] text-slate-400">Talento Humano · Demo</span></span>
        <button type="button" class="grid size-8 place-items-center rounded-md text-slate-400 hover:bg-white/10 hover:text-white" data-demo-nav="Configuración" aria-label="Configuración"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.4.8l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.4-.8l-1.7.7-1.4-2.4 1.4-1.1a7 7 0 0 1 0-1.7l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.4-.8l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.4.8l1.7-.7 1.4 2.4-1.4 1.1a7 7 0 0 1 0 1.6Z" transform="translate(-1 -1) scale(1.08)"/></svg></button>
    </div>
</aside>