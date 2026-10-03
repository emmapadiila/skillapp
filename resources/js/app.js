import './collaborators';
import './skills';
import './dashboard';
import './workspace-app';

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
document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeSidebar(); });

document.querySelectorAll('[data-demo-nav]').forEach((button) => {
    button.addEventListener('click', () => showToast(`${button.dataset.demoNav}: interfaz pendiente de conexión.`));
});

const notificationsToggle = document.querySelector('#notifications-toggle');
const notificationsPanel = document.querySelector('#notifications-panel');
notificationsToggle?.addEventListener('click', () => {
    const isExpanded = notificationsToggle.getAttribute('aria-expanded') === 'true';
    notificationsToggle.setAttribute('aria-expanded', String(!isExpanded));
    notificationsPanel?.classList.toggle('hidden', isExpanded);
});

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

document.querySelector('[data-login-form]')?.addEventListener('submit', (event) => {
    const submit = event.currentTarget.querySelector('[type="submit"]');
    submit.disabled = true;
    submit.textContent = 'Verificando…';
});

document.querySelector('#recover-password')?.addEventListener('click', async (event) => {
    const email = document.querySelector('#email');
    if (!email.reportValidity()) return;
    const button = event.currentTarget;
    const feedback = document.querySelector('#recovery-feedback');
    button.disabled = true; feedback.hidden = false; feedback.textContent = 'Solicitando recuperación…';
    try {
        const response = await fetch('/api/v1/auth/recover', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ email: email.value }), signal: AbortSignal.timeout(20000) });
        const result = await response.json();
        feedback.textContent = result.message ?? 'No se pudo solicitar la recuperación. Inténtalo nuevamente.';
    } catch { feedback.textContent = 'No fue posible conectar con el servidor. Inténtalo nuevamente.'; }
    finally { button.disabled = false; }
});
