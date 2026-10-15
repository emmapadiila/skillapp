import { getAll } from './data/platform';
import { e, label, date, percent, identity, badge, statusBadge, axisBadge, button, link, empty, progress, select, input, textarea, enums, options, formErrors } from './workspace-ui';
import { resultContent } from './workspace-personal';
import { reportData } from './workspace-admin';

const resources = { colaboradores: 'employees', 'perfil-colaborador': 'employees', habilidades: 'skills', preguntas: 'questions', cargos: 'positions', evaluaciones: 'evaluations', planes: 'improvement-plans', diagnostico: 'diagnostics', areas: 'areas', empresas: 'companies' };
const names = { employees: 'colaborador', skills: 'habilidad', questions: 'pregunta', positions: 'cargo', evaluations: 'evaluación', 'improvement-plans': 'plan', diagnostics: 'diagnóstico', areas: 'área', companies: 'empresa' };
const activeField = (record) => `<label class="flex items-center gap-2 text-sm"><input name="is_active" type="checkbox" class="accent-blue-600" ${record.is_active !== false ? 'checked' : ''}>Activo</label>`;
const today = () => new Intl.DateTimeFormat('en-CA').format(new Date());
const sameCompany = (records, companyId, shared = false) => records.filter((r) => (!companyId || String(r.company_id) === String(companyId)) || (shared && r.company_id == null));

export async function openRecordForm(ctx, id = null) {
    const resource = resources[ctx.screen];
    if (!resource) return;
    const record = id ? await ctx.get(`${resource}/${id}`) : {};
    const required = { employees: ['positions', 'employees'], positions: ['areas', 'skills'], evaluations: ['employees', 'evaluations'], 'improvement-plans': ['employees', 'skills'] }[resource] ?? [];
    for (const key of required) ctx.data[key] ??= await getAll(key);
    const companyId = record.company_id ?? ctx.state.filters.company_id ?? ctx.user.company_id ?? '';
    const organization = ctx.superadmin && !['companies', 'evaluations', 'improvement-plans'].includes(resource)
        ? select('Empresa', 'company_id', ctx.records('companies'), companyId, resource !== 'skills') : '';
    const current = (key, shared = false) => sameCompany(ctx.records(key), companyId, shared);
    let fields = organization;
    switch (resource) {
        case 'employees':
            fields += input('Nombres', 'first_name', record.first_name, 'text', 'required maxlength="100"') + input('Apellidos', 'last_name', record.last_name, 'text', 'required maxlength="100"') + input('Correo electrónico', 'email', record.email, 'email', 'required maxlength="255"')
                + select('Rol', 'role', enums({ collaborator: 'Colaborador', human_resources: 'Talento humano' }), record.role ?? 'collaborator')
                + select('Cargo', 'position_id', current('positions'), record.position_id, false)
                + select('Responsable', 'manager_id', current('employees').filter((r) => r.id !== record.id), record.manager_id, false)
                + input('Fecha de ingreso', 'hire_date', record.hire_date?.slice(0, 10), 'date', `max="${today()}"`) + activeField(record);
            break;
        case 'skills':
            fields += input('Nombre de la habilidad', 'name', record.name, 'text', 'required maxlength="255"') + select('Categoría', 'skill_category_id', ctx.data.catalogs.skill_categories, record.skill_category_id)
                + textarea('Descripción', 'description', record.description, 'maxlength="5000"') + input('Nivel objetivo (1–5)', 'target_level', record.target_level ?? 3, 'number', 'required min="1" max="5"')
                + `<fieldset class="col-span-full"><legend class="mb-2 text-xs font-semibold text-slate-500">Ejes aplicables</legend><div class="flex flex-wrap gap-4">${ctx.data.catalogs.organizational_axes.map((a) => `<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="axis_ids" value="${a.id}" ${(record.axes ?? []).some((s) => s.id === a.id) ? 'checked' : ''}>${e(a.name)}</label>`).join('')}</div></fieldset>` + activeField(record);
            break;
        case 'questions':
            fields += textarea('Pregunta', 'text', record.text, 'required maxlength="5000"') + select('Tipo', 'type', enums({ self_report: 'Autorreporte', situational: 'Situacional', situational_judgment: 'Juicio situacional' }), record.type ?? 'self_report')
                + select('Habilidad', 'skill_id', current('skills', true), record.skill_id)
                + select('Eje (opcional)', 'organizational_axis_id', ctx.data.catalogs.organizational_axes, record.organizational_axis_id, false)
                + select('Nivel', 'difficulty', enums({ basic: 'Básico', intermediate: 'Intermedio', advanced: 'Avanzado' }), record.difficulty ?? 'basic') + activeField(record)
                + `<fieldset id="question-options" class="col-span-full space-y-3"><legend class="mb-2 text-xs font-semibold text-slate-500">Opciones de respuesta · puntuación de 0 a 5</legend><div id="option-list" class="space-y-3">${(record.options?.length ? record.options : [{}, {}]).map(optionRow).join('')}</div><button id="add-option" type="button" class="ws-button">+ Añadir opción</button></fieldset><p id="likert-note" class="col-span-full text-xs text-slate-500">El autorreporte utiliza la escala Likert de 1 a 5.</p>`;
            break;
        case 'positions':
            fields += input('Nombre del cargo', 'name', record.name, 'text', 'required maxlength="255"') + select('Área', 'area_id', current('areas'), record.area_id) + select('Eje organizacional', 'organizational_axis_id', ctx.data.catalogs.organizational_axes, record.organizational_axis_id)
                + textarea('Descripción', 'description', record.description, 'maxlength="5000"') + activeField(record)
                + `<fieldset class="col-span-full"><legend class="mb-2 text-xs font-semibold text-slate-500">Habilidades asociadas · peso y nivel requerido</legend><div id="position-skills" class="space-y-3">${current('skills', true).map((s) => positionSkill(s, record)).join('')}</div></fieldset>`;
            break;
        case 'evaluations':
            if (id) {
                const due = new Date(record.due_at);
                const localDue = new Date(due.getTime() - due.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
                fields += input('Fecha límite', 'due_at', localDue, 'datetime-local', 'required'); break;
            }
            fields += select('Colaborador', 'user_id', ctx.records('employees').filter((u) => u.is_active && u.role === 'collaborator'))
                + select('Tipo', 'type', enums({ initial: 'Inicial', reevaluation: 'Reevaluación' }), 'initial')
                + select('Prueba de habilidades (opcional)', 'assessment_id', (ctx.records('assessments') ?? []).map((a) => ({ id: a.id, name: `${a.name} · ${a.axis?.name ?? ''}` })), '', false)
                + input('Número de preguntas', 'question_count', 20, 'number', 'min="1" max="200"')
                + input('Fecha límite', 'due_at', '', 'datetime-local', 'required')
                + select('Evaluación anterior (para reevaluación)', 'parent_evaluation_id', ctx.records('evaluations').filter((ev) => ev.status === 'completed').map((ev) => ({ id: ev.id, name: `${ev.user?.name} · ${date(ev.completed_at)}`, user_id: ev.user_id })), '', false)
                + '<p class="col-span-full text-xs text-slate-500">Si eliges una prueba, se aplican sus 9 preguntas. Si no, las preguntas se seleccionan según las habilidades del cargo del colaborador.</p>';
            break;
        case 'improvement-plans':
            fields += select('Colaborador', 'user_id', ctx.records('employees').filter((u) => u.is_active && u.role === 'collaborator'), record.user_id)
                + select('Habilidad objetivo', 'skill_id', ctx.records('skills'), record.skill_id)
                + input('Nivel inicial', 'initial_level', record.initial_level, 'text', 'required maxlength="50"') + input('Nivel objetivo', 'target_level', record.target_level, 'text', 'required maxlength="50"')
                + select('Estado', 'status', enums({ pending: 'Pendiente', in_progress: 'En progreso', completed: 'Completado', cancelled: 'Cancelado' }), record.status ?? 'pending')
                + input('Inicio', 'start_date', record.start_date?.slice(0, 10) ?? today(), 'date', 'required') + input('Fecha límite', 'due_date', record.due_date?.slice(0, 10), 'date')
                + input('Reevaluación', 'reevaluation_date', record.reevaluation_date?.slice(0, 10), 'date', 'required')
                + textarea('Recomendación registrada (opcional)', 'ai_recommendation', record.ai_recommendation, 'maxlength="10000"');
            if (!id) fields += `<fieldset class="col-span-full"><legend class="mb-2 text-xs font-semibold text-slate-500">Actividades</legend><div id="activity-list" class="space-y-3">${activityRow()}</div><button id="add-activity" type="button" class="ws-button mt-3">+ Añadir actividad</button></fieldset><fieldset class="col-span-full"><legend class="mb-2 text-xs font-semibold text-slate-500">Recursos de aprendizaje</legend><div id="resource-list" class="space-y-3"></div><button id="add-resource" type="button" class="ws-button">+ Añadir recurso</button></fieldset>`;
            else fields += '<p class="col-span-full text-xs text-slate-500">Se conservan las actividades, recursos y progreso de este plan.</p>';
            break;
        case 'diagnostics':
            fields += input('Inicio del período', 'period_start', '', 'date', `required max="${today()}"`) + input('Fin del período', 'period_end', '', 'date', `required max="${today()}"`)
                + '<p class="col-span-full text-xs text-slate-500">Se utilizarán las evaluaciones completadas en el período. El diagnóstico cerrado es inmutable.</p>';
            break;
        case 'areas': fields += input('Nombre', 'name', record.name, 'text', 'required maxlength="255"') + input('Código', 'code', record.code, 'text', 'maxlength="50"') + activeField(record); break;
        case 'companies': fields += input('Nombre', 'name', record.name, 'text', 'required maxlength="255"') + input('NIT', 'nit', record.nit, 'text', 'required maxlength="100"') + input('Sector', 'sector', record.sector, 'text', 'maxlength="255"') + activeField(record); break;
    }
    ctx.modal(`${id ? 'Editar' : 'Nuevo'} ${names[resource]}`, `<form id="record-form" class="space-y-4"><div class="grid gap-4 sm:grid-cols-2">${fields}</div><div id="record-feedback" role="alert"></div><div class="flex justify-end gap-3"><button type="button" data-dialog-close class="ws-button">Cancelar</button><button type="submit" class="ws-primary">${resource === 'diagnostics' ? 'Generar diagnóstico' : 'Guardar'}</button></div></form>`);
    const form = document.querySelector('#record-form');
    const toggleOptions = () => {
        if (resource !== 'questions') return;
        const show = form.elements.type.value !== 'self_report';
        document.querySelector('#question-options').hidden = !show;
        document.querySelector('#likert-note').hidden = show;
        document.querySelectorAll('#question-options input').forEach((el) => { el.disabled = !show; });
    };
    toggleOptions();
    const tenantInputs = () => {
        const selectedCompany = form.elements.company_id?.value ?? companyId;
        for (const [field, key, shared] of [['position_id', 'positions', false], ['manager_id', 'employees', false], ['area_id', 'areas', false], ['skill_id', 'skills', true]]) {
            const element = form.elements[field];
            if (element && !['evaluations', 'improvement-plans'].includes(resource)) {
                const value = element.value;
                element.innerHTML = options(sameCompany(ctx.records(key), selectedCompany, shared).filter((r) => field !== 'manager_id' || r.id !== record.id), value);
            }
        }
        if (resource === 'positions') document.querySelector('#position-skills').innerHTML = sameCompany(ctx.records('skills'), selectedCompany, true).map((s) => positionSkill(s, record)).join('');
        if (resource === 'improvement-plans') {
            const user = ctx.find('employees', form.elements.user_id.value);
            form.elements.skill_id.innerHTML = options(sameCompany(ctx.records('skills'), user?.company_id, true), form.elements.skill_id.value);
        }
        if (resource === 'evaluations' && !id) {
            const userId = Number(form.elements.user_id.value);
            form.elements.parent_evaluation_id.innerHTML = options(ctx.records('evaluations').filter((ev) => ev.status === 'completed' && ev.user_id === userId).map((ev) => ({ id: ev.id, name: date(ev.completed_at) })), form.elements.parent_evaluation_id.value);
            form.elements.parent_evaluation_id.disabled = form.elements.type.value !== 'reevaluation';
            form.elements.parent_evaluation_id.required = form.elements.type.value === 'reevaluation';
            const assessment = form.elements.assessment_id;
            const questionCount = form.elements.question_count;
            if (assessment && questionCount) {
                questionCount.disabled = Boolean(assessment.value);
                questionCount.required = !assessment.value;
            }
        }
    };
    if (['improvement-plans', 'evaluations'].includes(resource)) tenantInputs();
    form.addEventListener('change', (event) => {
        if (event.target.name === 'type') { toggleOptions(); if (resource === 'evaluations') tenantInputs(); }
        if (['company_id', 'user_id', 'assessment_id'].includes(event.target.name)) tenantInputs();
        const row = event.target.closest('[data-position-skill]');
        if (row && event.target.type === 'checkbox') row.querySelectorAll('input[type="number"]').forEach((el) => { el.disabled = !event.target.checked; });
    });
    form.addEventListener('click', (event) => {
        if (event.target.closest('[data-remove-row]')) event.target.closest('[data-editor-row]').remove();
        if (event.target.closest('#add-option') && document.querySelector('#option-list').children.length < 10) document.querySelector('#option-list').insertAdjacentHTML('beforeend', optionRow({}, document.querySelector('#option-list').children.length));
        if (event.target.closest('#add-activity') && document.querySelector('#activity-list').children.length < 100) document.querySelector('#activity-list').insertAdjacentHTML('beforeend', activityRow());
        if (event.target.closest('#add-resource') && document.querySelector('#resource-list').children.length < 100) document.querySelector('#resource-list').insertAdjacentHTML('beforeend', resourceRow());
    });
    let busy = false;
    form.addEventListener('submit', async (event) => {
        event.preventDefault(); event.stopPropagation(); if (busy) return;
        const data = Object.fromEntries(new FormData(form));
        delete data[''];
        for (const key of ['company_id', 'skill_category_id', 'position_id', 'manager_id', 'skill_id', 'area_id', 'organizational_axis_id', 'user_id', 'parent_evaluation_id', 'target_level', 'question_count', 'assessment_id']) {
            if (key in data) data[key] = data[key] ? Number(data[key]) : null;
        }
        if (resource === 'evaluations' && !id && data.assessment_id) delete data.question_count;
        for (const key of ['hire_date', 'due_date', 'description', 'ai_recommendation']) if (key in data && !data[key]) data[key] = null;
        if (['employees', 'skills', 'questions', 'positions', 'areas', 'companies'].includes(resource)) data.is_active = new FormData(form).has('is_active');
        if ('due_at' in data) data.due_at = new Date(data.due_at).toISOString();
        if (resource === 'skills') data.axis_ids = new FormData(form).getAll('axis_ids').map(Number);
        if (resource === 'questions') data.options = data.type === 'self_report' ? [] : [...form.querySelectorAll('[data-option]')].map((row, i) => ({ label: String.fromCharCode(65 + i), text: row.querySelector('[data-option-text]').value, score: Number(row.querySelector('[data-option-score]').value), display_order: i + 1 }));
        if (resource === 'positions') data.skills = [...form.querySelectorAll('[data-position-skill]')].filter((row) => row.querySelector('[type="checkbox"]').checked).map((row) => ({ id: Number(row.dataset.positionSkill), weight: Number(row.querySelector('[data-weight]').value), required_level: Number(row.querySelector('[data-level]').value) }));
        if (resource === 'improvement-plans' && !id) {
            data.activities = [...form.querySelectorAll('[data-activity]')].map((row, i) => ({ title: row.querySelector('[data-title]').value, description: row.querySelector('[data-description]').value, duration_minutes: row.querySelector('[data-duration]').value ? Number(row.querySelector('[data-duration]').value) : null, display_order: i + 1 }));
            data.resources = [...form.querySelectorAll('[data-resource]')].map((row) => ({ title: row.querySelector('[data-title]').value, type: row.querySelector('[data-type]').value, url: row.querySelector('[data-url]').value || null, description: row.querySelector('[data-description]').value || null, recommended_by_ai: false }));
        }
        busy = true; const submit = form.querySelector('[type="submit"]'); submit.disabled = true;
        const feedback = document.querySelector('#record-feedback'); feedback.innerHTML = '<p class="text-sm text-blue-600">Guardando…</p>';
        try {
            await ctx.request(`${resource}${id ? `/${id}` : ''}`, { method: id ? 'PUT' : 'POST', data });
            ctx.close(); ctx.toast('Registro guardado correctamente.'); await ctx.reload();
        } catch (error) {
            feedback.innerHTML = formErrors(error);
            const field = Object.keys(error.fields ?? {})[0]?.split('.')[0]; form.elements[field]?.focus?.();
        } finally { busy = false; submit.disabled = false; }
    });
}

function optionRow(record = {}, index = 0) {
    return `<div data-editor-row data-option class="grid grid-cols-[minmax(0,1fr)_5rem_auto] items-end gap-2">${input(`Opción ${index + 1}`, '', record.text, 'text', 'data-option-text required maxlength="2000"')}${input('Puntos', '', record.score ?? '', 'number', 'data-option-score required min="0" max="5" step="0.01"')}<button data-remove-row type="button" class="ws-button" aria-label="Quitar opción">×</button></div>`;
}

function positionSkill(skill, record) {
    const selected = record.skills?.find((s) => s.id === skill.id);
    return `<div data-position-skill="${skill.id}" class="grid grid-cols-2 items-end gap-2 rounded-lg bg-slate-50 p-3 sm:grid-cols-[minmax(0,1fr)_5rem_5rem]"><label class="col-span-2 flex items-center gap-2 text-sm sm:col-span-1"><input type="checkbox" ${selected ? 'checked' : ''}>${e(skill.name)}</label>${input('Peso', '', selected?.pivot?.weight ?? '', 'number', `data-weight required min="0.01" max="100" step="0.01" ${selected ? '' : 'disabled'}`)}${input('Nivel', '', selected?.pivot?.required_level ?? '', 'number', `data-level required min="1" max="5" ${selected ? '' : 'disabled'}`)}</div>`;
}

function activityRow() {
    return `<div data-editor-row data-activity class="grid gap-3 rounded-xl border border-slate-100 p-4">${input('Título', '', '', 'text', 'data-title required maxlength="255"')}${textarea('Descripción', '', '', 'data-description required maxlength="5000"')}${input('Duración (minutos)', '', '', 'number', 'data-duration min="1" max="10080"')}<button type="button" data-remove-row class="ws-button justify-self-end">Quitar actividad</button></div>`;
}

function resourceRow() {
    return `<div data-editor-row data-resource class="grid gap-3 rounded-xl border border-slate-100 p-4">${input('Título', '', '', 'text', 'data-title required maxlength="255"')}<label class="ws-field"><span>Tipo</span><select data-type required>${options(enums({ reading: 'Lectura', video: 'Video', exercise: 'Ejercicio' }))}</select></label>${input('Enlace HTTPS', '', '', 'url', 'data-url pattern="https://.*" maxlength="2048"')}${textarea('Descripción', '', '', 'data-description maxlength="5000"')}<button data-remove-row type="button" class="ws-button justify-self-end">Quitar recurso</button></div>`;
}

export async function recordAction(ctx, action, id) {
    if (action === 'read' || action === 'read-all') {
        const records = action === 'read' ? [ctx.find('notifications', id)] : ctx.records('notifications').filter((n) => !n.read_at);
        for (const n of records.filter(Boolean)) await ctx.request(`notifications/${n.id}/read`, { method: 'PATCH' });
        await ctx.reload(); return;
    }
    if (action === 'print-report') { window.print(); return; }
    if (action === 'export-report') {
        const csvCell = (value) => `"${String(value ?? '').replace(/^[=+@\-\t\r]/, "'$&").replaceAll('"', '""')}"`;
        const report = reportData(ctx);
        const rows = [report.headers, ...report.rows];
        const blob = new Blob(['\uFEFF' + rows.map((row) => row.map(csvCell).join(';')).join('\r\n')], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = 'reporte-evaluaciones.csv'; a.click(); setTimeout(() => URL.revokeObjectURL(url), 1000); return;
    }
    if (action === 'analysis') {
        const ev = ctx.data.personalResults?.find((r) => String(r.id) === String(id)) ?? await ctx.get(`evaluations/${id}`); const result = ev.result;
        ctx.modal('Análisis del resultado', `<div class="space-y-4"><p class="rounded-xl bg-slate-50 p-4 text-sm leading-6">${e(result?.ai_analysis ?? 'No hay un análisis IA registrado para esta evaluación.')}</p><p class="text-sm">Resultado: <strong>${e(result?.total_score)} / 5 (${percent(result?.total_score)}%)</strong></p><p class="text-xs text-slate-500">La puntuación es el promedio de las respuestas registradas. El porcentaje muestra su equivalencia sobre 100.</p><p class="text-sm">Nivel: ${statusBadge(result?.level)}</p><p class="text-sm">Confianza del análisis: ${result?.model_confidence == null ? 'No disponible' : `${Math.round(Number(result.model_confidence) * 100)}%`}</p></div>`); return;
    }
    if (action === 'cell') {
        const cell = ctx.data.diagnostic?.matrix_cells?.find((c) => String(c.id) === String(id));
        if (!cell) return;
        ctx.modal(`${ctx.axis(cell.organizational_axis_id)?.name} + ${ctx.category(cell.skill_category_id)?.name}`, `<div class="space-y-4"><strong class="text-3xl">${percent(cell.average_score)}%</strong><p class="text-sm">${e(cell.average_score)} / 5 · ${cell.evaluated_count} colaboradores evaluados</p>${statusBadge(cell.level)}<p class="text-xs text-slate-500">Resultado del período ${date(ctx.data.diagnostic.period_start, true)} – ${date(ctx.data.diagnostic.period_end, true)}.</p>${link('Ver planes de mejoramiento', 'planes', '', true)}</div>`); return;
    }
    if (action === 'plan-detail') {
        const plan = await ctx.get(`improvement-plans/${id}`);
        await showPlan(ctx, plan); return;
    }
    if (action === 'employee-evaluation') { const ev = await ctx.get(`evaluations/${id}`); ctx.modal('Resultado del colaborador', resultContent(ctx, ev)); return; }
    const resource = resources[ctx.screen];
    if (!resource) return;
    if (action === 'invite') {
        ctx.modal('Invitar al colaborador', `<p class="mb-5 text-sm text-slate-500">Se enviará una invitación a ${e(ctx.find('employees', id)?.email)}.</p><button id="confirm-invite" class="ws-primary">Enviar invitación</button><div id="invite-feedback" class="mt-3" role="alert"></div>`);
        document.querySelector('#confirm-invite').addEventListener('click', async (event) => {
            const el = event.currentTarget; el.disabled = true;
            try { await ctx.request(`employees/${id}/invite`, { method: 'POST' }); ctx.close(); ctx.toast('Invitación enviada.'); await ctx.reload(); }
            catch (error) { document.querySelector('#invite-feedback').innerHTML = formErrors(error); }
            finally { el.disabled = false; }
        }); return;
    }
    if (action === 'delete') {
        ctx.modal(resource === 'employees' ? 'Desactivar colaborador' : `Eliminar ${names[resource]}`, `<p class="mb-5 text-sm text-slate-500">¿Deseas ${resource === 'employees' ? 'desactivar esta cuenta' : 'eliminar este registro'}? El servidor comprobará si la operación está permitida.</p><div id="delete-feedback" role="alert"></div><div class="flex justify-end gap-3"><button data-dialog-close class="ws-button">Cancelar</button><button id="confirm-delete" class="ws-primary bg-red-600 hover:bg-red-700">Confirmar</button></div>`);
        document.querySelector('#confirm-delete').addEventListener('click', async (event) => {
            const el = event.currentTarget; el.disabled = true;
            try { await ctx.request(`${resource}/${id}`, { method: 'DELETE' }); ctx.close(); ctx.toast('Operación completada.'); await ctx.reload(); }
            catch (error) { document.querySelector('#delete-feedback').innerHTML = formErrors(error); }
            finally { el.disabled = false; }
        }); return;
    }
    if (action === 'detail') {
        const record = await ctx.get(`${resource}/${id}`);
        let content;
        if (resource === 'questions') content = `<p class="mb-4 whitespace-pre-wrap text-sm">${e(record.text)}</p><div class="mb-4 flex flex-wrap gap-2">${badge(record.type)}${badge(record.difficulty)}${axisBadge(ctx.axis(record.organizational_axis_id))}</div>${record.type === 'self_report' ? '<p class="text-sm text-slate-500">Respuesta mediante escala Likert de 1 a 5.</p>' : `<ol class="space-y-2">${record.options.map((o) => `<li class="rounded-lg bg-slate-50 p-3 text-sm"><strong>${e(o.label)}.</strong> ${e(o.text)}<p class="mt-1 text-xs text-slate-400">${e(o.score)} / 5 puntos</p></li>`).join('')}</ol>`}`;
        else if (resource === 'evaluations') content = `<div class="mb-4 flex flex-wrap gap-2">${statusBadge(record.status)}${badge(record.type)}${record.assessment ? badge(record.assessment.name, 'violet') : ''}${badge(`${record.question_count} preguntas`)}</div>${record.assessment?.description ? `<p class="mb-4 rounded-xl bg-blue-50 p-4 text-xs leading-5 text-blue-900">${e(record.assessment.description)}</p>` : ''}${record.result ? resultContent(ctx, record) : `<p class="text-sm text-slate-500">Fecha límite: ${date(record.due_at)}</p><ol class="mt-4 list-inside list-decimal space-y-3 text-sm">${record.questions.map((q) => `<li>${e(q.question.text)}</li>`).join('')}</ol>`}`;
        else if (resource === 'positions') content = `<p class="mb-3 text-sm text-slate-500">${e(record.description ?? 'Sin descripción')}</p><div class="mb-4">${axisBadge(ctx.axis(record.organizational_axis_id))}</div><ul class="space-y-2">${record.skills.map((s) => `<li class="rounded-lg bg-slate-50 p-3 text-sm"><strong>${e(s.name)}</strong><p class="mt-1 text-xs text-slate-500">Peso: ${e(s.pivot.weight)} · Nivel requerido: ${e(s.pivot.required_level)}</p></li>`).join('') || '<li class="text-sm text-slate-400">Sin habilidades asociadas.</li>'}</ul>`;
        else if (resource === 'skills') content = `<p class="text-sm text-slate-500">${e(record.description ?? 'Sin descripción')}</p><p class="my-3 text-sm">Categoría: ${e(record.category?.name)} · Nivel objetivo: ${record.target_level ?? '—'}</p><div class="flex flex-wrap gap-2">${record.axes.map(axisBadge).join('')}</div>`;
        else content = `<dl class="space-y-3 text-sm">${Object.entries({ Nombre: record.name, Código: record.code, NIT: record.nit, Sector: record.sector, Estado: record.is_active ? 'Activo' : 'Inactivo' }).filter(([, value]) => value != null).map(([key, value]) => `<div><dt class="text-xs text-slate-400">${key}</dt><dd>${e(value)}</dd></div>`).join('')}</dl>`;
        ctx.modal(record.name ?? `Detalle de ${names[resource]}`, content);
    }
}

async function showPlan(ctx, plan) {
    const safeLink = (url) => { try { const parsed = new URL(url); return parsed.protocol === 'https:' ? parsed.href : null; } catch { return null; } };
    ctx.modal(plan.skill?.name ?? 'Plan de mejoramiento', `<div class="space-y-5"><div class="flex flex-wrap items-center justify-between gap-3">${statusBadge(plan.status)}<span class="text-xs text-slate-400">Reevaluación: ${date(plan.reevaluation_date, true)}</span></div>${progress(plan.progress)}<p class="text-sm text-slate-500">${e(plan.ai_recommendation ?? 'Sin recomendación registrada.')}</p><h3 class="font-bold">Actividades del plan</h3><div class="space-y-3">${plan.activities.map((activity) => `<article class="rounded-xl border border-slate-100 p-4"><div class="flex flex-wrap items-center justify-between gap-3"><strong class="text-sm">${e(activity.title)}</strong>${statusBadge(activity.status)}</div><p class="mt-2 text-sm text-slate-500">${e(activity.description)}</p><p class="mt-2 text-xs text-slate-400">${activity.duration_minutes ? `${activity.duration_minutes} minutos` : 'Sin duración registrada'}</p>${activity.status !== 'completed' && plan.user_id === ctx.user.id ? `<button data-complete-activity="${activity.id}" class="ws-primary mt-3">Marcar como completada</button>` : ''}</article>`).join('') || empty('No hay actividades registradas')}</div><h3 class="font-bold">Recursos recomendados</h3><div class="space-y-3">${plan.resources.map((r) => `<article class="rounded-xl bg-slate-50 p-4"><div class="mb-2">${badge(r.type, 'blue')}</div><strong class="text-sm">${e(r.title)}</strong><p class="mt-2 text-sm text-slate-500">${e(r.description ?? '')}</p>${safeLink(r.url) ? `<a href="${e(safeLink(r.url))}" target="_blank" rel="noopener noreferrer" class="ws-button mt-3">Abrir recurso ↗</a>` : ''}</article>`).join('') || '<p class="text-sm text-slate-400">Sin recursos registrados.</p>'}</div><div id="activity-feedback" role="alert"></div></div>`);
    document.querySelectorAll('[data-complete-activity]').forEach((el) => el.addEventListener('click', async () => {
        el.disabled = true;
        try { await ctx.request(`improvement-plans/${plan.id}/activities/${el.dataset.completeActivity}/complete`, { method: 'POST' }); const updated = await ctx.get(`improvement-plans/${plan.id}`); await showPlan(ctx, updated); ctx.toast('Actividad completada.'); await ctx.reload(); }
        catch (error) { document.querySelector('#activity-feedback').innerHTML = formErrors(error); el.disabled = false; }
    }));
}
