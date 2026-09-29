const content = document.querySelector('#workspace-content');

if (content) {
    const title = document.querySelector('header h1');
    const breadcrumb = document.querySelector('[data-breadcrumb-current]');
    const original = { title: title.textContent, breadcrumb: breadcrumb.textContent };
    const screens = {
        '#habilidades': { id: 'skills', title: 'Catálogo de habilidades', breadcrumb: 'Habilidades' },
        '#preguntas': { id: 'questions', title: 'Banco de preguntas', breadcrumb: 'Banco de preguntas' },
        '#cargos': { id: 'positions', title: 'Cargos', breadcrumb: 'Cargos' },
    };
    const links = [...document.querySelectorAll('[data-workspace-nav]')];
    links.forEach((link) => { link.dataset.originalActive = String(link.getAttribute('aria-current') === 'page'); });

    function navigate() {
        const selected = screens[location.hash];
        Object.values(screens).forEach((screen) => {
            document.querySelector(`#${screen.id}-screen`).hidden = selected?.id !== screen.id;
        });
        content.hidden = !!selected;
        title.textContent = selected?.title ?? original.title;
        breadcrumb.textContent = selected?.breadcrumb ?? original.breadcrumb;
        document.title = `${title.textContent} | SkillDiagnóstico`;
        document.querySelector('#dashboard-search').closest('label').hidden = !selected && !!document.querySelector('[data-organizational-dashboard]');
        links.forEach((link) => {
            const active = selected ? link.dataset.workspaceNav === selected.id : link.dataset.originalActive === 'true';
            link.classList.toggle('admin-nav-link--active', active);
            if (active) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
        document.querySelector('#sidebar-close')?.click();
    }

    window.addEventListener('hashchange', navigate);
    navigate();
}
