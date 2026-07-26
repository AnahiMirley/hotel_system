<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Servicio.php';

class ServicioController extends BaseController
{
    private Servicio $modelo;

    public function __construct()
    {
        $this->modelo = new Servicio();
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
            $this->respuestaJson(false, 'Servicio no encontrado.');
        }
        $this->respuestaJson(true, 'OK', ['datos' => $registro]);
    }

    private function buscar(): void
    {
        $texto = trim($_GET['q'] ?? '');
        $datos = $texto === '' ? $this->modelo->listarTodos() : $this->modelo->buscar($texto);
        $this->respuestaJson(true, 'OK', ['datos' => $datos]);
    }

    /** Valida formato y tipo de los campos del servicio. */
    private function validarFormatoServicio(array $datos): ?string
    {
        if (mb_strlen(trim((string) $datos['nombre'])) < 2) {
            return 'El nombre del servicio debe tener al menos 2 caracteres.';
        }
        if (!is_numeric($datos['precio'])) {
            return 'El precio debe ser un número válido.';
        }
        if ((float) $datos['precio'] < 0) {
            return 'El precio no puede ser negativo.';
        }
        return null;
    }

    private function crear(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $error = $this->validarRequerido($datos, ['nombre', 'precio']);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $error = $this->validarFormatoServicio($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $datos['descripcion'] = $datos['descripcion'] ?? '';
        $id = $this->modelo->crear($datos);
        $this->respuestaJson(true, 'Servicio creado correctamente.', ['id' => $id]);
    }

    private function editar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id_servicio'] ?? 0);
        $error = $this->validarRequerido($datos, ['nombre', 'precio']);
        if ($id <= 0 || $error) {
            $this->respuestaJson(false, $error ?? 'ID inválido.');
        }
        $error = $this->validarFormatoServicio($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $datos['descripcion'] = $datos['descripcion'] ?? '';
        $this->modelo->actualizar($id, $datos);
        $this->respuestaJson(true, 'Servicio actualizado correctamente.');
    }

    private function eliminar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->respuestaJson(false, 'ID inválido.');
        }
        $this->modelo->eliminar($id);
        $this->respuestaJson(true, 'Servicio eliminado correctamente.');
    }
}