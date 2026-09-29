const passwordToggle = document.querySelector('.password-toggle');
const passwordInput = document.querySelector('#password');

if (passwordToggle instanceof HTMLButtonElement && passwordInput instanceof HTMLInputElement) {
    passwordToggle.addEventListener('click', () => {
        const isVisible = passwordInput.type === 'text';

        passwordInput.type = isVisible ? 'password' : 'text';
        passwordToggle.setAttribute('aria-pressed', String(!isVisible));
        passwordToggle.setAttribute('aria-label', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        passwordToggle.querySelector('.password-eye')?.classList.toggle('hidden', !isVisible);
        passwordToggle.querySelector('.password-eye-off')?.classList.toggle('hidden', isVisible);
        passwordInput.focus({ preventScroll: true });
    });
}

const dashboard = document.querySelector('[data-organizational-dashboard]');

if (dashboard instanceof HTMLElement) {
    const axes = {
        operativo: {
            label: 'Operativo',
            score: 78,
            strength: 'Trabajo colaborativo',
            gap: 'Resolución de problemas',
        },
        misional: {
            label: 'Misional',
            score: 71,
            strength: 'Orientación al cliente',
            gap: 'Comunicación entre áreas',
        },
        estrategico: {
            label: 'Estratégico',
            score: 84,
            strength: 'Liderazgo',
            gap: 'Gestión del cambio',
        },
    };
    const areas = ['Talento Humano', 'Operaciones', 'Comercial', 'Tecnología'];
    const positions = ['Analista', 'Coordinador', 'Líder', 'Especialista'];
    const periods = ['2026 · S1', '2025 · S2', '2025 · S1'];
    const skills = [
        { name: 'Comunicativas', baseline: 76 },
        { name: 'Trabajo colaborativo', baseline: 81 },
        { name: 'Cognitivas', baseline: 68 },
        { name: 'Dirección', baseline: 74 },
    ];
    const organizationalDiagnosisMockData = Array.from({ length: 245 }, (_, index) => {
        const axisScores = Object.fromEntries(
            Object.entries(axes).map(([key, axis], axisIndex) => [
                key,
                Math.min(100, Math.max(0, axis.score + ((index * 13 + axisIndex * 5) % 17) - 8)),
            ]),
        );

        return {
            area: areas[(index * 3 + Math.floor(index / 7)) % areas.length],
            position: positions[(index * 7 + Math.floor(index / 11)) % positions.length],
            period: periods[Math.floor(index / 49) % periods.length],
            evaluated: index < 198,
            inProgress: index >= 198 && index < 210,
            activePlan: index < 87,
            completedPlan: index >= 87 && index < 141,
            criticalGap: index < 12,
            averageScore: 76 + ((index * 13) % 15) - 7,
            axisScores,
            skillScores: skills.map(({ baseline }, skillIndex) =>
                Math.min(100, Math.max(0, baseline + ((index * 7 + skillIndex * 3) % 13) - 6)),
            ),
        };
    });

    const filterControls = [...dashboard.querySelectorAll('[data-filter]')];
    const axisKeys = Object.keys(axes);
    const metricElements = new Map(
        [...dashboard.querySelectorAll('[data-metric]')].map((element) => [element.dataset.metric, element]),
    );
    const toast = document.querySelector('#interface-toast');
    let toastTimeout;

    const showToast = (message) => {
        if (!(toast instanceof HTMLElement)) {
            return;
        }

        toast.textContent = message;
        toast.classList.remove('translate-y-3', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
        window.clearTimeout(toastTimeout);
        toastTimeout = window.setTimeout(() => {
            toast.classList.add('translate-y-3', 'opacity-0');
            toast.classList.remove('translate-y-0', 'opacity-100');
        }, 2400);
    };

    const selectedFilters = () => Object.fromEntries(
        filterControls.map((control) => [control.dataset.filter, control.value]),
    );

    const average = (values) => values.length
        ? Math.round(values.reduce((total, value) => total + value, 0) / values.length)
        : 0;

    const updateMetric = (key, value) => {
        const element = metricElements.get(key);

        if (element instanceof HTMLElement && element.firstChild) {
            element.firstChild.nodeValue = String(value);
        }
    };

    const renderDashboard = () => {
        const filters = selectedFilters();
        const selectedEmployees = organizationalDiagnosisMockData.filter((employee) =>
            (filters.period === 'all' || employee.period === filters.period)
            && (filters.area === 'all' || employee.area === filters.area)
            && (filters.position === 'all' || employee.position === filters.position),
        );
        const evaluatedCount = selectedEmployees.filter((employee) => employee.evaluated).length;
        const inProgressCount = selectedEmployees.filter((employee) => employee.inProgress).length;
        const pendingCount = selectedEmployees.length - evaluatedCount - inProgressCount;
        const activePlans = selectedEmployees.filter((employee) => employee.activePlan).length;
        const completedPlans = selectedEmployees.filter((employee) => employee.completedPlan).length;
        const scoreFor = (employee, axis) => axis === 'all'
            ? employee.averageScore
            : employee.axisScores[axis];
        const averageScore = average(selectedEmployees.filter((employee) => employee.evaluated)
            .map((employee) => scoreFor(employee, filters.axis)));
        const criticalGaps = filters.axis === 'all'
            ? selectedEmployees.filter((employee) => employee.criticalGap).length
            : selectedEmployees.filter((employee) => employee.evaluated && employee.axisScores[filters.axis] < 65).length;

        updateMetric('collaborators', selectedEmployees.length);
        updateMetric('evaluated', evaluatedCount);
        updateMetric('average', averageScore);
        updateMetric('gaps', criticalGaps);
        updateMetric('active-plans', activePlans);
        updateMetric('completed-plans', completedPlans);

        const evaluatedNote = dashboard.querySelector('[data-metric-note="evaluated"]');
        if (evaluatedNote) {
            evaluatedNote.textContent = `${selectedEmployees.length ? (evaluatedCount / selectedEmployees.length * 100).toFixed(1) : '0.0'}% del total`;
        }

        const completedNote = dashboard.querySelector('[data-metric-note="completed-plans"]');
        if (completedNote) {
            completedNote.textContent = `${activePlans + completedPlans ? Math.round(completedPlans / (activePlans + completedPlans) * 100) : 0}% de finalización`;
        }

        const activeAxisKeys = filters.axis === 'all' ? axisKeys : [filters.axis];
        activeAxisKeys.forEach((axis) => {
            const score = average(selectedEmployees.filter((employee) => employee.evaluated)
                .map((employee) => employee.axisScores[axis]));
            const chartValue = dashboard.querySelector(`[data-axis-value="${axis}"]`);
            const chartBar = dashboard.querySelector(`[data-axis-bar="${axis}"]`);
            const summaryScore = dashboard.querySelector(`[data-summary-score="${axis}"]`);
            const summaryBar = dashboard.querySelector(`[data-summary-bar="${axis}"]`);

            if (chartValue) chartValue.textContent = `${score}%`;
            const chartColumn = dashboard.querySelector(`[data-axis-column="${axis}"]`);
            if (chartColumn instanceof HTMLElement) chartColumn.style.setProperty('--bar-value', `${score}%`);
            if (summaryScore) summaryScore.textContent = `${score}%`;
            if (summaryBar instanceof HTMLElement) summaryBar.style.width = `${score}%`;
        });

        axisKeys.forEach((axis) => {
            const column = dashboard.querySelector(`[data-axis-column="${axis}"]`);
            const summary = dashboard.querySelector(`[data-axis-summary="${axis}"]`);
            if (column instanceof HTMLElement) column.hidden = filters.axis !== 'all' && filters.axis !== axis;
            if (summary instanceof HTMLElement) summary.classList.toggle('is-muted', filters.axis !== 'all' && filters.axis !== axis);
        });

        skills.forEach(({ name }, skillIndex) => {
            const score = average(selectedEmployees.filter((employee) => employee.evaluated)
                .map((employee) => employee.skillScores[skillIndex]));
            const indicator = dashboard.querySelector(`[data-skill-name="${name}"]`);
            const value = indicator?.querySelector('[data-skill-value]');
            const bar = indicator?.querySelector('[data-skill-bar]');
            if (value) value.textContent = `${score}%`;
            if (bar instanceof HTMLElement) bar.style.width = `${score}%`;
        });

        const stateCounts = { evaluated: evaluatedCount, pending: pendingCount, 'in-progress': inProgressCount };
        Object.entries(stateCounts).forEach(([key, value]) => {
            const element = dashboard.querySelector(`[data-state-count="${key}"]`);
            if (element) element.textContent = String(value);
        });

        const donut = dashboard.querySelector('#employee-donut');
        if (donut instanceof HTMLElement) {
            const total = selectedEmployees.length || 1;
            donut.style.setProperty('--evaluated-share', `${evaluatedCount / total * 100}%`);
            donut.style.setProperty('--pending-share', `${pendingCount / total * 100}%`);
            donut.setAttribute('aria-label', `${evaluatedCount} evaluados, ${pendingCount} pendientes y ${inProgressCount} en evaluación`);
        }

        const chartLabel = dashboard.querySelector('#chart-filter-label');
        if (chartLabel) {
            chartLabel.textContent = filters.axis === 'all' ? 'Promedio · escala 0–100' : `${axes[filters.axis].label} · escala 0–100`;
        }
    };

    filterControls.forEach((control) => control.addEventListener('change', renderDashboard));

    document.querySelector('#reset-filters')?.addEventListener('click', () => {
        filterControls.forEach((control) => { control.value = 'all'; });
        renderDashboard();
        showToast('Filtros restablecidos.');
    });

    const search = document.querySelector('#dashboard-search');
    search?.addEventListener('input', () => {
        const query = search.value.trim().toLocaleLowerCase('es');
        let visibleSkills = 0;

        dashboard.querySelectorAll('[data-skill-name]').forEach((indicator) => {
            const matches = indicator.dataset.skillName.toLocaleLowerCase('es').includes(query);
            indicator.classList.toggle('hidden', !matches);
            if (matches) visibleSkills += 1;
        });

        if (query && visibleSkills === 0) {
            showToast('No hay habilidades que coincidan con la búsqueda.');
        }
    });

    const sidebar = document.querySelector('#admin-sidebar');
    const backdrop = document.querySelector('#sidebar-backdrop');
    const sidebarOpen = document.querySelector('#sidebar-open');
    const closeSidebar = () => {
        sidebar?.classList.remove('is-open');
        backdrop?.classList.add('hidden');
        sidebarOpen?.setAttribute('aria-expanded', 'false');
    };

    sidebarOpen?.addEventListener('click', () => {
        sidebar?.classList.add('is-open');
        backdrop?.classList.remove('hidden');
        sidebarOpen.setAttribute('aria-expanded', 'true');
    });
    document.querySelector('#sidebar-close')?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);

    document.querySelectorAll('[data-demo-nav]').forEach((button) => {
        button.addEventListener('click', () => showToast(`${button.dataset.demoNav}: módulo de demostración, sin navegación configurada.`));
    });

    const notificationsToggle = document.querySelector('#notifications-toggle');
    const notificationsPanel = document.querySelector('#notifications-panel');
    notificationsToggle?.addEventListener('click', () => {
        const isExpanded = notificationsToggle.getAttribute('aria-expanded') === 'true';
        notificationsToggle.setAttribute('aria-expanded', String(!isExpanded));
        notificationsPanel?.classList.toggle('hidden', isExpanded);
    });

    const modal = document.querySelector('#diagnostic-modal');
    const closeModal = () => {
        if (modal instanceof HTMLDialogElement && modal.open) modal.close();
    };
    document.querySelectorAll('[data-open-diagnostic]').forEach((button) => {
        button.addEventListener('click', () => {
            const axis = axes[button.dataset.openDiagnostic];
            if (!axis || !(modal instanceof HTMLDialogElement)) return;

            document.querySelector('#diagnostic-modal-title').textContent = axis.label;
            document.querySelector('#diagnostic-modal-strength').textContent = axis.strength;
            document.querySelector('#diagnostic-modal-gap').textContent = axis.gap;
            modal.showModal();
        });
    });
    document.querySelector('#diagnostic-modal-close')?.addEventListener('click', closeModal);
    document.querySelector('#diagnostic-modal-done')?.addEventListener('click', closeModal);

    renderDashboard();
}
