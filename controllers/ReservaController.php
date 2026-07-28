<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/Habitacion.php';

class ReservaController extends BaseController
{
    private Reserva $modelo;

    public function __construct()
    {
        $this->modelo = new Reserva();
    }

    public function manejar(string $accion): void
    {
        try {
            match ($accion) {
                'listar'   => $this->listar(),
                'obtener'  => $this->obtener(),
                'buscar'   => $this->buscar(),
                'crear'    => $this->crear(),
                'editar'   => $this->editar(),
                'eliminar' => $this->eliminar(),
                'combos'   => $this->combos(),
                default    => $this->respuestaJson(false, 'Acción no reconocida.'),
            };
        } catch (Throwable $e) {
            $this->manejarExcepcion($e);
        }
    }

    private function listar(): void
    {
        $this->respuestaJson(true, 'OK', ['datos' => $this->modelo->listarTodos()]);
    }

    private function obtener(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $registro = $this->modelo->buscarPorId($id);
        if (!$registro) {
            $this->respuestaJson(false, 'Reserva no encontrada.');
        }
        $this->respuestaJson(true, 'OK', ['datos' => $registro]);
    }

    private function buscar(): void
    {
        $texto = trim($_GET['q'] ?? '');
        $datos = $texto === '' ? $this->modelo->listarTodos() : $this->modelo->buscar($texto);
        $this->respuestaJson(true, 'OK', ['datos' => $datos]);
    }

    private function combos(): void
    {
        $this->respuestaJson(true, 'OK', [
            'clientes'     => $this->modelo->obtenerClientes(),
            'habitaciones' => $this->modelo->obtenerHabitaciones(),
        ]);
    }

    private const ESTADOS_VALIDOS = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];

    /** Valida que una fecha tenga formato Y-m-d real (rechaza strings no parseables o fechas inexistentes). */
    private function fechaValida(string $fecha): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d !== false && $d->format('Y-m-d') === $fecha;
    }

    /** Valida formato de fechas, IDs y coherencia entrada/salida. */
    private function validarFormatoReserva(array $datos): ?string
    {
        if (!ctype_digit((string) $datos['id_cliente'])) {
            return 'El cliente seleccionado no es válido.';
        }
        if (!ctype_digit((string) $datos['id_habitacion'])) {
            return 'La habitación seleccionada no es válida.';
        }
        if (!$this->fechaValida((string) $datos['fecha_entrada'])) {
            return 'La fecha de entrada no es válida.';
        }
        if (!$this->fechaValida((string) $datos['fecha_salida'])) {
            return 'La fecha de salida no es válida.';
        }
        if ($datos['fecha_salida'] <= $datos['fecha_entrada']) {
            return 'La fecha de salida debe ser posterior a la fecha de entrada.';
        }
        if (!in_array($datos['estado'], self::ESTADOS_VALIDOS, true)) {
            return 'El estado indicado no es válido.';
        }
        return null;
    }

    /**
     * Verifica que la habitación exista y esté disponible.
     * Si no lo está, explica el motivo concreto (mantenimiento u ocupada)
     * en vez de un mensaje genérico de "no disponible".
     */
    private function validarDisponibilidadHabitacion(int $idHabitacion): ?string
    {
        $habitacion = (new Habitacion())->buscarPorId($idHabitacion);
        if (!$habitacion) {
            return 'La habitación seleccionada no existe.';
        }
        if ($habitacion['estado'] === 'mantenimiento') {
            return "La habitación {$habitacion['numero']} está en mantenimiento y no puede reservarse.";
        }
        if ($habitacion['estado'] === 'ocupada') {
            return "La habitación {$habitacion['numero']} está actualmente ocupada.";
        }
        return null; // 'disponible' → continúa el flujo normal
    }

    private function crear(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $error = $this->validarRequerido($datos, ['id_cliente', 'id_habitacion', 'fecha_entrada', 'fecha_salida', 'estado']);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $error = $this->validarFormatoReserva($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        // Solo exigir disponibilidad si la reserva sigue activa;
        // cancelar o finalizar una reserva nunca debería bloquearse por el estado de la habitación.
        if (!in_array($datos['estado'], ['cancelada', 'finalizada'], true)) {
            $error = $this->validarDisponibilidadHabitacion((int) $datos['id_habitacion']);
            if ($error) {
                $this->respuestaJson(false, $error);
            }
        }
        if ($this->modelo->existeSolapamiento((int) $datos['id_habitacion'], $datos['fecha_entrada'], $datos['fecha_salida'])) {
            $this->respuestaJson(false, 'La habitación ya está reservada en ese rango de fechas.');
        }
        $id = $this->modelo->crear($datos);
        $this->respuestaJson(true, 'Reserva creada correctamente.', ['id' => $id]);
    }

    private function editar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id_reserva'] ?? 0);
        $error = $this->validarRequerido($datos, ['id_cliente', 'id_habitacion', 'fecha_entrada', 'fecha_salida', 'estado']);
        if ($id <= 0 || $error) {
            $this->respuestaJson(false, $error ?? 'ID inválido.');
        }
        $error = $this->validarFormatoReserva($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $error = $this->validarDisponibilidadHabitacion((int) $datos['id_habitacion']);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        if ($this->modelo->existeSolapamiento((int) $datos['id_habitacion'], $datos['fecha_entrada'], $datos['fecha_salida'], $id)) {
            $this->respuestaJson(false, 'La habitación ya está reservada en ese rango de fechas.');
        }
        $this->modelo->actualizar($id, $datos);
        $this->respuestaJson(true, 'Reserva actualizada correctamente.');
    }

    private function eliminar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->respuestaJson(false, 'ID inválido.');
        }
        $this->modelo->eliminar($id);
        $this->respuestaJson(true, 'Reserva eliminada correctamente.');
    }
}