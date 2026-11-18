import { e, date, formatScore, empty, panel, scoreProgress, levelColor, table, statusBadge, badge, button, label, options } from './workspace-ui';

export const DASHBOARD_SCREENS = { 'tablero-operativo': 'operational', 'tablero-misional': 'mission', 'tablero-estrategico': 'strategic' };
const AXIS_ORDER = ['operational', 'mission', 'strategic'];
const LEVEL_CELL = { strength: 'bg-success-100 text-success-700', developing: 'bg-warning-100 text-warning-700', to_develop: 'bg-danger-100 text-danger-700' };
const score = (value) => (value == null ? '—' : formatScore(value));
// Orden del instrumento (CR, TC, CA, DD), no el alfabético del catálogo.
const CATEGORY_ORDER = ['communication', 'collaboration', 'cognitive', 'leadership'];
const AUDIENCE_TEXT = { operational: 'Para líderes de primera línea.', mission: 'Para responsables de procesos centrales.', strategic: 'Para la alta dirección.' };
// Promedios del tablero: mismos límites que ResultLevel en el servidor (<50, 50 a <75, ≥75).
const levelFor = (value) => (value == null ? null : value >= 75 ? 'strength' : value >= 50 ? 'developing' : 'to_develop');
const orderedCategories = (ctx) => [...(ctx.data.catalogs?.skill_categories ?? [])].sort((a, b) => CATEGORY_ORDER.indexOf(a.code) - CATEGORY_ORDER.indexOf(b.code));

/** Query string de los filtros del tablero (área, cargo y periodo). */
export function dashboardQuery(ctx) {
    const params = new URLSearchParams();
    ['area_id', 'position_id', 'from', 'to'].forEach((key) => { if (ctx.state.filters[key]) params.set(key, ctx.state.filters[key]); });
    return params.toString();
}

export function renderAxisDashboard(ctx) {
    const code = DASHBOARD_SCREENS[ctx.screen];
    const data = ctx.data.axisDashboard;
    const axis = data?.axes?.find((a) => a.code === code);
    if (!axis) return empty('No pudimos preparar este tablero', 'Vuelve a intentarlo con «Actualizar».');
    const filtered = ['area_id', 'position_id', 'from', 'to'].some((key) => ctx.state.filters[key]);

    const levelList = (items) => `<ul class="space-y-4">${items.map((item) => `<li class="space-y-2"><div class="flex flex-wrap items-center justify-between gap-2"><span class="min-w-0 text-[0.9375rem] text-slate-900">${e(item.name)}</span><span class="flex flex-wrap items-center gap-2">${item.heavy ? badge('Peso alto', 'brand') : ''}${item.value == null ? '' : statusBadge(levelFor(item.value))}<span class="text-sm tabular-nums text-slate-700">${score(item.value)}</span></span></div>${scoreProgress(item.value, levelColor(levelFor(item.value)))}</li>`).join('')}</ul>`;
    const categoryIds = orderedCategories(ctx).map((c) => c.id);
    const categories = [...axis.categories].sort((a, b) => categoryIds.indexOf(a.id) - categoryIds.indexOf(b.id));

    const content = axis.evaluated
        ? summary(axis) + explanation(axis)
            + `<div class="grid items-start gap-5 lg:grid-cols-2">${panel('Habilidades', levelList(axis.skills.map((s) => ({ name: s.name, value: s.average, heavy: s.weight > 1 }))))}${panel('Categorías', levelList(categories.map((c) => ({ name: ctx.category(c.id)?.name ?? 'Categoría', value: c.average }))))}</div>`
            + people(ctx, axis)
        : filtered
            ? `<div class="space-y-3">${empty('No hay resultados con estos filtros', 'Prueba con otra área, otro cargo u otro periodo.')}<div class="flex justify-center">${button('Limpiar filtros', 'dashboard-clear')}</div></div>`
            : empty(`Todavía nadie del eje ${axis.name.toLowerCase()} ha terminado su prueba`, code === 'strategic'
                ? 'Cuando alguien la termine, aquí verás el promedio y las brechas del eje. El mapa de calor de abajo ya compara los tres ejes.'
                : 'Cuando alguien la termine, aquí verás el promedio y las brechas del eje.');

    return filters(ctx, axis) + header(axis) + content + (code === 'strategic' ? heatmap(ctx, data) + vertical(data) : '');
}

function filters(ctx, axis) {
    const { area_id: areaId = '', position_id: positionId = '', from = '', to = '' } = ctx.state.filters;
    const positions = ctx.records('positions').filter((p) => p.organizational_axis_id === axis.id && (!areaId || String(p.area_id) === String(areaId)));
    const select = (key, title, records, value) => `<label class="ws-field"><span>${e(title)}</span><select data-filter="${key}">${options(records, value, 'Todas')}</select></label>`;
    const dateInput = (key, title, value) => `<label class="ws-field"><span>${e(title)}</span><input type="date" data-filter="${key}" value="${e(value)}"></label>`;
    const active = areaId || positionId || from || to;
    return `<div role="group" class="ws-panel grid grid-cols-2 gap-3 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto] lg:items-end" aria-label="Filtros del tablero">${select('area_id', 'Área', ctx.records('areas'), areaId)}${select('position_id', 'Cargo', positions, positionId)}${dateInput('from', 'Desde', from)}${dateInput('to', 'Hasta', to)}${active ? button('Limpiar filtros', 'dashboard-clear') : ''}</div>`;
}

function header(axis) {
    const indicators = axis.assessment?.indicators ?? [];
    const about = axis.assessment?.axis_description || indicators.length
        ? `<details class="mt-3 text-[0.9375rem]"><summary class="cursor-pointer font-medium text-brand-700">Sobre este eje</summary><div class="mt-3 space-y-3 leading-6 text-slate-700">${axis.assessment?.axis_description ? `<p>${e(axis.assessment.axis_description)}</p>` : ''}${indicators.length ? `<div><p class="font-medium text-slate-900">Indicadores organizacionales para contrastar</p><ol class="mt-1 list-decimal space-y-1 pl-5">${indicators.map((i) => `<li>${e(i)}</li>`).join('')}</ol></div>` : ''}</div></details>`
        : '';
    return `<section class="ws-panel" aria-labelledby="axis-title"><h2 id="axis-title" class="text-xl font-bold">Eje ${e(axis.name.toLowerCase())}</h2><p class="mt-1 text-[0.9375rem] text-slate-600">${e(AUDIENCE_TEXT[axis.code] ?? axis.audience)}</p>${about}</section>`;
}

function summary(axis) {
    const d = axis.distribution;
    const stat = (title, value, detail = '') => `<div class="min-w-0"><dt class="text-sm text-slate-600">${e(title)}</dt><dd class="mt-1 text-2xl font-bold tabular-nums text-slate-900">${value}</dd>${detail ? `<dd class="mt-1">${detail}</dd>` : ''}</div>`;
    return `<section class="ws-panel" aria-label="Resumen del eje"><dl class="grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-5">
        ${stat('Personas evaluadas', axis.evaluated)}
        ${stat('Promedio del eje', e(score(axis.average)), axis.level ? statusBadge(axis.level) : '')}
        ${stat('Fortaleza', d.strength, '<span class="text-sm text-slate-600">75 puntos o más</span>')}
        ${stat('En desarrollo', d.developing, '<span class="text-sm text-slate-600">de 50 a menos de 75</span>')}
        ${stat('Por desarrollar', d.to_develop, '<span class="text-sm text-slate-600">menos de 50</span>')}
    </dl></section>`;
}

/** Explicación en lenguaje sencillo de qué sostiene y qué baja el promedio del eje. */
function explanation(axis) {
    const measured = axis.skills.filter((s) => s.average != null).sort((a, b) => b.average - a.average);
    const list = (items) => items.map((s) => `${e(s.name)} (${score(s.average)})`).join(' y ');
    const top = measured.slice(0, 2); const bottom = measured.slice(-2).reverse().filter((s) => !top.includes(s));
    return panel('Cómo leer este resultado', `<div class="space-y-2 text-sm leading-6 text-slate-700"><p>El promedio de ${axis.evaluated} ${axis.evaluated === 1 ? 'persona' : 'personas'} es <strong>${score(axis.average)}</strong>, nivel <strong>${e(label(axis.level))}</strong>.</p>${top.length ? `<p>Lo que más lo sostiene: ${list(top)}.</p>` : ''}${bottom.length ? `<p>Lo que más lo baja: ${list(bottom)}.</p>` : ''}<p>Cada habilidad es el promedio de sus preguntas llevado a 0–100. Las de peso alto cuentan 1,5 veces en el total.</p></div>`);
}

function people(ctx, axis) {
    const rows = axis.people.map((p) => [e(p.name ?? 'Sin nombre'), e(p.position ?? 'Sin cargo'), e(p.area ?? 'Sin área'), scoreProgress(p.total, levelColor(p.level), true), statusBadge(p.level), date(p.completed_at), button('Ver resultado', 'employee-evaluation', p.evaluation_id)]);
    return panel('Resultados individuales', table(['Persona', 'Cargo', 'Área', 'Resultado', 'Nivel', 'Fecha', ''], ctx.sorted(rows), ctx.state, 'Resultados individuales del eje'));
}

function heatmap(ctx, data) {
    const axes = AXIS_ORDER.map((code) => data.axes.find((a) => a.code === code)).filter(Boolean);
    const categories = orderedCategories(ctx);
    const cellFor = (category, axis) => data.matrix.find((m) => m.category_id === category.id && m.axis_code === axis.code);
    const cell = (c) => {
        if (!c?.measured) return '<span class="block rounded-control border border-dashed border-slate-300 px-2 py-3 text-center text-xs text-slate-600">No se mide en este eje</span>';
        if (c.average == null) return '<span class="block rounded-control bg-slate-50 px-2 py-3 text-center text-xs text-slate-600">Sin datos</span>';
        return `<span class="block rounded-control px-2 py-2 text-center ${LEVEL_CELL[c.level] ?? ''}"><strong class="block text-base tabular-nums">${e(score(c.average))}</strong><span class="text-xs">${e(label(c.level))} · ${c.count} ${c.count === 1 ? 'persona' : 'personas'}</span></span>`;
    };
    // Escritorio: tabla categoría × eje. Móvil: una tarjeta por categoría con sus tres ejes apilados.
    const table = `<div class="hidden sm:block"><table class="ws-table"><caption class="sr-only">Promedio por categoría y eje</caption><thead><tr><th scope="col">Categoría</th>${axes.map((a) => `<th scope="col">${e(a.name)}</th>`).join('')}</tr></thead><tbody>${categories.map((c) => `<tr><th scope="row" class="whitespace-normal bg-white text-sm normal-case tracking-normal text-slate-800">${e(c.name)}</th>${axes.map((a) => `<td>${cell(cellFor(c, a))}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
    const stacked = `<ul class="space-y-4 sm:hidden">${categories.map((c) => `<li><h3 class="mb-2 text-[0.9375rem] font-semibold text-slate-900">${e(c.name)}</h3><dl class="grid gap-2">${axes.map((a) => `<div class="grid grid-cols-[6.5rem_1fr] items-center gap-2"><dt class="text-sm text-slate-600">${e(a.name)}</dt><dd>${cell(cellFor(c, a))}</dd></div>`).join('')}</dl></li>`).join('')}</ul>`;
    return panel('Mapa de calor: categorías × ejes', `<p class="mb-4 text-[0.9375rem] leading-6 text-slate-700">Cada celda es el promedio de los resultados de esa categoría en ese eje. Permite ver si una brecha es general o propia de un eje.</p>${table}${stacked}<div class="mt-4 flex flex-wrap gap-2">${badge('75 o más · Fortaleza', 'green')}${badge('De 50 a menos de 75 · En desarrollo', 'amber')}${badge('Menos de 50 · Por desarrollar', 'red')}</div>`);
}

function vertical(data) {
    const axisNames = ['Operativo', 'Misional', 'Estratégico'];
    const value = (s) => (s.average == null ? 'Sin datos' : e(score(s.average)));
    const table = `<div class="hidden sm:block"><table class="ws-table"><caption class="sr-only">Habilidades análogas por eje</caption><thead><tr>${axisNames.map((n) => `<th scope="col">${n}</th>`).join('')}</tr></thead><tbody>${data.vertical.map((row) => `<tr>${row.map((s) => `<td><span class="block text-sm text-slate-800">${e(s.name)}</span><strong class="tabular-nums">${value(s)}</strong></td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
    const stacked = `<ol class="space-y-4 sm:hidden">${data.vertical.map((row) => `<li class="rounded-card border border-slate-200 p-3"><dl class="grid gap-2">${row.map((s, i) => `<div><dt class="text-xs text-slate-600">${axisNames[i]}</dt><dd class="flex items-baseline justify-between gap-3"><span class="min-w-0 text-sm text-slate-900">${e(s.name)}</span><strong class="shrink-0 text-sm tabular-nums">${value(s)}</strong></dd></div>`).join('')}</dl></li>`).join('')}</ol>`;
    return panel('Lectura vertical: de la operación a la dirección', `<p class="mb-4 text-[0.9375rem] leading-6 text-slate-700">Compara habilidades análogas entre ejes para ver si las fortalezas de la operación continúan hacia la dirección.</p>${table}${stacked}`);
}

