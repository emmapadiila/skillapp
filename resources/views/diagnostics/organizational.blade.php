<x-layouts.admin title="Diagnóstico Organizacional">
    <section data-organizational-dashboard class="mx-auto w-full max-w-[1600px] px-4 pb-8 pt-5 sm:px-6 sm:pt-6 lg:px-7" aria-labelledby="diagnostic-description">
        <div class="mb-5 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <p id="diagnostic-description" class="max-w-2xl text-sm leading-6 text-slate-500">Vista general del estado de las habilidades blandas de la organización.</p>
            <div class="grid w-full grid-cols-2 items-center gap-2 sm:flex sm:w-auto" aria-label="Filtros del diagnóstico">
                <label class="sr-only" for="filter-period">Período</label>
                <select id="filter-period" data-filter="period" class="dashboard-filter"><option value="all">Período</option><option>2026 · S1</option><option>2025 · S2</option><option>2025 · S1</option></select>
                <label class="sr-only" for="filter-area">Área</label>
                <select id="filter-area" data-filter="area" class="dashboard-filter"><option value="all">Área</option><option>Talento Humano</option><option>Operaciones</option><option>Comercial</option><option>Tecnología</option></select>
                <label class="sr-only" for="filter-position">Cargo</label>
                <select id="filter-position" data-filter="position" class="dashboard-filter"><option value="all">Cargo</option><option>Analista</option><option>Coordinador</option><option>Líder</option><option>Especialista</option></select>
                <label class="sr-only" for="filter-axis">Eje</label>
                <select id="filter-axis" data-filter="axis" class="dashboard-filter"><option value="all">Eje</option><option value="operativo">Operativo</option><option value="misional">Misional</option><option value="estrategico">Estratégico</option></select>
                <button id="reset-filters" type="button" class="grid size-9 place-items-center rounded-md border border-[#e1e7ef] bg-white text-slate-500 transition hover:border-blue-300 hover:text-blue-600 max-sm:justify-self-end" aria-label="Restablecer filtros" title="Restablecer filtros">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h16l-6.5 7.5V19l-3 1v-7.5L4 5Z"/><path d="m17 4 3 3m0-3-3 3"/></svg>
                </button>
            </div>
        </div>

        <div class="mb-4 flex items-center gap-2 text-[.68rem] text-slate-500"><span class="size-1.5 rounded-full bg-emerald-500"></span>Datos de ejemplo · Los filtros se aplican en esta vista</div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6 xl:gap-3.5" aria-label="Indicadores principales">
            @php
                $metrics = [
                    ['Colaboradores', 'collaborators', '245', 'users', 'bg-slate-100 text-slate-700'],
                    ['Evaluados', 'evaluated', '198', 'check', 'bg-emerald-50 text-emerald-600'],
                    ['Resultado promedio', 'average', '76', 'chart', 'bg-blue-50 text-blue-600'],
                    ['Brechas críticas', 'gaps', '12', 'warning', 'bg-amber-50 text-amber-600'],
                    ['Planes activos', 'active-plans', '87', 'target', 'bg-orange-50 text-orange-600'],
                    ['Planes completados', 'completed-plans', '54', 'trophy', 'bg-emerald-50 text-emerald-600'],
                ];
            @endphp
            @foreach ($metrics as [$label, $key, $value, $icon, $tone])
                <article class="min-h-[124px] rounded-lg border border-[#e8edf3] bg-white p-4 shadow-[0_1px_2px_rgba(16,33,61,.06)] sm:p-4.5">
                    <div class="flex items-start justify-between gap-2"><h2 class="max-w-[8rem] text-[.64rem] font-semibold uppercase leading-4 tracking-[.045em] text-slate-500">{{ $label }}</h2><span class="grid size-9 shrink-0 place-items-center rounded-lg {{ $tone }}">
                        @switch($icon)
                            @case('users') <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20M9.5 10.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM16 4a3.5 3.5 0 0 1 0 7m2 3a4 4 0 0 1 3 4v2"/></svg> @break
                            @case('check') <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="9"/></svg> @break
                            @case('chart') <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19V5M4 19h17M8 15v-4M13 15V7M18 15V9"/></svg> @break
                            @case('warning') <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg> @break
                            @case('target') <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg> @break
                            @default <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 2.6 5.4 5.9.9-4.3 4.2 1 5.9-5.2-2.8-5.2 2.8 1-5.9-4.3-4.2 5.9-.9L12 3Z"/></svg>
                        @endswitch
                    </span></div>
                    <p class="mt-2 text-[1.8rem] font-bold leading-none tracking-tight text-[#17243a]" data-metric="{{ $key }}">{{ $value }}@if ($key === 'average')<span class="ml-0.5 text-base font-semibold text-slate-400">%</span>@endif</p>
                    @if ($key === 'evaluated') <p class="mt-2 text-[.66rem] font-semibold text-emerald-600" data-metric-note="evaluated">↑ 80.8% del total</p> @endif
                    @if ($key === 'average') <p class="mt-2 text-[.66rem] font-semibold text-emerald-600" data-metric-note="average">↑ 4 pts. vs. semestre anterior</p> @endif
                    @if ($key === 'completed-plans') <p class="mt-2 text-[.66rem] font-semibold text-emerald-600" data-metric-note="completed-plans">↑ 62% de finalización</p> @endif
                </article>
            @endforeach
        </div>

        <div class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1.8fr)_minmax(280px,.9fr)]">
            <section class="rounded-lg border border-[#e8edf3] bg-white p-4 shadow-[0_1px_2px_rgba(16,33,61,.06)] sm:p-5" aria-labelledby="axis-chart-title">
                <div class="flex flex-wrap items-center justify-between gap-2"><h2 id="axis-chart-title" class="text-sm font-bold text-[#17243a]">Resultados por eje organizacional</h2><span id="chart-filter-label" class="text-[.68rem] text-slate-400">Promedio · escala 0–100</span></div>
                <div class="axis-chart mt-4" role="img" aria-label="Resultados de los ejes Operativo, Misional y Estratégico">
                    <div class="axis-chart-grid" aria-hidden="true"><span>100</span><span>75</span><span>50</span><span>25</span><span>0</span></div>
                    <div class="axis-chart-bars">
                        @foreach ([['Operativo', 'operativo', 78, 'bg-blue-500'], ['Misional', 'misional', 71, 'bg-emerald-500'], ['Estratégico', 'estrategico', 84, 'bg-violet-500']] as [$label, $key, $value, $color])
                            <div class="axis-chart-column" data-axis-column="{{ $key }}" style="--bar-value: {{ $value }}%"><span class="axis-chart-value" data-axis-value="{{ $key }}">{{ $value }}%</span><div class="axis-chart-bar {{ $color }}" data-axis-bar="{{ $key }}"></div><span class="axis-chart-label">{{ $label }}</span></div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-x-5 gap-y-4 sm:grid-cols-4" aria-label="Resultados por habilidad">
                    @foreach ([['Comunicativas', 76], ['Trabajo colaborativo', 81], ['Cognitivas', 68], ['Dirección', 74]] as [$skill, $value])
                        <div class="skill-indicator" data-skill-name="{{ $skill }}"><div class="flex min-h-8 items-end justify-between gap-1 text-[.66rem] text-slate-500"><span>{{ $skill }}</span><strong data-skill-value class="shrink-0 font-bold text-[#17243a]">{{ $value }}%</strong></div><div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100"><span data-skill-bar class="block h-full rounded-full bg-blue-700 transition-[width] duration-300" style="width:{{ $value }}%"></span></div></div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-[#e8edf3] bg-white p-4 shadow-[0_1px_2px_rgba(16,33,61,.06)] sm:p-5" aria-labelledby="employee-state-title">
                <h2 id="employee-state-title" class="text-sm font-bold text-[#17243a]">Estado de colaboradores</h2>
                <div class="mt-4 flex flex-col items-center gap-4 sm:flex-row sm:justify-around xl:flex-col xl:gap-2">
                    <div id="employee-donut" class="employee-donut" role="img" aria-label="198 evaluados, 35 pendientes y 12 en evaluación"><span class="sr-only">Estado de colaboradores</span></div>
                    <div class="grid w-full gap-2 sm:max-w-48 xl:max-w-none">
                        <p class="flex items-center gap-2 text-xs text-slate-600"><span class="size-2.5 rounded-full bg-emerald-500"></span><span class="flex-1">Evaluados</span><strong class="text-slate-800" data-state-count="evaluated">198</strong></p>
                        <p class="flex items-center gap-2 text-xs text-slate-600"><span class="size-2.5 rounded-full bg-amber-500"></span><span class="flex-1">Pendientes</span><strong class="text-slate-800" data-state-count="pending">35</strong></p>
                        <p class="flex items-center gap-2 text-xs text-slate-600"><span class="size-2.5 rounded-full bg-blue-500"></span><span class="flex-1">En evaluación</span><strong class="text-slate-800" data-state-count="in-progress">12</strong></p>
                    </div>
                </div>
            </section>
        </div>

        <section class="mt-5 grid gap-4 lg:grid-cols-3" aria-label="Resumen de resultados por eje">
            @foreach ([['Operativo', 'operativo', 78, 'Trabajo colaborativo', 'Resolución de problemas', 'blue'], ['Misional', 'misional', 71, 'Orientación al cliente', 'Comunicación entre áreas', 'green'], ['Estratégico', 'estrategico', 84, 'Liderazgo', 'Gestión del cambio', 'violet']] as [$label, $key, $value, $strength, $gap, $color])
                <article class="axis-summary rounded-lg border border-[#e8edf3] bg-white p-4 shadow-[0_1px_2px_rgba(16,33,61,.06)] sm:p-5" data-axis-summary="{{ $key }}">
                    <div class="flex items-center justify-between gap-3"><span class="axis-pill axis-pill--{{ $color }}"><span class="size-1.5 rounded-full bg-current"></span>{{ $label }}</span><strong class="axis-score axis-score--{{ $color }} text-[1.75rem] font-bold leading-none tracking-tight" data-summary-score="{{ $key }}">{{ $value }}%</strong></div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100"><span class="axis-progress axis-progress--{{ $color }}" data-summary-bar="{{ $key }}" style="width:{{ $value }}%"></span></div>
                    <p class="mt-3 flex gap-2 text-[.7rem] leading-5 text-slate-600"><span class="shrink-0 font-bold text-emerald-600">↑ Fortaleza:</span><span data-summary-strength="{{ $key }}">{{ $strength }}</span></p>
                    <p class="mt-1 flex gap-2 text-[.7rem] leading-5 text-slate-600"><span class="shrink-0 font-bold text-red-500">↓ Brecha:</span><span data-summary-gap="{{ $key }}">{{ $gap }}</span></p>
                    <button type="button" class="mt-3 flex min-h-9 w-full items-center justify-center gap-2 rounded-md border border-[#e1e7ef] text-xs font-medium text-slate-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" data-open-diagnostic="{{ $key }}">Ver diagnóstico <span aria-hidden="true">→</span></button>
                </article>
            @endforeach
        </section>
    </section>

    <dialog id="diagnostic-modal" class="diagnostic-modal w-[min(92vw,460px)] rounded-xl border border-slate-200 bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-950/45">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4"><div><p class="text-[.65rem] font-bold uppercase tracking-wider text-blue-600">Detalle del eje</p><h2 id="diagnostic-modal-title" class="mt-1 text-lg font-bold"></h2></div><button id="diagnostic-modal-close" type="button" class="grid size-9 place-items-center rounded-md text-slate-500 hover:bg-slate-100" aria-label="Cerrar diagnóstico"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
        <div class="grid gap-4 px-5 py-5"><p class="text-sm leading-6 text-slate-600">Resumen ilustrativo basado en datos de demostración. No se envió ninguna solicitud.</p><div class="grid grid-cols-2 gap-3"><div class="rounded-md bg-emerald-50 p-3"><p class="text-[.65rem] font-semibold uppercase text-emerald-700">Fortaleza</p><p id="diagnostic-modal-strength" class="mt-1 text-sm font-semibold text-slate-800"></p></div><div class="rounded-md bg-red-50 p-3"><p class="text-[.65rem] font-semibold uppercase text-red-700">Brecha</p><p id="diagnostic-modal-gap" class="mt-1 text-sm font-semibold text-slate-800"></p></div></div></div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-3"><button id="diagnostic-modal-done" type="button" class="rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700">Cerrar</button></div>
    </dialog>
</x-layouts.admin>