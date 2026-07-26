<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/TipoHabitacion.php';

class TipoHabitacionController extends BaseController
{
    private TipoHabitacion $modelo;

    public function __construct()
    {
        $this->modelo = new TipoHabitacion();
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
            $this->respuestaJson(false, 'Tipo de habitación no encontrado.');
        }
        $this->respuestaJson(true, 'OK', ['datos' => $registro]);
    }

    private function buscar(): void
    {
        $texto = trim($_GET['q'] ?? '');
        $datos = $texto === '' ? $this->modelo->listarTodos() : $this->modelo->buscar($texto);
        $this->respuestaJson(true, 'OK', ['datos' => $datos]);
    }

    /** Valida formato y tipo de los campos del tipo de habitación. */
    private function validarFormatoTipo(array $datos): ?string
    {
        if (mb_strlen(trim((string) $datos['nombre'])) < 2) {
            return 'El nombre debe tener al menos 2 caracteres.';
        }
        if (!ctype_digit((string) $datos['capacidad'])) {
            return 'La capacidad debe ser un número entero válido.';
        }
        if ((int) $datos['capacidad'] < 1 || (int) $datos['capacidad'] > 20) {
            return 'La capacidad debe estar entre 1 y 20 personas.';
        }
        if (!is_numeric($datos['precio_base'])) {
            return 'El precio base debe ser un número válido.';
        }
        if ((float) $datos['precio_base'] < 0) {
            return 'El precio base no puede ser negativo.';
        }
        return null;
    }

    private function crear(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $error = $this->validarRequerido($datos, ['nombre', 'capacidad', 'precio_base']);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $error = $this->validarFormatoTipo($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $datos['descripcion'] = $datos['descripcion'] ?? '';
        $id = $this->modelo->crear($datos);
        $this->respuestaJson(true, 'Tipo de habitación creado correctamente.', ['id' => $id]);
    }

    private function editar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id_tipo_habitacion'] ?? 0);
        $error = $this->validarRequerido($datos, ['nombre', 'capacidad', 'precio_base']);
        if ($id <= 0 || $error) {
            $this->respuestaJson(false, $error ?? 'ID inválido.');
        }
        $error = $this->validarFormatoTipo($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $datos['descripcion'] = $datos['descripcion'] ?? '';
        $this->modelo->actualizar($id, $datos);
        $this->respuestaJson(true, 'Tipo de habitación actualizado correctamente.');
    }

    private function eliminar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->respuestaJson(false, 'ID inválido.');
        }
        if ($this->modelo->enUso($id)) {
            $this->respuestaJson(false, 'No se puede eliminar: el tipo está asignado a habitaciones.');
        }
        $this->modelo->eliminar($id);
        $this->respuestaJson(true, 'Tipo de habitación eliminado correctamente.');
    }
}