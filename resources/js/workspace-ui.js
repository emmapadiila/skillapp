import { escapeHtml as e } from './data/platform';

export { e };
export const labels = { pending: 'Pendiente', in_progress: 'En progreso', completed: 'Completado', expired: 'Vencido', cancelled: 'Cancelado', initial: 'Inicial', reevaluation: 'Reevaluación', self_report: 'Autorreporte', situational: 'Situacional', situational_judgment: 'Juicio situacional', basic: 'Básico', intermediate: 'Intermedio', advanced: 'Avanzado', critical: 'Crítico', high: 'Alta', medium: 'Media', low: 'Baja', outstanding: 'Sobresaliente', strong: 'Sólido', developing: 'En desarrollo', collaborator: 'Colaborador', human_resources: 'Talento humano', superadmin: 'Administrador', reading: 'Lectura', video: 'Video', exercise: 'Ejercicio' };
export const label = (value) => labels[value] ?? value ?? '—';
export const percent = (score) => score == null ? null : Math.round(Number(score) * 20);
export const date = (value, calendar = false) => value ? new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium', timeZone: 'America/Bogota' }).format(new Date(calendar || value.length === 10 ? `${value.slice(0, 10)}T12:00:00Z` : value)) : 'Sin fecha';
export const average = (values) => values.length ? values.reduce((a, b) => a + Number(b), 0) / values.length : null;
export const color = (value) => value == null ? '#94A3B8' : value >= 70 ? '#16A34A' : value >= 50 ? '#D97706' : '#DC2626';
export const initials = (name) => String(name ?? '').split(' ').filter(Boolean).map((s) => s[0]).slice(0, 2).join('');
export const identity = (user) => `<div class="flex min-w-0 items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-blue-600 text-xs font-bold text-white">${e(initials(user?.name))}</span><span class="min-w-0"><strong class="block break-words text-sm">${e(user?.name ?? 'Sin colaborador')}</strong>${user?.email ? `<span class="block break-all text-xs text-slate-400">${e(user.email)}</span>` : ''}</span></div>`;
export const badge = (text, tone = 'slate') => `<span class="ws-badge ws-badge--${tone}">${e(label(text))}</span>`;
export const statusBadge = (status) => badge(status, { completed: 'green', in_progress: 'blue', pending: 'amber', expired: 'red', cancelled: 'slate', critical: 'red', outstanding: 'green', strong: 'green', developing: 'amber', high: 'red', medium: 'amber' }[status] ?? 'slate');
export const axisBadge = (axis) => badge(axis?.name ?? 'Sin eje', { operational: 'blue', mission: 'green', strategic: 'violet' }[axis?.code] ?? 'slate');
export const button = (text, action, id = '', primary = false) => `<button type="button" class="${primary ? 'ws-primary' : 'ws-button'}" data-action="${e(action)}" data-id="${e(id)}">${e(text)}</button>`;
export const link = (text, screen, id = '', primary = false) => `<button type="button" class="${primary ? 'ws-primary' : 'ws-button'}" data-navigate="${e(screen)}" data-id="${e(id)}">${e(text)}</button>`;
export const empty = (title = 'No hay registros para mostrar', description = 'Los registros aparecerán aquí cuando estén disponibles.') => `<div class="rounded-xl border border-dashed border-slate-200 bg-white px-5 py-12 text-center"><h2 class="text-base font-bold">${e(title)}</h2><p class="mt-2 text-sm text-slate-500">${e(description)}</p></div>`;
export const panel = (title, content, action = '') => `<article class="ws-panel"><div class="mb-4 flex flex-wrap items-center justify-between gap-2"><h2 class="font-bold">${e(title)}</h2>${action}</div>${content}</article>`;
export const progress = (value, tone = '#1D4ED8') => `<div class="flex min-w-24 items-center gap-2"><progress class="ws-progress" style="--progress-color:${tone}" value="${Math.min(100, Math.max(0, Number(value) || 0))}" max="100" aria-label="Progreso"></progress><strong class="text-xs tabular-nums">${value == null ? '—' : `${Math.round(Number(value))}%`}</strong></div>`;
export const kpis = (items) => `<div class="grid grid-cols-2 gap-4 ${items.length > 4 ? 'lg:grid-cols-3 xl:grid-cols-6' : 'lg:grid-cols-4'}">${items.map(([title, value, icon = 'chart', tone = 'blue', hint = '']) => `<article class="ws-panel"><div class="mb-3 flex items-start justify-between gap-2"><h2 class="text-xs font-medium text-slate-500">${e(title)}</h2><span class="ws-badge ws-badge--${tone} rounded-lg p-2">${svg(icon)}</span></div><p class="text-2xl font-bold tabular-nums">${e(value ?? '—')}</p>${hint ? `<p class="mt-1 text-xs text-slate-400">${e(hint)}</p>` : ''}</article>`).join('')}</div>`;

const paths = {
    home: '<path d="m3 10 9-7 9 7v10H3V10Z"/><path d="M9 20v-7h6v7"/>',
    users: '<path d="M3 21v-3a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v3m2-17a4 4 0 0 1 0 8m2 3a4 4 0 0 1 3 4v2"/><circle cx="9" cy="6" r="4"/>',
    user: '<circle cx="12" cy="7" r="4"/><path d="M4 21v-3a5 5 0 0 1 5-5h6a5 5 0 0 1 5 5v3"/>',
    clipboard: '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M8 10h8M8 14h8M8 18h4"/>',
    chart: '<path d="M4 3v18h17M8 16v-5M13 16V7M18 16v-8"/>',
    target: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
    trend: '<path d="m3 17 6-6 4 4 8-11M15 4h6v6"/>',
    brain: '<path d="M12 3c-4-3-7 1-6 4-5 0-5 7-1 8-1 5 5 7 7 3m0-15c4-3 7 1 6 4 5 0 5 7 1 8 1 5-5 7-7 3V3Z"/>',
    help: '<circle cx="12" cy="12" r="9"/><path d="M9 8a3 3 0 1 1 4 3c-1 1-1 2-1 3m0 3h.01"/>',
    briefcase: '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V3h8v4M3 12h18m-9 0v3"/>',
    map: '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Zm6-3v15m6-12v15"/>',
    warning: '<path d="m12 3 10 18H2L12 3Zm0 6v5m0 3h.01"/>',
    sparkles: '<path d="m12 3 2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6Zm8 13 1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3Z"/>',
    calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 2v6m8-6v6M3 10h18"/>',
    file: '<path d="M5 3h9l5 5v13H5V3Zm9 0v5h5M8 13h8m-8 4h8"/>',
    bell: '<path d="M18 9a6 6 0 0 0-12 0c0 6-3 6-3 9h18c0-3-3-3-3-9Zm-8 12h4"/>',
    building: '<path d="M4 21V3h12v18m0-12h4v12H4m4-14h4m-4 4h4m-4 4h4"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    search: '<circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/>',
};
export const svg = (name) => `<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths[name] ?? paths.chart}</svg>`;
export function icons() { document.querySelectorAll('[data-icon]').forEach((el) => { el.innerHTML = svg(el.dataset.icon); }); }

export function bars(items, vertical = false) {
    if (!items.length || items.every((i) => i.value == null)) return empty('Todavía no hay resultados');
    if (!vertical) return `<div class="space-y-4">${items.map((item) => `<div><div class="mb-2 flex justify-between gap-3 text-xs"><span>${e(item.name)}</span><strong>${item.value == null ? '—' : `${Math.round(item.value)}%`}</strong></div>${progress(item.value, item.color ?? color(item.value))}</div>`).join('')}</div>`;
    return `<div class="ws-chart" role="img" aria-label="Resultados por eje de 0 a 100 por ciento"><div class="ws-chart-scale"><span>100</span><span>75</span><span>50</span><span>25</span><span>0</span></div><div class="ws-chart-bars">${items.map((item) => `<div class="ws-chart-column"><strong>${item.value == null ? '—' : `${Math.round(item.value)}%`}</strong><div style="height:${Math.max(0, Math.min(100, item.value ?? 0))}%;background:${item.color ?? '#3B82F6'}" title="${e(item.name)}: ${item.value == null ? 'Sin datos' : `${Math.round(item.value)}%`}"></div><span>${e(item.name)}</span></div>`).join('')}</div></div>`;
}

export function lineChart(series) {
    if (!series.length) return empty('No hay historial disponible');
    const points = series.map((point, i) => `${30 + i * (480 / Math.max(1, series.length - 1))},${210 - point.value * 1.8}`).join(' ');
    return `<svg viewBox="0 0 540 250" class="w-full" role="img" aria-label="Evolución de resultados"><path d="M30 30H510M30 75H510M30 120H510M30 165H510M30 210H510" stroke="#F1F5F9"/><polyline points="${points}" fill="none" stroke="#3B82F6" stroke-width="3"/>${series.map((p, i) => `<g><circle cx="${30 + i * (480 / Math.max(1, series.length - 1))}" cy="${210 - p.value * 1.8}" r="4" fill="#3B82F6"><title>${e(p.name)}: ${Math.round(p.value)}%</title></circle><text x="${30 + i * (480 / Math.max(1, series.length - 1))}" y="235" text-anchor="middle" fill="#64748B" font-size="10">${e(p.name)}</text></g>`).join('')}</svg>`;
}

export function table(columns, rows, state = {}, caption = 'Registros') {
    if (!rows.length) return empty('No hay resultados', 'Prueba otros filtros o registra un nuevo elemento.');
    const page = Math.min(state.page ?? 1, Math.max(1, Math.ceil(rows.length / 10)));
    const pageRows = rows.slice((page - 1) * 10, page * 10);
    return `<div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm"><div class="hidden overflow-x-auto md:block"><table class="ws-table"><caption class="sr-only">${e(caption)}</caption><thead><tr>${columns.map((col, i) => `<th aria-sort="${state.sort === i ? (state.direction === -1 ? 'descending' : 'ascending') : 'none'}"><button type="button" data-sort="${i}" class="text-left">${e(col)} ${state.sort === i ? (state.direction === -1 ? '↓' : '↑') : '↕'}</button></th>`).join('')}</tr></thead><tbody>${pageRows.map((row) => `<tr>${row.map((cell) => `<td>${cell}</td>`).join('')}</tr>`).join('')}</tbody></table></div><div class="divide-y divide-slate-100 md:hidden">${pageRows.map((row) => `<article class="grid grid-cols-2 gap-3 p-4">${row.map((cell, i) => `<div class="min-w-0 ${i === 0 || i === row.length - 1 ? 'col-span-2' : ''}"><p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">${e(columns[i])}</p><div class="text-sm">${cell}</div></div>`).join('')}</article>`).join('')}</div><div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-xs text-slate-500"><span>${(page - 1) * 10 + 1}–${Math.min(page * 10, rows.length)} de ${rows.length} resultados</span><div class="flex items-center gap-2"><button class="ws-button" data-page="${page - 1}" ${page === 1 ? 'disabled' : ''}>Anterior</button><span>${page} / ${Math.ceil(rows.length / 10)}</span><button class="ws-button" data-page="${page + 1}" ${page * 10 >= rows.length ? 'disabled' : ''}>Siguiente</button></div></div></div>`;
}

export const options = (records, selected = '', placeholder = 'Seleccionar') => `<option value="">${e(placeholder)}</option>${records.map((r) => `<option value="${e(r.id)}" ${String(r.id) === String(selected) ? 'selected' : ''}>${e(r.name)}</option>`).join('')}`;
export const select = (title, name, records, value = '', required = true) => `<label class="ws-field"><span>${e(title)}</span><select name="${e(name)}" ${required ? 'required' : ''}>${options(records, value)}</select></label>`;
export const input = (title, name, value = '', type = 'text', attrs = '') => `<label class="ws-field"><span>${e(title)}</span><input name="${e(name)}" type="${type}" value="${e(value ?? '')}" ${attrs}></label>`;
export const textarea = (title, name, value = '', attrs = '') => `<label class="ws-field col-span-full"><span>${e(title)}</span><textarea name="${e(name)}" rows="3" ${attrs}>${e(value ?? '')}</textarea></label>`;
export const filter = (name, title, records, value = '') => `<label><span class="sr-only">${e(title)}</span><select class="ws-input" data-filter="${e(name)}">${options(records, value, title)}</select></label>`;
export const enums = (map) => Object.entries(map).map(([id, name]) => ({ id, name }));
export const toolbar = (filters, add = '', summary = '') => `<div class="flex flex-wrap items-center gap-2">${filters}${add ? `<div class="ml-auto">${add}</div>` : ''}</div>${summary ? `<p class="mt-3 text-xs text-slate-500">${e(summary)}</p>` : ''}`;
export const formErrors = (error) => `<p class="text-sm font-medium text-red-700">${e(error.message)}</p>${Object.entries(error.fields ?? {}).map(([name, messages]) => `<p data-error-for="${e(name)}" class="mt-1 text-xs text-red-600">${e(messages.join(' '))}</p>`).join('')}`;
