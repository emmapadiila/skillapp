<section id="skills-screen" hidden class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-8" aria-label="Catálogo de habilidades">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <p id="skills-summary" class="text-sm text-slate-500" role="status">Catálogo pendiente de carga</p>
        <button id="skill-add" type="button" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">+ Nueva habilidad</button>
    </div>
    <div class="mb-4 flex flex-wrap items-center gap-3 text-sm text-slate-500"><p id="skills-status" role="status"></p><button id="skills-reload" type="button" class="text-blue-600 hover:underline">Actualizar catálogo</button><a href="{{ route('login') }}" class="text-blue-600 hover:underline">Iniciar sesión</a></div>
    <div id="skills-list" class="grid gap-4"></div>
    <p id="skills-empty" class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center text-sm text-slate-500">No hay habilidades disponibles para mostrar.</p>
    <dialog id="skill-dialog" class="collaborator-dialog w-[min(92vw,540px)] rounded-xl border border-slate-200 bg-white p-5 text-slate-800 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="skill-dialog-title">
        <form id="skill-form" class="grid gap-4">
            <div class="flex items-center justify-between gap-3"><h2 id="skill-dialog-title" class="text-lg font-bold">Nueva habilidad</h2><button type="button" data-close-skill class="rounded-md p-2 hover:bg-slate-100" aria-label="Cerrar">✕</button></div>
            <p class="text-sm leading-6 text-slate-500">Borrador temporal. Solo se añadirá a esta vista y se perderá al recargar. El guardado en el catálogo está pendiente de conexión.</p>
            <label class="grid gap-1.5 text-sm">Nombre<input name="name" required maxlength="255" class="collaborator-filter w-full"></label>
            <label class="grid gap-1.5 text-sm">Categoría<select name="category" required class="collaborator-filter w-full"><option value="">Selecciona una categoría</option></select></label>
            <fieldset><legend class="mb-2 text-sm">Ejes aplicables</legend><div id="skill-axes" class="flex flex-wrap gap-3"></div></fieldset>
            <label class="grid gap-1.5 text-sm">Nivel objetivo (1–5)<input name="level" type="number" min="1" max="5" step="1" required class="collaborator-filter w-full"></label>
            <p id="skill-feedback" role="alert" class="text-sm text-amber-700"></p>
            <div class="flex flex-wrap justify-end gap-2"><button type="button" data-close-skill class="rounded-lg px-4 py-2 text-sm hover:bg-slate-100">Cancelar</button><button id="skill-submit" type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Añadir borrador local</button></div>
        </form>
    </dialog>
</section>
