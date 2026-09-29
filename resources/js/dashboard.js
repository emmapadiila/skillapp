import { getAll, escapeHtml as escape, connectionMessage } from './data/platform';

const dashboard = document.querySelector('[data-organizational-dashboard]');
if (dashboard) {
    const metricNames = ['Colaboradores', 'Evaluaciones completadas', 'Resultado promedio', 'Habilidades'];
    const metrics = (values) => {
        document.querySelector('#dashboard-metrics').innerHTML = metricNames.map((name, index) => `<article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-xs font-semibold text-slate-500">${name}</h2><p class="mt-3 text-2xl font-bold text-slate-800">${escape(values[index])}</p></article>`).join('');
    };
    let loading = false;
    async function load() {
        if (loading) return;
        loading = true;
        const status = document.querySelector('#dashboard-status');
        status.textContent = 'Cargando información…';
        const results = await Promise.allSettled(['employees', 'evaluations', 'skills', 'diagnostics'].map(getAll));
        const [employees, evaluations, skills, diagnostics] = results.map((result) => result.status === 'fulfilled' ? result.value : null);
        const completed = evaluations?.filter((evaluation) => evaluation.status === 'completed');
        const scores = completed?.filter((evaluation) => evaluation.result?.total_score != null).map((evaluation) => Number(evaluation.result.total_score));
        metrics([employees?.length ?? '—', completed?.length ?? '—', scores?.length ? `${(scores.reduce((total, score) => total + score, 0) / scores.length).toFixed(2)} / 5` : '—', skills?.length ?? '—']);
        const statuses = { pending: 'Pendientes', in_progress: 'En curso', completed: 'Completadas', expired: 'Vencidas' };
        document.querySelector('#evaluation-distribution').innerHTML = evaluations?.length ? Object.entries(statuses).map(([key, label]) => {
            const count = evaluations.filter((evaluation) => evaluation.status === key).length;
            return `<div><div class="mb-2 flex justify-between gap-3 text-sm"><span>${label}</span><strong>${count}</strong></div><progress class="h-2 w-full accent-blue-600" value="${count}" max="${evaluations.length}" aria-label="${label}"></progress></div>`;
        }).join('') : `<p class="text-sm text-slate-500">${evaluations ? 'No hay evaluaciones registradas.' : 'Evaluaciones no disponibles.'}</p>`;
        document.querySelector('#dashboard-diagnostics').innerHTML = diagnostics?.length ? diagnostics.map((diagnostic) => `<article class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-4 text-sm"><span>Período ${escape(diagnostic.period_start?.slice(0, 10))} — ${escape(diagnostic.period_end?.slice(0, 10))}</span><span>${escape(diagnostic.total_evaluated)} evaluados · ${escape(diagnostic.gaps_count)} brechas registradas</span></article>`).join('') : `<p class="text-sm text-slate-500">${diagnostics ? 'No hay diagnósticos registrados.' : 'Diagnósticos no disponibles.'}</p>`;
        const failure = results.find((result) => result.status === 'rejected');
        status.textContent = failure ? connectionMessage(failure.reason) : 'Datos actualizados desde los registros de tu organización.';
        loading = false;
    }
    metrics(['—', '—', '—', '—']);
    document.querySelector('#dashboard-reload').addEventListener('click', load);
    load();
}
