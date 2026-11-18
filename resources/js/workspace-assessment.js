import { e, date, empty, progress, formErrors, svg } from './workspace-ui';
import { connectionMessage } from './data/platform';

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
        return `<div class="mx-auto max-w-2xl">${confirmation}<article class="ws-panel space-y-4 text-center"><span class="mx-auto grid size-14 place-items-center rounded-full bg-success-100 text-success-700" aria-hidden="true">${svg('check').replace('size-4', 'size-7')}</span><h2 class="text-xl font-bold">Ya completaste tu prueba</h2><p class="text-sm text-slate-600">La enviaste el ${e(date(completed.completed_at))}. Talento Humano programará tu próxima reevaluación.</p><button type="button" class="ws-primary" data-navigate="resultados">Ver mi resultado</button></article></div>`;
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

// Un solo aviso al cerrar la pestaña mientras haya respuestas guardándose.
let unsavedFlow = null;
// Cada apertura de la prueba es un flujo; uno viejo nunca dibuja sobre la pantalla actual.
let activeFlow = 0;
// Estado de guardado por evaluación: sobrevive a «Salir» y reabrir, para no perder ni ocultar cambios sin confirmar.
const stores = new Map();
const storeFor = (evaluationId) => {
    if (!stores.has(evaluationId)) stores.set(evaluationId, { answers: new Map(), pending: new Map(), failed: new Map(), choices: new Map(), finalizing: false });
    return stores.get(evaluationId);
};
window.addEventListener('beforeunload', (event) => { if (unsavedFlow?.()) { event.preventDefault(); event.returnValue = ''; } });

export async function showEvaluation(ctx, id) {
    const flow = ++activeFlow;
    const pageRevision = ctx.pageRevision;
    const isActive = () => flow === activeFlow && ctx.pageRevision === pageRevision;
    const target = String(id || (await ctx.request('evaluations/start', { method: 'POST' })).id);
    if (!isActive()) return;
    const store = storeFor(target);
    if (store.pending.size) await Promise.allSettled([...store.pending.values()]);
    if (!isActive()) return;
    const evaluation = await ctx.get(`evaluations/${target}`);
    if (!isActive()) return;
    if (evaluation.user_id !== ctx.user.id) throw new Error('Solo puedes responder tus propias evaluaciones.');
    if (evaluation.status === 'completed') { ctx.navigate('resultados'); return; }
    const questions = evaluation.questions ?? [];
    if (!questions.length) throw new Error('La prueba no tiene preguntas asignadas.');

    const [likertHelp, situationalHelp] = instructions(evaluation.assessment);
    const { answers, pending, failed, choices } = store;
    answers.clear();
    for (const response of evaluation.responses ?? []) answers.set(response.evaluation_question_id, response);
    const hasUnsavedAnswers = () => pending.size > 0 || failed.size > 0;
    unsavedFlow = hasUnsavedAnswers;
    let index = questions.findIndex((q) => !answers.has(q.id));
    if (index < 0) index = questions.length - 1;
    let reviewing = false;
    const total = questions.length;
    const isLikert = (item) => item.question.type === 'self_report';
    const firstOfType = (type) => questions.findIndex((item) => item.question.type === type);

    const status = (html, tone = 'text-slate-600') => { const el = isActive() && document.querySelector('#save-status'); if (el) { el.className = `min-h-6 text-sm ${tone}`; el.innerHTML = html; } };

    function save(item, value) {
        choices.set(item.id, value);
        const data = { evaluation_question_id: item.id, ...(isLikert(item) ? { likert_value: value } : { question_option_id: value }) };
        const previous = pending.get(item.id) ?? Promise.resolve();
        const request = previous.catch(() => {}).then(() => ctx.request(`evaluations/${evaluation.id}/responses`, { method: 'POST', data }));
        pending.set(item.id, request);
        failed.delete(item.id);
        request.then((response) => {
            answers.set(item.id, response);
            if (pending.get(item.id) !== request) return;
            choices.delete(item.id);
            pending.delete(item.id);
            if (questions[index] === item) { status('Respuesta guardada.'); updateProgress(); }
        }, () => {
            if (pending.get(item.id) !== request) return;
            pending.delete(item.id);
            failed.set(item.id, value);
            if (questions[index] === item) status('No se pudo guardar tu respuesta. Revisa tu conexión y pulsa "Siguiente" para intentarlo de nuevo.', 'font-medium text-danger-700');
        });
        return request;
    }

    // Garantiza que la respuesta de esta pregunta quedó guardada antes de avanzar.
    async function ensureSaved(item) {
        if (pending.has(item.id)) { try { await pending.get(item.id); } catch { /* se reintenta abajo */ } }
        if (isActive() && failed.has(item.id)) await save(item, failed.get(item.id));
    }

    // Con la edición bloqueada, espera lo pendiente y reintenta los cambios fallidos.
    async function flushAll() {
        await Promise.allSettled([...pending.values()]);
        if (!isActive()) return failed.size;
        await Promise.allSettled([...failed.keys()].map((itemId) => ensureSaved(questions.find((item) => item.id === itemId))));
        return failed.size;
    }

    const progressBar = () => progress(answers.size / total * 100, 'var(--color-brand-600)', false).replace(/aria-label="[^"]*"/, `aria-label="${answers.size} de ${total} preguntas respondidas"`);
    const updateProgress = () => {
        const bar = isActive() && document.querySelector('#test-progress progress');
        if (!bar) return;
        bar.value = Math.min(100, answers.size / total * 100);
        bar.setAttribute('aria-label', `${answers.size} de ${total} preguntas respondidas`);
    };
    const header = (label) => `<div class="space-y-2"><div class="flex items-center justify-between gap-3"><p class="text-sm font-semibold text-slate-900">${label}</p><button type="button" class="ws-link text-sm" data-navigate="inicio">Salir<span class="sr-only"> de la prueba; tu avance queda guardado</span></button></div><div id="test-progress">${progressBar()}</div></div>`;

    // Devuelve false si la persona ya salió de la prueba: así una espera terminada tarde no la reabre.
    function show(html) {
        if (!isActive()) return false;
        page().innerHTML = `<div class="mx-auto max-w-2xl space-y-4 pb-24 sm:pb-0">${html}</div>`;
        window.scrollTo({ top: 0 });
        page().querySelector('[data-focus]')?.focus({ preventScroll: true });
        return true;
    }

    function answerLabel(item) {
        const response = answers.get(item.id);
        if (!response) return null;
        if (isLikert(item)) return `${response.likert_value} · ${LIKERT[response.likert_value - 1]}`;
        return item.question.options?.find((o) => o.id === response.question_option_id)?.text ?? 'Respuesta guardada';
    }

    function renderQuestion() {
        const item = questions[index]; const q = item.question; const saved = answers.get(item.id);
        const likert = isLikert(item);
        // La elección local solo prevalece mientras esté pendiente o haya fallado.
        const selected = choices.get(item.id) ?? (likert ? saved?.likert_value : saved?.question_option_id);
        const initialStatus = pending.has(item.id) ? 'Guardando…' : failed.has(item.id) ? '' : saved ? 'Respuesta guardada.' : '';
        const position = `Pregunta ${index + 1} de ${total}`;
        const title = `<h2 data-focus tabindex="-1" class="text-lg font-semibold leading-7 text-slate-900 outline-none sm:text-xl"><span class="sr-only">${position}. </span>${e(q.text)}</h2>`;
        const statement = likert ? title : `<div class="rounded-card border border-brand-200 bg-brand-50 p-4">${title}</div>`;
        const help = (likert ? likertHelp : situationalHelp) || (likert ? 'Marca qué tanto describe tu forma habitual de trabajar.' : 'Elige lo que harías en realidad.');
        const choiceList = likert
            ? `<div class="grid gap-2 sm:grid-cols-5">${LIKERT.map((text, i) => `<label class="ws-choice group py-3 sm:min-h-32 sm:flex-col sm:justify-start sm:text-center"><input type="radio" name="answer" value="${i + 1}" ${selected === i + 1 ? 'checked' : ''} class="sr-only"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-slate-100 font-semibold text-slate-800 group-has-checked:bg-brand-600 group-has-checked:text-white" aria-hidden="true">${i + 1}</span><span>${text}</span></label>`).join('')}</div>`
            : `<div class="grid gap-3">${shuffled(q.options ?? [], `${evaluation.id}-${q.id}`).map((opt) => `<label class="ws-choice items-start"><input type="radio" name="answer" value="${opt.id}" ${selected === opt.id ? 'checked' : ''} class="mt-0.5 size-5 shrink-0 accent-brand-600"><span class="leading-6">${e(opt.text)}</span></label>`).join('')}</div>`;
        const last = index === total - 1;
        const nextLabel = reviewing ? 'Volver a la revisión' : last ? 'Revisar respuestas' : 'Siguiente';

        if (!show(header(`${position} <span class="font-normal text-slate-600">· ${likert ? 'Afirmación' : 'Situación'}</span>`) + `<form id="evaluation-response-form" class="ws-panel space-y-5" novalidate>
            <fieldset class="ws-enter space-y-4"><legend class="w-full">${statement}</legend>
                <details class="text-[0.9375rem] text-slate-700" ${index === firstOfType(q.type) ? 'open' : ''}><summary class="cursor-pointer font-medium text-brand-700">Cómo responder</summary><p class="mt-2 leading-6">${e(help)}</p></details>
                ${choiceList}
            </fieldset>
            <p id="save-status" role="status" class="min-h-6 text-sm text-slate-600">${initialStatus}</p>
            <div class="ws-test-actions"><button type="button" id="evaluation-prev" class="ws-button" ${index === 0 ? 'disabled' : ''}>${svg('arrowLeft')}Anterior</button><button type="submit" class="ws-primary flex-1 sm:flex-none">${nextLabel}${reviewing ? '' : svg('arrowRight')}</button></div>
        </form>`)) return;

        const form = document.querySelector('#evaluation-response-form');
        const fieldset = form.querySelector('fieldset');
        const previous = document.querySelector('#evaluation-prev');
        form.addEventListener('change', (event) => {
            if (event.target.name !== 'answer' || fieldset.disabled || store.finalizing || !form.isConnected || !isActive()) return;
            status('Guardando…');
            save(item, Number(event.target.value));
        });
        previous.addEventListener('click', () => { if (!previous.disabled) { index = Math.max(0, index - 1); renderQuestion(); } });
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            if (submit.disabled || store.finalizing || !form.isConnected || !isActive()) return;
            if (!new FormData(form).get('answer')) { status('Elige una respuesta para continuar.', 'font-medium text-danger-700'); return; }
            submit.disabled = true;
            const opensReview = reviewing || last;
            if (opensReview) {
                fieldset.disabled = true;
                previous.disabled = true;
            }
            try {
                await ensureSaved(item);
            } catch (error) {
                if (!form.isConnected || !isActive()) return;
                fieldset.disabled = false;
                previous.disabled = index === 0;
                status(`No se pudo guardar tu respuesta. ${e(connectionMessage(error))}`, 'font-medium text-danger-700');
                submit.disabled = false;
                return;
            }
            // Si mientras se esperaba la persona ya cambió de pregunta, este formulario viejo no mueve nada.
            if (!form.isConnected || !isActive()) return;
            if (opensReview) await renderReview(); else { index++; renderQuestion(); }
        });
    }

    async function renderReview() {
        reviewing = true;
        const unsaved = await flushAll();
        if (!isActive()) return;
        const missing = questions.filter((item) => !answers.has(item.id)).length;
        const rows = questions.map((item, i) => {
            const answer = answerLabel(item);
            const lost = failed.has(item.id);
            const detail = lost ? 'Tu último cambio no se guardó.' : answer ?? 'Sin responder';
            return `<li class="flex items-start gap-3 py-3"><span class="w-6 shrink-0 pt-0.5 text-sm font-semibold tabular-nums text-slate-600">${i + 1}</span><div class="min-w-0 flex-1"><p class="line-clamp-2 text-[0.9375rem] leading-6 text-slate-900">${e(item.question.text)}</p><p class="mt-1 text-sm ${answer && !lost ? 'text-slate-700' : 'font-medium text-warning-700'}">${e(detail)}</p></div><button type="button" class="ws-button" data-goto="${i}">${answer && !lost ? 'Cambiar' : 'Responder'}<span class="sr-only"> la pregunta ${i + 1}</span></button></li>`;
        }).join('');
        if (!show(header('Revisión de respuestas') + `<section class="ws-panel space-y-4" aria-labelledby="review-title">
            <h2 id="review-title" class="text-xl font-bold outline-none" data-focus tabindex="-1">Revisa antes de enviar</h2>
            <p class="text-[0.9375rem] leading-6 text-slate-700">${unsaved ? `${unsaved === 1 ? 'Una respuesta no se pudo guardar' : `${unsaved} respuestas no se pudieron guardar`}. Revisa tu conexión y vuelve a marcarlas antes de enviar.` : missing ? `Te ${missing === 1 ? 'falta 1 pregunta' : `faltan ${missing} preguntas`} por responder.` : `Respondiste las ${total} preguntas. Una vez enviada, la prueba ya no se puede modificar.`}</p>
            <ol class="ws-enter divide-y divide-slate-200 border-y border-slate-200">${rows}</ol>
            <div class="ws-test-actions"><button type="button" class="ws-button" data-goto="${total - 1}">${svg('arrowLeft')}Preguntas</button><button type="button" class="ws-primary flex-1 sm:flex-none" id="evaluation-submit" ${missing || unsaved ? 'disabled' : ''}>Enviar prueba</button></div>
        </section>`)) return;
        page().querySelectorAll('[data-goto]').forEach((el) => el.addEventListener('click', () => { index = Number(el.dataset.goto); renderQuestion(); }));
        document.querySelector('#evaluation-submit').addEventListener('click', confirmSubmit);
    }

    function confirmSubmit(event) {
        const submit = event.currentTarget;
        const canSubmit = () => isActive() && submit.isConnected && !submit.disabled && !store.finalizing && !hasUnsavedAnswers();
        if (!canSubmit()) return;
        ctx.modal('¿Enviar la prueba?', `<div class="space-y-5"><p class="text-[0.9375rem] leading-6 text-slate-700">Después de enviarla no podrás cambiar tus respuestas y se calculará tu resultado.</p><div id="confirm-feedback" aria-live="polite"></div><div class="flex flex-wrap justify-end gap-3"><button type="button" class="ws-button" data-dialog-close>Cancelar</button><button type="button" class="ws-primary" id="evaluation-confirm">Sí, enviar</button></div></div>`);
        const dialog = document.querySelector('#workspace-dialog');
        const confirm = document.querySelector('#evaluation-confirm');
        const feedback = document.querySelector('#confirm-feedback');
        const closers = [...dialog.querySelectorAll('[data-dialog-close]')];
        const blockEscape = (event) => event.preventDefault();
        // El diálogo modal deja inerte el resto de la página: mientras siga abierto nadie puede cambiar respuestas.
        const keepOpen = () => { if (store.finalizing && isActive()) dialog.showModal(); };
        const lock = (locked) => {
            store.finalizing = locked;
            closers.forEach((el) => { el.disabled = locked; });
            if (locked) { dialog.addEventListener('cancel', blockEscape); dialog.addEventListener('close', keepOpen); }
            else { dialog.removeEventListener('cancel', blockEscape); dialog.removeEventListener('close', keepOpen); }
        };
        confirm.addEventListener('click', async () => {
            if (!canSubmit()) return;
            confirm.disabled = true; confirm.textContent = 'Enviando…';
            lock(true);
            try {
                try {
                    await ctx.request(`evaluations/${evaluation.id}/complete`, { method: 'POST' });
                } catch (error) {
                    // Respuesta incierta (p. ej. se cortó la red): se pregunta al servidor si la prueba quedó enviada.
                    const current = await ctx.get(`evaluations/${evaluation.id}`).catch(() => null);
                    if (current?.status !== 'completed') throw error;
                }
                lock(false);
                stores.delete(target);
                if (unsavedFlow === hasUnsavedAnswers) unsavedFlow = null;
                ctx.close();
                if (!isActive()) return;
                if (!show(`<section class="ws-panel space-y-4 text-center" aria-labelledby="done-title"><span class="mx-auto grid size-16 place-items-center rounded-full bg-success-100 text-success-700" aria-hidden="true">${svg('check').replace('size-4', 'size-8')}</span><h2 id="done-title" class="text-2xl font-bold outline-none" data-focus tabindex="-1">¡Gracias! Tu prueba quedó enviada</h2><p class="text-[0.9375rem] leading-6 text-slate-700">Tus respuestas se guardaron y tu resultado ya está calculado. En él verás tus fortalezas y lo que puedes desarrollar.</p><button type="button" class="ws-primary" id="evaluation-results">Ver mi resultado</button></section>`)) return;
                document.querySelector('#evaluation-results').addEventListener('click', () => ctx.refresh('resultados'));
            } catch (error) {
                lock(false);
                if (!isActive()) { ctx.close(); return; }
                feedback.innerHTML = `<div class="ws-alert ws-alert--danger">${formErrors(error)}</div>`;
                confirm.disabled = false; confirm.textContent = 'Sí, enviar';
            }
        });
    }

    renderQuestion();
}
