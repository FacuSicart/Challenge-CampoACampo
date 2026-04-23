<?php

namespace App\Services;

use App\Models\Product;
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
     * @return Product[]
     */
    public function getAllProducts(): array
    {
        $products = $this->repository->findAll();
        
        return array_map(function ($product) {
            return $this->buildProduct($product);
        }, $products);
    }

    /**
     * obtiene un producto por ID con conversión de precio
     * @param int $id
     * @return Product|null
     */
    public function getProductById(int $id): ?Product
    {
        $product = $this->repository->findById($id);
        
        if ($product === null) {
            return null;
        }
        
        return $this->buildProduct($product);
    }

    /**
     * crea un nuevo producto
     * @param array $data datos del producto (nombre, descripcion, precio)
     * @return Product
     */
    public function createProduct(array $data): Product
    {
        $id = $this->repository->create($data);
        $product = $this->repository->findById($id);
        
        return $this->buildProduct($product);
    }

    /**
     * actualiza un producto existente
     * @param int $id
     * @param array $data campos a actualizar
     * @return Product|null
     */
    public function updateProduct(int $id, array $data): ?Product
    {
        $updated = $this->repository->update($id, $data);
        
        if (!$updated) {
            return null;
        }
        
        $product = $this->repository->findById($id);
        
        return $this->buildProduct($product);
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
     * construye un objeto Product desde un array de la base de datos
     * @param array $product fila de la base de datos
     * @return Product
     */
    private function buildProduct(array $product): Product
    {
        $precioARS = (float) $product['precio'];
        $precioUSD = $this->converter->convertToUSD($precioARS);
        
        return new Product(
            (int) $product['id'],
            $product['nombre'],
            $product['descripcion'] ?? null,
            $precioARS,
            $precioUSD
        );
    }
}
