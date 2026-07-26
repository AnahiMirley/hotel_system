<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Gasto.php';

class GastoController extends BaseController
{
    private Gasto $modelo;

    public function __construct()
    {
        $this->modelo = new Gasto();
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
            $this->respuestaJson(false, 'Gasto no encontrado.');
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
        $this->respuestaJson(true, 'OK', ['reservas' => $this->modelo->obtenerReservas()]);
    }

    /** Valida tipos y formatos de los campos del gasto (numéricos y fecha). */
    private function validarFormatoGasto(array $datos): ?string
    {
        if (!ctype_digit((string) $datos['id_reserva'])) {
            return 'La reserva seleccionada no es válida.';
        }
        if (!is_numeric($datos['monto'])) {
            return 'El monto debe ser un número válido.';
        }
        if ((float) $datos['monto'] < 0) {
            return 'El monto no puede ser negativo.';
        }
        if (mb_strlen(trim((string) $datos['concepto'])) < 3) {
            return 'El concepto debe tener al menos 3 caracteres.';
        }
        $fecha = DateTime::createFromFormat('Y-m-d', (string) $datos['fecha']);
        if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha']) {
            return 'La fecha ingresada no es válida.';
        }
        return null;
    }

    private function crear(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $error = $this->validarRequerido($datos, ['id_reserva', 'concepto', 'monto', 'fecha']);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $error = $this->validarFormatoGasto($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $id = $this->modelo->crear($datos);
        $this->respuestaJson(true, 'Gasto registrado correctamente.', ['id' => $id]);
    }

    private function editar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id_gasto'] ?? 0);
        $error = $this->validarRequerido($datos, ['id_reserva', 'concepto', 'monto', 'fecha']);
        if ($id <= 0 || $error) {
            $this->respuestaJson(false, $error ?? 'ID inválido.');
        }
        $error = $this->validarFormatoGasto($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $this->modelo->actualizar($id, $datos);
        $this->respuestaJson(true, 'Gasto actualizado correctamente.');
    }

    private function eliminar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->respuestaJson(false, 'ID inválido.');
        }
        $this->modelo->eliminar($id);
        $this->respuestaJson(true, 'Gasto eliminado correctamente.');
    }
}