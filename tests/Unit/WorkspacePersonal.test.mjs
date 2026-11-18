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
const { renderPersonal } = await import('../../resources/js/workspace-personal.js');
hooks.deregister();
delete globalThis.window;

const cases = [
    ['60.40', '60.90', '60,4', '60,9', '+0,5'],
    [0.07, 0.57, '0,0', '0,5', '+0,5'],
    ['75.00', '74.99', '75,0', '74,9', '-0,1'],
    ['49.96', '50.00', '49,9', '50,0', '+0,1'],
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
        assert.ok(!html.includes('%'));
    });
}
