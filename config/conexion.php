<?php
/**
 * config/conexion.php
 * Clase de conexión a la base de datos usando PDO.
 * Patrón Singleton para reutilizar una única conexión por petición.
 *
 * IMPORTANTE: esta clase NUNCA debe usar die()/echo/print para reportar un
 * error de conexión. index.php siempre responde en JSON (es una API
 * consumida por fetch()), así que cualquier salida de texto/HTML aquí rompe
 * el parseo en el frontend (resp.json() falla) y en el navegador se ve como
 * "No se pudo conectar con el servidor", incluso si el problema real es
 * otro (credenciales, SSL, host, etc.). En su lugar, dejamos que la
 * PDOException suba; quien la atrapa y decide qué responder es
 * BaseController::manejarExcepcion() / el try-catch de index.php.
 */

class Conexion
{
    private static ?PDO $instancia = null;

    private function __construct()
    {
        // Constructor privado: no se permite instanciar directamente.
    }

    public static function getConexion(): PDO
    {
        if (self::$instancia === null) {
            // Lee las variables de entorno (Render/Clever Cloud/Railway/etc.)
            // o usa los valores locales por defecto si no están definidas.
            $host   = getenv('DB_HOST') ?: '127.0.0.1';
            $puerto = getenv('DB_PORT') ?: '3306';
            $dbname = getenv('DB_NAME') ?: 'hotel_db';
            $user   = getenv('DB_USER') ?: 'root';
            $pass   = getenv('DB_PASS') ?: '';

            $dsn = "mysql:host={$host};port={$puerto};dbname={$dbname};charset=utf8mb4";

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Sin timeout, si el host en la nube no responde, la petición
                // puede quedarse "colgada" hasta el límite de PHP y devolver
                // un error 500 en HTML (mismo problema del die()). Con esto
                // falla rápido y de forma controlada.
                PDO::ATTR_TIMEOUT            => 5,
            ];

            // Muchos proveedores de base de datos en la nube (Aiven, PlanetScale,
            // Clever Cloud, Railway, TiDB Cloud, etc.) EXIGEN conexión por SSL/TLS
            // y rechazan conexiones planas. Si tu proveedor lo requiere, define
            // la variable de entorno DB_SSL_CA con la ruta al certificado que te
            // entregó el proveedor (o DB_SSL=1 si no necesitas verificar el CA).
            $sslCa = getenv('DB_SSL_CA');
            if ($sslCa) {
                $opciones[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
                $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            } elseif (getenv('DB_SSL') === '1') {
                $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            // Puede lanzar PDOException: se deja subir a propósito (ver nota arriba).
            self::$instancia = new PDO($dsn, $user, $pass, $opciones);
        }

        return self::$instancia;
    }
}
