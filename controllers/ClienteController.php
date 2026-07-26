<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Cliente.php';

class ClienteController extends BaseController
{
    private Cliente $modelo;

    public function __construct()
    {
        $this->modelo = new Cliente();
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
            $this->respuestaJson(false, 'Cliente no encontrado.');
        }
        $this->respuestaJson(true, 'OK', ['datos' => $registro]);
    }

    private function buscar(): void
    {
        $texto = trim($_GET['q'] ?? '');
        $datos = $texto === '' ? $this->modelo->listarTodos() : $this->modelo->buscar($texto);
        $this->respuestaJson(true, 'OK', ['datos' => $datos]);
    }

    private function validarFormatoCliente(array $datos): ?string
    {
        $patronNombre = '/^[\p{L}\s.\'-]{2,80}$/u';
        if (!preg_match($patronNombre, $datos['nombre'])) {
            return 'El nombre solo debe contener letras y espacios.';
        }
        if (!preg_match($patronNombre, $datos['apellido'])) {
            return 'El apellido solo debe contener letras y espacios.';
        }
        if (!preg_match('/^[0-9]{10}$/', $datos['dni'])) {
            return 'El DNI debe contener exactamente 10 dígitos numéricos.';
        }
        if (!preg_match('/^[0-9+ -]{7,20}$/', $datos['telefono'])) {
            return 'El teléfono ingresado no tiene un formato válido.';
        }
        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no tiene un formato válido.';
        }
        return null;
    }

    private function crear(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $error = $this->validarRequerido($datos, ['nombre', 'apellido', 'dni', 'telefono']);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        $error = $this->validarFormatoCliente($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        if ($this->modelo->dniExiste($datos['dni'])) {
            $this->respuestaJson(false, 'Ya existe un cliente registrado con ese DNI.');
        }
        $datos['direccion'] = $datos['direccion'] ?? '';
        $datos['email'] = $datos['email'] ?? '';
        $id = $this->modelo->crear($datos);
        $this->respuestaJson(true, 'Cliente creado correctamente.', ['id' => $id]);
    }

    private function editar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id_cliente'] ?? 0);
        $error = $this->validarRequerido($datos, ['nombre', 'apellido', 'dni', 'telefono']);
        if ($id <= 0 || $error) {
            $this->respuestaJson(false, $error ?? 'ID inválido.');
        }
        $error = $this->validarFormatoCliente($datos);
        if ($error) {
            $this->respuestaJson(false, $error);
        }
        if ($this->modelo->dniExiste($datos['dni'], $id)) {
            $this->respuestaJson(false, 'Ya existe otro cliente registrado con ese DNI.');
        }
        $datos['direccion'] = $datos['direccion'] ?? '';
        $datos['email'] = $datos['email'] ?? '';
        $this->modelo->actualizar($id, $datos);
        $this->respuestaJson(true, 'Cliente actualizado correctamente.');
    }

    private function eliminar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->respuestaJson(false, 'ID inválido.');
        }
        $this->modelo->eliminar($id);
        $this->respuestaJson(true, 'Cliente eliminado correctamente.');
    }
}
