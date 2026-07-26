/**
 * js/habitacion.js
 */

document.addEventListener('DOMContentLoaded', () => {
    cargarHabitaciones();
    cargarCombosHabitacion();
    document.getElementById('form-habitacion').addEventListener('submit', guardarHabitacion);
});

const BADGES_ESTADO_HABITACION = {
    disponible: 'text-bg-success',
    ocupada: 'text-bg-danger',
    mantenimiento: 'text-bg-warning',
};

async function cargarHabitaciones() {
    const resp = await apiGet('habitacion', 'listar');
    pintarHabitaciones(resp.datos || []);
}

async function buscarHabitaciones() {
    const texto = document.getElementById('buscar-habitacion').value.trim();
    const resp = await apiGet('habitacion', 'buscar', { q: texto });
    pintarHabitaciones(resp.datos || []);
}

async function cargarCombosHabitacion() {
    const resp = await apiGet('habitacion', 'combos');
    const selTipo = document.getElementById('id_tipo_habitacion');
    selTipo.innerHTML = resp.tipos.map(t => `<option value="${t.id_tipo_habitacion}">${escaparHtml(t.nombre)}</option>`).join('');
}

function pintarHabitaciones(lista) {
    const tbody = document.getElementById('tabla-habitacion');
    if (lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay habitaciones registradas.</td></tr>';
        return;
    }
    tbody.innerHTML = lista.map(h => `
        <tr>
            <td class="fw-semibold">${escaparHtml(h.numero)}</td>
            <td>${h.planta}</td>
            <td>${escaparHtml(h.tipo_nombre)}</td>
            <td><span class="badge ${BADGES_ESTADO_HABITACION[h.estado] || 'text-bg-secondary'}">${escaparHtml(h.estado)}</span></td>
            <td class="text-end tabla-acciones">
                <button class="btn btn-sm btn-outline-primary" onclick="editarHabitacion(${h.id_habitacion})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="eliminarHabitacion(${h.id_habitacion})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function abrirFormularioHabitacion() {
    const form = document.getElementById('form-habitacion');
    form.reset();
    form.classList.remove('was-validated');
    document.getElementById('id_habitacion').value = '';
    document.getElementById('titulo-modal-habitacion').textContent = 'Nueva Habitación';
    abrirModal('modal-habitacion');
}

async function editarHabitacion(id) {
    const resp = await apiGet('habitacion', 'obtener', { id });
    if (!resp.exito) {
        mostrarNotificacion(resp.mensaje, 'error');
        return;
    }
    const h = resp.datos;
    const form = document.getElementById('form-habitacion');
    form.classList.remove('was-validated');
    document.getElementById('id_habitacion').value = h.id_habitacion;
    document.getElementById('numero').value = h.numero;
    document.getElementById('planta').value = h.planta;
    document.getElementById('id_tipo_habitacion').value = h.id_tipo_habitacion;
    document.getElementById('estado').value = h.estado;
    document.getElementById('titulo-modal-habitacion').textContent = 'Editar Habitación';
    abrirModal('modal-habitacion');
}

async function guardarHabitacion(evento) {
    evento.preventDefault();
    const form = evento.target;
    if (!formularioValido(form)) return;

    const id = document.getElementById('id_habitacion').value;
    const datos = {
        numero: document.getElementById('numero').value.trim(),
        planta: document.getElementById('planta').value,
        id_tipo_habitacion: document.getElementById('id_tipo_habitacion').value,
        estado: document.getElementById('estado').value,
    };
    let resp;
    if (id) {
        datos.id_habitacion = id;
        resp = await apiPost('habitacion', 'editar', datos);
    } else {
        resp = await apiPost('habitacion', 'crear', datos);
    }
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) {
        cerrarModal('modal-habitacion');
        cargarHabitaciones();
    }
}

async function eliminarHabitacion(id) {
    if (!confirmarEliminacion('¿Eliminar esta habitación?')) return;
    const resp = await apiPost('habitacion', 'eliminar', { id });
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) cargarHabitaciones();
}
