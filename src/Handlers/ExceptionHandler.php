<?php

namespace App\Handlers;

use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConfigurationException;
use App\Helpers\Response;
use Throwable;
use PDOException;

class ExceptionHandler
{
    /**
     * Maneja las excepción
     * @param Throwable $e la excepción a enviar
     * @return void
     */
    public static function handle(Throwable $e): void
    {
        error_log(
            "Exception: " . get_class($e) . "\n" .
            "Message: " . $e->getMessage() . "\n"
        );

        if ($e instanceof ValidationException) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            Response::error($e->getMessage(), $statusCode);
        } elseif ($e instanceof NotFoundException) {
            Response::error($e->getMessage(), 404);
        } elseif ($e instanceof ConfigurationException) {
            Response::error("Error de configuración del servidor", 500);
        } elseif ($e instanceof PDOException) {
            Response::error("Error de base de datos", 500);
        } else {
            Response::error("Error interno del servidor", 500);
        }
    }
}
