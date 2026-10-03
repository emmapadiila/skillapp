import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile, writeFile, mkdir, mkdtemp, rm } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { tmpdir } from 'node:os';
import { resolve, join } from 'node:path';

const fixturePath = resolve('storage/framework/testing/workspace-browser');
const chromePath = process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';

test('workspace screens, responsive layouts and persisted form payloads work in Chrome', {
    skip: !existsSync(join(fixturePath, 'fixtures.json')) || !existsSync(chromePath), timeout: 120000,
}, async () => {
    const fixtures = JSON.parse(await readFile(join(fixturePath, 'fixtures.json'), 'utf8'));
    const manifest = JSON.parse(await readFile('public/build/manifest.json', 'utf8'));
    let role = 'admin';
    let forcedFailure = false;
    const writes = [];
    const errors = [];
    const server = createServer(async (req, res) => {
        try {
            const pathname = new URL(req.url, 'http://localhost').pathname;
            if (pathname.startsWith('/api/v1/')) {
                const key = pathname.slice('/api/v1/'.length);
                if (forcedFailure) { res.writeHead(503, { 'Content-Type': 'application/json' }); res.end(JSON.stringify({ message: 'Servicio temporalmente no disponible.' })); return; }
                if (req.method !== 'GET') {
                    let body = ''; for await (const chunk of req) body += chunk;
                    const payload = body ? JSON.parse(body) : {};
                    writes.push({ method: req.method, key, payload });
                    res.writeHead(200, { 'Content-Type': 'application/json' });
                    if (key.endsWith('/complete') && key.startsWith('evaluations/')) res.end(JSON.stringify(fixtures[role].responses[`evaluations/${fixtures[role].ids.completed}`]));
                    else res.end(JSON.stringify({ data: { ...payload, id: 100 } }));
                    return;
                }
                const value = fixtures[role].responses[key];
                assert.ok(value, `Missing real API fixture: ${role}/${key}`);
                res.writeHead(200, { 'Content-Type': 'application/json' }); res.end(JSON.stringify(value)); return;
            }
            if (pathname.startsWith('/build/') || pathname.startsWith('/fonts/') || pathname === '/favicon.ico') {
                const file = resolve('public', '.' + pathname);
                assert.ok(file.startsWith(resolve('public') + '\\') || file.startsWith(resolve('public') + '/'));
                const content = await readFile(file);
                res.writeHead(200, { 'Content-Type': file.endsWith('.js') ? 'text/javascript' : file.endsWith('.css') ? 'text/css' : file.endsWith('.ttf') ? 'font/ttf' : 'image/x-icon' }); res.end(content); return;
            }
            let html = pathname === '/login' ? await readFile(join(fixturePath, 'login.html'), 'utf8') : fixtures[role].html;
            html = html.replaceAll('http://localhost:8000', origin).replace('</head>', `<link rel="stylesheet" href="/build/${manifest['resources/css/app.css'].file}"><script type="module" src="/build/${manifest['resources/js/app.js'].file}"></script></head>`);
            res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' }); res.end(html);
        } catch (error) { errors.push(error.message); res.writeHead(500); res.end(error.message); }
    });
    await new Promise((done) => server.listen(0, '127.0.0.1', done));
    const origin = `http://127.0.0.1:${server.address().port}`;
    const profile = await mkdtemp(join(tmpdir(), 'skillapp-browser-test-'));
    const chrome = spawn(chromePath, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--user-data-dir=${profile}`, '--remote-debugging-port=0', 'about:blank'], { windowsHide: true });
    let ws;
    try {
        const browserUrl = await new Promise((done, reject) => {
            let stderr = ''; const timer = setTimeout(() => reject(new Error('Chrome startup timeout')), 15000);
            chrome.stderr.on('data', (data) => { stderr += data; const match = stderr.match(/DevTools listening on (ws:\/\/[^\s]+)/); if (match) { clearTimeout(timer); done(match[1]); } });
            chrome.on('error', reject);
        });
        const port = new URL(browserUrl).port;
        const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
        ws = new WebSocket(targets.find((target) => target.type === 'page').webSocketDebuggerUrl);
        await new Promise((done, reject) => { ws.onopen = done; ws.onerror = reject; });
        let nextId = 0; const pending = new Map();
        ws.onmessage = ({ data }) => {
            const message = JSON.parse(data);
            if (message.id) { const p = pending.get(message.id); pending.delete(message.id); if (message.error) p?.reject(new Error(message.error.message)); else p?.resolve(message.result); }
            if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.exception?.description ?? message.params.exceptionDetails.text);
            if (message.method === 'Runtime.consoleAPICalled' && message.params.type === 'error') errors.push(message.params.args.map((a) => a.value ?? a.description).join(' '));
        };
        const call = (method, params = {}) => new Promise((resolveCall, reject) => { const id = ++nextId; pending.set(id, { resolve: resolveCall, reject }); ws.send(JSON.stringify({ id, method, params })); });
        const evaluate = async (expression) => {
            const result = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
            if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description ?? result.exceptionDetails.text);
            return result.result.value;
        };
        const waitFor = async (expression) => {
            const until = Date.now() + 10000;
            while (Date.now() < until) { if (await evaluate(expression)) return; await new Promise((done) => setTimeout(done, 50)); }
            throw new Error(`Timed out: ${expression}\n${await evaluate('document.body.innerText')}`);
        };
        const click = async (selector) => { assert.ok(await evaluate(`!!document.querySelector(${JSON.stringify(selector)})`), selector); await evaluate(`document.querySelector(${JSON.stringify(selector)}).click()`); };
        const ready = () => waitFor(`document.querySelector('#workspace-page')?.innerText.length > 0 && !document.querySelector('#workspace-page').hasAttribute('aria-busy')`);
        const shot = async (name) => { const result = await call('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false }); await writeFile(join(fixturePath, name + '.png'), Buffer.from(result.data, 'base64')); };
        await call('Runtime.enable'); await call('Page.enable');
        await call('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });
        await call('Page.navigate', { url: origin + '/dashboard' }); await ready();
        assert.equal(await evaluate(`document.querySelector('#workspace-title').textContent`), 'Diagnóstico Organizacional');
        assert.ok(await evaluate(`document.querySelector('#workspace-page').innerText.includes('80%')`));
        await shot('admin-dashboard-desktop');
        const adminScreens = ['colaboradores', 'evaluaciones', 'habilidades', 'preguntas', 'cargos', 'diagnostico', 'mapa-calor', 'brechas', 'recomendaciones', 'planes', 'seguimiento', 'reportes', 'areas'];
        for (const screen of adminScreens) {
            await click(`[data-screen-link="${screen}"]`); await ready();
            assert.ok(!(await evaluate(`document.querySelector('#workspace-page').innerText`)).includes('No pudimos cargar'), screen);
            if (['colaboradores', 'mapa-calor', 'planes'].includes(screen)) await shot(screen + '-desktop');
        }
        await click('[data-screen-link="colaboradores"]'); await ready();
        await click(`[data-navigate="perfil-colaborador"][data-id="${fixtures.admin.ids.employee}"]`); await ready();
        assert.ok(await evaluate(`document.querySelector('#workspace-page').innerText.includes('Persona de prueba')`));
        await shot('employee-profile-desktop');
        await click('[data-screen-link="colaboradores"]'); await ready();
        await click('[data-action="create"]'); await waitFor(`!!document.querySelector('#record-form')`);
        await evaluate(`(() => { const f=document.querySelector('#record-form'); f.elements.first_name.value='Nueva'; f.elements.last_name.value='Persona'; f.elements.email.value='nueva@example.test'; f.requestSubmit(); })()`);
        await waitFor(`!document.querySelector('#workspace-dialog').open`); await ready();
        const employeeWrite = writes.find((w) => w.key === 'employees' && w.method === 'POST');
        assert.equal(employeeWrite.payload.first_name, 'Nueva'); assert.equal(employeeWrite.payload.role, 'collaborator');
        assert.equal(employeeWrite.payload.is_active, true);
        await click('[data-screen-link="preguntas"]'); await ready(); await click('[data-action="create"]');
        await waitFor(`!!document.querySelector('#record-form')`);
        await evaluate(`(() => { const f=document.querySelector('#record-form'); f.elements.text.value='Pregunta de prueba'; f.elements.skill_id.selectedIndex=1; f.requestSubmit(); })()`);
        await waitFor(`!document.querySelector('#workspace-dialog').open`); await ready();
        const questionWrite = writes.find((w) => w.key === 'questions' && w.method === 'POST');
        assert.equal(questionWrite.payload.type, 'self_report'); assert.deepEqual(questionWrite.payload.options, []);
        await call('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
        for (const screen of ['dashboard', ...adminScreens]) {
            await click(`[data-screen-link="${screen}"]`); await ready();
            assert.ok(await evaluate('document.documentElement.scrollWidth <= window.innerWidth'), 'Mobile overflow: ' + screen);
            if (screen === 'colaboradores') await shot('collaborators-mobile');
        }
        await click('#sidebar-open'); assert.equal(await evaluate(`document.querySelector('#sidebar-open').getAttribute('aria-expanded')`), 'true');
        await click('#sidebar-close');
        role = 'collaborator';
        await call('Page.navigate', { url: origin + '/mi-espacio' }); await ready();
        for (const screen of ['inicio', 'evaluacion', 'resultados', 'plan', 'progreso', 'perfil', 'notificaciones']) {
            await click(`[data-screen-link="${screen}"]`); await ready();
            assert.ok(!(await evaluate(`document.querySelector('#workspace-page').innerText`)).includes('No pudimos cargar'), screen);
            assert.ok(await evaluate('document.documentElement.scrollWidth <= window.innerWidth'), 'Mobile overflow: ' + screen);
            if (screen === 'inicio' || screen === 'resultados') await shot(screen + '-mobile');
        }
        await click('[data-screen-link="evaluacion"]'); await ready(); await click('[data-action="evaluation-start"]');
        await waitFor(`!!document.querySelector('#evaluation-response-form')`);
        await click('input[name="answer"][value="4"]'); await shot('evaluation-mobile');
        await evaluate(`document.querySelector('#evaluation-response-form').requestSubmit()`);
        await waitFor(`document.querySelector('#dialog-title').textContent.includes('completada')`);
        const responseWrite = writes.find((w) => w.key.endsWith('/responses'));
        assert.equal(responseWrite.payload.likert_value, 4); assert.ok(responseWrite.payload.evaluation_question_id);
        await click('[data-navigate="resultados"]'); await ready();
        await click('[data-screen-link="plan"]'); await ready(); await click('[data-action="plan-detail"]');
        await waitFor(`!!document.querySelector('[data-complete-activity]')`); await click('[data-complete-activity]');
        await waitFor(`document.querySelector('#workspace-toast').textContent.includes('Actividad completada')`);
        assert.ok(writes.some((w) => w.key.includes('/activities/') && w.key.endsWith('/complete')));
        await click('[data-dialog-close]');
        forcedFailure = true; await click('[data-action="reload"]'); await ready();
        assert.ok(await evaluate(`document.querySelector('#workspace-page').innerText.includes('No pudimos cargar')`));
        forcedFailure = false; await click('[data-action="reload"]'); await ready();
        await call('Page.navigate', { url: origin + '/login' });
        await waitFor(`!!document.querySelector('#password')`); await shot('login-mobile');
        await click('.password-toggle'); assert.equal(await evaluate(`document.querySelector('#password').type`), 'text');
        await call('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false }); await shot('login-desktop');
        assert.deepEqual(errors, []);
        console.log('Verified 21 screens, mobile overflow, dialogs, form payloads, evaluation responses, plan activity completion, error/retry, password toggle and console.');
    } finally {
        ws?.close(); chrome.kill(); await new Promise((done) => server.close(done));
        await rm(profile, { recursive: true, force: true, maxRetries: 10, retryDelay: 200 });
    }
});
