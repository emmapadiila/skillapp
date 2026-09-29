import './collaborators';
import './skills';
import './dashboard';

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
