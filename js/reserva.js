/**
 * js/reserva.js
 */

const BADGES_ESTADO_RESERVA = {
    pendiente: 'text-bg-warning',
    confirmada: 'text-bg-success',
    cancelada: 'text-bg-danger',
    finalizada: 'text-bg-secondary',
};

document.addEventListener('DOMContentLoaded', () => {
    cargarReservas();
    cargarCombosReserva();
    document.getElementById('form-reserva').addEventListener('submit', guardarReserva);

    const entrada = document.getElementById('fecha_entrada');
    const salida = document.getElementById('fecha_salida');
    const validarRangoFechas = () => {
        if (entrada.value && salida.value && salida.value <= entrada.value) {
            salida.setCustomValidity('La fecha de salida debe ser posterior a la entrada.');
        } else {
            salida.setCustomValidity('');
        }
    };
    entrada.addEventListener('change', validarRangoFechas);
    salida.addEventListener('change', validarRangoFechas);
});

async function cargarReservas() {
    const resp = await apiGet('reserva', 'listar');
    pintarReservas(resp.datos || []);
}

async function buscarReservas() {
    const texto = document.getElementById('buscar-reserva').value.trim();
    const resp = await apiGet('reserva', 'buscar', { q: texto });
    pintarReservas(resp.datos || []);
}

async function cargarCombosReserva() {
    const resp = await apiGet('reserva', 'combos');
    const selCliente = document.getElementById('id_cliente');
    const selHabitacion = document.getElementById('id_habitacion');
    selCliente.innerHTML = '<option value="" selected disabled>Seleccione un cliente...</option>' +
        resp.clientes.map(c => `<option value="${c.id_cliente}">${escaparHtml(c.nombre)} ${escaparHtml(c.apellido)}</option>`).join('');
    selHabitacion.innerHTML = '<option value="" selected disabled>Seleccione una habitación...</option>' +
        resp.habitaciones.map(h => `<option value="${h.id_habitacion}">Hab. ${escaparHtml(h.numero)}</option>`).join('');
}

function pintarReservas(lista) {
    const tbody = document.getElementById('tabla-reserva');
    if (lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No hay reservas registradas.</td></tr>';
        return;
    }
    tbody.innerHTML = lista.map(r => `
        <tr>
            <td>#${r.id_reserva}</td>
            <td>${escaparHtml(r.cliente_nombre)}</td>
            <td>${escaparHtml(r.habitacion_numero)}</td>
            <td>${r.fecha_entrada}</td>
            <td>${r.fecha_salida}</td>
            <td><span class="badge ${BADGES_ESTADO_RESERVA[r.estado] || 'text-bg-secondary'}">${escaparHtml(r.estado)}</span></td>
            <td class="text-end tabla-acciones">
                <button class="btn btn-sm btn-outline-primary" onclick="editarReserva(${r.id_reserva})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="eliminarReserva(${r.id_reserva})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function abrirFormularioReserva() {
    const form = document.getElementById('form-reserva');
    form.reset();
    form.classList.remove('was-validated');
    document.getElementById('fecha_salida').setCustomValidity('');
    document.getElementById('id_reserva').value = '';
    document.getElementById('titulo-modal-reserva').textContent = 'Nueva Reserva';
    abrirModal('modal-reserva');
}

async function editarReserva(id) {
    const resp = await apiGet('reserva', 'obtener', { id });
    if (!resp.exito) {
        mostrarNotificacion(resp.mensaje, 'error');
        return;
    }
    const r = resp.datos;
    const form = document.getElementById('form-reserva');
    form.classList.remove('was-validated');
    document.getElementById('fecha_salida').setCustomValidity('');
    document.getElementById('id_reserva').value = r.id_reserva;
    document.getElementById('id_cliente').value = r.id_cliente;
    document.getElementById('id_habitacion').value = r.id_habitacion;
    document.getElementById('fecha_entrada').value = r.fecha_entrada;
    document.getElementById('fecha_salida').value = r.fecha_salida;
    document.getElementById('estado').value = r.estado;
    document.getElementById('titulo-modal-reserva').textContent = 'Editar Reserva';
    abrirModal('modal-reserva');
}

async function guardarReserva(evento) {
    evento.preventDefault();
    const form = evento.target;
    if (!formularioValido(form)) return;

    const id = document.getElementById('id_reserva').value;
    const datos = {
        id_cliente: document.getElementById('id_cliente').value,
        id_habitacion: document.getElementById('id_habitacion').value,
        fecha_entrada: document.getElementById('fecha_entrada').value,
        fecha_salida: document.getElementById('fecha_salida').value,
        estado: document.getElementById('estado').value,
    };
    let resp;
    if (id) {
        datos.id_reserva = id;
        resp = await apiPost('reserva', 'editar', datos);
    } else {
        resp = await apiPost('reserva', 'crear', datos);
    }
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) {
        cerrarModal('modal-reserva');
        cargarReservas();
    }
}

async function eliminarReserva(id) {
    if (!confirmarEliminacion('¿Eliminar esta reserva?')) return;
    const resp = await apiPost('reserva', 'eliminar', { id });
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) cargarReservas();
}
