<?php

namespace App\Services;

use App\Repositories\ProductRepository;

class ProductService
{
    private ProductRepository $repository;
    private PriceConverter $converter;

    public function __construct(ProductRepository $repository, PriceConverter $converter)
    {
        $this->repository = $repository;
        $this->converter = $converter;
    }

    /**
     * obtiene todos los productos con conversión de precio
     * @return array array de productos con precio_ars y precio_usd
     */
    public function getAllProducts(): array
    {
        $products = $this->repository->findAll();
        
        return array_map(function ($product) {
            return $this->PriceProduct($product);
        }, $products);
    }

    /**
     * obtiene un producto por el ID con conversión de precio
     * @param int $id
     * @return array|null producto con precio_ars y precio_usd
     */
    public function getProductById(int $id): ?array
    {
        $product = $this->repository->findById($id);
        
        if ($product === null) {
            return null;
        }
        
        return $this->PriceProduct($product);
    }

    /**
     * crea un nuevo producto 
     * @param array $data datos del producto (nombre, descripcion, precio)
     * @return array producto creado con precio_ars y precio_usd
     */
    public function createProduct(array $data): array
    {
        $id = $this->repository->create($data);
        $product = $this->repository->findById($id);
        
        return $this->PriceProduct($product);
    }

    /**
     * actualiza un producto existente
     * @param int $id
     * @param array $data campos a actualizar
     * @return array|null producto actualizado con precio_ars y precio_usd
     */
    public function updateProduct(int $id, array $data): ?array
    {
        $updated = $this->repository->update($id, $data);
        
        if (!$updated) {
            return null;
        }
        
        $product = $this->repository->findById($id);
        
        return $this->PriceProduct($product);
    }

    /**
     * elimina un producto
     * @param int $id
     * @return bool true si fue eliminado, false si no se encontró
     */
    public function deleteProduct(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * toma el precio y lo separa en pesos y convierte a dolares
     * @param array $product datos del producto desde el repositorio
     * @return array datos del producto
     */
    private function PriceProduct(array $product): array
    {
        $precioARS = (float) $product['precio'];
        $precioUSD = $this->converter->convertToUSD($precioARS);
        
        unset($product['precio']);
        $product['precio_ars'] = $precioARS;
        $product['precio_usd'] = $precioUSD;
        
        return $product;
    }
}
