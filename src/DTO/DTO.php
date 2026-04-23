<?php

namespace App\DTO;

use PDO;
use PDOException;

class DTO
{
    private static ?DTO $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $this->connection = $this->connect();
    }

    /**
     * obtiene la instancia de la base de datos
     * @return DTO la instancia
     */
    public static function getInstance(): DTO
    {
        if (self::$instance === null) {
            self::$instance = new DTO();
        }
        
        return self::$instance;
    }

    /**
     * obtiene la instancia de conexión PDO
     * @return PDO conexión PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Lee la configuración desde las variables de entorno para establecer la conexión
     * @return PDO instancia PDO configurada
     * @throws PDOException si la conexión falla
     */
    private function connect(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'mysql';
        $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        $dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'productos_db';
        $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $user, $password, $options);
    }

    /**
     * previene la clonación de la instancia
     */
    private function __clone()
    {
    }

    /**
     * previene la deserialización de la instancia
     */
    public function __wakeup()
    {
        throw new \Exception("no se puede deserializar");
    }
}
