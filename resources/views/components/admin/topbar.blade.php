@props(['title', 'breadcrumb' => null])

<header class="sticky top-0 z-20 flex h-[74px] items-center gap-3 border-b border-[#e6ebf2] bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-7">
    <button id="sidebar-open" type="button" class="grid size-10 shrink-0 place-items-center rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Abrir menú" aria-controls="admin-sidebar" aria-expanded="false">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="min-w-0 flex-1">
        @if ($breadcrumb)
            <nav class="flex items-center gap-1 text-[.68rem] font-medium text-slate-500" aria-label="Ruta de navegación">
                <a href="{{ route('diagnostics.organizational') }}" class="hover:text-blue-600">Dashboard</a>
                <svg class="size-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                <span>{{ $breadcrumb }}</span>
            </nav>
        @else
            <p class="text-[.68rem] font-medium text-slate-500">Dashboard</p>
        @endif
        <h1 class="truncate text-base font-bold leading-6 text-[#17243a] sm:text-lg">{{ $title }}</h1>
    </div>
    <label class="relative hidden w-44 md:block xl:w-52">
        <span class="sr-only">Buscar habilidad</span>
        <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
        <input id="dashboard-search" type="search" placeholder="Buscar..." class="h-9 w-full rounded-md border border-[#e4eaf2] bg-[#f8fafc] pl-9 pr-3 text-xs text-slate-700 outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
    </label>
    <div class="relative">
        <button id="notifications-toggle" type="button" class="relative grid size-10 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Notificaciones de ejemplo" aria-expanded="false" aria-controls="notifications-panel">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
            <span class="absolute right-0.5 top-0.5 grid size-4 place-items-center rounded-full bg-red-500 text-[.6rem] font-bold text-white">2</span>
        </button>
        <div id="notifications-panel" class="absolute right-0 top-12 hidden w-72 rounded-lg border border-slate-200 bg-white p-4 shadow-xl">
            <p class="text-sm font-bold text-slate-800">Notificaciones de ejemplo</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-600">12 evaluaciones están en curso.</p>
            <p class="mt-2 text-xs leading-5 text-slate-600">Hay 12 brechas críticas por revisar.</p>
        </div>
    </div>
    <button type="button" class="grid size-9 shrink-0 place-items-center rounded-full bg-blue-600 text-[.68rem] font-bold text-white" data-demo-nav="Perfil de Daniela Castro" aria-label="Perfil de demostración">DC</button>
</header>