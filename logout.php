<?php
/**
 * logout.php
 * Cierre de sesión independiente y a prueba de fallos.
 *
 * A propósito NO pasa por index.php ni por AuthController: no depende de
 * JavaScript, fetch, JSON ni de la base de datos. Es un simple enlace
 * <a href="logout.php"> que el navegador visita directamente. Así, cerrar
 * sesión funciona siempre, incluso si la BD remota está caída o si algo
 * falla en el JS del frontend.
 */

declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

// Vacía los datos de sesión.
$_SESSION = [];

// Expira también la cookie en el navegador.
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

// Destruye la sesión en el servidor.
session_destroy();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Location: index.php?vista=login');
exit;
