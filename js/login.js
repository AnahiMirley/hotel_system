/**
 * js/login.js
 */

document.addEventListener('DOMContentLoaded', () => {
    const form      = document.getElementById('form-login');
    const errorBox  = document.getElementById('login-error');
    const btnVer    = document.getElementById('btn-ver-password');
    const campoPass = document.getElementById('password');

    if (btnVer && campoPass) {
        btnVer.addEventListener('click', () => {
            const icono = btnVer.querySelector('i');
            const oculto = campoPass.type === 'password';
            campoPass.type = oculto ? 'text' : 'password';
            icono.classList.toggle('bi-eye');
            icono.classList.toggle('bi-eye-slash');
        });
    }

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        form.classList.add('was-validated');
        if (!form.checkValidity()) return;

        errorBox.classList.add('d-none');
        const boton = document.getElementById('btn-login');
        boton.disabled = true;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Ingresando...';

        try {
            const resp = await apiPost('auth', 'login', {
                usuario: document.getElementById('usuario').value.trim(),
                password: document.getElementById('password').value,
            });

            if (resp.exito) {
                window.location.href = 'index.php?vista=dashboard';
            } else {
                errorBox.textContent = resp.mensaje;
                errorBox.classList.remove('d-none');
            }
        } catch (e) {
            errorBox.textContent = 'No se pudo conectar con el servidor.';
            errorBox.classList.remove('d-none');
        } finally {
            boton.disabled = false;
            boton.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión';
        }
    });
});