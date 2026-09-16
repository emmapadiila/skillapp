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
