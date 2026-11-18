import assert from 'node:assert/strict';
import { register } from 'node:module';
import { setImmediate } from 'node:timers/promises';
import test from 'node:test';

register('./support/extensionless-loader.mjs', import.meta.url);
globalThis.window = new EventTarget();
const { showEvaluation } = await import('../../resources/js/workspace-assessment.js');
delete globalThis.window;

function element(properties = {}) {
    const listeners = new Map();
    return {
        disabled: false,
        isConnected: true,
        addEventListener(type, listener) { listeners.set(type, listener); },
        removeEventListener(type) { listeners.delete(type); },
        fire(type, target = this) {
            return listeners.get(type)({ target, currentTarget: this, preventDefault() {} });
        },
        ...properties,
    };
}

function workspace(t, id, values) {
    const questions = values.map((_, index) => ({
        id: index + 1, question: { type: 'self_report', text: `Afirmación ${index + 1}` },
    }));
    const saved = new Map(values.map((value, index) => [index + 1, value]));
    const snapshot = () => ({
        id, user_id: 1, status: 'in_progress', questions,
        responses: [...saved].map(([questionId, value]) => ({
            evaluation_question_id: questionId, likert_value: value,
        })),
    });
    const get = t.mock.fn(async () => snapshot());
    const request = t.mock.fn(async () => {});
    let elements;
    let html;
    const page = {
        get innerHTML() { return html; },
        set innerHTML(markup) {
            Object.values(elements ?? {}).forEach((el) => { el.isConnected = false; });
            html = markup;
            elements = {
                '#evaluation-response-form': element({
                    querySelector: (selector) => selector === 'fieldset' ? elements.fieldset : elements.next,
                }),
                '#evaluation-prev': element({ disabled: /id="evaluation-prev"[^>]*disabled/.test(html) }),
                '#save-status': element(),
                '#test-progress': element(),
                '#evaluation-submit': element({ disabled: /id="evaluation-submit"[^>]*disabled/.test(html) }),
                '#evaluation-results': element(),
                fieldset: element(),
                next: element(),
            };
        },
        querySelector: () => null,
        querySelectorAll: () => [],
    };
    const find = (selector) => elements[selector];
    t.mock.method(globalThis, 'FormData', function () {
        return { get: () => html.match(/name="answer" value="(\d+)" checked/)?.[1] };
    });
    globalThis.window = { scrollTo() {} };
    globalThis.document = { querySelector: (selector) => selector === '#workspace-page' ? page : find(selector) };
    t.after(() => { delete globalThis.window; delete globalThis.document; });
    const ctx = {
        pageRevision: 0, user: { id: 1 }, get, request, close() {},
        modal() {
            elements['#workspace-dialog'] = element({ querySelectorAll: () => [] });
            elements['#evaluation-confirm'] = element();
            elements['#confirm-feedback'] = element();
        },
    };
    return { ctx, page, saved, snapshot, find, form: () => find('#evaluation-response-form') };
}

test('Revisar bloquea la edición mientras espera el guardado de otra pregunta', async (t) => {
    const { ctx, page, saved, find, form } = workspace(t, 101, [1, 2]);
    const saving = Promise.withResolvers();
    ctx.request.mock.mockImplementation(async (path, { data }) => {
        if (path.endsWith('/complete')) return;
        if (data.evaluation_question_id === 2) await saving.promise;
        saved.set(data.evaluation_question_id, data.likert_value);
        return data;
    });
    await showEvaluation(ctx, 101);
    form().fire('change', { name: 'answer', value: '4' });
    find('#evaluation-prev').fire('click');
    await form().fire('submit');
    const reviewing = form().fire('submit');
    await setImmediate();
    assert.equal(find('fieldset').disabled, true);
    assert.equal(find('#evaluation-prev').disabled, true);
    assert.doesNotMatch(page.innerHTML, /Revisa antes de enviar/);

    form().fire('change', { name: 'answer', value: '5' });
    saving.resolve();
    await reviewing;
    assert.match(page.innerHTML, /4 · De acuerdo/);
    assert.doesNotMatch(page.innerHTML, /5 · Totalmente de acuerdo/);
    assert.equal(find('#evaluation-submit').disabled, false);
    find('#evaluation-submit').fire('click');
    await find('#evaluation-confirm').fire('click');
    assert.deepEqual([...saved.values()], [1, 4]);
    assert.deepEqual(ctx.request.mock.calls.map(({ arguments: [path] }) => path), [
        'evaluations/101/responses', 'evaluations/101/complete',
    ]);
});

test('Reabrir espera el guardado antes de consultar y muestra lo confirmado en ambas pantallas', async (t) => {
    const { ctx, page, saved, snapshot, find, form } = workspace(t, 102, [2]);
    const saving = Promise.withResolvers();
    const reading = Promise.withResolvers();
    ctx.get.mock.mockImplementationOnce(async () => {
        const evaluation = snapshot();
        await reading.promise;
        return evaluation;
    }, 1);
    ctx.request.mock.mockImplementation(async (path, { data }) => {
        if (path.endsWith('/complete')) return;
        await saving.promise;
        saved.set(data.evaluation_question_id, data.likert_value);
        return data;
    });
    await showEvaluation(ctx, 102);
    form().fire('change', { name: 'answer', value: '5' });
    ctx.pageRevision++;
    const reopening = showEvaluation(ctx, '102');
    await setImmediate();
    assert.equal(ctx.get.mock.callCount(), 1);

    saving.resolve();
    await setImmediate();
    assert.equal(ctx.get.mock.callCount(), 2);
    reading.resolve();
    await reopening;
    assert.match(page.innerHTML, /name="answer" value="5" checked/);
    await form().fire('submit');
    assert.match(page.innerHTML, /5 · Totalmente de acuerdo/);
    assert.equal(find('#evaluation-submit').disabled, false);
});
