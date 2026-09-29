import { getAll, getData, escapeHtml as escape, normalize, axisTone, connectionMessage } from './data/platform';

const questionTypes = { self_report: 'Autoinforme', situational: 'Situacional', situational_judgment: 'Juicio situacional' };
const difficulties = { basic: 'Básico', intermediate: 'Intermedio', advanced: 'Avanzado' };
const badge = (label, tone = 'gray') => `<span class="collaborator-badge collaborator-badge--${tone}">${escape(label)}</span>`;
const icons = {
    view: '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    edit: '<path d="m15 4 5 5M4 20l5-1L21 7l-5-5L4 14v6Z"/>',
    remove: '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
};
const action = (kind, id, label) => `<button type="button" data-action="${kind}" data-id="${escape(id)}" aria-label="${escape(label)}" title="${escape(label)}" class="grid size-8 shrink-0 place-items-center rounded-md text-slate-400 hover:bg-blue-50 hover:text-blue-600 focus-visible:outline-2 focus-visible:outline-blue-600"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">${icons[kind]}</svg></button>`;
const options = (records, selected, prompt = 'Selecciona una opción') => `<option value="">${prompt}</option>` + records.map((record) => `<option value="${escape(record.id)}" ${String(record.id) === String(selected) ? 'selected' : ''}>${escape(record.name)}</option>`).join('');
const enumRecords = (values) => Object.entries(values).map(([id, name]) => ({ id, name }));
const select = (label, name, records, value, required = true) => `<label class="grid min-w-0 gap-1.5 text-sm">${label}<select name="${name}" ${required ? 'required' : ''} class="collaborator-filter w-full">${options(records, value)}</select></label>`;

for (const [resource, hash] of [['questions', '#preguntas'], ['positions', '#cargos']]) {
    const screen = document.querySelector(`#${resource}-screen`);
    if (!screen) continue;
    const questions = resource === 'questions';
    const search = screen.querySelector('[data-search]');
    const globalSearch = document.querySelector('#dashboard-search');
    const form = screen.querySelector('[data-form]');
    const editor = screen.querySelector('[data-editor]');
    const detail = screen.querySelector('[data-detail]');
    const drafts = new Map();
    let records = [];
    let skills = [];
    let areas = [];
    let axes = [];
    let loaded = false;
    let loading = false;
    let editingId = null;
    const allRecords = () => [...records.map((record) => drafts.get(String(record.id)) ?? record), ...[...drafts.values()].filter((record) => String(record.id).startsWith('local-'))];
    const axisFor = (record) => axes.find((axis) => axis.id === record.organizational_axis_id) ?? record.organizational_axis;
    const skillFor = (record) => skills.find((skill) => skill.id === record.skill_id) ?? record.skill;
    const axisBadge = (record) => { const axis = axisFor(record); return axis ? badge(axis.name, axisTone(axis)) : badge('Sin eje'); };
    const stateBadge = (record) => badge(record.is_active ? 'Activo' : 'Inactivo', record.is_active ? 'green' : 'gray');
    const draftLabel = (record) => record.local ? '<span class="mt-1 block text-xs text-amber-700">Borrador local · sin guardar</span>' : '';
    const buttons = (record) => `<div class="flex items-center gap-1">${action('view', record.id, 'Ver detalle')}${action('edit', record.id, 'Editar borrador')}${questions || record.local ? action('remove', record.id, record.local ? 'Descartar borrador local' : 'Eliminar pregunta: pendiente de conexión') : ''}</div>`;

    function render() {
        const filters = [...screen.querySelectorAll('[data-filter]')];
        const visible = allRecords().filter((record) => {
            const text = questions ? `${record.text} ${skillFor(record)?.name ?? ''}` : `${record.name} ${record.area?.name ?? ''} ${(record.skills ?? []).map((skill) => skill.name).join(' ')}`;
            return normalize(text).includes(normalize(search.value.trim())) && filters.every((filter) => !filter.value || String(record[filter.dataset.filter]) === filter.value);
        });
        screen.querySelector('[data-summary]').textContent = loaded
            ? `${visible.length} ${questions ? 'preguntas' : 'cargos'} de ${allRecords().length} · ${visible.filter((record) => record.is_active).length} activos · ${visible.filter((record) => !record.is_active).length} inactivos${drafts.size ? ` · ${drafts.size} borradores locales` : ''}` : 'Datos no disponibles';
        screen.querySelector('[data-empty]').hidden = !loaded || visible.length > 0;
        if (questions) {
            screen.querySelector('[data-table-wrap]').hidden = visible.length === 0;
            screen.querySelector('[data-rows]').innerHTML = visible.map((record) => `<tr><td><p class="line-clamp-2" title="${escape(record.text)}">${escape(record.text)}</p>${draftLabel(record)}</td><td>${badge(questionTypes[record.type] ?? record.type, record.type === 'self_report' ? 'blue' : 'violet')}</td><td>${escape(skillFor(record)?.name ?? 'Sin habilidad disponible')}</td><td>${axisBadge(record)}</td><td>${escape(difficulties[record.difficulty] ?? record.difficulty)}</td><td>${stateBadge(record)}</td><td>${buttons(record)}</td></tr>`).join('');
        }
        screen.querySelector('[data-cards]').innerHTML = visible.map((record) => questions
            ? `<article class="min-w-0 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="break-words text-sm font-semibold">${escape(record.text)}</h2>${draftLabel(record)}<p class="mt-2 text-sm text-slate-500">${escape(skillFor(record)?.name ?? 'Sin habilidad disponible')}</p><div class="mt-3 flex flex-wrap gap-2">${badge(questionTypes[record.type] ?? record.type, 'blue')}${axisBadge(record)}${badge(difficulties[record.difficulty] ?? record.difficulty)}${stateBadge(record)}</div><div class="mt-4 flex justify-end border-t border-slate-100 pt-2">${buttons(record)}</div></article>`
            : `<article class="min-w-0 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-start gap-3"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-blue-100 text-blue-600"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="4" y="7" width="16" height="14" rx="2"/><path d="M9 7V3h6v4M9 7v14M15 7v14"/></svg></span><div class="min-w-0 flex-1"><h2 class="break-words text-sm font-semibold">${escape(record.name)}</h2><p class="mt-1 break-words text-xs text-slate-400">${escape(record.area?.name ?? 'Sin área disponible')}</p>${draftLabel(record)}</div>${action('edit', record.id, 'Editar borrador de ' + record.name)}</div><div class="mt-3 flex flex-wrap gap-2">${axisBadge(record)}${stateBadge(record)}</div><h3 class="mb-2 mt-5 text-xs font-medium uppercase text-slate-400">Habilidades asociadas</h3><div class="flex flex-wrap gap-1.5">${(record.skills ?? []).map((skill) => `<span class="max-w-full break-words rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-600">${escape(skill.name)}</span>`).join('') || '<p class="text-sm text-slate-400">Sin habilidades asociadas.</p>'}</div><div class="mt-3 flex justify-end">${action('view', record.id, 'Ver detalle del cargo')}${record.local ? action('remove', record.id, 'Descartar borrador local') : ''}</div></article>`).join('');
    }

    function setFilters() {
        const configs = questions ? [['type', 'Tipo', enumRecords(questionTypes)], ['skill_id', 'Habilidad', skills], ['organizational_axis_id', 'Eje', axes], ['difficulty', 'Nivel', enumRecords(difficulties)]] : [['area_id', 'Área', areas], ['organizational_axis_id', 'Eje', axes]];
        screen.querySelector('[data-filters]').innerHTML = configs.map(([key, label, values]) => `<label class="min-w-0"><span class="sr-only">${label}</span><select data-filter="${key}" class="collaborator-filter w-full sm:max-w-48">${options(values, '', label)}</select></label>`).join('');
        screen.querySelectorAll('[data-filter]').forEach((filter) => filter.addEventListener('change', render));
    }

    async function load() {
        if (loading) return;
        loading = true;
        screen.querySelector('[data-status]').textContent = 'Cargando registros…';
        screen.querySelector('[data-reload]').disabled = true;
        try {
            const [items, catalog, skillRecords, areaRecords] = await Promise.all([getAll(resource), getData('catalogs'), getAll('skills'), questions ? Promise.resolve([]) : getAll('areas')]);
            records = items;
            axes = catalog.organizational_axes;
            skills = skillRecords;
            areas = areaRecords;
            loaded = true;
            setFilters();
            screen.querySelector('[data-status]').textContent = 'Registros consultados. Los cambios locales no se guardan en el servidor.';
        } catch (error) {
            screen.querySelector('[data-status]').textContent = connectionMessage(error) + (loaded ? ' Se conservan los últimos datos consultados.' : '');
        } finally {
            loading = false;
            screen.querySelector('[data-reload]').disabled = false;
            render();
        }
    }

    function optionRow(option = {}, index = 0) {
        return `<div data-option-row class="grid grid-cols-[minmax(0,1fr)_5rem_auto] items-end gap-2"><label class="grid min-w-0 gap-1 text-xs">Opción ${index + 1}<input data-option-text required maxlength="2000" value="${escape(option.text ?? '')}" class="collaborator-filter w-full"></label><label class="grid gap-1 text-xs">Puntos<input data-option-score type="number" min="0" max="5" step="0.01" required value="${escape(option.score ?? '')}" class="collaborator-filter w-full"></label><button type="button" data-remove-option class="size-9 rounded-md text-slate-500 hover:bg-slate-100" aria-label="Quitar opción">✕</button></div>`;
    }

    function updateQuestionOptions() {
        const selfReport = form.elements.type.value === 'self_report';
        form.querySelector('[data-question-options]').hidden = selfReport;
        form.querySelectorAll('[data-question-options] input').forEach((input) => { input.disabled = selfReport; });
        form.querySelector('[data-likert-note]').hidden = !selfReport;
    }

    function openEditor(record = {}) {
        editingId = record.id ?? null;
        form.reset();
        const ready = loaded && skills.length > 0 && (questions || (axes.length > 0 && areas.length > 0));
        screen.querySelector('[data-editor-title]').textContent = `${editingId ? 'Editar' : 'Nuevo'} ${questions ? 'borrador de pregunta' : 'borrador de cargo'}`;
        screen.querySelector('[data-submit]').disabled = !ready;
        screen.querySelector('[data-feedback]').textContent = ready ? '' : 'Carga primero los catálogos necesarios. No hay datos de ejemplo en este formulario.';
        const active = `<label class="flex items-center gap-2 text-sm"><input name="is_active" type="checkbox" ${record.is_active !== false ? 'checked' : ''} class="accent-blue-600">Activo</label>`;
        const fields = screen.querySelector('[data-fields]');
        if (questions) {
            fields.innerHTML = `<label class="col-span-full grid gap-1.5 text-sm">Pregunta<textarea name="text" required maxlength="5000" rows="3" class="w-full rounded-lg border border-slate-200 p-3 outline-none focus:border-blue-400">${escape(record.text ?? '')}</textarea></label>${select('Tipo', 'type', enumRecords(questionTypes), record.type)}${select('Habilidad', 'skill_id', skills, record.skill_id)}${select('Eje (opcional)', 'organizational_axis_id', axes, record.organizational_axis_id, false)}${select('Nivel', 'difficulty', enumRecords(difficulties), record.difficulty)}${active}<p data-likert-note hidden class="col-span-full text-sm text-slate-500">El autoinforme utiliza la escala Likert del sistema, sin opciones personalizadas.</p><fieldset data-question-options class="col-span-full grid gap-3"><legend class="mb-2 text-sm font-medium">Opciones de respuesta · puntuación de 0 a 5</legend><div data-options-list class="grid gap-3">${(record.options?.length ? record.options : [{}, {}]).map(optionRow).join('')}</div><button data-add-option type="button" class="justify-self-start text-sm text-blue-600">+ Añadir opción</button></fieldset>`;
            form.elements.type.addEventListener('change', updateQuestionOptions);
            updateQuestionOptions();
        } else {
            fields.innerHTML = `<label class="col-span-full grid gap-1.5 text-sm">Nombre del cargo<input name="name" required maxlength="255" value="${escape(record.name ?? '')}" class="collaborator-filter w-full"></label>${select('Área', 'area_id', areas, record.area_id)}${select('Eje organizacional', 'organizational_axis_id', axes, record.organizational_axis_id)}<label class="col-span-full grid gap-1.5 text-sm">Descripción<textarea name="description" maxlength="5000" rows="2" class="w-full rounded-lg border border-slate-200 p-3">${escape(record.description ?? '')}</textarea></label>${active}<fieldset class="col-span-full grid gap-3"><legend class="mb-2 text-sm font-medium">Habilidades asociadas</legend><p class="text-xs text-slate-500">Selecciona habilidades y define su peso (0,01–100) y nivel requerido (1–5).</p>${skills.map((skill) => {
                const selected = record.skills?.find((item) => item.id === skill.id);
                return `<div data-position-skill="${skill.id}" class="grid grid-cols-2 items-end gap-2 rounded-lg bg-slate-50 p-3 sm:grid-cols-[minmax(0,1fr)_5rem_5rem]"><label class="col-span-2 flex min-w-0 items-center gap-2 text-sm sm:col-span-1"><input type="checkbox" ${selected ? 'checked' : ''} class="accent-blue-600"><span class="break-words">${escape(skill.name)}</span></label><label class="grid gap-1 text-xs">Peso<input data-weight type="number" min="0.01" max="100" step="0.01" required ${selected ? '' : 'disabled'} value="${escape(selected?.pivot?.weight ?? '')}" class="collaborator-filter w-full"></label><label class="grid gap-1 text-xs">Nivel<input data-level type="number" min="1" max="5" required ${selected ? '' : 'disabled'} value="${escape(selected?.pivot?.required_level ?? '')}" class="collaborator-filter w-full"></label></div>`;
            }).join('')}</fieldset>`;
            fields.querySelectorAll('[data-position-skill] input[type="checkbox"]').forEach((checkbox) => checkbox.addEventListener('change', () => {
                checkbox.closest('[data-position-skill]').querySelectorAll('input[type="number"]').forEach((input) => { input.disabled = !checkbox.checked; });
            }));
        }
        editor.showModal();
    }

    screen.addEventListener('click', (event) => {
        const close = event.target.closest('[data-close]');
        if (close) close.closest('dialog').close();
        if (event.target.closest('[data-add-option]')) {
            const list = form.querySelector('[data-options-list]');
            if (list.children.length < 10) list.insertAdjacentHTML('beforeend', optionRow({}, list.children.length));
        }
        if (event.target.closest('[data-remove-option]')) event.target.closest('[data-option-row]').remove();
        const button = event.target.closest('[data-action]');
        if (!button) return;
        const record = allRecords().find((item) => String(item.id) === button.dataset.id);
        if (!record) return;
        if (button.dataset.action === 'edit') { openEditor(record); return; }
        if (button.dataset.action === 'remove' && record.local) {
            drafts.delete(String(record.id));
            render();
            screen.querySelector('[data-status]').textContent = 'Borrador descartado. El registro del servidor no se modificó.';
            return;
        }
        screen.querySelector('[data-detail-title]').textContent = button.dataset.action === 'remove' ? 'Eliminación pendiente de conexión' : questions ? 'Detalle de la pregunta' : record.name;
        screen.querySelector('[data-detail-content]').innerHTML = button.dataset.action === 'remove'
            ? '<p>No se eliminará ningún registro desde esta interfaz. El servidor debe comprobar si la pregunta está vinculada a evaluaciones antes de permitir la eliminación.</p>'
            : questions ? `<p class="whitespace-pre-wrap break-words">${escape(record.text)}</p><div class="flex flex-wrap gap-2">${badge(questionTypes[record.type] ?? record.type)}${axisBadge(record)}${badge(difficulties[record.difficulty] ?? record.difficulty)}${stateBadge(record)}</div><p>Habilidad: ${escape(skillFor(record)?.name ?? 'No disponible')}</p>${draftLabel(record)}${record.type === 'self_report' ? '<p class="text-slate-500">Respuesta mediante escala Likert.</p>' : `<ol class="grid gap-2">${(record.options ?? []).map((option) => `<li class="rounded-lg bg-slate-50 p-3"><p class="break-words">${escape(option.label)}. ${escape(option.text)}</p><p class="mt-1 text-xs text-slate-500">${escape(option.score)} / 5 puntos</p></li>`).join('')}</ol>`}`
                : `<p class="whitespace-pre-wrap break-words">${escape(record.description || 'Sin descripción.')}</p><p>Área: ${escape(record.area?.name ?? 'No disponible')}</p><div class="flex flex-wrap gap-2">${axisBadge(record)}${stateBadge(record)}</div>${draftLabel(record)}<ul class="grid gap-2">${(record.skills ?? []).map((skill) => `<li class="rounded-lg bg-slate-50 p-3"><p class="break-words">${escape(skill.name)}</p><p class="mt-1 text-xs text-slate-500">Peso: ${escape(skill.pivot?.weight ?? '—')} · Nivel requerido: ${escape(skill.pivot?.required_level ?? '—')}</p></li>`).join('') || '<li>Sin habilidades asociadas.</li>'}</ul>`;
        detail.showModal();
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const data = new FormData(form);
        const original = allRecords().find((record) => record.id === editingId);
        const draft = { ...original, id: editingId ?? `local-${crypto.randomUUID()}`, local: true, is_active: data.has('is_active'), organizational_axis_id: data.get('organizational_axis_id') ? Number(data.get('organizational_axis_id')) : null };
        const fail = (message) => { screen.querySelector('[data-feedback]').textContent = message; };
        if (questions) {
            draft.text = data.get('text').trim();
            draft.type = data.get('type');
            draft.difficulty = data.get('difficulty');
            draft.skill_id = Number(data.get('skill_id'));
            draft.options = draft.type === 'self_report' ? [] : [...form.querySelectorAll('[data-option-row]')].map((row, index) => ({ label: String.fromCharCode(65 + index), text: row.querySelector('[data-option-text]').value.trim(), score: Number(row.querySelector('[data-option-score]').value), display_order: index + 1 }));
            if (!draft.text) return fail('Escribe el texto de la pregunta.');
            if (draft.type !== 'self_report' && (draft.options.length < 2 || draft.options.some((option) => !option.text))) return fail('Las preguntas situacionales requieren al menos dos opciones con texto y puntuación.');
        } else {
            draft.name = data.get('name').trim();
            if (!draft.name) return fail('Escribe el nombre del cargo.');
            draft.description = data.get('description').trim();
            draft.area_id = Number(data.get('area_id'));
            draft.area = areas.find((area) => area.id === draft.area_id);
            draft.organizational_axis = axes.find((axis) => axis.id === draft.organizational_axis_id);
            draft.skills = [...form.querySelectorAll('[data-position-skill]')].filter((row) => row.querySelector('input[type="checkbox"]').checked).map((row) => ({ ...skills.find((skill) => skill.id === Number(row.dataset.positionSkill)), pivot: { weight: Number(row.querySelector('[data-weight]').value), required_level: Number(row.querySelector('[data-level]').value) } }));
        }
        drafts.set(String(draft.id), draft);
        search.value = '';
        screen.querySelectorAll('[data-filter]').forEach((filter) => { filter.value = ''; });
        render();
        editor.close();
        screen.querySelector('[data-status]').textContent = 'Borrador aplicado solo a esta vista. No se enviaron datos al servidor.';
    });

    search.addEventListener('input', render);
    globalSearch.addEventListener('input', () => { if (location.hash === hash) { search.value = globalSearch.value; render(); } });
    screen.querySelector('[data-reload]').addEventListener('click', load);
    screen.querySelector('[data-add]').addEventListener('click', () => openEditor());
    screen.querySelector('[data-reset]').addEventListener('click', () => { search.value = ''; globalSearch.value = ''; screen.querySelectorAll('[data-filter]').forEach((filter) => { filter.value = ''; }); render(); });
    const activate = () => { if (location.hash === hash && !loaded) load(); };
    window.addEventListener('hashchange', activate);
    setFilters();
    activate();
}
