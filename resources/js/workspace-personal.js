import { assessmentScreen } from './workspace-assessment';
import { e, label, date, percent, color, average, initials, identity, statusBadge, badge, button, link, empty, panel, progress, kpis, bars, lineChart, table, filter, formErrors } from './workspace-ui';

const own = (ctx, key) => ctx.records(key).filter((r) => r.user_id === ctx.user.id);
const resultFor = (ctx) => ctx.data.personalResults?.find((ev) => String(ev.id) === String(ctx.state.filters.evaluation_id)) ?? ctx.data.personalResults?.[0];
const namesFor = (evaluation) => new Map((evaluation?.questions ?? []).map((q) => [q.question.skill_id, q.question.skill?.name]));
const resultFilters = (ctx) => `<div class="flex flex-wrap gap-2">${filter('evaluation_id', 'Última evaluación', (ctx.data.personalResults ?? []).map((ev) => ({ id: ev.id, name: `${date(ev.completed_at)} · ${label(ev.type)}` })), ctx.state.filters.evaluation_id)}</div>`;

export function renderPersonal(ctx) {
    switch (ctx.screen) {
        case 'inicio': return home(ctx);
        case 'evaluacion': return assessmentScreen(ctx);
        case 'resultados': return results(ctx);
        case 'plan': return plans(ctx);
        case 'progreso': return evolution(ctx);
        case 'perfil': return profile(ctx);
        case 'notificaciones': return notifications(ctx);
        default: return empty('Pantalla no disponible');
    }
}

function home(ctx) {
    const ev = own(ctx, 'evaluations')[0];
    const result = resultFor(ctx)?.result;
    const plans = own(ctx, 'improvement-plans');
    const active = plans.find((p) => ['pending', 'in_progress'].includes(p.status));
    const scores = result?.skill_results ?? [];
    return `<div><h2 class="text-2xl font-bold">Hola, ${e(ctx.user.first_name ?? ctx.user.name.split(' ')[0])} 👋</h2><p class="mt-1 text-sm text-slate-500">Conoce el estado actual de tus habilidades y tu progreso.</p></div>`
        + `<div class="flex flex-col justify-between gap-4 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 p-6 text-white shadow-lg sm:flex-row sm:items-center"><div><span class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-xs">${ev ? e(label(ev.status)) : 'Por iniciar'}</span><h2 class="mt-3 text-lg font-semibold">Evaluación de habilidades blandas</h2><p class="mt-2 text-sm text-brand-200">${ev ? `Asignada: ${date(ev.assigned_at)} · Fecha límite: ${date(ev.due_at)}` : 'Tu prueba está disponible. Responderla toma entre 10 y 15 minutos.'}</p></div>${link(ev?.status === 'completed' ? 'Ver resultados →' : 'Ver evaluación →', ev?.status === 'completed' ? 'resultados' : 'evaluacion')}</div>`
        + kpis([['Resultado general', result ? `${percent(result.total_score)}%` : '—', 'chart'], ['Fortalezas', result ? scores.filter((s) => s.level === 'strength').length : '—', 'sparkles', 'green'], ['Oportunidades de mejora', result ? scores.filter((s) => ['to_develop', 'developing'].includes(s.level)).length : '—', 'trend', 'amber'], ['Plan de mejoramiento', active ? `${Math.round(Number(active.progress))}%` : '—', 'target', 'violet']])
        + `<div class="grid gap-5 lg:grid-cols-3"><div class="lg:col-span-2">${panel('Mis categorías de habilidades', result ? bars(result.category_results.map((r) => ({ name: ctx.category(r.skill_category_id)?.name ?? 'Categoría', value: percent(r.score) }))) : empty('Todavía no tienes resultados'), link('Ver todo', 'resultados'))}</div><div class="space-y-4"><article class="rounded-xl bg-slate-900 p-4 text-white"><h2 class="text-xs font-semibold uppercase tracking-wider text-brand-400">Análisis IA</h2><p class="mt-3 text-sm leading-6 text-slate-300">${e(result?.ai_analysis ?? 'No hay análisis IA disponible para tu evaluación.')}</p><div class="mt-3">${link('Ver análisis completo →', 'resultados')}</div></article>${panel('Mi plan activo', active ? `<strong class="text-sm">${e(active.skill?.name)}</strong><div class="my-3">${progress(active.progress)}</div><p class="text-xs text-slate-500">Reevaluación: ${date(active.reevaluation_date, true)}</p><div class="mt-4">${link('Ver plan completo', 'plan')}</div>` : empty('Sin plan activo'))}</div></div>`;
}

export function resultContent(ctx, evaluation) {
    const result = evaluation?.result;
    if (!result) return empty('No hay diagnóstico disponible', 'Completa una evaluación para consultar tus resultados.');
    const score = percent(result.total_score);
    const names = namesFor(evaluation);
    const skills = result.skill_results ?? [];
    const skillCards = (items) => items.map((s) => panel(s.skill?.name ?? names.get(s.skill_id) ?? 'Habilidad', `<div class="mb-3 flex items-center justify-between">${statusBadge(s.level)}<strong style="color:${color(percent(s.score))}" class="text-xl">${percent(s.score)}%</strong></div>${progress(percent(s.score), color(percent(s.score)))}${(s.strengths ?? []).map((t) => `<p class="mt-2 text-xs text-slate-500">${e(typeof t === 'string' ? t : JSON.stringify(t))}</p>`).join('')}`)).join('') || empty('Sin habilidades en esta categoría');
    return `<div class="ws-panel"><div class="flex flex-col items-center gap-6 md:flex-row"><div class="grid size-36 shrink-0 place-items-center rounded-full" style="background:conic-gradient(var(--color-brand-600) ${score}%, var(--color-slate-100) 0)"><div class="grid size-28 content-center rounded-full bg-white text-center"><strong class="text-3xl">${score}%</strong><small class="text-slate-500">${e(result.total_score)} de 100</small></div></div><div class="text-center md:text-left"><h2 class="mb-2 text-2xl font-bold">Mi diagnóstico</h2>${statusBadge(result.level)}<p class="mt-3 text-sm text-slate-500">Resultado de la evaluación realizada el ${date(evaluation.completed_at)}.</p><div class="mt-4">${button('¿Cómo se obtuvo este resultado?', 'analysis', evaluation.id)}</div></div></div></div>`
        + `<div><h2 class="mb-4 font-bold">Categorías de habilidades</h2><div class="grid gap-4 md:grid-cols-2">${(result.category_results ?? []).map((category) => panel(ctx.category(category.skill_category_id)?.name ?? 'Categoría', `<div class="mb-3 flex items-center justify-between">${statusBadge(category.level)}<strong class="text-2xl">${percent(category.score)}%</strong></div>${progress(percent(category.score), color(percent(category.score)))}<details class="mt-4 text-sm"><summary class="cursor-pointer text-xs font-medium text-brand-600">Ver habilidades</summary><div class="mt-3 space-y-2">${skills.filter((s) => evaluation.questions?.some((q) => q.question.skill_id === s.skill_id && q.question.skill.skill_category_id === category.skill_category_id)).map((s) => `<p class="flex justify-between gap-3"><span>${e(names.get(s.skill_id))}</span><strong>${percent(s.score)}%</strong></p>`).join('') || '<p class="text-xs text-slate-500">No hay detalle de habilidades disponible.</p>'}</div></details>`)).join('')}</div></div>`
        + `<div class="grid gap-5 md:grid-cols-2"><section class="space-y-3"><h2 class="font-bold text-green-700">Mis fortalezas</h2>${skillCards(skills.filter((s) => s.level === 'strength'))}</section><section class="space-y-3"><h2 class="font-bold text-amber-700">Oportunidades de mejora</h2>${skillCards(skills.filter((s) => ['to_develop', 'developing'].includes(s.level)))}</section></div>`;
}

function results(ctx) { return resultFilters(ctx) + resultContent(ctx, resultFor(ctx)); }

function plans(ctx) {
    const plans = own(ctx, 'improvement-plans');
    if (!plans.length) return empty('No tienes planes asignados', 'Los planes de mejoramiento que te asigne tu organización aparecerán aquí.');
    return `<div class="mx-auto max-w-3xl space-y-5">${plans.map((plan) => panel(plan.skill?.name ?? 'Plan de mejoramiento', `<div class="mb-4 flex flex-wrap items-center justify-between gap-2">${statusBadge(plan.status)}<span class="text-xs text-slate-500">${e(plan.initial_level)} → ${e(plan.target_level)}</span></div><div class="mb-4 grid grid-cols-2 gap-3 text-sm"><div><p class="text-xs text-slate-500">Inicio</p>${date(plan.start_date, true)}</div><div><p class="text-xs text-slate-500">Reevaluación</p>${date(plan.reevaluation_date, true)}</div></div>${progress(plan.progress)}<p class="my-4 text-sm text-slate-500">${e(plan.ai_recommendation ?? 'Consulta las actividades y los recursos de tu plan.')}</p>${button('Ver actividades y recursos', 'plan-detail', plan.id, true)}`)).join('')}</div>`;
}

function evolution(ctx) {
    const results = [...(ctx.data.personalResults ?? [])].sort((a, b) => a.completed_at.localeCompare(b.completed_at));
    const before = results.at(-2); const now = results.at(-1);
    const names = namesFor(now);
    const delta = before && now ? Math.round(percent(now.result.total_score) - percent(before.result.total_score)) : null;
    const plans = own(ctx, 'improvement-plans').filter((p) => p.status !== 'completed');
    return `<div class="mx-auto max-w-3xl space-y-5"><div class="rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 p-5 text-white"><h2 class="text-lg font-bold">${delta == null ? 'Tu progreso comienza con tu primera evaluación' : `Tu resultado cambió ${delta >= 0 ? '+' : ''}${delta} puntos porcentuales`}</h2><p class="mt-1 text-sm text-green-100">${before ? `Comparación entre ${date(before.completed_at)} y ${date(now.completed_at)}.` : 'Necesitas dos evaluaciones completadas para comparar tu evolución.'}</p></div>`
        + panel('Comparación: Antes → Ahora', before ? `<div class="space-y-5">${now.result.skill_results.map((skill) => { const old = before.result.skill_results.find((s) => s.skill_id === skill.skill_id); return `<div><div class="mb-2 flex flex-wrap justify-between gap-2 text-sm"><strong>${e(names.get(skill.skill_id) ?? 'Habilidad')}</strong><span>${old ? percent(old.score) + '%' : 'Sin dato anterior'} → <strong class="text-brand-600">${percent(skill.score)}%</strong></span></div>${progress(percent(skill.score))}</div>`; }).join('')}</div>` : empty('Sin comparación disponible'))
        + panel('Evolución de habilidades', lineChart(results.map((ev) => ({ name: date(ev.completed_at), value: percent(ev.result.total_score) }))))
        + panel('Próxima reevaluación', plans.length ? `<p class="mb-3 text-sm text-slate-500">${date(plans.sort((a, b) => a.reevaluation_date.localeCompare(b.reevaluation_date))[0].reevaluation_date)}</p>${link('Ver evaluaciones asignadas', 'evaluacion', '', true)}` : '<p class="text-sm text-slate-500">Sin fecha de reevaluación registrada.</p>') + '</div>';
}

function profile(ctx) {
    const user = ctx.user;
    const ev = resultFor(ctx);
    return `<div class="mx-auto max-w-2xl space-y-5"><article class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm"><div class="h-24 bg-gradient-to-r from-brand-600 to-brand-700"></div><div class="px-6 pb-6"><span class="relative -mt-10 grid size-20 place-items-center rounded-2xl border-4 border-white bg-brand-600 text-2xl font-bold text-white shadow-md">${e(initials(user.name))}</span><h2 class="mt-3 text-xl font-bold">${e(user.name)}</h2><p class="mt-1 text-sm text-slate-500">${e(user.position?.name ?? label(user.role))}</p></div></article>`
        + panel('Información del colaborador', `<div class="grid gap-4 md:grid-cols-2">${[['Correo electrónico', user.email], ['Empresa', user.company?.name ?? 'Sin empresa'], ['Cargo', user.position?.name ?? 'Sin cargo'], ['Fecha de ingreso', date(user.hire_date, true)], ['Última evaluación', ev ? date(ev.completed_at) : 'Sin evaluación'], ['Rol', label(user.role)]].map(([name, value]) => `<div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">${name}</p><p class="mt-1 break-words text-sm font-semibold">${e(value)}</p></div>`).join('')}</div><p class="mt-4 text-xs text-slate-500">Solicita a talento humano cualquier cambio en tus datos de perfil.</p>`)
        + panel('Resumen de habilidades', ev ? bars((ev.result.category_results ?? []).map((r) => ({ name: ctx.category(r.skill_category_id)?.name ?? 'Categoría', value: percent(r.score) }))) : empty('Todavía no tienes resultados')) + '</div>';
}

function notifications(ctx) {
    const records = ctx.filtered(ctx.records('notifications'));
    return `<div class="mx-auto max-w-2xl"><div class="mb-4 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-500">${records.filter((r) => !r.read_at).length} notificaciones sin leer</p>${button('Marcar todo como leído', 'read-all')}</div><div class="space-y-2">${records.map((n) => `<article class="flex items-start gap-4 rounded-xl border bg-white p-4 ${n.read_at ? 'border-slate-100 opacity-70' : 'border-brand-100 shadow-sm'}"><span class="mt-1 size-2 shrink-0 rounded-full ${n.read_at ? 'bg-slate-300' : 'bg-brand-500'}"></span><div class="min-w-0 flex-1"><h2 class="text-sm font-semibold">${e(n.data?.title ?? ({ evaluation_assigned: 'Evaluación asignada', improvement_plan_assigned: 'Plan de mejoramiento asignado', reevaluation_due: 'Reevaluación pendiente' }[n.data?.type] ?? 'Notificación'))}</h2><p class="mt-1 text-sm text-slate-600">${e(n.data?.message ?? (n.data?.due_at ? `Fecha límite: ${date(n.data.due_at)}` : 'Tienes una actualización en tu espacio de trabajo.'))}</p><p class="mt-1 text-xs text-slate-500">${date(n.created_at)}</p><div class="mt-3 flex flex-wrap gap-2">${n.data?.evaluation_id ? link('Ver evaluación', ctx.admin ? 'evaluaciones' : 'evaluacion') : n.data?.improvement_plan_id ? link('Ver plan', ctx.admin ? 'planes' : 'plan') : ''}${!n.read_at ? button('Marcar como leído', 'read', n.id) : ''}</div></div></article>`).join('') || empty('No tienes notificaciones')}</div></div>`;
}
