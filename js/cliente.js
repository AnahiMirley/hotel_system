/**
 * js/cliente.js
 */

document.addEventListener('DOMContentLoaded', () => {
    cargarClientes();
    document.getElementById('form-cliente').addEventListener('submit', guardarCliente);
});

async function cargarClientes() {
    const resp = await apiGet('cliente', 'listar');
    pintarClientes(resp.datos || []);
}

async function buscarClientes() {
    const texto = document.getElementById('buscar-cliente').value.trim();
    const resp = await apiGet('cliente', 'buscar', { q: texto });
    pintarClientes(resp.datos || []);
}

function pintarClientes(lista) {
    const tbody = document.getElementById('tabla-cliente');
    if (lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay clientes registrados.</td></tr>';
        return;
    }
    tbody.innerHTML = lista.map(c => `
        <tr>
            <td class="fw-semibold">${escaparHtml(c.nombre)} ${escaparHtml(c.apellido)}</td>
            <td>${escaparHtml(c.dni)}</td>
            <td>${escaparHtml(c.telefono)}</td>
            <td>${escaparHtml(c.email) || '<span class="text-muted">—</span>'}</td>
            <td class="text-end tabla-acciones">
                <button class="btn btn-sm btn-outline-primary" onclick="editarCliente(${c.id_cliente})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="eliminarCliente(${c.id_cliente})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function abrirFormularioCliente() {
    const form = document.getElementById('form-cliente');
    form.reset();
    form.classList.remove('was-validated');
    document.getElementById('id_cliente').value = '';
    document.getElementById('titulo-modal-cliente').textContent = 'Nuevo Cliente';
    abrirModal('modal-cliente');
}

async function editarCliente(id) {
    const resp = await apiGet('cliente', 'obtener', { id });
    if (!resp.exito) {
        mostrarNotificacion(resp.mensaje, 'error');
        return;
    }
    const c = resp.datos;
    const form = document.getElementById('form-cliente');
    form.classList.remove('was-validated');
    document.getElementById('id_cliente').value = c.id_cliente;
    document.getElementById('nombre').value = c.nombre;
    document.getElementById('apellido').value = c.apellido;
    document.getElementById('dni').value = c.dni;
    document.getElementById('direccion').value = c.direccion;
    document.getElementById('telefono').value = c.telefono;
    document.getElementById('email').value = c.email;
    document.getElementById('titulo-modal-cliente').textContent = 'Editar Cliente';
    abrirModal('modal-cliente');
}

async function guardarCliente(evento) {
    evento.preventDefault();
    const form = evento.target;
    if (!formularioValido(form)) return;

    const id = document.getElementById('id_cliente').value;
    const datos = {
        nombre: document.getElementById('nombre').value.trim(),
        apellido: document.getElementById('apellido').value.trim(),
        dni: document.getElementById('dni').value.trim(),
        direccion: document.getElementById('direccion').value.trim(),
        telefono: document.getElementById('telefono').value.trim(),
        email: document.getElementById('email').value.trim(),
    };
    let resp;
    if (id) {
        datos.id_cliente = id;
        resp = await apiPost('cliente', 'editar', datos);
    } else {
        resp = await apiPost('cliente', 'crear', datos);
    }
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) {
        cerrarModal('modal-cliente');
        cargarClientes();
    }
}

async function eliminarCliente(id) {
    if (!confirmarEliminacion('¿Eliminar este cliente?')) return;
    const resp = await apiPost('cliente', 'eliminar', { id });
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) cargarClientes();
}