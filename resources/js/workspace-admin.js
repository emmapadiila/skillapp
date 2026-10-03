import { e, label, date, average, percent, color, identity, badge, statusBadge, axisBadge, button, link, empty, panel, progress, kpis, bars, lineChart, table, filter, enums, toolbar } from './workspace-ui';

export const companyRecords = (ctx, resource) => ctx.records(resource).filter((r) => !ctx.state.filters.company_id || String(r.company_id) === String(ctx.state.filters.company_id));
const active = (r) => badge(r.is_active ? 'Activo' : 'Inactivo', r.is_active ? 'green' : 'slate');
const actions = (r, view = 'detail', canEdit = true) => `<div class="flex flex-wrap gap-1">${button('Ver', view, r.id)}${canEdit ? button('Editar', 'edit', r.id) : ''}${canEdit ? button('Eliminar', 'delete', r.id) : ''}</div>`;
const selected = (ctx, key, value) => !ctx.state.filters[key] || String(value) === String(ctx.state.filters[key]);
const byState = (ctx) => filter('status', 'Todos los estados', enums({ pending: 'Pendiente', in_progress: 'En progreso', completed: 'Completado', expired: 'Vencido', cancelled: 'Cancelado' }), ctx.state.filters.status);
const f = (ctx, key, title, records) => filter(key, title, records, ctx.state.filters[key]);
const companyFilter = (ctx) => ctx.superadmin ? f(ctx, 'company_id', 'Todas las empresas', ctx.records('companies')) : '';
const scopeBar = (ctx, extra = '') => toolbar(companyFilter(ctx) + extra);
const positionFor = (ctx, user) => ctx.find('positions', user?.position_id) ?? user?.position;
const latest = (ctx, userId) => ctx.records('evaluations').find((ev) => ev.user_id === userId);
const state = (ctx, records, columns, rows) => table(columns, ctx.sorted(ctx.filtered(records).map(rows)), ctx.state);
const matrix = (ctx) => ctx.data.diagnostic?.matrix_cells ?? [];
const axisSeries = (ctx) => (ctx.data.catalogs?.organizational_axes ?? []).map((axis) => ({ name: axis.name, color: { operational: '#3B82F6', mission: '#22C55E', strategic: '#8B5CF6' }[axis.code] ?? '#3B82F6', value: percent(average(matrix(ctx).filter((c) => c.organizational_axis_id === axis.id).map((c) => c.average_score))), axis }));
const categorySeries = (ctx) => (ctx.data.catalogs?.skill_categories ?? []).map((category) => ({ name: category.name, value: percent(average(matrix(ctx).filter((c) => c.skill_category_id === category.id).map((c) => c.average_score))) }));
const diagnosticFilters = (ctx) => scopeBar(ctx, f(ctx, 'diagnostic_id', 'Último diagnóstico', companyRecords(ctx, 'diagnostics').map((d) => ({ id: d.id, name: `${date(d.period_start, true)} – ${date(d.period_end, true)}` })))) + `<p class="text-xs text-slate-400">${ctx.data.diagnostic ? `Período: ${date(ctx.data.diagnostic.period_start, true)} – ${date(ctx.data.diagnostic.period_end, true)}` : 'No hay diagnóstico guardado para este período.'}</p>`;

export function renderAdministration(ctx) {
    switch (ctx.screen) {
        case 'dashboard': return dashboard(ctx);
        case 'colaboradores': return collaborators(ctx);
        case 'habilidades': return skills(ctx);
        case 'preguntas': return questions(ctx);
        case 'cargos': return positions(ctx);
        case 'evaluaciones': return evaluations(ctx);
        case 'planes': return plans(ctx);
        case 'diagnostico': return diagnostics(ctx);
        case 'mapa-calor': return diagnosticFilters(ctx) + heatmap(ctx);
        case 'brechas': return gaps(ctx);
        case 'recomendaciones': return recommendations(ctx);
        case 'seguimiento': return tracking(ctx);
        case 'reportes': return reports(ctx);
        case 'perfil-colaborador': return employeeProfile(ctx);
        case 'areas': return toolbar(companyFilter(ctx), button('+ Nueva área', 'create', '', true)) + state(ctx, companyRecords(ctx, 'areas'), ['Área', 'Código', 'Cargos', 'Estado', 'Acciones'], (r) => [e(r.name), e(r.code ?? '—'), e(r.positions_count), active(r), actions(r)]);
        case 'empresas': return toolbar('', button('+ Nueva empresa', 'create', '', true)) + state(ctx, ctx.records('companies'), ['Empresa', 'NIT', 'Sector', 'Colaboradores', 'Estado', 'Acciones'], (r) => [e(r.name), e(r.nit), e(r.sector ?? '—'), e(r.users_count), active(r), actions(r)]);
        default: return empty('Pantalla no disponible');
    }
}

function dashboard(ctx) {
    const employees = companyRecords(ctx, 'employees');
    const evaluations = companyRecords(ctx, 'evaluations');
    const completed = evaluations.filter((ev) => ev.status === 'completed');
    const plans = companyRecords(ctx, 'improvement-plans');
    const evaluated = new Set(completed.map((ev) => ev.user_id)).size;
    const mean = percent(average(completed.filter((ev) => ev.result?.total_score != null).map((ev) => ev.result.total_score)));
    const critical = (ctx.data.diagnostic?.gaps ?? []).filter((gap) => gap.priority === 'critical').length;
    const axes = axisSeries(ctx);
    return `<div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-500">Vista general del estado de las habilidades blandas de la organización.</p>${companyFilter(ctx)}</div>`
        + kpis([['Colaboradores', employees.length, 'users', 'slate'], ['Evaluados', evaluated, 'clipboard', 'green', employees.length ? `${Math.round(evaluated / employees.length * 100)}% del total` : ''], ['Resultado promedio', mean == null ? '—' : `${mean}%`, 'chart', 'blue', 'Puntuación original sobre 5'], ['Brechas críticas', ctx.data.diagnostic ? critical : '—', 'warning', 'red'], ['Planes activos', plans.filter((p) => ['pending', 'in_progress'].includes(p.status)).length, 'target', 'amber'], ['Planes completados', plans.filter((p) => p.status === 'completed').length, 'target', 'green']])
        + `<div class="grid gap-5 lg:grid-cols-3"><div class="lg:col-span-2">${panel('Resultados por eje organizacional', bars(axes, true) + `<div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">${categorySeries(ctx).map((c) => `<div class="text-center"><p class="mb-2 text-xs text-slate-400">${e(c.name)}</p>${progress(c.value)}</div>`).join('')}</div>`, link('Ver diagnóstico', 'diagnostico'))}</div>${panel('Estado de colaboradores', distribution(ctx, employees, evaluations))}</div>`
        + axisCards(ctx, axes)
        + panel('Evaluaciones recientes', completed.length ? `<div class="divide-y divide-slate-50">${completed.slice(0, 5).map((ev) => `<div class="flex flex-wrap items-center gap-4 py-3"><div class="min-w-0 flex-1">${identity(ev.user)}</div>${statusBadge(ev.status)}<strong class="text-sm">${percent(ev.result?.total_score) ?? '—'}%</strong>${link('Ver perfil', 'perfil-colaborador', ev.user_id)}</div>`).join('')}</div>` : empty('No hay evaluaciones completadas'), link('Ver todas', 'evaluaciones'));
}

function distribution(ctx, employees, evaluations) {
    if (!employees.length) return empty('No hay colaboradores registrados');
    const statuses = employees.map((user) => evaluations.find((ev) => ev.user_id === user.id)?.status ?? 'pending');
    const slices = [['completed', 'Evaluados', '#22C55E'], ['pending', 'Pendientes', '#F59E0B'], ['in_progress', 'En evaluación', '#3B82F6'], ['expired', 'Vencidos', '#EF4444'], ['cancelled', 'Cancelados', '#94A3B8']].map(([key, name, tone]) => ({ name, tone, count: statuses.filter((s) => s === key).length }));
    let offset = 0;
    const gradient = slices.map((s) => { const from = offset; offset += s.count / employees.length * 100; return `${s.tone} ${from}% ${offset}%`; }).join(',');
    return `<div class="mx-auto my-4 grid size-36 place-items-center rounded-full" style="background:conic-gradient(${gradient})" role="img" aria-label="Estado de colaboradores"><div class="grid size-24 place-items-center rounded-full bg-white"><strong class="text-2xl">${employees.length}</strong></div></div><div class="space-y-2">${slices.map((s) => `<div class="flex justify-between text-xs"><span class="flex items-center gap-2"><i class="size-2.5 rounded-full" style="background:${s.tone}"></i>${s.name}</span><strong>${s.count}</strong></div>`).join('')}</div>`;
}

function axisCards(ctx, axes = axisSeries(ctx)) {
    return `<div class="grid gap-4 md:grid-cols-3">${axes.map((item) => {
        const gap = ctx.data.diagnostic?.gaps?.find((g) => g.organizational_axis_id === item.axis.id);
        return panel(item.name, `<div class="mb-3 flex items-center justify-between">${axisBadge(item.axis)}<strong class="text-3xl" style="color:${item.color}">${item.value == null ? '—' : `${item.value}%`}</strong></div>${progress(item.value, item.color)}<p class="mt-4 text-xs text-slate-500">${gap ? `Brecha: ${e(ctx.find('skills', gap.skill_id)?.name ?? 'Habilidad no disponible')}` : 'No hay brechas registradas en este eje.'}</p><div class="mt-4">${link('Ver diagnóstico →', 'mapa-calor')}</div>`);
    }).join('')}</div>`;
}

function collaborators(ctx) {
    const employees = ctx.filtered(companyRecords(ctx, 'employees'), (r) => `${r.name} ${r.email} ${positionFor(ctx, r)?.name ?? ''} ${positionFor(ctx, r)?.area?.name ?? ''}`, (r) => {
        const pos = positionFor(ctx, r);
        return selected(ctx, 'axis_id', pos?.organizational_axis_id) && selected(ctx, 'area_id', pos?.area_id) && selected(ctx, 'status', latest(ctx, r.id)?.status ?? 'pending');
    });
    const rows = employees.map((r) => { const pos = positionFor(ctx, r); const ev = latest(ctx, r.id); const score = percent(ev?.result?.total_score); return [identity(r), e(pos?.name ?? 'Sin cargo'), e(pos?.area?.name ?? ctx.find('areas', pos?.area_id)?.name ?? 'Sin área'), axisBadge(ctx.axis(pos?.organizational_axis_id)), score == null ? '<span class="text-xs italic text-slate-400">Sin evaluar</span>' : progress(score, color(score)), statusBadge(ev?.status ?? 'pending'), `<div class="flex flex-wrap gap-1">${link('Ver perfil', 'perfil-colaborador', r.id)}${button('Editar', 'edit', r.id)}</div>`]; });
    return toolbar(companyFilter(ctx) + f(ctx, 'axis_id', 'Todos los ejes', ctx.data.catalogs.organizational_axes) + f(ctx, 'area_id', 'Todas las áreas', ctx.records('areas')) + byState(ctx), button('+ Agregar', 'create', '', true), `${employees.length} colaboradores · ${employees.filter((u) => latest(ctx, u.id)?.status === 'completed').length} evaluados`)
        + table(['Nombre', 'Cargo', 'Área', 'Eje', 'Resultado', 'Estado', 'Acciones'], ctx.sorted(rows), ctx.state, 'Colaboradores');
}

function skills(ctx) {
    const records = ctx.filtered(companyRecords(ctx, 'skills'));
    return `<div class="mx-auto max-w-3xl space-y-4">${toolbar(companyFilter(ctx), button('+ Nueva habilidad', 'create', '', true), `${records.length} habilidades en ${ctx.data.catalogs.skill_categories.length} categorías`)}${ctx.data.catalogs.skill_categories.map((category, i) => {
        const rows = records.filter((r) => r.skill_category_id === category.id);
        return `<details class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm" ${i === 0 ? 'open' : ''}><summary class="flex cursor-pointer items-center gap-4 p-5"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-600 text-white">${e(category.name[0])}</span><span class="flex-1"><strong class="block">${e(category.name)}</strong><small class="text-slate-400">${rows.length} habilidades</small></span><span>⌄</span></summary><div class="divide-y divide-slate-50 border-t border-slate-100">${rows.map((skill) => `<div class="flex flex-wrap items-center gap-3 px-5 py-3"><div class="min-w-0 flex-1"><h3 class="text-sm font-semibold">${e(skill.name)}</h3><div class="mt-1 flex flex-wrap gap-1">${(skill.axes ?? []).map(axisBadge).join('')}</div></div>${badge(`Nivel ${skill.target_level ?? '—'}`)}${actions(skill, 'detail', ctx.superadmin || skill.company_id === ctx.user.company_id)}</div>`).join('') || '<p class="p-5 text-sm text-slate-500">Sin habilidades registradas.</p>'}</div></details>`;
    }).join('')}</div>`;
}

function questions(ctx) {
    const records = ctx.filtered(companyRecords(ctx, 'questions'), (r) => `${r.text} ${r.skill?.name}`, (r) => ['type', 'difficulty', 'skill_id', 'organizational_axis_id'].every((key) => selected(ctx, key, r[key])));
    return toolbar(companyFilter(ctx) + f(ctx, 'type', 'Tipo', enums({ self_report: 'Autorreporte', situational: 'Situacional', situational_judgment: 'Juicio situacional' })) + f(ctx, 'skill_id', 'Habilidad', ctx.records('skills')) + f(ctx, 'organizational_axis_id', 'Eje', ctx.data.catalogs.organizational_axes) + f(ctx, 'difficulty', 'Nivel', enums({ basic: 'Básico', intermediate: 'Intermedio', advanced: 'Avanzado' })), button('+ Agregar pregunta', 'create', '', true), `${records.length} preguntas · ${records.filter((r) => r.is_active).length} activas`)
        + table(['Pregunta', 'Tipo', 'Habilidad', 'Eje', 'Nivel', 'Estado', 'Acciones'], ctx.sorted(records.map((r) => [`<p class="line-clamp-2" title="${e(r.text)}">${e(r.text)}</p>`, badge(r.type, r.type === 'self_report' ? 'blue' : 'violet'), e(r.skill?.name), axisBadge(ctx.axis(r.organizational_axis_id)), badge(r.difficulty), active(r), actions(r)])), ctx.state);
}

function positions(ctx) {
    const records = ctx.filtered(companyRecords(ctx, 'positions'), (r) => `${r.name} ${r.area?.name} ${(r.skills ?? []).map((s) => s.name).join(' ')}`, (r) => selected(ctx, 'area_id', r.area_id) && selected(ctx, 'organizational_axis_id', r.organizational_axis_id));
    return toolbar(companyFilter(ctx) + f(ctx, 'area_id', 'Área', ctx.records('areas')) + f(ctx, 'organizational_axis_id', 'Eje', ctx.data.catalogs.organizational_axes), button('+ Nuevo cargo', 'create', '', true), `${records.length} cargos registrados`)
        + (records.length ? `<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">${records.map((r) => panel(r.name, `<p class="mb-3 text-xs text-slate-500">${e(r.area?.name)}</p><div class="mb-4 flex gap-2">${axisBadge(ctx.axis(r.organizational_axis_id))}${active(r)}</div><p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Habilidades asociadas</p><div class="flex flex-wrap gap-1.5">${(r.skills ?? []).map((s) => badge(s.name)).join('') || '<p class="text-xs text-slate-400">Sin habilidades asociadas</p>'}</div><div class="mt-4">${actions(r)}</div>`)).join('')}</div>` : empty());
}

function evaluations(ctx) {
    const records = ctx.filtered(companyRecords(ctx, 'evaluations'), (r) => `${r.user?.name} ${label(r.status)} ${label(r.type)}`, (r) => selected(ctx, 'status', r.status));
    return toolbar(companyFilter(ctx) + byState(ctx), button('+ Asignar evaluación', 'create', '', true))
        + table(['Colaborador', 'Tipo', 'Preguntas', 'Estado', 'Fecha límite', 'Resultado', 'Acciones'], ctx.sorted(records.map((r) => [identity(r.user), badge(r.type, 'blue'), e(r.question_count), statusBadge(r.status), date(r.due_at), r.result ? `${percent(r.result.total_score)}%` : '—', `<div class="flex flex-wrap gap-1">${button('Ver', 'detail', r.id)}${r.status === 'pending' ? button('Reprogramar', 'edit', r.id) + button('Eliminar', 'delete', r.id) : ''}</div>`])), ctx.state);
}

function plans(ctx) {
    const records = ctx.filtered(companyRecords(ctx, 'improvement-plans'), (r) => `${r.user?.name} ${r.skill?.name} ${label(r.status)}`, (r) => selected(ctx, 'status', r.status));
    return kpis([['Planes activos', records.filter((p) => p.status === 'in_progress').length, 'target'], ['Completados', records.filter((p) => p.status === 'completed').length, 'target', 'green'], ['Pendientes', records.filter((p) => p.status === 'pending').length, 'calendar', 'amber'], ['Con retraso', records.filter((p) => p.due_date && new Date(p.due_date) < new Date() && !['completed', 'cancelled'].includes(p.status)).length, 'warning', 'red']])
        + toolbar(companyFilter(ctx) + byState(ctx), button('+ Nuevo plan', 'create', '', true))
        + table(['Colaborador', 'Habilidad objetivo', 'Progreso', 'Estado', 'Reevaluación', 'Acciones'], ctx.sorted(records.map((r) => [identity(r.user), `<strong class="text-sm">${e(r.skill?.name)}</strong><p class="text-xs text-slate-400">${e(r.initial_level)} → ${e(r.target_level)}</p>`, progress(r.progress), statusBadge(r.status), date(r.reevaluation_date, true), actions(r, 'plan-detail')])), ctx.state);
}

function diagnostics(ctx) {
    return diagnosticFilters(ctx) + `<div class="flex justify-end">${button('+ Generar diagnóstico', 'create', '', true)}</div>` + axisCards(ctx)
        + `<div class="grid gap-5 lg:grid-cols-2">${panel('Resultado por categoría', bars(categorySeries(ctx), true))}${panel('Comparación por eje', bars(axisSeries(ctx)))}</div>`
        + panel('Matriz de diagnóstico', heatmap(ctx), link('Ver mapa de calor completo →', 'mapa-calor'));
}

function heatmap(ctx) {
    if (!ctx.data.diagnostic) return empty('No hay diagnóstico disponible', 'Genera un diagnóstico con las evaluaciones completadas del período.');
    const axes = ctx.data.catalogs.organizational_axes;
    const categories = ctx.data.catalogs.skill_categories;
    const values = matrix(ctx);
    return `<div class="ws-panel"><p class="mb-3 text-sm text-slate-500">Selecciona una celda para consultar su resultado y número de colaboradores. Porcentajes equivalentes a la puntuación sobre 5.</p><div class="mb-4 flex flex-wrap gap-2">${badge('≥70% · Sólido', 'green')}${badge('50–69% · En desarrollo', 'amber')}${badge('<50% · Crítico', 'red')}</div><div class="overflow-x-auto"><table class="ws-table"><caption class="sr-only">Matriz de diagnóstico</caption><thead><tr><th>Categoría de habilidades</th>${axes.map((a) => `<th>${axisBadge(a)}</th>`).join('')}</tr></thead><tbody>${categories.map((cat) => `<tr><td class="font-semibold">${e(cat.name)}</td>${axes.map((a) => { const cell = values.find((v) => v.skill_category_id === cat.id && v.organizational_axis_id === a.id); const pct = percent(cell?.average_score); return `<td>${cell ? `<button class="flex h-16 w-20 flex-col items-center justify-center rounded-xl border ${pct >= 70 ? 'border-green-200 bg-green-100 text-green-800' : pct >= 50 ? 'border-amber-100 bg-amber-50 text-amber-800' : 'border-red-200 bg-red-100 text-red-800'}" data-action="cell" data-id="${cell.id}"><strong class="text-xl tabular-nums">${pct}%</strong><small>${e(label(cell.level))}</small></button>` : '<span class="text-xs text-slate-400">Sin datos</span>'}</td>`; }).join('')}</tr>`).join('')}</tbody></table></div></div>`;
}

function gaps(ctx) {
    const gaps = ctx.filtered(ctx.data.diagnostic?.gaps ?? [], (g) => `${ctx.find('skills', g.skill_id)?.name} ${ctx.find('areas', g.area_id)?.name}`, (g) => selected(ctx, 'organizational_axis_id', g.organizational_axis_id) && selected(ctx, 'area_id', g.area_id));
    const ranking = [...gaps].sort((a, b) => Number(b.score_gap) - Number(a.score_gap));
    return diagnosticFilters(ctx) + `<div class="rounded-xl border border-red-100 bg-red-50 p-4"><p class="text-sm font-semibold text-red-800">${gaps.filter((g) => g.priority === 'critical').length} brechas críticas registradas</p><p class="mt-1 text-xs text-red-600">La brecha es la diferencia entre el nivel requerido por el cargo y el resultado obtenido, en una escala de 0 a 5.</p></div>`
        + toolbar(f(ctx, 'organizational_axis_id', 'Eje', ctx.data.catalogs.organizational_axes) + f(ctx, 'area_id', 'Área', ctx.records('areas')))
        + (gaps.length ? `<div class="grid gap-5 lg:grid-cols-3"><div>${panel('Ranking de brechas', ranking.map((g, i) => `<div class="flex items-start gap-3 border-b border-slate-50 py-3"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-slate-100 text-xs">${i + 1}</span><div class="min-w-0 flex-1"><strong class="text-sm">${e(ctx.find('skills', g.skill_id)?.name ?? 'Habilidad no disponible')}</strong><p class="mt-1 text-xs text-slate-400">${e(ctx.find('areas', g.area_id)?.name ?? '')}</p></div><div class="text-right"><strong class="text-red-600">${e(g.score_gap)} / 5</strong><div>${statusBadge(g.priority)}</div></div></div>`).join(''))}</div><div class="lg:col-span-2">${panel('Comparación de brechas', bars(ranking.map((g) => ({ name: ctx.find('skills', g.skill_id)?.name ?? 'Habilidad', value: Number(g.score_gap) * 20, color: '#DC2626' }))))}</div></div><div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">${ranking.map((g) => panel(ctx.find('skills', g.skill_id)?.name ?? 'Habilidad', `<div class="flex gap-2">${axisBadge(ctx.axis(g.organizational_axis_id))}${statusBadge(g.priority)}</div><p class="my-3 text-xs text-slate-500">${g.affected_count} colaboradores afectados · Brecha ${e(g.score_gap)} / 5</p><p class="mb-4 text-sm text-slate-500">${e(g.ai_recommendation ?? 'Sin recomendación registrada.')}</p>${link('Crear plan de intervención', 'planes', '', true)}`)).join('')}</div>` : empty('No hay brechas registradas'));
}

function recommendations(ctx) {
    const records = (ctx.data.diagnostic?.gaps ?? []).filter((g) => g.ai_recommendation && selected(ctx, 'priority', g.priority));
    const general = ctx.data.diagnostic?.ai_recommendations ?? [];
    return diagnosticFilters(ctx) + `<div class="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-slate-900 p-5 text-white"><div><h2 class="font-bold">Motor de recomendaciones IA</h2><p class="mt-1 text-xs text-slate-400">Recomendaciones disponibles en el diagnóstico guardado.</p></div><strong class="text-2xl">${records.length + general.length}</strong></div>`
        + toolbar(f(ctx, 'priority', 'Todas las prioridades', enums({ critical: 'Crítica', high: 'Alta', medium: 'Media', low: 'Baja' })))
        + (records.length || general.length ? general.map((rec) => panel('Recomendación organizacional', `<p class="text-sm text-slate-500">${e(typeof rec === 'string' ? rec : rec.description ?? rec.recommendation ?? JSON.stringify(rec))}</p>`)).join('') + records.map((g) => panel(ctx.find('skills', g.skill_id)?.name ?? 'Recomendación', `<div class="mb-3 flex gap-2">${statusBadge(g.priority)}${axisBadge(ctx.axis(g.organizational_axis_id))}</div><p class="text-sm leading-6 text-slate-500">${e(g.ai_recommendation)}</p><p class="my-3 text-xs text-slate-400">${g.affected_count} colaboradores afectados</p>${link('Crear plan', 'planes', '', true)}`)).join('') : empty('No hay recomendaciones IA disponibles', 'El diagnóstico actual no contiene recomendaciones generadas. Puedes gestionar planes de mejoramiento con los resultados existentes.'));
}

function tracking(ctx) {
    const plans = companyRecords(ctx, 'improvement-plans');
    const completed = companyRecords(ctx, 'evaluations').filter((ev) => ev.status === 'completed' && ev.result);
    const months = [...new Set(completed.map((ev) => ev.completed_at?.slice(0, 7)).filter(Boolean))].sort();
    return scopeBar(ctx) + kpis([['Planes activos', plans.filter((p) => p.status === 'in_progress').length, 'target'], ['Completados', plans.filter((p) => p.status === 'completed').length, 'clipboard', 'green'], ['Progreso promedio', plans.length ? `${Math.round(average(plans.map((p) => p.progress)))}%` : '—', 'trend', 'violet'], ['Con retraso', plans.filter((p) => p.due_date && new Date(p.due_date) < new Date() && !['completed', 'cancelled'].includes(p.status)).length, 'warning', 'red']])
        + `<div class="grid gap-5 lg:grid-cols-2">${panel('Evolución del resultado promedio', lineChart(months.map((m) => ({ name: m, value: percent(average(completed.filter((ev) => ev.completed_at.startsWith(m)).map((ev) => ev.result.total_score))) }))))}${panel('Estado de planes', bars([{ name: 'Completados', value: plans.length ? plans.filter((p) => p.status === 'completed').length / plans.length * 100 : null, color: '#22C55E' }, { name: 'En progreso', value: plans.length ? plans.filter((p) => p.status === 'in_progress').length / plans.length * 100 : null }]))}</div>`
        + panel('Seguimiento por área', ctx.records('areas').map((area) => {
            const relevant = plans.filter((p) => positionFor(ctx, ctx.find('employees', p.user_id))?.area_id === area.id);
            return `<div class="mb-4 flex flex-wrap items-center gap-4"><strong class="min-w-24 text-sm">${e(area.name)}</strong><div class="min-w-32 flex-1">${progress(relevant.length ? average(relevant.map((p) => p.progress)) : null, '#22C55E')}</div><span class="text-xs text-slate-400">${relevant.filter((p) => p.status === 'completed').length}/${relevant.length} completados</span></div>`;
        }).join('') || empty('No hay áreas registradas'));
}

export function reportRows(ctx) {
    return companyRecords(ctx, 'evaluations').filter((ev) => {
        const user = ctx.find('employees', ev.user_id); const pos = positionFor(ctx, user);
        return selected(ctx, 'user_id', ev.user_id) && selected(ctx, 'area_id', pos?.area_id) && selected(ctx, 'position_id', pos?.id) && selected(ctx, 'axis_id', pos?.organizational_axis_id) && (!ctx.state.filters.month || ev.completed_at?.startsWith(ctx.state.filters.month));
    });
}

export function reportData(ctx) {
    const records = reportRows(ctx);
    const type = ctx.state.filters.report_type ?? 'all';
    if (type === 'all' || type === 'user_id') return {
        headers: ['Colaborador', 'Fecha', 'Estado', 'Resultado / 5', 'Resultado %'],
        rows: records.map((ev) => [ev.user?.name, date(ev.completed_at), label(ev.status), ev.result?.total_score ?? '', percent(ev.result?.total_score) ?? '']),
    };
    const groups = new Map();
    for (const ev of records) {
        const position = positionFor(ctx, ctx.find('employees', ev.user_id));
        const name = type === 'area_id' ? ctx.find('areas', position?.area_id)?.name
            : type === 'position_id' ? position?.name : ctx.axis(position?.organizational_axis_id)?.name;
        const key = name ?? 'Sin asignar';
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(ev);
    }
    return {
        headers: [({ area_id: 'Área', position_id: 'Cargo', axis_id: 'Eje del cargo' })[type], 'Evaluaciones', 'Completadas', 'Promedio / 5', 'Promedio %'],
        rows: [...groups].map(([name, evaluations]) => {
            const score = average(evaluations.filter((ev) => ev.result).map((ev) => ev.result.total_score));
            return [name, evaluations.length, evaluations.filter((ev) => ev.status === 'completed').length, score == null ? '' : score.toFixed(2), percent(score) ?? ''];
        }),
    };
}

function reports(ctx) {
    const rows = reportRows(ctx);
    const report = reportData(ctx);
    const months = [...new Set(ctx.records('evaluations').map((ev) => ev.completed_at?.slice(0, 7)).filter(Boolean))].sort().reverse().map((id) => ({ id, name: id }));
    return `<div class="mx-auto max-w-3xl space-y-5">${panel('Tipo de reporte', `<div class="grid gap-3 sm:grid-cols-3">${[['user_id', 'Individual'], ['area_id', 'Por área'], ['position_id', 'Por cargo'], ['axis_id', 'Por eje'], ['all', 'Organizacional']].map(([id, text]) => `<label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 p-4 ${ctx.state.filters.report_type === id || (!ctx.state.filters.report_type && id === 'all') ? 'border-blue-500 bg-blue-50' : 'border-slate-100'}"><input type="radio" name="report-type" data-filter="report_type" value="${id}" ${(ctx.state.filters.report_type ?? 'all') === id ? 'checked' : ''}><strong class="text-sm">${text}</strong></label>`).join('')}</div>`)}
        ${panel('Filtros del reporte', `<div class="flex flex-wrap gap-3">${companyFilter(ctx)}${f(ctx, 'month', 'Todos los períodos', months)}${f(ctx, 'user_id', 'Todos los colaboradores', ctx.records('employees'))}${f(ctx, 'area_id', 'Todas las áreas', ctx.records('areas'))}${f(ctx, 'position_id', 'Todos los cargos', ctx.records('positions'))}${f(ctx, 'axis_id', 'Todos los ejes', ctx.data.catalogs.organizational_axes)}</div>`)}
        ${panel('Vista previa', `<p class="mb-4 text-sm text-slate-500">${rows.length} evaluaciones en los filtros seleccionados</p>${table(report.headers, report.rows.map((row) => row.map(e)), ctx.state)}`)}
        <div class="flex flex-wrap gap-3">${button('Imprimir / Guardar PDF', 'print-report', '', true)}${button('Descargar CSV', 'export-report')}</div><p class="text-xs text-slate-400">Puedes guardar el reporte como PDF desde la impresión del navegador o abrir el CSV en una hoja de cálculo.</p></div>`;
}

function employeeProfile(ctx) {
    const user = ctx.find('employees', ctx.selectedId);
    if (!user) return empty('Colaborador no disponible');
    const pos = positionFor(ctx, user);
    const evaluations = ctx.records('evaluations').filter((ev) => ev.user_id === user.id);
    const plans = ctx.records('improvement-plans').filter((p) => p.user_id === user.id);
    const result = ctx.data.personalResults?.[0]?.result;
    const tab = ctx.state.filters.profile_tab ?? 'Resumen';
    const tabs = ['Resumen', 'Evaluaciones', 'Habilidades', 'Plan de mejoramiento', 'Historial'];
    return link('← Volver a colaboradores', 'colaboradores') + `<article class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm"><div class="h-20 bg-gradient-to-r from-blue-600 to-blue-700"></div><div class="p-6">${identity(user)}<p class="my-2 text-sm text-slate-500">${e(pos?.name ?? 'Sin cargo')} · ${e(pos?.area?.name ?? 'Sin área')}</p>${axisBadge(ctx.axis(pos?.organizational_axis_id))}<div class="mt-3 flex flex-wrap gap-2">${button('Editar perfil', 'edit', user.id)}${button('Enviar invitación', 'invite', user.id)}${button('Desactivar', 'delete', user.id)}</div><div class="mt-5 flex flex-wrap gap-2">${tabs.map((t) => `<label class="ws-button ${tab === t ? 'border-blue-400 bg-blue-50 text-blue-700' : ''}"><input class="sr-only" type="radio" name="profile-tab" data-filter="profile_tab" value="${t}" ${tab === t ? 'checked' : ''}>${t}</label>`).join('')}</div></div></article>`
        + (tab === 'Resumen' ? `<div class="grid gap-5 lg:grid-cols-3"><div>${panel('Información', `<dl class="space-y-3 text-sm"><div><dt class="text-xs text-slate-400">Correo electrónico</dt><dd class="break-all">${e(user.email)}</dd></div><div><dt class="text-xs text-slate-400">Fecha de ingreso</dt><dd>${date(user.hire_date, true)}</dd></div><div><dt class="text-xs text-slate-400">Responsable</dt><dd>${e(ctx.find('employees', user.manager_id)?.name ?? 'Sin responsable')}</dd></div></dl>`)}</div><div class="lg:col-span-2">${panel('Categorías de habilidades', bars((result?.category_results ?? []).map((c) => ({ name: ctx.category(c.skill_category_id)?.name ?? 'Categoría', value: percent(c.score) }))))}</div></div>`
            : tab === 'Habilidades' ? panel('Habilidades', bars((result?.skill_results ?? []).map((s) => ({ name: s.skill?.name ?? ctx.data.personalResults?.[0]?.questions?.find((q) => q.question.skill_id === s.skill_id)?.question.skill?.name ?? 'Habilidad', value: percent(s.score) }))))
                : tab === 'Plan de mejoramiento' ? panel('Planes asignados', plans.map((p) => `<div class="mb-4"><strong>${e(p.skill?.name)}</strong>${progress(p.progress)}<div class="mt-2">${button('Ver plan', 'plan-detail', p.id)}</div></div>`).join('') || empty('No tiene planes asignados'))
                    : table(['Tipo', 'Fecha', 'Estado', 'Resultado', 'Acciones'], evaluations.map((ev) => [e(label(ev.type)), date(ev.assigned_at), statusBadge(ev.status), ev.result ? `${percent(ev.result.total_score)}%` : '—', button('Ver evaluación', 'employee-evaluation', ev.id)]), ctx.state));
}
