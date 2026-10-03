import { e, label, date, percent, color, average, initials, identity, statusBadge, badge, button, link, empty, panel, progress, kpis, bars, lineChart, table, filter, formErrors } from './workspace-ui';

const own = (ctx, key) => ctx.records(key).filter((r) => r.user_id === ctx.user.id);
const resultFor = (ctx) => ctx.data.personalResults?.find((ev) => String(ev.id) === String(ctx.state.filters.evaluation_id)) ?? ctx.data.personalResults?.[0];
const namesFor = (evaluation) => new Map((evaluation?.questions ?? []).map((q) => [q.question.skill_id, q.question.skill?.name]));
const resultFilters = (ctx) => `<div class="flex flex-wrap gap-2">${filter('evaluation_id', 'Última evaluación', (ctx.data.personalResults ?? []).map((ev) => ({ id: ev.id, name: `${date(ev.completed_at)} · ${label(ev.type)}` })), ctx.state.filters.evaluation_id)}</div>`;

export function renderPersonal(ctx) {
    switch (ctx.screen) {
        case 'inicio': return home(ctx);
        case 'evaluacion': return evaluations(ctx);
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
        + `<div class="flex flex-col justify-between gap-4 rounded-2xl bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white shadow-lg sm:flex-row sm:items-center"><div><span class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-xs">${ev ? e(label(ev.status)) : 'Sin evaluación asignada'}</span><h2 class="mt-3 text-lg font-semibold">Evaluación de habilidades blandas</h2><p class="mt-2 text-sm text-blue-200">${ev ? `Asignada: ${date(ev.assigned_at)} · Fecha límite: ${date(ev.due_at)}` : 'Tu organización te avisará cuando tengas una evaluación disponible.'}</p></div>${link(ev?.status === 'completed' ? 'Ver resultados →' : 'Ver evaluación →', ev?.status === 'completed' ? 'resultados' : 'evaluacion')}</div>`
        + kpis([['Resultado general', result ? `${percent(result.total_score)}%` : '—', 'chart'], ['Fortalezas', result ? scores.filter((s) => ['strong', 'outstanding'].includes(s.level)).length : '—', 'sparkles', 'green'], ['Oportunidades de mejora', result ? scores.filter((s) => ['critical', 'developing'].includes(s.level)).length : '—', 'trend', 'amber'], ['Plan de mejoramiento', active ? `${Math.round(Number(active.progress))}%` : '—', 'target', 'violet']])
        + `<div class="grid gap-5 lg:grid-cols-3"><div class="lg:col-span-2">${panel('Mis categorías de habilidades', result ? bars(result.category_results.map((r) => ({ name: ctx.category(r.skill_category_id)?.name ?? 'Categoría', value: percent(r.score) }))) : empty('Todavía no tienes resultados'), link('Ver todo', 'resultados'))}</div><div class="space-y-4"><article class="rounded-xl bg-slate-900 p-4 text-white"><h2 class="text-xs font-semibold uppercase tracking-wider text-blue-400">Análisis IA</h2><p class="mt-3 text-sm leading-6 text-slate-300">${e(result?.ai_analysis ?? 'No hay análisis IA disponible para tu evaluación.')}</p><div class="mt-3">${link('Ver análisis completo →', 'resultados')}</div></article>${panel('Mi plan activo', active ? `<strong class="text-sm">${e(active.skill?.name)}</strong><div class="my-3">${progress(active.progress)}</div><p class="text-xs text-slate-400">Reevaluación: ${date(active.reevaluation_date, true)}</p><div class="mt-4">${link('Ver plan completo', 'plan')}</div>` : empty('Sin plan activo'))}</div></div>`;
}

function evaluations(ctx) {
    const evaluations = own(ctx, 'evaluations');
    if (!evaluations.length) return empty('No tienes evaluaciones asignadas', 'Recibirás una notificación cuando tu organización te asigne una evaluación.');
    return `<div class="mx-auto max-w-2xl space-y-5">${evaluations.map((ev) => `<article class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm"><div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white"><h2 class="text-xl font-bold">Evaluación de habilidades blandas</h2><p class="mt-2 text-sm text-blue-200">${e(label(ev.type))} · ${ev.question_count} preguntas</p></div><div class="space-y-4 p-6"><div class="flex flex-wrap gap-2">${statusBadge(ev.status)}${badge(`Fecha límite: ${date(ev.due_at)}`)}</div><p class="text-sm leading-6 text-slate-500">Responde según tu comportamiento habitual. Tus respuestas se guardan al continuar y puedes retomar una evaluación en curso.</p>${ev.status === 'completed' ? link('Ver mi diagnóstico', 'resultados', '', true) : new Date(ev.due_at) < new Date() || ev.status === 'expired' ? '<p class="text-sm text-red-600">La evaluación está vencida. Solicita una nueva asignación a tu organización.</p>' : button(ev.status === 'in_progress' ? 'Continuar evaluación' : 'Comenzar evaluación', 'evaluation-start', ev.id, true)}</div></article>`).join('')}</div>`;
}

export function resultContent(ctx, evaluation) {
    const result = evaluation?.result;
    if (!result) return empty('No hay diagnóstico disponible', 'Completa una evaluación para consultar tus resultados.');
    const score = percent(result.total_score);
    const names = namesFor(evaluation);
    const skills = result.skill_results ?? [];
    const skillCards = (items) => items.map((s) => panel(s.skill?.name ?? names.get(s.skill_id) ?? 'Habilidad', `<div class="mb-3 flex items-center justify-between">${statusBadge(s.level)}<strong style="color:${color(percent(s.score))}" class="text-xl">${percent(s.score)}%</strong></div>${progress(percent(s.score), color(percent(s.score)))}${(s.strengths ?? []).map((t) => `<p class="mt-2 text-xs text-slate-500">${e(typeof t === 'string' ? t : JSON.stringify(t))}</p>`).join('')}`)).join('') || empty('Sin habilidades en esta categoría');
    return `<div class="ws-panel"><div class="flex flex-col items-center gap-6 md:flex-row"><div class="grid size-36 shrink-0 place-items-center rounded-full" style="background:conic-gradient(#1D4ED8 ${score}%, #F1F5F9 0)"><div class="grid size-28 content-center rounded-full bg-white text-center"><strong class="text-3xl">${score}%</strong><small class="text-slate-400">${e(result.total_score)} de 5</small></div></div><div class="text-center md:text-left"><h2 class="mb-2 text-2xl font-bold">Mi diagnóstico</h2>${statusBadge(result.level)}<p class="mt-3 text-sm text-slate-500">Resultado de la evaluación realizada el ${date(evaluation.completed_at)}.</p><div class="mt-4">${button('¿Cómo se obtuvo este resultado?', 'analysis', evaluation.id)}</div></div></div></div>`
        + `<div><h2 class="mb-4 font-bold">Categorías de habilidades</h2><div class="grid gap-4 md:grid-cols-2">${(result.category_results ?? []).map((category) => panel(ctx.category(category.skill_category_id)?.name ?? 'Categoría', `<div class="mb-3 flex items-center justify-between">${statusBadge(category.level)}<strong class="text-2xl">${percent(category.score)}%</strong></div>${progress(percent(category.score), color(percent(category.score)))}<details class="mt-4 text-sm"><summary class="cursor-pointer text-xs font-medium text-blue-600">Ver habilidades</summary><div class="mt-3 space-y-2">${skills.filter((s) => evaluation.questions?.some((q) => q.question.skill_id === s.skill_id && q.question.skill.skill_category_id === category.skill_category_id)).map((s) => `<p class="flex justify-between gap-3"><span>${e(names.get(s.skill_id))}</span><strong>${percent(s.score)}%</strong></p>`).join('') || '<p class="text-xs text-slate-400">No hay detalle de habilidades disponible.</p>'}</div></details>`)).join('')}</div></div>`
        + `<div class="grid gap-5 md:grid-cols-2"><section class="space-y-3"><h2 class="font-bold text-green-700">Mis fortalezas</h2>${skillCards(skills.filter((s) => ['strong', 'outstanding'].includes(s.level)))}</section><section class="space-y-3"><h2 class="font-bold text-amber-700">Oportunidades de mejora</h2>${skillCards(skills.filter((s) => ['developing', 'critical'].includes(s.level)))}</section></div>`;
}

function results(ctx) { return resultFilters(ctx) + resultContent(ctx, resultFor(ctx)); }

function plans(ctx) {
    const plans = own(ctx, 'improvement-plans');
    if (!plans.length) return empty('No tienes planes asignados', 'Los planes de mejoramiento que te asigne tu organización aparecerán aquí.');
    return `<div class="mx-auto max-w-3xl space-y-5">${plans.map((plan) => panel(plan.skill?.name ?? 'Plan de mejoramiento', `<div class="mb-4 flex flex-wrap items-center justify-between gap-2">${statusBadge(plan.status)}<span class="text-xs text-slate-400">${e(plan.initial_level)} → ${e(plan.target_level)}</span></div><div class="mb-4 grid grid-cols-2 gap-3 text-sm"><div><p class="text-xs text-slate-400">Inicio</p>${date(plan.start_date, true)}</div><div><p class="text-xs text-slate-400">Reevaluación</p>${date(plan.reevaluation_date, true)}</div></div>${progress(plan.progress)}<p class="my-4 text-sm text-slate-500">${e(plan.ai_recommendation ?? 'Consulta las actividades y los recursos de tu plan.')}</p>${button('Ver actividades y recursos', 'plan-detail', plan.id, true)}`)).join('')}</div>`;
}

function evolution(ctx) {
    const results = [...(ctx.data.personalResults ?? [])].sort((a, b) => a.completed_at.localeCompare(b.completed_at));
    const before = results.at(-2); const now = results.at(-1);
    const names = namesFor(now);
    const delta = before && now ? Math.round(percent(now.result.total_score) - percent(before.result.total_score)) : null;
    const plans = own(ctx, 'improvement-plans').filter((p) => p.status !== 'completed');
    return `<div class="mx-auto max-w-3xl space-y-5"><div class="rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 p-5 text-white"><h2 class="text-lg font-bold">${delta == null ? 'Tu progreso comienza con tu primera evaluación' : `Tu resultado cambió ${delta >= 0 ? '+' : ''}${delta} puntos porcentuales`}</h2><p class="mt-1 text-sm text-green-100">${before ? `Comparación entre ${date(before.completed_at)} y ${date(now.completed_at)}.` : 'Necesitas dos evaluaciones completadas para comparar tu evolución.'}</p></div>`
        + panel('Comparación: Antes → Ahora', before ? `<div class="space-y-5">${now.result.skill_results.map((skill) => { const old = before.result.skill_results.find((s) => s.skill_id === skill.skill_id); return `<div><div class="mb-2 flex flex-wrap justify-between gap-2 text-sm"><strong>${e(names.get(skill.skill_id) ?? 'Habilidad')}</strong><span>${old ? percent(old.score) + '%' : 'Sin dato anterior'} → <strong class="text-blue-600">${percent(skill.score)}%</strong></span></div>${progress(percent(skill.score))}</div>`; }).join('')}</div>` : empty('Sin comparación disponible'))
        + panel('Evolución de habilidades', lineChart(results.map((ev) => ({ name: date(ev.completed_at), value: percent(ev.result.total_score) }))))
        + panel('Próxima reevaluación', plans.length ? `<p class="mb-3 text-sm text-slate-500">${date(plans.sort((a, b) => a.reevaluation_date.localeCompare(b.reevaluation_date))[0].reevaluation_date)}</p>${link('Ver evaluaciones asignadas', 'evaluacion', '', true)}` : '<p class="text-sm text-slate-500">Sin fecha de reevaluación registrada.</p>') + '</div>';
}

function profile(ctx) {
    const user = ctx.user;
    const ev = resultFor(ctx);
    return `<div class="mx-auto max-w-2xl space-y-5"><article class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm"><div class="h-24 bg-gradient-to-r from-blue-600 to-blue-700"></div><div class="px-6 pb-6"><span class="relative -mt-10 grid size-20 place-items-center rounded-2xl border-4 border-white bg-blue-600 text-2xl font-bold text-white shadow-md">${e(initials(user.name))}</span><h2 class="mt-3 text-xl font-bold">${e(user.name)}</h2><p class="mt-1 text-sm text-slate-500">${e(user.position?.name ?? label(user.role))}</p></div></article>`
        + panel('Información del colaborador', `<div class="grid gap-4 md:grid-cols-2">${[['Correo electrónico', user.email], ['Empresa', user.company?.name ?? 'Sin empresa'], ['Cargo', user.position?.name ?? 'Sin cargo'], ['Fecha de ingreso', date(user.hire_date, true)], ['Última evaluación', ev ? date(ev.completed_at) : 'Sin evaluación'], ['Rol', label(user.role)]].map(([name, value]) => `<div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-400">${name}</p><p class="mt-1 break-words text-sm font-semibold">${e(value)}</p></div>`).join('')}</div><p class="mt-4 text-xs text-slate-400">Solicita a talento humano cualquier cambio en tus datos de perfil.</p>`)
        + panel('Resumen de habilidades', ev ? bars((ev.result.category_results ?? []).map((r) => ({ name: ctx.category(r.skill_category_id)?.name ?? 'Categoría', value: percent(r.score) }))) : empty('Todavía no tienes resultados')) + '</div>';
}

function notifications(ctx) {
    const records = ctx.filtered(ctx.records('notifications'));
    return `<div class="mx-auto max-w-2xl"><div class="mb-4 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-500">${records.filter((r) => !r.read_at).length} notificaciones sin leer</p>${button('Marcar todo como leído', 'read-all')}</div><div class="space-y-2">${records.map((n) => `<article class="flex items-start gap-4 rounded-xl border bg-white p-4 ${n.read_at ? 'border-slate-100 opacity-70' : 'border-blue-100 shadow-sm'}"><span class="mt-1 size-2 shrink-0 rounded-full ${n.read_at ? 'bg-slate-300' : 'bg-blue-500'}"></span><div class="min-w-0 flex-1"><h2 class="text-sm font-semibold">${e(n.data?.title ?? ({ evaluation_assigned: 'Evaluación asignada', improvement_plan_assigned: 'Plan de mejoramiento asignado', reevaluation_due: 'Reevaluación pendiente' }[n.data?.type] ?? 'Notificación'))}</h2><p class="mt-1 text-sm text-slate-600">${e(n.data?.message ?? (n.data?.due_at ? `Fecha límite: ${date(n.data.due_at)}` : 'Tienes una actualización en tu espacio de trabajo.'))}</p><p class="mt-1 text-xs text-slate-400">${date(n.created_at)}</p><div class="mt-3 flex flex-wrap gap-2">${n.data?.evaluation_id ? link('Ver evaluación', ctx.admin ? 'evaluaciones' : 'evaluacion') : n.data?.improvement_plan_id ? link('Ver plan', ctx.admin ? 'planes' : 'plan') : ''}${!n.read_at ? button('Marcar como leído', 'read', n.id) : ''}</div></div></article>`).join('') || empty('No tienes notificaciones')}</div></div>`;
}

export async function showEvaluation(ctx, id) {
    const evaluation = await ctx.get(`evaluations/${id}`);
    if (evaluation.user_id !== ctx.user.id) throw new Error('Solo puedes responder tus propias evaluaciones.');
    if (evaluation.status === 'completed') { ctx.navigate('resultados'); return; }
    const questions = evaluation.questions ?? [];
    if (!questions.length) throw new Error('La evaluación no tiene preguntas asignadas.');
    const answers = new Map((evaluation.responses ?? []).map((r) => [r.evaluation_question_id, r]));
    let index = questions.findIndex((q) => !answers.has(q.id));
    if (index < 0) index = questions.length - 1;
    let busy = false;

    function renderQuestion() {
        const item = questions[index]; const q = item.question; const saved = answers.get(item.id);
        ctx.modal('Mi evaluación', `<form id="evaluation-response-form" class="space-y-5"><div><div class="mb-2 flex justify-between text-sm"><strong>Pregunta ${index + 1} de ${questions.length}</strong><span>${answers.size} guardadas</span></div>${progress(answers.size / questions.length * 100)}</div><div class="flex flex-wrap gap-2">${badge(q.type, q.type === 'self_report' ? 'blue' : 'amber')}${badge(q.skill?.name)}</div><fieldset><legend class="mb-5 text-base font-semibold">${e(q.text)}</legend><div class="${q.type === 'self_report' ? 'grid grid-cols-2 gap-3 sm:grid-cols-5' : 'space-y-3'}">${q.type === 'self_report' ? ['Nunca', 'Rara vez', 'Algunas veces', 'Frecuentemente', 'Siempre'].map((t, i) => `<label class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 border-slate-200 p-3 text-center text-sm has-checked:border-blue-500 has-checked:bg-blue-50"><input type="radio" name="answer" value="${i + 1}" required ${saved?.likert_value === i + 1 ? 'checked' : ''}><strong>${i + 1}</strong><span class="text-xs text-slate-500">${t}</span></label>`).join('') : (q.options ?? []).map((opt) => `<label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 border-slate-200 p-4 has-checked:border-blue-500 has-checked:bg-blue-50"><input class="mt-1" type="radio" name="answer" value="${opt.id}" required ${saved?.question_option_id === opt.id ? 'checked' : ''}><strong class="text-sm text-blue-600">${e(opt.label)}</strong><span class="text-sm">${e(opt.text)}</span></label>`).join('')}</div></fieldset><div id="evaluation-feedback" role="alert"></div><div class="flex flex-wrap justify-between gap-3"><button type="button" id="evaluation-prev" class="ws-button" ${index === 0 ? 'disabled' : ''}>← Anterior</button><button type="submit" class="ws-primary">${index === questions.length - 1 ? 'Guardar y finalizar' : 'Guardar y continuar →'}</button></div><p class="text-xs text-slate-400">Cada respuesta se guarda en tu evaluación. Puedes cerrar y continuar más tarde.</p></form>`);
        const form = document.querySelector('#evaluation-response-form');
        document.querySelector('#evaluation-prev').addEventListener('click', () => { if (!busy) { index = Math.max(0, index - 1); renderQuestion(); } });
        form.addEventListener('submit', async (event) => {
            event.preventDefault(); event.stopPropagation();
            if (busy) return;
            busy = true; const submit = form.querySelector('[type="submit"]'); submit.disabled = true;
            try {
                const value = Number(new FormData(form).get('answer'));
                const data = { evaluation_question_id: item.id, ...(q.type === 'self_report' ? { likert_value: value } : { question_option_id: value }) };
                const response = await ctx.request(`evaluations/${evaluation.id}/responses`, { method: 'POST', data });
                answers.set(item.id, response);
                if (index < questions.length - 1) { index++; renderQuestion(); }
                else if (answers.size < questions.length) { index = questions.findIndex((q) => !answers.has(q.id)); renderQuestion(); }
                else {
                    document.querySelector('#evaluation-feedback').innerHTML = '<p class="text-sm text-blue-600" role="status">Guardando tu diagnóstico…</p>';
                    await ctx.request(`evaluations/${evaluation.id}/complete`, { method: 'POST' });
                    ctx.modal('¡Evaluación completada!', `<div class="space-y-4 text-center"><div class="mx-auto grid size-16 place-items-center rounded-full bg-green-50 text-3xl text-green-600">✓</div><p class="text-sm text-slate-500">Tus respuestas y tu diagnóstico quedaron guardados correctamente.</p>${link('Ver mi diagnóstico', 'resultados', '', true)}</div>`);
                    ctx.toast('Evaluación completada.');
                    await ctx.reload();
                }
            } catch (error) { document.querySelector('#evaluation-feedback').innerHTML = formErrors(error); }
            finally { busy = false; submit.disabled = false; }
        });
    }
    renderQuestion();
}
