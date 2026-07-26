/**
 * js/dashboard.js
 */

document.addEventListener('DOMContentLoaded', cargarDashboard);

async function cargarDashboard() {
    const resp = await apiGet('dashboard', 'resumen');
    if (!resp.exito) {
        mostrarNotificacion(resp.mensaje, 'error');
        return;
    }

    const t = resp.totales;
    document.getElementById('res-habitaciones').textContent = t.habitaciones;
    document.getElementById('res-habitaciones-disp').textContent = `${t.habitaciones_disponibles} disponibles`;
    document.getElementById('res-clientes').textContent = t.clientes;
    document.getElementById('res-reservas').textContent = t.reservas_activas;
    document.getElementById('res-ingresos').textContent = `$${parseFloat(t.ingresos_totales).toFixed(2)}`;

    const badgesEstado = {
        pendiente: 'text-bg-warning',
        confirmada: 'text-bg-success',
        cancelada: 'text-bg-danger',
        finalizada: 'text-bg-secondary',
    };

    const tbody = document.getElementById('tabla-proximas-llegadas');
    if (resp.proximas_llegadas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay llegadas próximas.</td></tr>';
    } else {
        tbody.innerHTML = resp.proximas_llegadas.map(r => `
            <tr>
                <td>#${r.id_reserva}</td>
                <td>${escaparHtml(r.cliente_nombre)}</td>
                <td>${escaparHtml(r.habitacion_numero)}</td>
                <td>${r.fecha_entrada}</td>
                <td>${r.fecha_salida}</td>
                <td><span class="badge ${badgesEstado[r.estado] || 'text-bg-secondary'}">${escaparHtml(r.estado)}</span></td>
            </tr>
        `).join('');
    }

    const lista = document.getElementById('lista-ocupacion-tipo');
    lista.innerHTML = resp.ocupacion_por_tipo.map(o => `
        <li class="list-group-item d-flex justify-content-between align-items-center">
            ${escaparHtml(o.nombre)}
            <span class="badge text-bg-primary rounded-pill">${o.total}</span>
        </li>
    `).join('') || '<li class="list-group-item text-muted">Sin datos.</li>';
}
