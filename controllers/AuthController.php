<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController extends BaseController
{
    private ?Usuario $modelo = null;

    /**
     * Conexión perezosa: solo se abre cuando realmente se necesita (login,
     * estado). 'logout' NO debe depender de la base de datos -- solo borra
     * la sesión local -- para que siempre funcione aunque la BD remota esté
     * caída, lenta o inaccesible desde este entorno.
     */
    private function modelo(): Usuario
    {
        if ($this->modelo === null) {
            $this->modelo = new Usuario();
        }
        return $this->modelo;
    }

    public function manejar(string $accion): void
    {
        try {
            match ($accion) {
                'login'   => $this->login(),
                'logout'  => $this->logout(),
                'estado'  => $this->estado(),
                default   => $this->respuestaJson(false, 'Acción no reconocida.'),
            };
        } catch (Throwable $e) {
            $this->manejarExcepcion($e);
        }
    }

    private function login(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $error = $this->validarRequerido($datos, ['usuario', 'password']);
        if ($error) {
            $this->respuestaJson(false, $error);
        }

        $usuario = $this->modelo()->buscarPorNombreUsuario(trim((string) $datos['usuario']));

        // Mensaje genérico a propósito: no revelar si falló el usuario o la contraseña.
        if (!$usuario || !password_verify((string) $datos['password'], $usuario['password_hash'])) {
            $this->respuestaJson(false, 'Usuario o contraseña incorrectos.');
        }

        session_regenerate_id(true);
        $_SESSION['usuario_id']       = (int) $usuario['id_usuario'];
        $_SESSION['usuario_nombre']   = $usuario['nombre_completo'];
        $_SESSION['usuario_login']    = $usuario['nombre_usuario'];
        $_SESSION['usuario_rol']      = $usuario['rol'];

        $this->respuestaJson(true, 'Bienvenido, ' . $usuario['nombre_completo'] . '.');
    }

    private function logout(): void
    {
        $_SESSION = [];

        // Destruye también la cookie en el navegador (session_destroy() solo
        // borra los datos en el servidor; si no se expira la cookie, algunos
        // navegadores pueden seguir enviándola).
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parametros['path'],
                $parametros['domain'],
                $parametros['secure'],
                $parametros['httponly']
            );
        }

        session_destroy();
        $this->respuestaJson(true, 'Sesión cerrada correctamente.');
    }

    private function estado(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            $this->respuestaJson(false, 'No hay sesión activa.');
        }
        $this->respuestaJson(true, 'OK', [
            'usuario' => [
                'nombre'   => $_SESSION['usuario_nombre'],
                'login'    => $_SESSION['usuario_login'],
                'rol'      => $_SESSION['usuario_rol'],
            ],
        ]);
    }
}