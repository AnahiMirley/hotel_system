/**
 * js/gastos.js
 */

document.addEventListener('DOMContentLoaded', () => {
    cargarGastos();
    cargarCombosGasto();
    document.getElementById('form-gasto').addEventListener('submit', guardarGasto);
});

async function cargarGastos() {
    const resp = await apiGet('gasto', 'listar');
    pintarGastos(resp.datos || []);
}

async function buscarGastos() {
    const texto = document.getElementById('buscar-gasto').value.trim();
    const resp = await apiGet('gasto', 'buscar', { q: texto });
    pintarGastos(resp.datos || []);
}

async function cargarCombosGasto() {
    const resp = await apiGet('gasto', 'combos');
    const sel = document.getElementById('id_reserva');
    sel.innerHTML = '<option value="" selected disabled>Seleccione una reserva...</option>' +
        resp.reservas.map(r => `<option value="${r.id_reserva}">#${r.id_reserva} - ${escaparHtml(r.cliente_nombre)} (Hab. ${escaparHtml(r.numero)})</option>`).join('');
}

function pintarGastos(lista) {
    const tbody = document.getElementById('tabla-gasto');
    if (lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay gastos registrados.</td></tr>';
        return;
    }
    tbody.innerHTML = lista.map(g => `
        <tr>
            <td>#${g.reserva_codigo}</td>
            <td>${escaparHtml(g.cliente_nombre)}</td>
            <td>${escaparHtml(g.concepto)}</td>
            <td>$${parseFloat(g.monto).toFixed(2)}</td>
            <td>${g.fecha}</td>
            <td class="text-end tabla-acciones">
                <button class="btn btn-sm btn-outline-primary" onclick="editarGasto(${g.id_gasto})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="eliminarGasto(${g.id_gasto})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function abrirFormularioGasto() {
    const form = document.getElementById('form-gasto');
    form.reset();
    form.classList.remove('was-validated');
    document.getElementById('id_gasto').value = '';
    document.getElementById('titulo-modal-gasto').textContent = 'Nuevo Gasto';
    abrirModal('modal-gasto');
}

async function editarGasto(id) {
    const resp = await apiGet('gasto', 'obtener', { id });
    if (!resp.exito) {
        mostrarNotificacion(resp.mensaje, 'error');
        return;
    }
    const g = resp.datos;
    const form = document.getElementById('form-gasto');
    form.classList.remove('was-validated');
    document.getElementById('id_gasto').value = g.id_gasto;
    document.getElementById('id_reserva').value = g.id_reserva;
    document.getElementById('concepto').value = g.concepto;
    document.getElementById('monto').value = g.monto;
    document.getElementById('fecha').value = g.fecha;
    document.getElementById('titulo-modal-gasto').textContent = 'Editar Gasto';
    abrirModal('modal-gasto');
}

async function guardarGasto(evento) {
    evento.preventDefault();
    const form = evento.target;
    if (!formularioValido(form)) return;

    const id = document.getElementById('id_gasto').value;
    const datos = {
        id_reserva: document.getElementById('id_reserva').value,
        concepto: document.getElementById('concepto').value.trim(),
        monto: document.getElementById('monto').value,
        fecha: document.getElementById('fecha').value,
    };
    let resp;
    if (id) {
        datos.id_gasto = id;
        resp = await apiPost('gasto', 'editar', datos);
    } else {
        resp = await apiPost('gasto', 'crear', datos);
    }
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) {
        cerrarModal('modal-gasto');
        cargarGastos();
    }
}

async function eliminarGasto(id) {
    if (!confirmarEliminacion('¿Eliminar este gasto?')) return;
    const resp = await apiPost('gasto', 'eliminar', { id });
    mostrarNotificacion(resp.mensaje, resp.exito ? 'exito' : 'error');
    if (resp.exito) cargarGastos();
}
