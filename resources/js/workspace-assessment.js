import { e, date, empty, progress, formErrors } from './workspace-ui';

// Escala del anexo (1–5). Se muestran todas las etiquetas, no solo los extremos.
const LIKERT = ['Totalmente en desacuerdo', 'En desacuerdo', 'Ni de acuerdo ni en desacuerdo', 'De acuerdo', 'Totalmente de acuerdo'];

const own = (ctx) => ctx.records('evaluations').filter((ev) => ev.user_id === ctx.user.id);
const isOpen = (ev) => ['pending', 'in_progress'].includes(ev.status) && new Date(ev.due_at) > new Date();
const page = () => document.querySelector('#workspace-page');

/** Pantalla "Mi evaluación": consentimiento → presentación → (flujo en showEvaluation). */
export function assessmentScreen(ctx) {
    if (!ctx.user.consent_accepted_at) return consent(ctx);

    const confirmation = ctx.consentJustAccepted ? '<p id="consent-confirmation" tabindex="-1" class="ws-alert ws-alert--success mb-4 outline-none">Autorización registrada.</p>' : '';
    ctx.consentJustAccepted = false;

    const evaluations = own(ctx);
    const open = evaluations.find(isOpen);
    const completed = evaluations.find((ev) => ev.status === 'completed');
    const assessment = ctx.records('assessments').find((a) => a.id === open?.assessment_id) ?? open?.assessment ?? ctx.records('assessments')[0];

    if (!open && completed) {
        return `<div class="mx-auto max-w-2xl">${confirmation}<article class="ws-panel space-y-4 text-center"><span class="mx-auto grid size-14 place-items-center rounded-full bg-success-100 text-2xl text-success-700" aria-hidden="true">✓</span><h2 class="text-xl font-bold">Ya completaste tu prueba</h2><p class="text-sm text-slate-600">La enviaste el ${e(date(completed.completed_at))}. Talento Humano programará tu próxima reevaluación.</p><button type="button" class="ws-primary" data-navigate="resultados">Ver mi resultado</button></article></div>`;
    }
    if (!assessment && !open) return `<div class="mx-auto max-w-2xl">${confirmation}</div>` + empty('Todavía no tienes una prueba disponible', 'Tu cargo no tiene una prueba asociada. Consulta con el área de Talento Humano.');

    const questions = open?.question_count ?? assessment?.questions?.length ?? 0;
    const [likertHelp, situationalHelp] = instructions(assessment);
    return `<div class="mx-auto max-w-2xl">${confirmation}<article class="ws-panel space-y-5">
        <header><h2 class="text-xl font-bold">${e(assessment?.name ?? 'Evaluación de habilidades blandas')}</h2></header>
        <ul class="grid gap-3 text-sm sm:grid-cols-3">
            <li class="rounded-control bg-slate-50 p-3"><strong class="block text-lg">${questions}</strong><span class="text-slate-600">preguntas</span></li>
            <li class="rounded-control bg-slate-50 p-3"><strong class="block text-lg">10–15 min</strong><span class="text-slate-600">tiempo estimado</span></li>
            <li class="rounded-control bg-slate-50 p-3"><strong class="block text-lg">Guardado</strong><span class="text-slate-600">automático al avanzar</span></li>
        </ul>
        <section class="space-y-3 text-sm leading-6 text-slate-700"><h3 class="font-semibold text-slate-900">Cómo responder</h3>
            ${likertHelp ? `<p><strong>Afirmaciones.</strong> ${e(likertHelp)}</p>` : ''}
            ${situationalHelp ? `<p><strong>Situaciones.</strong> ${e(situationalHelp)}</p>` : ''}
            <p>Puedes cerrar la prueba en cualquier momento y retomarla donde la dejaste${open ? `, hasta el ${e(date(open.due_at))}` : ''}.</p>
            <p class="text-slate-600">Autorizaste el tratamiento de tus datos el ${e(date(ctx.user.consent_accepted_at))}.</p>
        </section>
        <button type="button" class="ws-primary w-full sm:w-auto" data-action="evaluation-start" data-id="${e(open?.id ?? '')}">${open?.status === 'in_progress' ? 'Continuar la prueba' : 'Comenzar la prueba'}</button>
    </article></div>`;
}

function instructions(assessment) {
    const lines = String(assessment?.instructions ?? '').split('\n');
    const find = (prefix) => lines.find((l) => l.startsWith(prefix))?.slice(prefix.length).trim() ?? '';
    return [find('Afirmaciones:'), find('Situaciones:')];
}

function consent(ctx) {
    const company = ctx.user.company?.name ?? 'la empresa';
    setTimeout(() => {
        const form = document.querySelector('#consent-form');
        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]'); submit.disabled = true;
            try {
                ctx.user = await ctx.request('me/consent', { method: 'POST' });
                ctx.consentJustAccepted = true;
                ctx.render();
                document.querySelector('#consent-confirmation')?.focus();
            } catch (error) { form.querySelector('#consent-feedback').innerHTML = formErrors(error); submit.disabled = false; }
        });
    });
    return `<div class="mx-auto max-w-2xl"><form id="consent-form" class="ws-panel space-y-5" aria-labelledby="consent-title">
        <header><h2 id="consent-title" class="text-xl font-bold">Consentimiento informado</h2><p class="mt-1 text-sm text-slate-600">Un paso antes de comenzar tu prueba.</p></header>
        <p class="text-sm leading-6 text-slate-700">De acuerdo con la Ley 1581 de 2012 de protección de datos personales, ${e(company)} te pide autorización para tratar la información de esta prueba.</p>
        <dl class="divide-y divide-slate-200 border-y border-slate-200 text-[0.9375rem] leading-6">
            <div class="grid gap-1 py-3 sm:grid-cols-[9rem_1fr] sm:gap-4"><dt class="font-semibold">Qué datos</dt><dd class="text-slate-700">Tus respuestas, tus resultados y los datos de tu cargo y área.</dd></div>
            <div class="grid gap-1 py-3 sm:grid-cols-[9rem_1fr] sm:gap-4"><dt class="font-semibold">Para qué</dt><dd class="text-slate-700">Diagnosticar habilidades blandas y diseñar planes de mejoramiento.</dd></div>
            <div class="grid gap-1 py-3 sm:grid-cols-[9rem_1fr] sm:gap-4"><dt class="font-semibold">Quién los ve</dt><dd class="text-slate-700">Tú y el área de Talento Humano.</dd></div>
            <div class="grid gap-1 py-3 sm:grid-cols-[9rem_1fr] sm:gap-4"><dt class="font-semibold">Tus derechos</dt><dd class="text-slate-700">Conocer, actualizar y rectificar tus datos, pedir que se supriman y revocar esta autorización, solicitándolo a Talento Humano.</dd></div>
        </dl>
        <label class="ws-choice"><input type="checkbox" name="accept" required class="size-5 shrink-0 accent-brand-600"><span>He leído la información anterior y autorizo el tratamiento de mis datos.</span></label>
        <div id="consent-feedback" aria-live="polite"></div>
        <button type="submit" class="ws-primary w-full sm:w-auto">Aceptar y continuar</button>
    </form></div>`;
}

/** Orden fijo pero mezclado por evaluación y pregunta: no cambia al volver atrás ni al retomar. */
function shuffled(items, seed) {
    let state = [...String(seed)].reduce((hash, char) => Math.imul(hash ^ char.charCodeAt(0), 16777619), 2166136261) >>> 0;
    const random = () => { state = (state + 0x6D2B79F5) >>> 0; let t = state; t = Math.imul(t ^ (t >>> 15), t | 1); t ^= t + Math.imul(t ^ (t >>> 7), t | 61); return ((t ^ (t >>> 14)) >>> 0) / 4294967296; };
    const result = [...items];
    for (let i = result.length - 1; i > 0; i--) { const j = Math.floor(random() * (i + 1)); [result[i], result[j]] = [result[j], result[i]]; }
    return result;
}

export async function showEvaluation(ctx, id) {
    const target = id || (await ctx.request('evaluations/start', { method: 'POST' })).id;
    const evaluation = await ctx.get(`evaluations/${target}`);
    if (evaluation.user_id !== ctx.user.id) throw new Error('Solo puedes responder tus propias evaluaciones.');
    if (evaluation.status === 'completed') { ctx.navigate('resultados'); return; }
    const questions = evaluation.questions ?? [];
    if (!questions.length) throw new Error('La prueba no tiene preguntas asignadas.');

    const [likertHelp, situationalHelp] = instructions(evaluation.assessment);
    const answers = new Map((evaluation.responses ?? []).map((r) => [r.evaluation_question_id, r]));
    let index = questions.findIndex((q) => !answers.has(q.id));
    if (index < 0) index = questions.length - 1;
    let busy = false;
    const total = questions.length;

    const header = () => `<div class="ws-panel space-y-3"><div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-medium text-slate-700">${e(evaluation.assessment?.name ?? 'Prueba de habilidades blandas')}</p><button type="button" class="ws-link text-sm" data-navigate="inicio">Salir · tu avance queda guardado</button></div>${progress(answers.size / total * 100, 'var(--color-brand-600)').replace('aria-label="Progreso"', `aria-label="${answers.size} de ${total} preguntas respondidas"`)}<p class="text-xs text-slate-600">${answers.size} de ${total} respondidas</p></div>`;

    function show(html) {
        page().innerHTML = `<div class="mx-auto max-w-2xl space-y-4">${html}</div>`;
        window.scrollTo({ top: 0 });
        page().querySelector('[data-focus]')?.focus({ preventScroll: true });
    }

    function renderQuestion() {
        const item = questions[index]; const q = item.question; const saved = answers.get(item.id);
        const likert = q.type === 'self_report';
        const statement = likert
            ? `<p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Afirmación</p><p class="mt-1 text-lg font-semibold leading-7 text-slate-900">${e(q.text)}</p>`
            : `<div class="rounded-card border border-brand-200 bg-brand-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-brand-800">Situación</p><p class="mt-1 text-base font-semibold leading-7 text-slate-900">${e(q.text)}</p></div>`;
        const choices = likert
            ? `<div class="grid gap-2 sm:grid-cols-5">${LIKERT.map((text, i) => `<label class="ws-choice sm:flex-col sm:justify-start sm:text-center"><input type="radio" name="answer" value="${i + 1}" required ${saved?.likert_value === i + 1 ? 'checked' : ''} class="size-5 shrink-0 accent-brand-600"><span><strong class="block text-base">${i + 1}</strong><span class="text-slate-700">${text}</span></span></label>`).join('')}</div>`
            : `<div class="grid gap-3">${shuffled(q.options ?? [], `${evaluation.id}-${q.id}`).map((opt) => `<label class="ws-choice items-start"><input type="radio" name="answer" value="${opt.id}" required ${saved?.question_option_id === opt.id ? 'checked' : ''} class="mt-0.5 size-5 shrink-0 accent-brand-600"><span class="leading-6">${e(opt.text)}</span></label>`).join('')}</div>`;
        const last = index === total - 1;

        show(header() + `<form id="evaluation-response-form" class="ws-panel space-y-5" novalidate>
            <p class="text-sm font-semibold text-brand-700" data-focus tabindex="-1">Pregunta ${index + 1} de ${total}</p>
            <fieldset class="space-y-4"><legend class="w-full">${statement}</legend>
                <p class="text-sm text-slate-600">${e((likert ? likertHelp : situationalHelp) || (likert ? 'Marca qué tanto describe tu forma habitual de trabajar.' : 'Elige lo que harías en realidad.'))}</p>
                ${choices}
            </fieldset>
            <div id="evaluation-feedback" aria-live="polite"></div>
            <div class="flex flex-wrap justify-between gap-3"><button type="button" id="evaluation-prev" class="ws-button" ${index === 0 ? 'disabled' : ''}>← Anterior</button><button type="submit" class="ws-primary">${last ? 'Revisar respuestas' : 'Siguiente →'}</button></div>
        </form>`);

        const form = document.querySelector('#evaluation-response-form');
        document.querySelector('#evaluation-prev').addEventListener('click', () => { if (!busy) { index = Math.max(0, index - 1); renderQuestion(); } });
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (busy) return;
            const value = Number(new FormData(form).get('answer'));
            if (!value) { form.querySelector('#evaluation-feedback').innerHTML = '<p class="text-sm font-medium text-danger-700" role="alert">Elige una respuesta para continuar.</p>'; return; }
            const unchanged = likert ? saved?.likert_value === value : saved?.question_option_id === value;
            busy = true; const submit = form.querySelector('[type="submit"]'); submit.disabled = true;
            try {
                if (!unchanged) {
                    const data = { evaluation_question_id: item.id, ...(likert ? { likert_value: value } : { question_option_id: value }) };
                    answers.set(item.id, await ctx.request(`evaluations/${evaluation.id}/responses`, { method: 'POST', data }));
                }
                busy = false;
                if (last) renderReview(); else { index++; renderQuestion(); }
            } catch (error) {
                form.querySelector('#evaluation-feedback').innerHTML = `<div class="ws-alert ws-alert--danger" role="alert">${formErrors(error)}</div>`;
                busy = false; submit.disabled = false;
            }
        });
    }

    function renderReview() {
        const missing = questions.map((q, i) => (answers.has(q.id) ? null : i)).filter((i) => i !== null);
        show(header() + `<section class="ws-panel space-y-5" aria-labelledby="review-title">
            <h2 id="review-title" class="text-xl font-bold" data-focus tabindex="-1">Revisa antes de enviar</h2>
            ${missing.length
                ? `<div class="ws-alert ws-alert--warning"><p class="font-semibold">Te faltan ${missing.length} ${missing.length === 1 ? 'pregunta' : 'preguntas'}.</p><div class="mt-3 flex flex-wrap gap-2">${missing.map((i) => `<button type="button" class="ws-button" data-goto="${i}">Pregunta ${i + 1}</button>`).join('')}</div></div>`
                : `<p class="text-sm leading-6 text-slate-700">Respondiste las ${total} preguntas. Puedes volver para cambiar alguna respuesta. Una vez enviada, la prueba ya no se puede modificar.</p>`}
            <div class="flex flex-wrap justify-between gap-3"><button type="button" class="ws-button" data-goto="${total - 1}">← Volver a las preguntas</button><button type="button" class="ws-primary" id="evaluation-submit" ${missing.length ? 'disabled' : ''}>Enviar prueba</button></div>
        </section>`);
        page().querySelectorAll('[data-goto]').forEach((el) => el.addEventListener('click', () => { index = Number(el.dataset.goto); renderQuestion(); }));
        document.querySelector('#evaluation-submit').addEventListener('click', confirmSubmit);
    }

    function confirmSubmit() {
        ctx.modal('¿Enviar la prueba?', `<div class="space-y-5"><p class="text-sm leading-6 text-slate-700">Después de enviarla no podrás cambiar tus respuestas y se calculará tu resultado.</p><div id="confirm-feedback" aria-live="polite"></div><div class="flex flex-wrap justify-end gap-3"><button type="button" class="ws-button" data-dialog-close>Cancelar</button><button type="button" class="ws-primary" id="evaluation-confirm">Sí, enviar</button></div></div>`);
        const confirm = document.querySelector('#evaluation-confirm');
        confirm.addEventListener('click', async () => {
            confirm.disabled = true; confirm.textContent = 'Enviando…';
            try {
                await ctx.request(`evaluations/${evaluation.id}/complete`, { method: 'POST' });
                ctx.close();
                show(`<section class="ws-panel space-y-4 text-center" aria-labelledby="done-title"><span class="mx-auto grid size-16 place-items-center rounded-full bg-success-100 text-3xl text-success-700" aria-hidden="true">✓</span><h2 id="done-title" class="text-2xl font-bold" data-focus tabindex="-1">¡Gracias! Tu prueba quedó enviada</h2><p class="text-sm leading-6 text-slate-700">Tus respuestas se guardaron y tu resultado ya está calculado. En él verás tus fortalezas y lo que puedes desarrollar.</p><button type="button" class="ws-primary" id="evaluation-results">Ver mi resultado</button></section>`);
                document.querySelector('#evaluation-results').addEventListener('click', () => ctx.refresh('resultados'));
            } catch (error) {
                document.querySelector('#confirm-feedback').innerHTML = `<div class="ws-alert ws-alert--danger">${formErrors(error)}</div>`;
                confirm.disabled = false; confirm.textContent = 'Sí, enviar';
            }
        });
    }

    renderQuestion();
}
