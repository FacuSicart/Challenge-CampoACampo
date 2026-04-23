<?php

namespace App\Controllers;

use App\Services\ProductService;
use App\Helpers\Response;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class ProductController
{
    private ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /productos - lista los productos
     * @return void
     */
    public function index(): void
    {
        $products = $this->service->getAllProducts();
        Response::json($products, 200);
    }

    /**
     * GET /productos/{id} - Trae el producto por id, sirve para el modificar
     * @param int $id 
     * @return void
     * @throws ValidationException si el id no es positivo
     * @throws NotFoundException si no encuentra el producto
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
        
        Response::json($product, 200);
    }

    /**
     * POST /productos para crear el producto
     * 
     * @return void
     * @throws ValidationException si falla la validacion
     */
    public function crear(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            throw new ValidationException("Content-Type debe ser application/json", 415);
        }
        
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException("JSON inválido en request body");
        }
        
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
        Response::json($product, 201);
    }

    /**
     * PUT /productos/{id} actualiza un producto existente
     * 
     * @param int $id
     * @return void
     * @throws ValidationException 
     * @throws NotFoundException 
     */
    public function actualizar(int $id): void
    {
        if ($id <= 0) {
            throw new ValidationException("El id tiene que ser positivo");
        }
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            throw new ValidationException("Content-Type debe ser application/json", 415);
        }

        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException("JSON inválido en request body");
        }

        if (isset($data['precio']) && (!is_numeric($data['precio']) || $data['precio'] <= 0)) {
            throw new ValidationException("El precio debe ser positivo");
        }
        
        $product = $this->service->updateProduct($id, $data);
        
        if ($product === null) {
            throw new NotFoundException("Producto no encontrado");
        }
        
        Response::json($product, 200);
    }

    /**
     * DELETE /productos/{id} borra el producto
     * 
     * @param int $id Product ID
     * @return void
     * @throws ValidationException
     * @throws NotFoundException
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
        
        Response::json(['message' => 'Producto eliminado exitosamente'], 200);
    }
}
