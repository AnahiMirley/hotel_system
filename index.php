<?php
/**
 * index.php
 * Punto de entrada único de la aplicación.
 * Enruta peticiones a controladores (API interna, vía ?api=entidad&accion=xxx)
 * o sirve las vistas HTML (vía ?vista=entidad).
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Configuración explícita de la cookie de sesión. En local (HTTP) "secure"
// se desactiva solo; en producción (HTTPS, como en Clever Cloud) se activa
// automáticamente. Esto evita comportamientos inconsistentes de la sesión
// al pasar de local a un hosting en la nube.
$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $esHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

// La sesión debe iniciarse antes de cualquier salida
session_start();

// Evita que el navegador o un proxy/CDN intermedio guarde en caché estas
// respuestas dinámicas (dependen de si hay sesión iniciada o no). Sin esto,
// una vez que alguien ve el dashboard cacheado, puede seguir viéndolo
// aunque no tenga sesión activa o incluso después de cerrar sesión.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ---------- Enrutamiento de API (peticiones AJAX) ----------
if (isset($_GET['api'])) {
    $entidad = $_GET['api'];
    $accion  = $_GET['accion'] ?? 'listar';

    $mapa = [
        'auth'            => ['controllers/AuthController.php', 'AuthController'],
        'tipo_habitacion' => ['controllers/TipoHabitacionController.php', 'TipoHabitacionController'],
        'habitacion'      => ['controllers/HabitacionController.php', 'HabitacionController'],
        'cliente'         => ['controllers/ClienteController.php', 'ClienteController'],
        'reserva'         => ['controllers/ReservaController.php', 'ReservaController'],
        'servicio'        => ['controllers/ServicioController.php', 'ServicioController'],
        'gasto'           => ['controllers/GastoController.php', 'GastoController'],
        'dashboard'       => ['controllers/DashboardController.php', 'DashboardController'],
    ];

    if (!isset($mapa[$entidad])) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(404);
        echo json_encode(['exito' => false, 'mensaje' => 'Entidad no encontrada.']);
        exit;
    }

    if ($entidad !== 'auth' && !isset($_SESSION['usuario_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['exito' => false, 'mensaje' => 'Sesión requerida.']);
        exit;
    }

    [$archivo, $clase] = $mapa[$entidad];
    require_once __DIR__ . '/' . $archivo;

    try {
        (new $clase())->manejar($accion);
    } catch (Throwable $e) {
        // Cubre también fallos que ocurren ANTES de entrar al try/catch
        // interno de cada controlador (p. ej. la conexión a la BD se abre
        // en el constructor, como en AuthController -> new Usuario()).
        // Sin este bloque, un error aquí generaría una página de error en
        // HTML/texto plano en lugar de JSON, y el frontend (que siempre
        // espera JSON) lo interpretaría como "no se pudo conectar".
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        $mensaje = 'No se pudo conectar con la base de datos. Intenta nuevamente en unos segundos.';
        if (getenv('APP_DEBUG') === '1') {
            $mensaje .= ' Detalle: ' . $e->getMessage();
        }
        echo json_encode(['exito' => false, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ---------- Enrutamiento de vistas (páginas HTML) ----------
$vistas = [
    'dashboard'       => ['titulo' => 'Panel General',         'icono' => 'bi-speedometer2',       'archivo' => 'dashboard'],
    'tipo_habitacion' => ['titulo' => 'Tipos de Habitación',   'icono' => 'bi-grid-3x3-gap',       'archivo' => 'tipo_habitacion'],
    'habitacion'      => ['titulo' => 'Habitaciones',          'icono' => 'bi-door-closed',        'archivo' => 'habitacion'],
    'cliente'         => ['titulo' => 'Clientes',              'icono' => 'bi-people',             'archivo' => 'cliente'],
    'reserva'         => ['titulo' => 'Reservas',              'icono' => 'bi-calendar-check',     'archivo' => 'reserva'],
    'servicios'       => ['titulo' => 'Servicios',             'icono' => 'bi-stars',              'archivo' => 'servicios'],
    'gastos'          => ['titulo' => 'Gastos',                'icono' => 'bi-receipt',            'archivo' => 'gastos'],
];

$vista = $_GET['vista'] ?? 'dashboard';

// ---------- Vista de login ----------
if ($vista === 'login') {
    if (isset($_SESSION['usuario_id'])) {
        header('Location: index.php?vista=dashboard');
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Iniciar sesión · Sistema de Gestión Hotelera</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <link rel="stylesheet" href="css/estilos.css">
    </head>
    <body class="pagina-login">
        <?php require __DIR__ . '/views/login/index.html'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/comun.js"></script>
        <script src="js/login.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// ---------- Guarda de autenticación ----------
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php?vista=login');
    exit;
}

if (!array_key_exists($vista, $vistas)) {
    $vista = 'dashboard';
}
$actual = $vistas[$vista];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($actual['titulo']) ?> · Sistema de Gestión Hotelera</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<nav class="navbar navbar-dark bg-dark navbar-topbar px-3 shadow-sm">

    <span class="navbar-brand mb-0 h1 ms-2">
        <i class="bi bi-building"></i> Sistema de Gestión Hotelera
    </span>
    <span class="navbar-text small text-light opacity-75 d-none d-md-inline">
        Panel administrativo
    </span>
</nav>

<div class="overlay-sidebar d-lg-none" id="overlay-sidebar"></div>

<div class="d-flex app-layout">
    <nav class="sidebar bg-white border-end shadow-sm" id="sidebar">
        <div class="sidebar-marca p-3 border-bottom d-flex align-items-center gap-2">
            <span class="sidebar-marca-icono fs-4 text-primary"><i class="bi bi-building"></i></span>
            <div class="sidebar-marca-texto">
                <strong class="d-block text-dark">Hotel System</strong>
                <small class="text-muted">Panel administrativo</small>
            </div>
        </div>

        <div class="sidebar-nav nav flex-column p-2 gap-1">
            <?php foreach ($vistas as $clave => $info): ?>
           <a href="index.php?vista=<?= $clave ?>"
   class="nav-link rounded px-3 py-2 d-flex align-items-center gap-2 <?= $vista === $clave ? 'active bg-primary text-white' : '' ?>"></a>
                <i class="bi <?= $info['icono'] ?> fs-5"></i>
                <span><?= htmlspecialchars($info['titulo']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-pie p-3 border-top mt-auto d-flex align-items-center justify-content-between">
            <div class="sidebar-pie-usuario d-flex align-items-center gap-2">
                <i class="bi bi-person-circle fs-4 text-secondary"></i>
                <span class="small fw-semibold text-truncate" style="max-width: 120px;"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?></span>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm btn-logout" id="btn-logout" title="Cerrar sesión" onclick="return confirm('¿Cerrar sesión?');">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </nav>

    <main class="flex-grow-1 p-4 contenido bg-light">
        <?php require __DIR__ . '/views/' . $actual['archivo'] . '/index.html'; ?>
    </main>
</div>

<!-- Contenedor de notificaciones -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/comun.js"></script>
<script src="js/sidebar.js"></script>
<?php
$scripts = [
    'dashboard'       => 'js/dashboard.js',
    'tipo_habitacion' => 'js/tipo_habitacion.js',
    'habitacion'      => 'js/habitacion.js',
    'cliente'         => 'js/cliente.js',
    'reserva'         => 'js/reserva.js',
    'servicios'       => 'js/servicios.js',
    'gastos'          => 'js/gastos.js',
];
?>
<script src="<?= $scripts[$vista] ?? 'js/comun.js' ?>"></script>
</body>
</html>