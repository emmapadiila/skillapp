import { e, date, formatScore, empty, panel, scoreProgress, levelColor, kpis, bars, table, statusBadge, badge, button, label, options } from './workspace-ui';

export const DASHBOARD_SCREENS = { 'tablero-operativo': 'operational', 'tablero-misional': 'mission', 'tablero-estrategico': 'strategic' };
const AXIS_ORDER = ['operational', 'mission', 'strategic'];
const LEVEL_CELL = { strength: 'bg-success-100 text-success-700', developing: 'bg-warning-100 text-warning-700', to_develop: 'bg-danger-100 text-danger-700' };
const score = (value) => (value == null ? '—' : formatScore(value));

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
    if (!axis) return empty('No pudimos preparar este tablero');

    const content = axis.evaluated
        ? summary(axis) + explanation(axis) + `<div class="grid gap-5 lg:grid-cols-2">${panel('Habilidades', bars(axis.skills.map((s) => ({ name: `${s.name}${s.weight > 1 ? ' · peso alto' : ''}`, value: s.average })), false, formatScore))}${panel('Categorías', bars(axis.categories.map((c) => ({ name: ctx.category(c.id)?.name ?? 'Categoría', value: c.average })), false, formatScore))}</div>` + people(ctx, axis)
        : empty('Aún no hay pruebas completadas en este eje', 'Ajusta los filtros o espera a que las personas de este eje terminen su prueba.');

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
    return `<section class="ws-panel space-y-3" aria-labelledby="axis-title"><p class="text-sm font-medium text-brand-700">Dirigido a: ${e(axis.audience)}</p><h2 id="axis-title" class="text-xl font-bold">Eje ${e(axis.name.toLowerCase())}</h2>${axis.assessment?.axis_description ? `<p class="text-sm leading-6 text-slate-700">${e(axis.assessment.axis_description)}</p>` : ''}${indicators.length ? `<details class="text-sm"><summary class="cursor-pointer font-medium text-brand-700">Indicadores organizacionales para contrastar (${indicators.length})</summary><ol class="mt-2 list-decimal space-y-1 pl-5 text-slate-700">${indicators.map((i) => `<li>${e(i)}</li>`).join('')}</ol></details>` : ''}</section>`;
}

function summary(axis) {
    const d = axis.distribution;
    return kpis([
        ['Personas evaluadas', axis.evaluated, 'users', 'slate'],
        ['Promedio del eje', score(axis.average), 'chart', 'brand', label(axis.level)],
        ['Fortaleza', d.strength, 'sparkles', 'green', '75 o más'],
        ['Por debajo de 75', d.developing + d.to_develop, 'trend', 'amber', `${d.developing} en desarrollo · ${d.to_develop} por desarrollar`],
    ]);
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
    const categories = ctx.data.catalogs?.skill_categories ?? [];
    const cell = (category, axis) => {
        const c = data.matrix.find((m) => m.category_id === category.id && m.axis_code === axis.code);
        if (!c?.measured) return '<td><span class="block rounded-control border border-dashed border-slate-300 px-2 py-3 text-center text-xs text-slate-600">No se mide en este eje</span></td>';
        if (c.average == null) return '<td><span class="block rounded-control bg-slate-50 px-2 py-3 text-center text-xs text-slate-600">Sin datos</span></td>';
        return `<td><span class="block rounded-control px-2 py-2 text-center ${LEVEL_CELL[c.level] ?? ''}"><strong class="block text-lg tabular-nums">${score(c.average)}</strong><span class="text-xs">${e(label(c.level))} · ${c.count} ${c.count === 1 ? 'persona' : 'personas'}</span></span></td>`;
    };
    return panel('Mapa de calor: categorías × ejes', `<p class="mb-4 text-sm leading-6 text-slate-700">Cada celda es el promedio (0–100) de los resultados de esa categoría en ese eje. Permite ver si una brecha es general o propia de un eje.</p><div class="overflow-x-auto"><table class="ws-table min-w-[560px]"><caption class="sr-only">Promedio por categoría y eje</caption><thead><tr><th scope="col">Categoría</th>${axes.map((a) => `<th scope="col">${e(a.name)}</th>`).join('')}</tr></thead><tbody>${categories.map((c) => `<tr><th scope="row" class="whitespace-normal bg-white text-sm normal-case tracking-normal text-slate-800">${e(c.name)}</th>${axes.map((a) => cell(c, a)).join('')}</tr>`).join('')}</tbody></table></div><div class="mt-4 flex flex-wrap gap-2">${badge('75 o más · Fortaleza', 'green')}${badge('Desde 50 hasta menos de 75 · En desarrollo', 'amber')}${badge('Menos de 50 · Por desarrollar', 'red')}</div>`);
}

function vertical(data) {
    return panel('Lectura vertical: de la operación a la dirección', `<p class="mb-4 text-sm leading-6 text-slate-700">Compara habilidades análogas entre ejes para ver si las fortalezas de la operación continúan hacia la dirección.</p><div class="overflow-x-auto"><table class="ws-table min-w-[560px]"><caption class="sr-only">Habilidades análogas por eje</caption><thead><tr><th scope="col">Operativo</th><th scope="col">Misional</th><th scope="col">Estratégico</th></tr></thead><tbody>${data.vertical.map((row) => `<tr>${row.map((s) => `<td><span class="block text-sm text-slate-800">${e(s.name)}</span><strong class="tabular-nums">${s.average == null ? 'Sin datos' : score(s.average)}</strong></td>`).join('')}</tr>`).join('')}</tbody></table></div>`);
}
