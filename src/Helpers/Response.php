<?php

namespace App\Helpers;

class Response
{
    /**
     * envia una respuesta JSON con datos
     * @param mixed $data datos a codificar como JSON
     * @param int $statusCode código de estado HTTP
     * @return void
     */
    public static function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * envia una respuesta JSON de error
     * @param string $message mensaje de error
     * @param int $statusCode código de estado HTTP
     * @return void
     */
    public static function error(string $message, int $statusCode = 400): void
    {
        self::json(['error' => $message], $statusCode);
    }
}
