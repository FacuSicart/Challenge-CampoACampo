<?php

namespace App\Repositories;

use App\Connection\Connection;

class ProductRepository
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * buscar todos los productos
     * @return array array de todos los productos
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM productos ORDER BY id";
        $response = $this->db->getConnection()->prepare($sql);
        $response->execute();
        
        return $response->fetchAll();
    }

    /**
     * buscar producto por ID
     * @param int $id 
     * @return array|null datos del producto o null si no se encuentra
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM productos WHERE id = ?";
        $response = $this->db->getConnection()->prepare($sql);
        $response->execute([$id]);
        
        $result = $response->fetch();
        
        return $result !== false ? $result : null;
    }

    /**
     * crea un producto
     * @param array $data datos del producto (nombre, descripcion, precio)
     * @return int el ID del producto recién creado
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO productos (nombre, descripcion, precio) VALUES (?, ?, ?)";
        $response = $this->db->getConnection()->prepare($sql);
        $response->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['precio']
        ]);
        
        return (int) $this->db->getConnection()->lastInsertId();
    }

    /**
     * actualiza un producto que ya existe
     * @param int $id
     * @param array $data son los campos a actualizar
     * @return bool true si el producto fue actualizado, false si no se encontró
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        if (isset($data['nombre'])) {
            $fields[] = "nombre = ?";
            $values[] = $data['nombre'];
        }
        
        if (isset($data['descripcion'])) {
            $fields[] = "descripcion = ?";
            $values[] = $data['descripcion'];
        }
        
        if (isset($data['precio'])) {
            $fields[] = "precio = ?";
            $values[] = $data['precio'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $id;
        $sql = "UPDATE productos SET " . implode(", ", $fields) . " WHERE id = ?";
        $response = $this->db->getConnection()->prepare($sql);
        $response->execute($values);
        
        return $response->rowCount() > 0;
    }

    /**
     * elimina un producto
     * @param int $id
     * @return bool true si el producto fue eliminado, false si no se encontró
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM productos WHERE id = ?";
        $response = $this->db->getConnection()->prepare($sql);
        $response->execute([$id]);
        
        return $response->rowCount() > 0;
    }
}
