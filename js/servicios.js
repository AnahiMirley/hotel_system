/**
 * js/servicios.js
 */

document.addEventListener('DOMContentLoaded', () => {
    cargarServicios();
    document.getElementById('form-servicio').addEventListener('submit', guardarServicio);
});

async function cargarServicios() {
    const resp = await apiGet('servicio', 'listar');
    pintarServicios(resp.datos || []);
}

async function buscarServicios() {
    const texto = document.getElementById('buscar-servicio').value.trim();
    const resp = await apiGet('servicio', 'buscar', { q: texto });
    pintarServicios(resp.datos || []);
}

function pintarServicios(lista) {
    const tbody = document.getElementById('tabla-servicio');
    if (lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No hay servicios registrados.</td></tr>';
        return;
    }
    tbody.innerHTML = lista.map(s => `
        <tr>
            <td class="fw-semibold">${escaparHtml(s.nombre)}</td>
            <td class="text-muted">${escaparHtml(s.descripcion)}</td>
            <td>$${parseFloat(s.precio).toFixed(2)}</td>
            <td class="text-end tabla-acciones">
                <button class="btn btn-sm btn-outline-primary" onclick="editarServicio(${s.id_servicio})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="eliminarServicio(${s.id_servicio})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function abrirFormularioServicio() {
    const form = document.getElementById('form-servicio');
    form.reset();
    form.classList.remove('was-validated');
    document.getElementById('id_servicio').value = '';
    document.getElementById('titulo-modal-servicio').textContent = 'Nuevo Servicio';
    abrirModal('modal-servicio');
}

async function editarServicio(id) {
    const resp = await apiGet('servicio', 'obtener', { id });
    if (!resp.exito) {
        mostrarNotificacion(resp.mensaje, 'error');
        return;
    }
    const s = resp.datos;
    const form = document.getElementById('form-servicio');
    form.classList.remove('was-validated');
    document.getElementById('id_servicio').value = s.id_servicio;
    document.getElementById('nombre').value = s.nombre;
    document.getElementById('descripcion').value = s.descripcion;
    document.getElementById('precio').value = s.precio;
    document.getElementById('titulo-modal-servicio').textContent = 'Editar Servicio';
    abrirModal('modal-servicio');
}

async function guardarServicio(evento) {
    evento.preventDefault();
    const form = evento.target;
    if (!formularioValido(form)) return;

    const id = document.getElementById('id_servicio').value;
    const datos = {
        nombre: document.getElementById('nombre').value.trim(),
        descripcion: document.getElementById('descripcion').value.trim(),
        precio: document.getElementById('precio').value,
    };
    let resp;
    if (id) {
        datos.id_servicio = id;
        resp = await apiPost('servicio', 'editar', datos);
    } else {
        resp = await apiPost('servicio', 'crear', datos);
    }
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) {
        cerrarModal('modal-servicio');
        cargarServicios();
    }
}

async function eliminarServicio(id) {
    if (!confirmarEliminacion('¿Eliminar este servicio?')) return;
    const resp = await apiPost('servicio', 'eliminar', { id });
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) cargarServicios();
}
