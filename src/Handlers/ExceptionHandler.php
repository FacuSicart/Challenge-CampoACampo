<?php

namespace App\Handlers;

use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConfigurationException;
use Throwable;
use PDOException;

class ExceptionHandler
{
    /**
     * Maneja la excepción y responde con JSON
     */
    public static function handle(Throwable $e): void
    {
        error_log(
            "Exception: " . get_class($e) . "\n" .
            "Message: " . $e->getMessage() . "\n"
        );

        if ($e instanceof ValidationException) {
            $codigo = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            self::respuestaError($e->getMessage(), $codigo);
        } elseif ($e instanceof NotFoundException) {
            self::respuestaError($e->getMessage(), 404);
        } elseif ($e instanceof ConfigurationException) {
            self::respuestaError("Error de configuración del servidor", 500);
        } elseif ($e instanceof PDOException) {
            self::respuestaError("Error de base de datos", 500);
        } else {
            self::respuestaError("Error interno del servidor", 500);
        }
    }

    private static function respuestaError(string $mensaje, int $codigo): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json');
        echo json_encode(['error' => $mensaje], JSON_UNESCAPED_UNICODE);
    }
}
