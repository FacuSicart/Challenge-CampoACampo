<?php

namespace App\Controllers;

use App\Services\ProductService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class ProductController
{
    private ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    private function respuesta(array $data, int $codigo): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * GET /productos - lista los productos
     */
    public function index(): void
    {
        $products = $this->service->getAllProducts();
        $result = [];
        foreach ($products as $p) {
            $result[] = $p->toArray();
        }
        $this->respuesta($result, 200);
    }

    /**
     * GET /productos/{id} - trae el producto por id
     */
    public function mostrar(int $id): void
    {
        if ($id <= 0) {
            throw new ValidationException("El id tiene que ser positivo");
        }
        
        $product = $this->service->getProductById($id);
        
        if ($product === null) {
            throw new NotFoundException("Producto no encontrado");
        }
        
        $this->respuesta($product->toArray(), 200);
    }

    /**
     * POST /productos - crea un producto
     */
    public function crear(): void
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if (!isset($data['nombre']) || trim($data['nombre']) === '') {
            throw new ValidationException("El nombre es obligatorio");
        }
        
        if (!isset($data['precio'])) {
            throw new ValidationException("El precio es obligatorio");
        }
        
        if (!is_numeric($data['precio']) || $data['precio'] <= 0) {
            throw new ValidationException("El precio debe ser positivo");
        }
        
        $product = $this->service->createProduct($data);
        $this->respuesta($product->toArray(), 201);
    }

    /**
     * PUT /productos/{id} - actualiza un producto existente
     */
    public function actualizar(int $id): void
    {
        if ($id <= 0) {
            throw new ValidationException("El id tiene que ser positivo");
        }

        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if (isset($data['precio']) && (!is_numeric($data['precio']) || $data['precio'] <= 0)) {
            throw new ValidationException("El precio debe ser positivo");
        }
        
        $product = $this->service->updateProduct($id, $data);
        
        if ($product === null) {
            throw new NotFoundException("Producto no encontrado");
        }
        
        $this->respuesta($product->toArray(), 200);
    }

    /**
     * DELETE /productos/{id} - borra el producto
     */
    public function borrar(int $id): void
    {
        if ($id <= 0) {
            throw new ValidationException("El id tiene que ser positivo");
        }
        
        $deleted = $this->service->deleteProduct($id);
        
        if (!$deleted) {
            throw new NotFoundException("Producto no encontrado");
        }
        
        $this->respuesta(['message' => 'Producto eliminado exitosamente'], 200);
    }
}
