<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Habitacion.php';
require_once __DIR__ . '/../models/TipoHabitacion.php';

class HabitacionController extends BaseController
{
    private Habitacion $modelo;

    public function __construct()
    {
        $this->modelo = new Habitacion();
    }

    public function manejar(string $accion): void
    {
        try {
            match ($accion) {
                'listar'    => $this->listar(),
                'obtener'   => $this->obtener(),
                'buscar'    => $this->buscar(),
                'crear'     => $this->crear(),
                'editar'    => $this->editar(),
                'eliminar'  => $this->eliminar(),
                'combos'    => $this->combos(),
                default     => $this->respuestaJson(false, 'Acción no reconocida.'),
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
            $this->respuestaJson(false, 'Habitación no encontrada.');
        }
        $this->respuestaJson(true, 'OK', ['datos' => $registro]);
    }

    private function buscar(): void
    {
        $texto = trim($_GET['q'] ?? '');
        $datos = $texto === '' ? $this->modelo->listarTodos() : $this->modelo->buscar($texto);
        $this->respuestaJson(true, 'OK', ['datos' => $datos]);
    }

    /** Devuelve el combo de tipos de habitación para los formularios. */
    private function combos(): void
    {
        $tipos = (new TipoHabitacion())->listarTodos();
        $this->respuestaJson(true, 'OK', ['tipos' => $tipos]);
    }

    private const ESTADOS_VALIDOS = ['disponible', 'ocupada', 'mantenimiento'];

    /** Valida formato y tipo de los campos de la habitación. */
    private function validarFormatoHabitacion(array $datos): ?string
    {
        if (!preg_match('/^[0-9A-Za-z-]{1,10}$/', (string) $datos['numero'])) {
            return 'El número de habitación debe ser alfanumérico (máx. 10 caracteres).';
        }
        if (!ctype_digit((string) $datos['planta'])) {
            return 'La planta debe ser un número entero válido.';
        }
        if ((int) $datos['planta'] < 0 || (int) $datos['planta'] > 200) {
            return 'La planta debe estar entre 0 y 200.';
        }
        if (!in_array($datos['estado'], self::ESTADOS_VALIDOS, true)) {
            return 'El estado indicado no es válido.';
        }
        if (!ctype_digit((string) $datos['id_tipo_habitacion'])) {
            return 'El tipo de habitación seleccionado no es válido.';
        }
        return null;
    }

    private function crear(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $error = $this->validarRequerido($datos, ['numero', 'planta', 'estado', 'id_tipo_habitacion']);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $error = $this->validarFormatoHabitacion($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $id = $this->modelo->crear($datos);
        $this->respuestaJson(true, 'Habitación creada correctamente.', ['id' => $id]);
    }

    private function editar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id_habitacion'] ?? 0);
        $error = $this->validarRequerido($datos, ['numero', 'planta', 'estado', 'id_tipo_habitacion']);
        if ($id <= 0 || $error) {
            $this->respuestaJson(false, $error ?? 'ID inválido.');
        }
        $error = $this->validarFormatoHabitacion($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $this->modelo->actualizar($id, $datos);
        $this->respuestaJson(true, 'Habitación actualizada correctamente.');
    }

    private function eliminar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->respuestaJson(false, 'ID inválido.');
        }
        if ($this->modelo->tieneReservas($id)) {
            $this->respuestaJson(false, 'No se puede eliminar: la habitación tiene reservas asociadas.');
        }
        $this->modelo->eliminar($id);
        $this->respuestaJson(true, 'Habitación eliminada correctamente.');
    }
}