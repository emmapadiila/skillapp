import { assessmentScreen } from './workspace-assessment';
import { e, label, date, formatScore, levelColor, initials, identity, statusBadge, badge, button, link, empty, panel, progress, scoreProgress, kpis, bars, lineChart, table, filter, formErrors, radar } from './workspace-ui';

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
    const evaluations = own(ctx, 'evaluations');
    const open = evaluations.find((ev) => ['pending', 'in_progress'].includes(ev.status) && new Date(ev.due_at) > new Date());
    const result = resultFor(ctx)?.result;
    const active = own(ctx, 'improvement-plans').find((p) => ['pending', 'in_progress'].includes(p.status));
    const name = ctx.user.first_name ?? ctx.user.name.split(' ')[0];

    // Una sola acción principal: retomar o empezar la prueba si está abierta; si no, ver el resultado.
    const testCard = open || !result
        ? `<section class="ws-panel space-y-3" aria-labelledby="home-test"><h2 id="home-test" class="text-lg font-bold">${open?.status === 'in_progress' ? 'Tienes una prueba en curso' : 'Tu prueba de habilidades blandas'}</h2><p class="text-[0.9375rem] leading-6 text-slate-700">${open ? `Puedes responderla hasta el ${date(open.due_at)}. Toma entre 10 y 15 minutos y tu avance se guarda solo.` : 'Cuando Talento Humano habilite tu prueba, podrás empezarla desde aquí.'}</p>${link(open?.status === 'in_progress' ? 'Continuar mi prueba' : 'Ir a mi prueba', 'evaluacion', '', true)}</section>`
        : '';
    const resultCard = result
        ? `<section class="ws-panel space-y-3" aria-labelledby="home-result"><h2 id="home-result" class="text-lg font-bold">Tu nivel general: <span class="${LEVEL_TONE[result.level] ?? ''}">${e(label(result.level))}</span></h2><p class="text-[0.9375rem] leading-6 text-slate-700">${e(LEVEL_MEANING[result.level] ?? '')} Puntaje total: <strong class="tabular-nums">${e(formatScore(result.total_score))}</strong>.</p>${link('Ver mi resultado', 'resultados', '', !open)}</section>`
        : '';
    const planCard = active
        ? panel('Mi plan de mejoramiento', `<p class="text-[0.9375rem] font-medium text-slate-900">${e(active.skill?.name ?? 'Plan activo')}</p><div class="my-3">${progress(active.progress)}</div><p class="text-sm text-slate-600">Reevaluación: ${date(active.reevaluation_date, true)}</p><div class="mt-4">${link('Ver mi plan', 'plan')}</div>`)
        : result ? panel('Mi plan de mejoramiento', '<p class="text-[0.9375rem] leading-6 text-slate-700">Talento Humano preparará tu plan a partir de tu resultado.</p>') : '';

    return `<div class="mx-auto max-w-3xl space-y-5"><div><h2 class="text-2xl font-bold">Hola, ${e(name)}</h2><p class="mt-1 text-[0.9375rem] text-slate-600">Aquí está tu prueba de habilidades blandas y tu resultado.</p></div>${testCard}${resultCard}${planCard}</div>`;
}

// Qué significa cada nivel para la persona (los rangos vienen de ResultLevel: <50, 50 a <75, ≥75).
const LEVEL_MEANING = {
    strength: 'Aplicas estas habilidades de forma habitual en tu trabajo. Vale la pena mantenerlas.',
    developing: 'Aplicas varias de estas habilidades y hay otras que puedes reforzar con práctica.',
    to_develop: 'Hay varias habilidades que conviene trabajar, y este resultado te muestra por dónde empezar.',
};
const LEVEL_TONE = { strength: 'text-success-700', developing: 'text-warning-700', to_develop: 'text-danger-700' };

export function resultContent(ctx, evaluation) {
    const result = evaluation?.result;
    if (!result) return empty('Todavía no tienes resultados', 'Cuando termines tu prueba, aquí verás tu nivel y lo que lo explica.');
    const names = namesFor(evaluation);
    const weights = new Map((evaluation.assessment?.skills ?? []).map((s) => [s.id, Number(s.pivot?.weight ?? 1)]));
    const skills = (result.skill_results ?? []).map((s) => ({ ...s, name: s.skill?.name ?? names.get(s.skill_id) ?? 'Habilidad', value: Number(s.score), weight: weights.get(s.skill_id) ?? 1 }));
    const strengths = skills.filter((s) => s.level === 'strength').sort((a, b) => b.value - a.value);
    const toWork = skills.filter((s) => s.level !== 'strength').sort((a, b) => a.value - b.value);

    // Cada explicación aparece una sola vez, junto a su habilidad.
    const skillRow = (s) => `<li class="space-y-2 py-4 first:pt-0 last:pb-0"><div class="flex flex-wrap items-center justify-between gap-2"><h3 class="min-w-0 text-[0.9375rem] font-semibold text-slate-900">${e(s.name)}</h3><span class="flex flex-wrap items-center gap-2">${s.weight > 1 ? badge('Cuenta más', 'brand') : ''}${statusBadge(s.level)}<span class="text-sm tabular-nums text-slate-700">${formatScore(s.value)}</span></span></div>${scoreProgress(s.value, levelColor(s.level))}${[...(s.strengths ?? []), ...(s.opportunities ?? [])].map((t) => `<p class="text-sm leading-6 text-slate-600">${e(t)}</p>`).join('')}</li>`;
    const group = (title, id, items, emptyText) => `<section class="ws-panel space-y-3" aria-labelledby="${id}"><h2 id="${id}" class="text-lg font-bold">${title}</h2>${items.length ? `<ul class="divide-y divide-slate-100">${items.map(skillRow).join('')}</ul>` : `<p class="text-[0.9375rem] text-slate-600">${emptyText}</p>`}</section>`;
    const categories = (result.category_results ?? []).map((c) => `<li class="space-y-2"><div class="flex flex-wrap items-center justify-between gap-2"><span class="text-[0.9375rem] text-slate-900">${e(ctx.category(c.skill_category_id)?.name ?? 'Categoría')}</span><span class="flex items-center gap-2">${statusBadge(c.level)}<span class="text-sm tabular-nums text-slate-700">${formatScore(c.score)}</span></span></div>${scoreProgress(c.score, levelColor(c.level))}</li>`).join('');

    return `<section class="ws-panel" aria-labelledby="result-title">
            <p class="text-sm text-slate-600">${e(evaluation.assessment?.name ?? 'Prueba de habilidades blandas')} · enviada el ${date(evaluation.completed_at)}</p>
            <h2 id="result-title" class="mt-2 text-2xl font-bold text-slate-900">Tu nivel general: <span class="${LEVEL_TONE[result.level] ?? ''}">${e(label(result.level))}</span></h2>
            <p class="mt-2 max-w-2xl text-[0.9375rem] leading-6 text-slate-700">${e(LEVEL_MEANING[result.level] ?? '')}</p>
            <p class="mt-3 text-sm text-slate-600">Puntaje total: <strong class="tabular-nums text-slate-900">${formatScore(result.total_score)} de 100</strong></p>
        </section>`
        + `<div class="space-y-5">${group('Tus fortalezas', 'strengths-title', strengths, 'Todavía ninguna habilidad llega al nivel Fortaleza (75 o más).')}${group('Para seguir desarrollando', 'develop-title', toWork, 'Todas tus habilidades están en el nivel Fortaleza.')}</div>`
        + `<div class="space-y-5">${panel('Tus categorías', `<ul class="space-y-4">${categories}</ul>`)}<div class="hidden sm:block">${panel('Mapa de tus habilidades', radar(skills.map((s) => ({ name: s.name, value: s.value, color: levelColor(s.level) })), 'Puntaje por habilidad de 0 a 100'))}</div></div>`
        + `<details class="ws-panel"><summary class="cursor-pointer font-semibold text-slate-900">Cómo se calculó tu resultado</summary><ul class="mt-3 list-disc space-y-1.5 pl-5 text-[0.9375rem] leading-6 text-slate-700">
            <li>Cada respuesta vale de 1 a 5.</li>
            <li>Cada habilidad es el promedio de sus preguntas llevado a una escala de 0 a 100 (1 equivale a 0, 3 a 50 y 5 a 100).</li>
            <li>Tu puntaje total y el de cada categoría son promedios en los que las habilidades de peso alto cuentan 1,5 veces.</li>
            <li>Tu nivel es Por desarrollar si obtienes menos de 50 puntos, En desarrollo desde 50 hasta menos de 75 puntos y Fortaleza desde 75 puntos.</li>
        </ul></details>`
        + (result.ai_analysis ? panel('Análisis con IA', `<p class="text-[0.9375rem] leading-6 text-slate-700">${e(result.ai_analysis)}</p>`) : '')
        + (ctx.admin ? '' : panel('Qué sigue', `<p class="text-[0.9375rem] leading-6 text-slate-700">Este resultado lo ven tú y el área de Talento Humano. Con él se define tu plan de mejoramiento.</p><div class="mt-4">${link('Ver mi plan', 'plan')}</div>`));
}

function results(ctx) { return `<div class="mx-auto max-w-3xl space-y-5">${resultFilters(ctx)}${resultContent(ctx, resultFor(ctx))}</div>`; }

function plans(ctx) {
    const plans = own(ctx, 'improvement-plans');
    if (!plans.length) return empty('No tienes planes asignados', 'Los planes de mejoramiento que te asigne tu organización aparecerán aquí.');
    return `<div class="mx-auto max-w-3xl space-y-5">${plans.map((plan) => panel(plan.skill?.name ?? 'Plan de mejoramiento', `<div class="mb-4 flex flex-wrap items-center justify-between gap-2">${statusBadge(plan.status)}<span class="text-xs text-slate-500">${e(plan.initial_level)} → ${e(plan.target_level)}</span></div><div class="mb-4 grid grid-cols-2 gap-3 text-sm"><div><p class="text-xs text-slate-500">Inicio</p>${date(plan.start_date, true)}</div><div><p class="text-xs text-slate-500">Reevaluación</p>${date(plan.reevaluation_date, true)}</div></div>${progress(plan.progress)}<p class="my-4 text-sm text-slate-500">${e(plan.ai_recommendation ?? 'Consulta las actividades y los recursos de tu plan.')}</p>${button('Ver actividades y recursos', 'plan-detail', plan.id, true)}`)).join('')}</div>`;
}

const scoreHundredths = (score) => Math.round(Number(score) * 100);

function evolution(ctx) {
    const results = [...(ctx.data.personalResults ?? [])].sort((a, b) => a.completed_at.localeCompare(b.completed_at));
    const before = results.at(-2); const now = results.at(-1);
    const names = namesFor(now);
    const delta = before && now ? (scoreHundredths(now.result.total_score) - scoreHundredths(before.result.total_score)) / 100 : null;
    const plans = own(ctx, 'improvement-plans').filter((p) => p.status !== 'completed');
    if (!now) return empty('Todavía no tienes resultados', 'Cuando termines tu prueba, aquí verás cómo evoluciona tu resultado.');
    if (!before) {
        return `<div class="mx-auto max-w-3xl space-y-5">${panel('Tu primera medición', `<p class="text-[0.9375rem] leading-6 text-slate-700">El ${date(now.completed_at)} obtuviste el nivel <strong>${e(label(now.result.level))}</strong> con ${e(formatScore(now.result.total_score))}. Verás tu evolución después de tu reevaluación.</p><div class="mt-4">${link('Ver mi resultado', 'resultados')}</div>`)}</div>`;
    }
    return `<div class="mx-auto max-w-3xl space-y-5"><div class="rounded-card bg-brand-700 p-5 text-white"><h2 class="text-lg font-bold">${delta == null ? 'Tu progreso comienza con tu primera evaluación' : `Tu resultado cambió ${delta > 0 ? '+' : ''}${formatScore(delta)}`}</h2><p class="mt-1 text-sm text-green-100">${before ? `Comparación entre ${date(before.completed_at)} y ${date(now.completed_at)}.` : 'Necesitas dos evaluaciones completadas para comparar tu evolución.'}</p></div>`
        + panel('Comparación: Antes → Ahora', before ? `<div class="space-y-5">${now.result.skill_results.map((skill) => { const old = before.result.skill_results.find((s) => s.skill_id === skill.skill_id); const score = skill.score; return `<div><div class="mb-2 flex flex-wrap justify-between gap-2 text-sm"><strong>${e(names.get(skill.skill_id) ?? 'Habilidad')}</strong><span>${old ? formatScore(old.score) : 'Sin dato anterior'} → <strong class="text-brand-600">${formatScore(score)}</strong></span></div>${scoreProgress(score)}</div>`; }).join('')}</div>` : empty('Sin comparación disponible'))
        + panel('Evolución de habilidades', lineChart(results.map((ev) => ({ name: date(ev.completed_at), value: ev.result.total_score })), formatScore))
        + panel('Próxima reevaluación', plans.length ? `<p class="mb-3 text-sm text-slate-500">${date(plans.sort((a, b) => a.reevaluation_date.localeCompare(b.reevaluation_date))[0].reevaluation_date)}</p>${link('Ver evaluaciones asignadas', 'evaluacion', '', true)}` : '<p class="text-sm text-slate-500">Sin fecha de reevaluación registrada.</p>') + '</div>';
}

function profile(ctx) {
    const user = ctx.user;
    const ev = resultFor(ctx);
    return `<div class="mx-auto max-w-2xl space-y-5"><article class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm"><div class="h-24 bg-brand-700"></div><div class="px-6 pb-6"><span class="relative -mt-10 grid size-20 place-items-center rounded-2xl border-4 border-white bg-brand-600 text-2xl font-bold text-white shadow-md">${e(initials(user.name))}</span><h2 class="mt-3 text-xl font-bold">${e(user.name)}</h2><p class="mt-1 text-sm text-slate-500">${e(user.position?.name ?? label(user.role))}</p></div></article>`
        + panel('Información del colaborador', `<div class="grid gap-4 md:grid-cols-2">${[['Correo electrónico', user.email], ['Empresa', user.company?.name ?? 'Sin empresa'], ['Cargo', user.position?.name ?? 'Sin cargo'], ['Fecha de ingreso', date(user.hire_date, true)], ['Última evaluación', ev ? date(ev.completed_at) : 'Sin evaluación'], ['Rol', label(user.role)]].map(([name, value]) => `<div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">${name}</p><p class="mt-1 break-words text-sm font-semibold">${e(value)}</p></div>`).join('')}</div><p class="mt-4 text-xs text-slate-500">Solicita a talento humano cualquier cambio en tus datos de perfil.</p>`)
        + panel('Resumen de habilidades', ev ? bars((ev.result.category_results ?? []).map((r) => ({ name: ctx.category(r.skill_category_id)?.name ?? 'Categoría', value: r.score, color: levelColor(r.level) })), false, formatScore) : empty('Todavía no tienes resultados')) + '</div>';
}

function notifications(ctx) {
    const records = ctx.filtered(ctx.records('notifications'));
    return `<div class="mx-auto max-w-2xl"><div class="mb-4 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-500">${records.filter((r) => !r.read_at).length} notificaciones sin leer</p>${button('Marcar todo como leído', 'read-all')}</div><div class="space-y-2">${records.map((n) => `<article class="flex items-start gap-4 rounded-xl border bg-white p-4 ${n.read_at ? 'border-slate-100 opacity-70' : 'border-brand-100 shadow-sm'}"><span class="mt-1 size-2 shrink-0 rounded-full ${n.read_at ? 'bg-slate-300' : 'bg-brand-500'}"></span><div class="min-w-0 flex-1"><h2 class="text-sm font-semibold">${e(n.data?.title ?? ({ evaluation_assigned: 'Evaluación asignada', improvement_plan_assigned: 'Plan de mejoramiento asignado', reevaluation_due: 'Reevaluación pendiente' }[n.data?.type] ?? 'Notificación'))}</h2><p class="mt-1 text-sm text-slate-600">${e(n.data?.message ?? (n.data?.due_at ? `Fecha límite: ${date(n.data.due_at)}` : 'Tienes una actualización en tu espacio de trabajo.'))}</p><p class="mt-1 text-xs text-slate-500">${date(n.created_at)}</p><div class="mt-3 flex flex-wrap gap-2">${n.data?.evaluation_id ? link('Ver evaluación', ctx.admin ? 'evaluaciones' : 'evaluacion') : n.data?.improvement_plan_id ? link('Ver plan', ctx.admin ? 'planes' : 'plan') : ''}${!n.read_at ? button('Marcar como leído', 'read', n.id) : ''}</div></div></article>`).join('') || empty('No tienes notificaciones')}</div></div>`;
}
