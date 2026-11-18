import assert from 'node:assert/strict';
import { registerHooks } from 'node:module';
import test from 'node:test';

const workspaceUrl = new URL('../../resources/js/', import.meta.url).href;
const hooks = registerHooks({
    resolve(specifier, context, nextResolve) {
        if (context.parentURL?.startsWith(workspaceUrl) && specifier.startsWith('./') && !/\.[^/]+$/.test(specifier)) {
            return nextResolve(`${specifier}.js`, context);
        }
        return nextResolve(specifier, context);
    },
});
globalThis.window = new EventTarget();
const { renderPersonal, resultContent } = await import('../../resources/js/workspace-personal.js');
hooks.deregister();
delete globalThis.window;

const cases = [
    ['60.40', '60.90', '60,40', '60,90', '+0,50'],
    [0.07, 0.57, '0,07', '0,57', '+0,50'],
    ['75.00', '74.99', '75,00', '74,99', '-0,01'],
    ['49.99', '50.00', '49,99', '50,00', '+0,01'],
    ['60.01', '60.09', '60,01', '60,09', '+0,08'],
    ['50.00', '50.00', '50,00', '50,00', '0,00'],
];

for (const [before, now, visibleBefore, visibleNow, change] of cases) {
    test(`Mi progreso muestra el cambio entre ${before} y ${now} con la precisión de los puntajes visibles`, () => {
        const personalResults = [now, before].map((score, index) => ({
            completed_at: index === 0 ? '2026-02-01' : '2026-01-01',
            result: { total_score: score, skill_results: [{ skill_id: 1, score }] },
        }));

        const html = renderPersonal({ screen: 'progreso', data: { personalResults }, records: () => [] });

        assert.ok(html.includes(`Tu resultado cambió ${change} puntos</h2>`));
        assert.ok(html.includes(`${visibleBefore} puntos → <strong class="text-brand-600">${visibleNow} puntos</strong>`));
        assert.deepEqual(
            [...html.matchAll(/<title>[^<]+: ([^<]+)<\/title>/g)].map((match) => match[1]),
            [`${visibleBefore} puntos`, `${visibleNow} puntos`],
        );
        assert.ok(html.includes(`aria-label="${visibleNow} puntos"`));
        assert.ok(html.includes(`aria-valuetext="${visibleNow} puntos"`));
        assert.deepEqual(
            [...html.matchAll(/<circle [^>]*cy="([^"]+)"/g)].map((match) => Number(match[1])),
            [210 - Number(before) * 1.8, 210 - Number(now) * 1.8],
        );
        assert.ok(!html.includes('%'));
    });
}

test('Resultados muestra centésimas en el total, las habilidades, las categorías y el radar', () => {
    const evaluation = {
        completed_at: '2026-02-01',
        result: {
            total_score: '74.99',
            level: 'developing',
            skill_results: [
                { skill_id: 1, skill: { name: 'Escucha' }, score: '49.99', level: 'to_develop' },
                { skill_id: 2, skill: { name: 'Comunicación' }, score: '75.00', level: 'strength' },
                { skill_id: 3, skill: { name: 'Empatía' }, score: '50.00', level: 'developing' },
            ],
            category_results: [{ skill_category_id: 1, score: '74.98', level: 'developing' }],
        },
    };

    const html = resultContent({ category: () => ({ name: 'Relaciones' }) }, evaluation);

    assert.match(html, /Tu nivel general: <span[^>]*>En desarrollo<\/span>/);
    assert.match(html, /Puntaje total: <strong[^>]*>74,99 puntos de 100<\/strong>/);
    assert.match(html, /74,98 puntos<\/span>.*aria-label="74,98 puntos"/);
    assert.match(html, /aria-label="[^"]*Escucha: 49,99 puntos[^"]*"/);
    assert.match(html, /aria-label="75,00 puntos"/);
    assert.match(html, /aria-label="50,00 puntos"/);
    assert.match(html, /En desarrollo desde 50 hasta menos de 75 puntos/);
});
