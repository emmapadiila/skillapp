import { connectionMessage } from './data/platform';
import { collaborators, collaboratorAxes, collaboratorStatuses, loadCollaborators } from './data/collaborators';

const page = document.querySelector('[data-collaborators-page]');

if (page) {
    const search = page.querySelector('#collaborator-search');
    const globalSearch = document.querySelector('#dashboard-search');
    const filters = [...page.querySelectorAll('[data-collaborator-filter]')];
    const sortButtons = [...page.querySelectorAll('[data-collaborator-sort]')];
    const mobileSort = page.querySelector('#collaborator-mobile-sort');
    const tableBody = page.querySelector('#collaborators-table-body');
    const cards = page.querySelector('#collaborator-cards');
    let hasData = false;
    let loading = false;
    let sortColumn = 'name';
    let sortDirection = 'ascending';
    const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
    const escape = (value) => String(value).replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);
    const initials = (name) => name.split(' ').map((part) => part[0]).slice(0, 2).join('');
    const badge = ({ label, tone }) => `<span class="collaborator-badge collaborator-badge--${tone}">${escape(label)}</span>`;
    const score = (person) => person.score === null
        ? '<span class="text-xs italic text-slate-400">Sin resultado</span>'
        : `<span class="font-bold ${person.score >= 3.5 ? 'text-emerald-600' : 'text-amber-600'}">${person.score} / 5</span>`;
    const profileButton = (person) => `<button type="button" data-profile="${person.id}" class="collaborator-profile-button" aria-label="Ver perfil de ${escape(person.name)}"><svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>Ver perfil</button>`;
    const identity = (person) => `<div class="flex min-w-0 items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-blue-600 text-xs font-bold text-white">${escape(initials(person.name))}</span><div class="min-w-0"><p class="font-semibold text-slate-900">${escape(person.name)}</p><p class="break-all text-xs text-slate-400">${escape(person.email)}</p></div></div>`;
    const valueFor = (person) => sortColumn === 'axis' ? collaboratorAxes[person.axis].label
        : sortColumn === 'status' ? collaboratorStatuses[person.status].label : person[sortColumn];

    const render = () => {
        const query = normalize(search.value.trim());
        const selected = collaborators.filter((person) =>
            ['name', 'email', 'position', 'area'].some((key) => normalize(person[key]).includes(query))
            && filters.every((filter) => filter.value === 'all' || person[filter.dataset.collaboratorFilter] === filter.value),
        ).sort((first, second) => {
            const a = valueFor(first);
            const b = valueFor(second);
            if (a === null || b === null) return a === b ? 0 : a === null ? 1 : -1;
            const comparison = typeof a === 'number' ? a - b : a.localeCompare(b, 'es', { sensitivity: 'base' });
            return sortDirection === 'ascending' ? comparison : -comparison;
        });

        page.querySelector('#collaborator-total').textContent = selected.length;
        page.querySelector('#collaborator-evaluated').textContent = selected.filter((person) => person.status === 'evaluated').length;
        page.querySelector('#collaborator-pending').textContent = selected.filter((person) => person.status === 'pending').length;
        page.querySelector('#collaborators-empty').classList.toggle('hidden', selected.length > 0);
        page.querySelector('#collaborator-table-wrap').hidden = selected.length === 0;
        cards.hidden = selected.length === 0;
        if (!hasData) {
            ['total', 'evaluated', 'pending'].forEach((key) => { page.querySelector('#collaborator-' + key).textContent = '—'; });
            page.querySelector('#collaborators-empty').classList.add('hidden');
        }
        sortButtons.forEach((button) => button.closest('th').setAttribute('aria-sort', button.dataset.collaboratorSort === sortColumn ? sortDirection : 'none'));
        mobileSort.value = `${sortColumn}:${sortDirection}`;

        tableBody.innerHTML = selected.map((person) => `<tr><td>${identity(person)}</td><td>${escape(person.position)}</td><td>${escape(person.area)}</td><td>${badge(collaboratorAxes[person.axis])}</td><td>${score(person)}</td><td>${badge(collaboratorStatuses[person.status])}</td><td class="text-right">${profileButton(person)}</td></tr>`).join('');
        cards.innerHTML = selected.map((person) => `<article class="min-w-0 rounded-lg border border-slate-200 bg-white p-4">${identity(person)}<dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div class="min-w-0"><dt class="text-xs text-slate-500">Cargo</dt><dd class="mt-1 break-words">${escape(person.position)}</dd></div><div class="min-w-0"><dt class="text-xs text-slate-500">Área</dt><dd class="mt-1 break-words">${escape(person.area)}</dd></div><div><dt class="mb-1 text-xs text-slate-500">Eje</dt><dd>${badge(collaboratorAxes[person.axis])}</dd></div><div><dt class="mb-1 text-xs text-slate-500">Resultado</dt><dd>${score(person)}</dd></div></dl><div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">${badge(collaboratorStatuses[person.status])}${profileButton(person)}</div></article>`).join('');
    };

    [search, globalSearch].filter(Boolean).forEach((input) => input.addEventListener('input', () => {
        if (location.hash === '#habilidades') return;
        search.value = input.value;
        if (globalSearch) globalSearch.value = input.value;
        render();
    }));
    filters.forEach((filter) => filter.addEventListener('change', render));
    sortButtons.forEach((button) => button.addEventListener('click', () => {
        sortDirection = sortColumn === button.dataset.collaboratorSort && sortDirection === 'ascending' ? 'descending' : 'ascending';
        sortColumn = button.dataset.collaboratorSort;
        render();
    }));
    mobileSort.addEventListener('change', () => {
        [sortColumn, sortDirection] = mobileSort.value.split(':');
        render();
    });
    const reset = () => {
        search.value = '';
        if (globalSearch) globalSearch.value = '';
        filters.forEach((filter) => { filter.value = 'all'; });
        render();
    };
    page.querySelector('#collaborator-reset-filters').addEventListener('click', reset);
    page.querySelector('[data-reset-collaborator-search]').addEventListener('click', reset);
    page.querySelector('#collaborator-extra-filters-toggle').addEventListener('click', (event) => {
        const button = event.currentTarget;
        const expanded = button.getAttribute('aria-expanded') !== 'true';
        button.setAttribute('aria-expanded', String(expanded));
        page.querySelector('#collaborator-extra-filters').classList.toggle('hidden', !expanded);
    });

    const profile = document.querySelector('#collaborator-profile-dialog');
    page.addEventListener('click', (event) => {
        const button = event.target.closest('[data-profile]');
        if (!button) return;
        const person = collaborators.find((item) => item.id === Number(button.dataset.profile));
        const details = { ...person, initials: initials(person.name), axis: collaboratorAxes[person.axis].label, status: collaboratorStatuses[person.status].label, score: person.score === null ? 'Sin resultado' : `${person.score} / 5` };
        ['name', 'email', 'initials', 'position', 'area', 'axis', 'status', 'score'].forEach((key) => {
            profile.querySelector(`#profile-${key}`).textContent = details[key];
        });
        profile.showModal();
    });
    const addDialog = document.querySelector('#collaborator-add-dialog');
    const form = document.querySelector('#collaborator-add-form');
    const feedback = document.querySelector('#collaborator-form-feedback');
    page.querySelector('#collaborator-add').addEventListener('click', () => {
        form.reset();
        feedback.textContent = '';
        addDialog.showModal();
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        feedback.textContent = 'El alta de colaboradores desde esta pantalla está pendiente de conexión. No se enviaron ni guardaron datos.';
    });
    document.querySelectorAll('.collaborator-dialog').forEach((dialog) => {
        dialog.querySelectorAll('.dialog-close').forEach((button) => button.addEventListener('click', () => dialog.close()));
    });
    async function load() {
        if (loading) return;
        loading = true;
        const status = page.querySelector('#collaborators-status');
        status.textContent = 'Cargando colaboradores…';
        try {
            await loadCollaborators();
            hasData = true;
            const axisFilter = page.querySelector('#collaborator-axis-filter');
            axisFilter.innerHTML = '<option value="all">Todos los ejes</option>' + Object.entries(collaboratorAxes).map(([key, axis]) => `<option value="${escape(key)}">${escape(axis.label)}</option>`).join('');
            page.querySelector('#collaborator-area-filter').innerHTML = '<option value="all">Todas las áreas</option>' + [...new Set(collaborators.map((person) => person.area))].sort().map((area) => `<option>${escape(area)}</option>`).join('');
            render();
            status.textContent = 'Datos consultados · Estado y resultado de la última evaluación asignada.';
        } catch (error) {
            status.textContent = connectionMessage(error);
            ['total', 'evaluated', 'pending'].forEach((key) => { page.querySelector(`#collaborator-${key}`).textContent = '—'; });
        } finally {
            loading = false;
        }
    }
    render();
    page.querySelector('#collaborators-reload').addEventListener('click', load);
    load();
}
