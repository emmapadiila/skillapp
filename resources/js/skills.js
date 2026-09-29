import { getAll, getData, escapeHtml as escape, normalize, axisTone, connectionMessage } from './data/platform';

const screen = document.querySelector('#skills-screen');
if (screen) {
    const content = document.querySelector('#workspace-content');
    const title = document.querySelector('header h1');
    const breadcrumb = document.querySelector('[data-breadcrumb-current]');
    const originalTitle = title.textContent;
    const originalBreadcrumb = breadcrumb.textContent;
    const search = document.querySelector('#dashboard-search');
    const form = document.querySelector('#skill-form');
    const dialog = document.querySelector('#skill-dialog');
    let skills = [];
    let categories = [];
    let axes = [];
    let loaded = false;
    let loading = false;
    const expanded = new Set();
    const icons = { communication: 'M4 4h16v12H9l-5 4V4Z M8 8h8M8 12h5', collaboration: 'M8 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM2 21v-3a6 6 0 0 1 12 0v3M17 4a4 4 0 0 1 0 8M17 15a5 5 0 0 1 5 5', cognitive: 'M9 4a4 4 0 0 0-6 4 4 4 0 0 0 0 8 4 4 0 0 0 6 4V4ZM15 4a4 4 0 0 1 6 4 4 4 0 0 1 0 8 4 4 0 0 1-6 4V4Z', leadership: 'M12 3a9 9 0 1 0 9 9M12 7a5 5 0 1 0 5 5M12 12l9-9M16 3h5v5' };
    const tones = { communication: 'bg-sky-100 text-sky-600', collaboration: 'bg-emerald-100 text-emerald-600', cognitive: 'bg-violet-100 text-violet-600', leadership: 'bg-orange-100 text-orange-600' };

    function render() {
        const query = normalize(search.value.trim());
        const visible = skills.filter((skill) => normalize(`${skill.name} ${skill.category?.name ?? ''}`).includes(query));
        const groups = categories.filter((category) => !query || visible.some((skill) => skill.skill_category_id === category.id));
        document.querySelector('#skills-summary').textContent = loaded ? `${skills.length} habilidades en ${categories.length} categorías${skills.some((skill) => skill.local) ? ' · Incluye borradores locales' : ''}` : 'Catálogo no disponible';
        document.querySelector('#skills-empty').hidden = groups.length > 0;
        document.querySelector('#skills-list').innerHTML = groups.map((category) => {
            const rows = visible.filter((skill) => skill.skill_category_id === category.id);
            return `<details data-category="${category.id}" ${expanded.has(String(category.id)) ? 'open' : ''} class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"><summary class="flex cursor-pointer list-none items-center gap-4 p-5 focus-visible:outline-2 focus-visible:outline-blue-600"><span class="grid size-10 shrink-0 place-items-center rounded-xl ${tones[category.code] ?? 'bg-slate-100 text-slate-600'}" aria-hidden="true"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="${icons[category.code] ?? 'M12 3 21 12 12 21 3 12 12 3Z'}"/></svg></span><span class="min-w-0 flex-1"><span class="block break-words font-semibold">${escape(category.name)}</span><span class="text-xs text-slate-400">${rows.length} habilidades</span></span><span class="text-slate-400 group-open:rotate-180" aria-hidden="true">⌄</span></summary><div class="divide-y divide-slate-100 border-t border-slate-100">${rows.map((skill) => `<div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"><div class="min-w-0"><h3 class="break-words text-sm font-medium">${escape(skill.name)}${skill.local ? ' <span class="text-xs text-amber-700">· Borrador local</span>' : ''}</h3><div class="mt-2 flex flex-wrap gap-1.5">${(skill.axes ?? []).map((axis) => `<span class="collaborator-badge collaborator-badge--${axisTone(axis)}">${escape(axis.name)}</span>`).join('')}</div></div><span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">${skill.target_level == null ? 'Sin nivel objetivo' : `Nivel ${escape(skill.target_level)}`}</span></div>`).join('') || '<p class="p-5 text-sm text-slate-500">Esta categoría aún no tiene habilidades.</p>'}</div></details>`;
        }).join('');
        screen.querySelectorAll('details').forEach((details) => details.addEventListener('toggle', () => {
            if (details.open) expanded.add(details.dataset.category);
            else expanded.delete(details.dataset.category);
        }));
    }

    async function load() {
        if (loading) return;
        loading = true;
        document.querySelector('#skills-status').textContent = 'Cargando catálogo…';
        try {
            const [catalog, records] = await Promise.all([getData('catalogs'), getAll('skills')]);
            categories = catalog.skill_categories;
            axes = catalog.organizational_axes;
            skills = [...records, ...skills.filter((skill) => skill.local)];
            if (!loaded && categories.length) expanded.add(String(categories[0].id));
            loaded = true;
            document.querySelector('#skills-status').textContent = 'Catálogo consultado. Los borradores solo existen en esta página.';
            form.elements.category.innerHTML = '<option value="">Selecciona una categoría</option>' + categories.map((category) => `<option value="${category.id}">${escape(category.name)}</option>`).join('');
            document.querySelector('#skill-axes').innerHTML = axes.map((axis) => `<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="axis" value="${axis.id}" class="accent-blue-600">${escape(axis.name)}</label>`).join('');
        } catch (error) {
            document.querySelector('#skills-status').textContent = connectionMessage(error);
        } finally {
            loading = false;
            render();
        }
    }

    function navigate() {
        const active = location.hash === '#habilidades';
        search.closest('label').hidden = !active && !!document.querySelector('[data-organizational-dashboard]');
        screen.hidden = !active;
        content.hidden = active;
        title.textContent = active ? 'Catálogo de habilidades' : originalTitle;
        breadcrumb.textContent = active ? 'Habilidades' : originalBreadcrumb;
        document.title = `${title.textContent} | SkillDiagnóstico`;
        document.querySelectorAll('[data-workspace-nav]').forEach((link) => {
            const current = active ? link.dataset.workspaceNav === 'skills' : link.dataset.originalActive === 'true';
            link.classList.toggle('admin-nav-link--active', current);
            if (current) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
        if (active && !loaded) load();
    }
    document.querySelectorAll('[data-workspace-nav]').forEach((link) => { link.dataset.originalActive = String(link.getAttribute('aria-current') === 'page'); });
    window.addEventListener('hashchange', navigate);
    search.addEventListener('input', () => { if (!screen.hidden) render(); });
    document.querySelector('#skills-reload').addEventListener('click', load);
    document.querySelector('#skill-add').addEventListener('click', () => {
        form.reset();
        const available = loaded && categories.length > 0 && axes.length > 0;
        document.querySelector('#skill-submit').disabled = !available;
        document.querySelector('#skill-feedback').textContent = available ? '' : 'Carga las categorías y ejes de la organización para preparar una habilidad. No se crearán opciones de ejemplo.';
        dialog.showModal();
    });
    screen.querySelectorAll('[data-close-skill]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const selectedAxes = [...form.querySelectorAll('[name="axis"]:checked')].map((input) => axes.find((axis) => axis.id === Number(input.value)));
        const name = form.elements.name.value.trim();
        const category = categories.find((item) => item.id === Number(form.elements.category.value));
        if (!name || !category || !selectedAxes.length) {
            document.querySelector('#skill-feedback').textContent = 'Escribe un nombre y selecciona una categoría y al menos un eje.';
            return;
        }
        skills.push({ name, category, skill_category_id: category.id, axes: selectedAxes, target_level: Number(form.elements.level.value), local: true });
        expanded.add(String(category.id));
        search.value = '';
        render();
        dialog.close();
    });
    navigate();
}
