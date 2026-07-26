/**
 * js/comun.js
 * Utilidades compartidas por todos los módulos de la aplicación.
 * Los modales y las notificaciones usan los componentes nativos de Bootstrap 5.
 */

async function apiGet(entidad, accion, params = {}) {
    const query = new URLSearchParams({ api: entidad, accion, ...params }).toString();
    const resp = await fetch(`index.php?${query}`);
    if (resp.status === 401) {
        window.location.href = 'index.php?vista=login';
        return { exito: false, mensaje: 'Sesión expirada.' };
    }
    return resp.json();
}

async function apiPost(entidad, accion, datos) {
    const resp = await fetch(`index.php?api=${entidad}&accion=${accion}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos),
    });
    if (resp.status === 401) {
        window.location.href = 'index.php?vista=login';
        return { exito: false, mensaje: 'Sesión expirada.' };
    }
    return resp.json();
}

/** Muestra una notificación tipo toast (Bootstrap) en la esquina inferior derecha. */
function mostrarNotificacion(mensaje, tipo = 'exito') {
    const contenedor = document.getElementById('toast-container');
    if (!contenedor) return;

    const clases = tipo === 'error' ? 'text-bg-danger' : 'text-bg-success';
    const icono = tipo === 'error' ? 'bi-exclamation-triangle' : 'bi-check-circle';

    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center ${clases} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi ${icono} me-2"></i>${escaparHtml(mensaje)}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
    contenedor.appendChild(toastEl);

    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

/** Abre un modal de Bootstrap dado su id. */
function abrirModal(idModal) {
    const el = document.getElementById(idModal);
    bootstrap.Modal.getOrCreateInstance(el).show();
}

/** Cierra un modal de Bootstrap dado su id. */
function cerrarModal(idModal) {
    const el = document.getElementById(idModal);
    const instancia = bootstrap.Modal.getOrCreateInstance(el);
    instancia.hide();
}

function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function confirmarEliminacion(mensaje) {
    return window.confirm(mensaje || '¿Está seguro de que desea eliminar este registro?');
}

/**
 * Aplica la validación visual de Bootstrap a un formulario y detiene el
 * envío si el propio navegador detecta campos inválidos (required, min,
 * max, type=email, etc.). Devuelve true si el formulario es válido.
 */
function formularioValido(form) {
    form.classList.add('was-validated');
    return form.checkValidity();
}

/**
 * Filtros de entrada en vivo (oninput) para reforzar visualmente las reglas
 * de validación. Se usan junto con "pattern"/"maxlength"/"minlength" en el
 * HTML; el navegador sigue siendo quien valida al enviar, esto solo evita
 * que el usuario pueda escribir caracteres que de todas formas serán
 * rechazados.
 */

/** Deja pasar solo dígitos (0-9). Útil para DNI, códigos numéricos, etc. */
function soloNumeros(evento) {
    const campo = evento.target;
    const posicion = campo.selectionStart;
    const largoPrevio = campo.value.length;
    campo.value = campo.value.replace(/\D/g, '');
    const diferencia = largoPrevio - campo.value.length;
    if (posicion !== null) {
        campo.setSelectionRange(posicion - diferencia, posicion - diferencia);
    }
}

/** Deja pasar solo letras (incluye tildes/ñ) y espacios. Útil para nombre/apellido. */
function soloLetras(evento) {
    const campo = evento.target;
    const posicion = campo.selectionStart;
    const largoPrevio = campo.value.length;
    campo.value = campo.value.replace(/[^\p{L}\s.'-]/gu, '');
    const diferencia = largoPrevio - campo.value.length;
    if (posicion !== null) {
        campo.setSelectionRange(posicion - diferencia, posicion - diferencia);
    }
}

/** Deja pasar solo dígitos, espacios, "+" y "-". Útil para teléfono. */
function soloTelefono(evento) {
    const campo = evento.target;
    const posicion = campo.selectionStart;
    const largoPrevio = campo.value.length;
    campo.value = campo.value.replace(/[^0-9+\-\s]/g, '');
    const diferencia = largoPrevio - campo.value.length;
    if (posicion !== null) {
        campo.setSelectionRange(posicion - diferencia, posicion - diferencia);
    }
}

/** URL de imagen ilustrativa y determinista para un tipo de habitación, según su id. */
function imagenTipoHabitacion(id) {
    return `https://picsum.photos/seed/hab-tipo-${id}/160/110`;
}