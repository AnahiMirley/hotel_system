<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/conexion.php';

class DashboardController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::getConexion();
    }

    public function manejar(string $accion): void
    {
        try {
            match ($accion) {
                'resumen' => $this->resumen(),
                default   => $this->respuestaJson(false, 'Acción no reconocida.'),
            };
        } catch (Throwable $e) {
            $this->manejarExcepcion($e);
        }
    }

    private function resumen(): void
    {
        $totalHabitaciones = (int) $this->db->query('SELECT COUNT(*) FROM habitacion')->fetchColumn();
        $habitacionesDisponibles = (int) $this->db->query(
            "SELECT COUNT(*) FROM habitacion WHERE estado = 'disponible'"
        )->fetchColumn();
        $totalClientes = (int) $this->db->query('SELECT COUNT(*) FROM cliente')->fetchColumn();
        $reservasActivas = (int) $this->db->query(
            "SELECT COUNT(*) FROM reserva WHERE estado IN ('pendiente','confirmada')"
        )->fetchColumn();
        $ingresosTotales = (float) $this->db->query('SELECT COALESCE(SUM(monto), 0) FROM gastos')->fetchColumn();

        $proximasLlegadas = $this->db->query(
            "SELECT r.id_reserva, CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
                    h.numero AS habitacion_numero, r.fecha_entrada, r.fecha_salida, r.estado
             FROM reserva r
             INNER JOIN cliente c ON c.id_cliente = r.id_cliente
             INNER JOIN habitacion h ON h.id_habitacion = r.id_habitacion
             WHERE r.estado IN ('pendiente','confirmada')
             ORDER BY r.fecha_entrada ASC
             LIMIT 5"
        )->fetchAll();

        $ocupacionPorTipo = $this->db->query(
            "SELECT t.nombre, COUNT(h.id_habitacion) AS total
             FROM tipo_habitacion t
             LEFT JOIN habitacion h ON h.id_tipo_habitacion = t.id_tipo_habitacion
             GROUP BY t.id_tipo_habitacion, t.nombre
             ORDER BY t.nombre"
        )->fetchAll();

        $this->respuestaJson(true, 'OK', [
            'totales' => [
                'habitaciones'             => $totalHabitaciones,
                'habitaciones_disponibles' => $habitacionesDisponibles,
                'clientes'                 => $totalClientes,
                'reservas_activas'         => $reservasActivas,
                'ingresos_totales'         => round($ingresosTotales, 2),
            ],
            'proximas_llegadas' => $proximasLlegadas,
            'ocupacion_por_tipo' => $ocupacionPorTipo,
        ]);
    }
}
