@foreach (['questions' => 'Banco de preguntas', 'positions' => 'Cargos'] as $key => $label)
    <section id="{{ $key }}-screen" hidden class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-7" aria-label="{{ $label }}">
        <div class="mb-4 flex flex-wrap items-center gap-3 text-sm text-slate-500">
            <p data-status role="status">Información pendiente de carga.</p>
            <button data-reload type="button" class="text-blue-600 hover:underline">Actualizar</button>
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Iniciar sesión</a>
        </div>
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
            <label class="relative min-w-0 xl:w-80"><span class="sr-only">{{ $key === 'questions' ? 'Buscar pregunta' : 'Buscar cargo, área o habilidad' }}</span><svg class="pointer-events-none absolute left-3 top-3 size-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></svg><input data-search type="search" placeholder="{{ $key === 'questions' ? 'Buscar pregunta…' : 'Buscar cargo, área o habilidad…' }}" class="h-10 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"></label>
            <div data-filters class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap"></div>
            <button data-add type="button" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 xl:ml-auto">+ {{ $key === 'questions' ? 'Agregar pregunta' : 'Nuevo cargo' }}</button>
        </div>
        <p data-summary class="my-4 text-xs text-slate-500" aria-live="polite">—</p>
        <div data-empty hidden class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center"><h2 class="font-semibold">{{ $key === 'questions' ? 'No hay preguntas para mostrar' : 'No hay cargos para mostrar' }}</h2><p class="mt-2 text-sm text-slate-500">No hay registros disponibles o ninguno coincide con los filtros.</p><button data-reset type="button" class="mt-4 rounded-md px-3 py-2 text-sm text-blue-600 hover:bg-blue-50">Limpiar filtros</button></div>
        @if ($key === 'questions')
            <div data-table-wrap hidden class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:block">
                <table class="collaborator-table w-full table-fixed text-left"><caption class="sr-only">Banco de preguntas</caption><thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="w-[30%] px-4 py-3">Pregunta</th><th class="w-[14%] px-3 py-3">Tipo</th><th class="w-[16%] px-3 py-3">Habilidad</th><th class="w-[12%] px-3 py-3">Eje</th><th class="w-[9%] px-3 py-3">Nivel</th><th class="w-[9%] px-3 py-3">Estado</th><th class="w-[10%] px-3 py-3">Acciones</th></tr></thead><tbody data-rows class="divide-y divide-slate-100"></tbody></table>
            </div>
        @endif
        <div data-cards class="grid min-w-0 gap-4 {{ $key === 'questions' ? 'xl:hidden' : 'md:grid-cols-2 2xl:grid-cols-3' }}"></div>
        <dialog data-editor class="collaborator-dialog w-[min(94vw,680px)] rounded-xl border border-slate-200 bg-white p-5 text-slate-800 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="{{ $key }}-editor-title">
            <form data-form class="grid gap-4">
                <div class="flex items-center justify-between gap-3"><h2 id="{{ $key }}-editor-title" data-editor-title class="text-lg font-semibold"></h2><button data-close type="button" class="rounded-md p-2 hover:bg-slate-100" aria-label="Cerrar formulario">✕</button></div>
                <p class="text-sm leading-6 text-slate-500">Los cambios serán un borrador temporal en esta página. No se guardan en el servidor y se perderán al recargar.</p>
                <div data-fields class="grid gap-4 sm:grid-cols-2"></div>
                <p data-feedback role="alert" class="text-sm text-amber-700"></p>
                <div class="flex flex-wrap justify-end gap-2"><button data-close type="button" class="rounded-lg px-4 py-2 text-sm hover:bg-slate-100">Cancelar</button><button data-submit type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Aplicar borrador local</button></div>
            </form>
        </dialog>
        <dialog data-detail class="collaborator-dialog w-[min(94vw,620px)] rounded-xl border border-slate-200 bg-white p-5 text-slate-800 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="{{ $key }}-detail-title">
            <div class="mb-4 flex items-center justify-between gap-3"><h2 id="{{ $key }}-detail-title" data-detail-title class="text-lg font-semibold"></h2><button data-close type="button" class="rounded-md p-2 hover:bg-slate-100" aria-label="Cerrar detalle">✕</button></div>
            <div data-detail-content class="grid gap-4 text-sm"></div>
            <div class="mt-5 flex justify-end"><button data-close type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Cerrar</button></div>
        </dialog>
    </section>
@endforeach
