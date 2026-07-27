/**
 * js/tipo_habitacion.js
 */

document.addEventListener('DOMContentLoaded', () => {
    cargarTipos();
    document.getElementById('form-tipo').addEventListener('submit', guardarTipo);
});

async function cargarTipos() {
    const resp = await apiGet('tipo_habitacion', 'listar');
    pintarTipos(resp.datos || []);
}

async function buscarTipos() {
    const texto = document.getElementById('buscar-tipo').value.trim();
    const resp = await apiGet('tipo_habitacion', 'buscar', { q: texto });
    pintarTipos(resp.datos || []);
}

function pintarTipos(lista) {
    const tbody = document.getElementById('tabla-tipo');
    if (lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay tipos de habitación registrados.</td></tr>';
        return;
    }
    tbody.innerHTML = lista.map(t => `
        <tr>
            <td><img src="${imagenTipoHabitacion(t.id_tipo_habitacion)}" class="thumb-tipo" alt="${escaparHtml(t.nombre)}"></td>
            <td class="fw-semibold">${escaparHtml(t.nombre)}</td>
            <td class="text-muted">${escaparHtml(t.descripcion)}</td>
            <td class="text-center"><span class="badge badge-pax"><i class="bi bi-people-fill me-1"></i>${t.capacidad}</span></td>
            <td class="text-end">$${parseFloat(t.precio_base).toFixed(2)}</td>
            <td class="text-end tabla-acciones">
                <button class="btn btn-sm btn-outline-primary" onclick="editarTipo(${t.id_tipo_habitacion})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="eliminarTipo(${t.id_tipo_habitacion})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function abrirFormularioTipo() {
    const form = document.getElementById('form-tipo');
    form.reset();
    form.classList.remove('was-validated');
    document.getElementById('id_tipo_habitacion').value = '';
    document.getElementById('titulo-modal-tipo').textContent = 'Nuevo Tipo de Habitación';
    abrirModal('modal-tipo');
}

async function editarTipo(id) {
    const resp = await apiGet('tipo_habitacion', 'obtener', { id });
    if (!resp.exito) {
        mostrarNotificacion(resp.mensaje, 'error');
        return;
    }
    const t = resp.datos;
    const form = document.getElementById('form-tipo');
    form.classList.remove('was-validated');
    document.getElementById('id_tipo_habitacion').value = t.id_tipo_habitacion;
    document.getElementById('nombre').value = t.nombre;
    document.getElementById('descripcion').value = t.descripcion;
    document.getElementById('capacidad').value = t.capacidad;
    document.getElementById('precio_base').value = t.precio_base;
    document.getElementById('titulo-modal-tipo').textContent = 'Editar Tipo de Habitación';
    abrirModal('modal-tipo');
}

async function guardarTipo(evento) {
    evento.preventDefault();
    const form = evento.target;
    if (!formularioValido(form)) return;

    const id = document.getElementById('id_tipo_habitacion').value;
    const datos = {
        nombre: document.getElementById('nombre').value.trim(),
        descripcion: document.getElementById('descripcion').value.trim(),
        capacidad: document.getElementById('capacidad').value,
        precio_base: document.getElementById('precio_base').value,
    };
    let resp;
    if (id) {
        datos.id_tipo_habitacion = id;
        resp = await apiPost('tipo_habitacion', 'editar', datos);
    } else {
        resp = await apiPost('tipo_habitacion', 'crear', datos);
    }
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) {
        cerrarModal('modal-tipo');
        cargarTipos();
    }
}

async function eliminarTipo(id) {
    if (!confirmarEliminacion('¿Eliminar este tipo de habitación?')) return;
    const resp = await apiPost('tipo_habitacion', 'eliminar', { id });
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) cargarTipos();
}