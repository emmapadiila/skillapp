<x-layouts.admin title="Colaboradores" active-nav="collaborators" breadcrumb="Colaboradores">
    <section data-collaborators-page class="mx-auto w-full max-w-[1600px] px-4 pb-8 pt-5 sm:px-6 sm:pt-5 lg:px-7" aria-labelledby="collaborators-title">
        <div class="mb-4 flex flex-wrap items-center gap-3 text-sm text-slate-500"><p id="collaborators-status" role="status">Cargando colaboradores…</p><button id="collaborators-reload" type="button" class="text-blue-600">Actualizar</button><a href="{{ route('login') }}" class="text-blue-600">Iniciar sesión</a></div>
        <h2 id="collaborators-title" class="sr-only">Directorio de colaboradores</h2>

        <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
            <label class="relative min-w-0 flex-1 xl:max-w-96">
                <span class="sr-only">Buscar por nombre, correo, cargo o área</span>
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
                <input id="collaborator-search" type="search" placeholder="Buscar colaborador, cargo, área..." class="h-10 w-full rounded-md border border-[#e1e7ef] bg-white pl-9 pr-3 text-xs text-slate-700 outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
            </label>

            <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                <label class="sr-only" for="collaborator-axis-filter">Eje organizacional</label>
                <select id="collaborator-axis-filter" data-collaborator-filter="axis" class="collaborator-filter"><option value="all">Todos los ejes</option></select>
                <label class="sr-only" for="collaborator-status-filter">Estado</label>
                <select id="collaborator-status-filter" data-collaborator-filter="status" class="collaborator-filter"><option value="all">Todos los estados</option><option value="evaluated">Evaluado</option><option value="pending">Pendiente</option><option value="in-progress">En evaluación</option><option value="expired">Vencida</option><option value="unassigned">Sin evaluación</option></select>
                <button id="collaborator-extra-filters-toggle" type="button" class="grid size-9 place-items-center rounded-md border border-[#e1e7ef] bg-white text-slate-500 transition hover:border-blue-300 hover:text-blue-600" aria-label="Más filtros" aria-expanded="false" aria-controls="collaborator-extra-filters" title="Más filtros"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h16l-6.5 7.5V19l-3 1v-7.5L4 5Z"/></svg></button>
                <button id="collaborator-add" type="button" class="col-span-2 inline-flex h-9 items-center justify-center gap-2 rounded-md bg-blue-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 sm:col-span-1"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Agregar</button>
            </div>
        </div>

        <div id="collaborator-extra-filters" class="mt-3 hidden rounded-lg border border-[#e5eaf1] bg-white p-3" aria-label="Filtros adicionales">
            <div class="grid gap-2 sm:grid-cols-[minmax(12rem,18rem)_auto] sm:items-end">
                <label class="grid gap-1.5 text-[.68rem] font-medium text-slate-600" for="collaborator-area-filter">Área
                    <select id="collaborator-area-filter" data-collaborator-filter="area" class="collaborator-filter"><option value="all">Todas las áreas</option></select>
                </label>
                <button id="collaborator-reset-filters" type="button" class="min-h-9 justify-self-start rounded-md px-3 text-xs font-medium text-blue-700 hover:bg-blue-50">Restablecer filtros</button>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[.68rem] text-slate-500" aria-live="polite" aria-atomic="true">
            <span><strong id="collaborator-total" class="font-medium text-slate-700">—</strong> colaboradores</span><span aria-hidden="true">·</span>
            <span class="font-semibold text-emerald-600"><strong id="collaborator-evaluated">—</strong> evaluados</span><span aria-hidden="true">·</span>
            <span class="font-semibold text-amber-600"><strong id="collaborator-pending">—</strong> pendientes</span>
        </div>

        <div id="collaborators-empty" class="mt-4 hidden rounded-lg border border-dashed border-slate-300 bg-white px-5 py-14 text-center">
            <span class="mx-auto grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg></span>
            <h3 class="mt-3 text-sm font-semibold text-slate-800">No se encontraron colaboradores</h3>
            <p class="mt-1 text-xs text-slate-500">Prueba con otro término o restablece los filtros.</p>
            <button type="button" data-reset-collaborator-search class="mt-4 rounded-md px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50">Limpiar búsqueda y filtros</button>
        </div>

        <label class="mt-4 grid gap-1.5 text-xs text-slate-600 xl:hidden" for="collaborator-mobile-sort">Ordenar por
            <select id="collaborator-mobile-sort" class="collaborator-filter">
                @foreach (['name' => 'Nombre', 'position' => 'Cargo', 'area' => 'Área', 'axis' => 'Eje', 'score' => 'Resultado', 'status' => 'Estado'] as $key => $label)
                    <option value="{{ $key }}:ascending">{{ $label }} · ascendente</option>
                    <option value="{{ $key }}:descending">{{ $label }} · descendente</option>
                @endforeach
            </select>
        </label>
        <div id="collaborator-cards" class="mt-4 grid gap-2.5 xl:hidden" aria-label="Lista de colaboradores"></div>

        <div id="collaborator-table-wrap" class="mt-4 hidden overflow-hidden rounded-lg border border-[#e8edf3] bg-white shadow-[0_1px_2px_rgba(16,33,61,.05)] xl:block">
            <table class="collaborator-table w-full table-fixed text-left">
                <thead class="border-b border-[#edf1f5] bg-white text-[.64rem] font-semibold uppercase tracking-[.035em] text-slate-500">
                    <tr>
                        @foreach ([['name', 'Nombre', 'w-[22%]'], ['position', 'Cargo', 'w-[18%]'], ['area', 'Área', 'w-[12%]'], ['axis', 'Eje', 'w-[12%]'], ['score', 'Resultado', 'w-[9%]'], ['status', 'Estado', 'w-[13%]']] as [$column, $label, $width])
                            <th scope="col" class="{{ $width }} px-3 py-3 first:pl-4" aria-sort="{{ $column === 'name' ? 'ascending' : 'none' }}"><button type="button" class="inline-flex items-center gap-1.5 text-left hover:text-blue-700" data-collaborator-sort="{{ $column }}" aria-label="Ordenar por {{ strtolower($label) }}">{{ $label }}<svg class="size-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5-5 5 5m-10 4 5 5 5-5"/></svg></button></th>
                        @endforeach
                        <th scope="col" class="w-[14%] px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="collaborators-table-body" class="divide-y divide-[#f0f3f7]"></tbody>
            </table>
        </div>
    </section>

    <dialog aria-labelledby="collaborator-add-title" id="collaborator-add-dialog" class="collaborator-dialog w-[min(92vw,500px)] rounded-xl border border-slate-200 bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-950/45">
        <form id="collaborator-add-form">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4"><div><p class="text-[.65rem] font-bold uppercase tracking-wider text-blue-600">Alta pendiente de conexión</p><h2 id="collaborator-add-title" class="mt-1 text-lg font-bold">Agregar colaborador</h2></div><button type="button" class="dialog-close grid size-9 place-items-center rounded-md text-slate-500 hover:bg-slate-100" aria-label="Cerrar formulario"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
            <div class="grid gap-3 px-5 py-5 sm:grid-cols-2">
                <label class="grid gap-1.5 text-xs font-semibold text-slate-700">Nombre completo<input name="name" required maxlength="120" class="h-10 rounded-md border border-slate-200 px-3 font-normal outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"></label>
                <label class="grid gap-1.5 text-xs font-semibold text-slate-700">Correo electrónico<input name="email" type="email" required maxlength="255" class="h-10 rounded-md border border-slate-200 px-3 font-normal outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"></label>
                <label class="grid gap-1.5 text-xs font-semibold text-slate-700">Cargo<input name="position" required maxlength="120" class="h-10 rounded-md border border-slate-200 px-3 font-normal outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"></label>
                <label class="grid gap-1.5 text-xs font-semibold text-slate-700">Área<input name="area" required maxlength="120" class="collaborator-filter"></label>
                <p class="col-span-full text-xs leading-5 text-slate-500">El alta desde esta pantalla está pendiente de conexión. Este formulario no enviará ni guardará datos.</p><p id="collaborator-form-feedback" class="col-span-full text-sm font-medium text-blue-700" role="status"></p>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3"><button type="button" class="dialog-close min-h-9 rounded-md px-3 text-xs font-medium text-slate-600 hover:bg-slate-100">Cancelar</button><button type="submit" class="min-h-9 rounded-md bg-blue-600 px-4 text-xs font-semibold text-white hover:bg-blue-700">Revisar formulario</button></div>
        </form>
    </dialog>

    <dialog aria-labelledby="profile-name" id="collaborator-profile-dialog" class="collaborator-dialog w-[min(92vw,460px)] rounded-xl border border-slate-200 bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-950/45">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4"><div class="flex items-center gap-3"><span id="profile-initials" class="grid size-11 place-items-center rounded-full bg-blue-600 text-xs font-bold text-white"></span><div><p class="text-[.65rem] font-bold uppercase tracking-wider text-blue-600">Colaborador</p><h2 id="profile-name" class="mt-1 text-lg font-bold"></h2></div></div><button type="button" class="dialog-close grid size-9 place-items-center rounded-md text-slate-500 hover:bg-slate-100" aria-label="Cerrar perfil"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
        <dl class="grid grid-cols-2 gap-x-4 gap-y-4 px-5 py-5 text-sm"><div><dt class="text-xs text-slate-500">Correo</dt><dd id="profile-email" class="mt-1 break-all font-medium"></dd></div><div><dt class="text-xs text-slate-500">Resultado</dt><dd id="profile-score" class="mt-1 font-semibold"></dd></div><div><dt class="text-xs text-slate-500">Cargo</dt><dd id="profile-position" class="mt-1 font-medium"></dd></div><div><dt class="text-xs text-slate-500">Área</dt><dd id="profile-area" class="mt-1 font-medium"></dd></div><div><dt class="text-xs text-slate-500">Eje organizacional</dt><dd id="profile-axis" class="mt-1 font-medium"></dd></div><div><dt class="text-xs text-slate-500">Estado</dt><dd id="profile-status" class="mt-1 font-medium"></dd></div></dl>
        <div class="flex justify-end border-t border-slate-100 px-5 py-3"><button type="button" class="dialog-close min-h-9 rounded-md bg-blue-600 px-4 text-xs font-semibold text-white hover:bg-blue-700">Cerrar</button></div>
    </dialog>
</x-layouts.admin>