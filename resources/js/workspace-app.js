import { getAll, getData, requestData, normalize, connectionMessage } from './data/platform';
import { e, icons, empty, button, formErrors, initials } from './workspace-ui';
import { renderAdministration } from './workspace-admin';
import { renderPersonal, showEvaluation } from './workspace-personal';
import { openRecordForm, recordAction } from './workspace-forms';

const root = document.querySelector('[data-workspace]');
const titles = { dashboard: 'Diagnóstico Organizacional', inicio: 'Inicio', colaboradores: 'Colaboradores', evaluaciones: 'Evaluaciones', habilidades: 'Catálogo de habilidades', preguntas: 'Banco de preguntas', cargos: 'Cargos', diagnostico: 'Diagnóstico organizacional', 'mapa-calor': 'Mapa de diagnóstico', brechas: 'Brechas organizacionales', recomendaciones: 'Recomendaciones inteligentes', planes: 'Planes de mejoramiento', seguimiento: 'Seguimiento', reportes: 'Reportes', evaluacion: 'Mi evaluación', resultados: 'Mis resultados', plan: 'Mi plan de mejoramiento', progreso: 'Mi progreso', perfil: 'Mi perfil', notificaciones: 'Notificaciones', 'perfil-colaborador': 'Perfil del colaborador', areas: 'Áreas', empresas: 'Empresas' };
const dependencies = {
    dashboard: ['employees', 'evaluations', 'improvement-plans', 'diagnostics', 'positions', 'skills'],
    inicio: ['evaluations', 'improvement-plans'],
    colaboradores: ['employees', 'positions', 'evaluations', 'areas'],
    evaluaciones: ['evaluations', 'employees', 'questions', 'assessments'],
    habilidades: ['skills'], preguntas: ['questions', 'skills'], cargos: ['positions', 'areas', 'skills'],
    diagnostico: ['diagnostics', 'skills'], 'mapa-calor': ['diagnostics', 'skills'], brechas: ['diagnostics', 'skills', 'areas'], recomendaciones: ['diagnostics', 'skills', 'areas'],
    planes: ['improvement-plans', 'employees', 'skills'], seguimiento: ['improvement-plans', 'evaluations', 'employees', 'positions', 'areas'],
    reportes: ['evaluations', 'employees', 'positions', 'areas', 'improvement-plans', 'diagnostics'],
    evaluacion: ['evaluations'], resultados: ['evaluations'], plan: ['improvement-plans'], progreso: ['evaluations', 'improvement-plans'],
    perfil: ['evaluations', 'improvement-plans'], notificaciones: ['notifications'], 'perfil-colaborador': ['employees', 'positions', 'areas', 'evaluations', 'improvement-plans'],
    areas: ['areas'], empresas: ['companies'],
};

if (root) {
    const page = document.querySelector('#workspace-page');
    const dialog = document.querySelector('#workspace-dialog');
    const globalSearch = document.querySelector('#workspace-search');
    const cache = new Map();
    let generation = 0;
    let toastTimeout;
    const urls = new Map([...document.querySelectorAll('[data-screen-link]')].map((el) => [el.dataset.screenLink, el.getAttribute('href')]));
    const ctx = {
        screen: root.dataset.screen, admin: root.dataset.role !== 'collaborator', superadmin: root.dataset.role === 'superadmin',
        data: {}, state: { search: '', filters: {}, page: 1, sort: 0, direction: 1 }, selectedId: null, user: null,
        request: requestData, get: getData, reload: () => load(true), navigate, render,
        records: (key) => ctx.data[key] ?? [],
        axis: (id) => ctx.data.catalogs?.organizational_axes?.find((r) => r.id === Number(id)),
        category: (id) => ctx.data.catalogs?.skill_categories?.find((r) => r.id === Number(id)),
        find: (key, id) => ctx.records(key).find((r) => String(r.id) === String(id)),
        toast(message) {
            const el = document.querySelector('#workspace-toast'); el.textContent = message; el.hidden = false;
            clearTimeout(toastTimeout); toastTimeout = setTimeout(() => { el.hidden = true; }, 4000);
        },
        modal(title, html) {
            document.querySelector('#dialog-title').textContent = title;
            document.querySelector('#dialog-content').innerHTML = html;
            if (!dialog.open) dialog.showModal();
        },
        close: () => dialog.close(),
        filtered(records, searchable = (r) => JSON.stringify(r), predicate = () => true) {
            return records.filter((r) => normalize(searchable(r)).includes(normalize(ctx.state.search)) && predicate(r));
        },
        sorted(rows) {
            const index = ctx.state.sort;
            const text = (value) => new DOMParser().parseFromString(value, 'text/html').body.textContent;
            return [...rows].sort((a, b) => text(a[index] ?? '').localeCompare(text(b[index] ?? ''), 'es', { numeric: true, sensitivity: 'base' }) * ctx.state.direction);
        },
    };

    function navigate(screen, id = '') {
        const target = urls.get(screen) ?? (screen === 'perfil-colaborador' ? urls.get('colaboradores') : null);
        if (!target && screen !== ctx.screen) return;
        dialog.close();
        const url = new URL(target ?? location.href, location.origin);
        if (id) { url.searchParams.set('id', id); url.searchParams.set('screen', screen); }
        history.pushState({ screen }, '', url);
        ctx.screen = screen; ctx.selectedId = id || null;
        ctx.state = { search: '', filters: {}, page: 1, sort: 0, direction: 1 };
        globalSearch.value = '';
        document.querySelector('#sidebar-close')?.click();
        page.focus({ preventScroll: true }); window.scrollTo({ top: 0 });
        load();
    }

    function render() {
        document.querySelector('#workspace-title').textContent = titles[ctx.screen];
        document.querySelector('#workspace-breadcrumb').textContent = titles[ctx.screen];
        document.title = `${titles[ctx.screen]} | SkillDiagnóstico IA`;
        document.querySelectorAll('[data-screen-link]').forEach((el) => {
            if (el.dataset.screenLink === (ctx.screen === 'perfil-colaborador' ? 'colaboradores' : ctx.screen)) el.setAttribute('aria-current', 'page');
            else el.removeAttribute('aria-current');
        });
        page.innerHTML = `<div class="mb-4 flex flex-wrap items-center gap-3 text-xs text-slate-400"><span>Datos de tu organización</span>${button('Actualizar', 'reload')}</div><div class="space-y-5">${ctx.admin && !['perfil', 'notificaciones', 'resultados', 'evaluacion', 'plan', 'progreso', 'inicio'].includes(ctx.screen) ? renderAdministration(ctx) : renderPersonal(ctx)}</div>`;
        icons();
    }

    async function list(resource, force) {
        if (force || !cache.has(resource)) {
            const pending = getAll(resource).catch((error) => { cache.delete(resource); throw error; });
            cache.set(resource, pending);
        }
        return cache.get(resource);
    }

    async function load(force = false) {
        const current = ++generation;
        if (force) cache.clear();
        page.setAttribute('aria-busy', 'true');
        page.innerHTML = `<div role="status" class="space-y-4"><p class="text-sm text-slate-500">Cargando ${e(titles[ctx.screen])}…</p><div class="grid grid-cols-2 gap-4 lg:grid-cols-4">${Array.from({ length: 4 }, () => '<div class="h-28 animate-pulse rounded-xl bg-slate-200/60"></div>').join('')}</div><div class="h-64 animate-pulse rounded-xl bg-slate-200/60"></div></div>`;
        document.querySelector('#workspace-title').textContent = titles[ctx.screen];
        try {
            ctx.user = await getData('me');
            ctx.data.catalogs ??= await getData('catalogs');
            const requests = [...new Set(dependencies[ctx.screen] ?? [])];
            if (ctx.superadmin && !requests.includes('companies')) requests.push('companies');
            for (const resource of requests) {
                const data = await list(resource, force);
                if (current !== generation) return;
                ctx.data[resource] = data;
            }
            if (['dashboard', 'diagnostico', 'mapa-calor', 'brechas', 'recomendaciones', 'reportes'].includes(ctx.screen)) {
                const companyId = ctx.state.filters.company_id || ctx.user.company_id;
                const snapshots = ctx.records('diagnostics').filter((d) => !companyId || String(d.company_id) === String(companyId));
                const diagnostic = snapshots.find((d) => String(d.id) === String(ctx.state.filters.diagnostic_id)) ?? snapshots[0];
                ctx.data.diagnostic = diagnostic ? await getData(`diagnostics/${diagnostic.id}`) : null;
            }
            if (['resultados', 'progreso', 'perfil', 'inicio', 'perfil-colaborador'].includes(ctx.screen)) {
                const target = ctx.screen === 'perfil-colaborador' ? Number(ctx.selectedId) : ctx.user.id;
                const evaluated = ctx.records('evaluations').filter((ev) => ev.user_id === target && ev.status === 'completed');
                ctx.data.personalResults = [];
                for (const evaluation of evaluated) {
                    const detail = await getData(`evaluations/${evaluation.id}`);
                    if (current !== generation) return;
                    ctx.data.personalResults.push(detail);
                }
            }
            if (current !== generation) return;
            const notifications = await list('notifications', force);
            ctx.data.notifications = notifications;
            const unread = notifications.filter((n) => !n.read_at).length;
            const counter = document.querySelector('#unread-count'); counter.textContent = unread; counter.hidden = !unread;
            document.querySelectorAll('[data-user-name]').forEach((el) => { el.textContent = ctx.user.name; });
            document.querySelectorAll('[data-user-initials]').forEach((el) => { el.textContent = initials(ctx.user.name); });
            render();
        } catch (error) {
            if (current !== generation) return;
            page.innerHTML = `<div role="alert" class="rounded-xl border border-red-100 bg-red-50 p-5"><h2 class="font-bold text-red-800">No pudimos cargar esta pantalla</h2><p class="my-3 text-sm text-red-700">${e(connectionMessage(error))}</p><div class="flex flex-wrap gap-2">${button('Reintentar', 'reload')}<a class="ws-button" href="${e(document.querySelector('meta[name="login-url"]').content)}">Iniciar sesión</a></div></div>`;
        } finally {
            if (current === generation) page.removeAttribute('aria-busy');
        }
    }

    root.addEventListener('click', async (event) => {
        const nav = event.target.closest('[data-screen-link], [data-navigate]');
        if (nav && !event.ctrlKey && !event.metaKey && !event.shiftKey && event.button === 0) {
            event.preventDefault(); navigate(nav.dataset.screenLink ?? nav.dataset.navigate, nav.dataset.id); return;
        }
        if (event.target.closest('[data-dialog-close]')) { dialog.close(); return; }
        const sorter = event.target.closest('[data-sort]');
        if (sorter) { const sort = Number(sorter.dataset.sort); ctx.state.direction = ctx.state.sort === sort ? -ctx.state.direction : 1; ctx.state.sort = sort; render(); return; }
        const pagination = event.target.closest('[data-page]');
        if (pagination) { ctx.state.page = Number(pagination.dataset.page); render(); return; }
        const buttonEl = event.target.closest('[data-action]');
        if (!buttonEl) return;
        const { action, id } = buttonEl.dataset;
        if (action === 'reload') { load(true); return; }
        buttonEl.disabled = true;
        try {
            if (action === 'create' || action === 'edit') await openRecordForm(ctx, id || null);
            else if (action === 'evaluation-start') await showEvaluation(ctx, id);
            else await recordAction(ctx, action, id);
        } catch (error) {
            if (dialog.open) document.querySelector('#dialog-content').insertAdjacentHTML('afterbegin', `<div role="alert" class="mb-4">${formErrors(error)}</div>`);
            else ctx.toast(connectionMessage(error));
        } finally { buttonEl.disabled = false; }
    });

    root.addEventListener('change', (event) => {
        if (!event.target.matches('[data-filter]')) return;
        ctx.state.filters[event.target.dataset.filter] = event.target.value; ctx.state.page = 1;
        if (['company_id', 'diagnostic_id'].includes(event.target.dataset.filter) && ['dashboard', 'diagnostico', 'mapa-calor', 'brechas', 'recomendaciones', 'reportes'].includes(ctx.screen)) load();
        else render();
    });
    globalSearch.addEventListener('input', () => { ctx.state.search = globalSearch.value; ctx.state.page = 1; if (!page.hasAttribute('aria-busy')) render(); });
    dialog.addEventListener('click', (event) => { if (event.target === dialog) { const r = dialog.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close(); } });
    window.addEventListener('popstate', () => {
        const url = new URL(location.href);
        const matched = [...urls].find(([, path]) => new URL(path, location.origin).pathname === url.pathname);
        ctx.screen = url.searchParams.get('screen') === 'perfil-colaborador' ? 'perfil-colaborador' : matched?.[0] ?? root.dataset.screen;
        ctx.selectedId = url.searchParams.get('id'); ctx.state = { search: '', filters: {}, page: 1, sort: 0, direction: 1 }; globalSearch.value = ''; load();
    });
    const params = new URLSearchParams(location.search);
    if (params.get('screen') === 'perfil-colaborador' && ctx.admin) { ctx.screen = 'perfil-colaborador'; ctx.selectedId = params.get('id'); }
    const hashes = { '#habilidades': 'habilidades', '#preguntas': 'preguntas', '#cargos': 'cargos' };
    if (hashes[location.hash] && ctx.admin) ctx.screen = hashes[location.hash];
    icons(); load();
}
