<?php
/**
 * controllers/BaseController.php
 * Funciones auxiliares comunes a todos los controladores:
 * respuestas JSON uniformes y manejo centralizado de excepciones.
 */

abstract class BaseController
{
    protected function respuestaJson(bool $exito, string $mensaje, array $datos = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'exito'   => $exito,
            'mensaje' => $mensaje,
        ], $datos), JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function manejarExcepcion(Throwable $e): void
    {
        error_log('EXCEPCION LOGIN: ' . $e->getMessage()); // <-- línea temporal de diagnóstico
        
        $mensaje = 'Error en el servidor.';
        // 23000 = violación de restricción de integridad (FK, UNIQUE, CHECK)
        if ($e instanceof PDOException && str_starts_with((string) $e->getCode(), '23')) {
            $mensaje = 'No se puede completar la operación: el registro está en uso o los datos son inválidos.';
        }
        $this->respuestaJson(false, $mensaje);
    }

    protected function validarRequerido(array $datos, array $campos): ?string
    {
        foreach ($campos as $campo) {
            if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === '') {
                return "El campo '$campo' es obligatorio.";
            }
        }
        return null;
    }
}
