<?php

namespace App\Services;

use RuntimeException;

class PriceConverter
{
    private float $exchangeRate;

    public function __construct()
    {
        $this->exchangeRate = $this->loadExchangeRate();
    }

    /**
     * convierte el precio de pesos a dolares
     * @param float $priceARS precio en Pesos Argentinos
     * @return float precio en Dólares
     */
    public function convertToUSD(float $priceARS): float
    {
        $priceUSD = $priceARS / $this->exchangeRate;
        return round($priceUSD, 2);
    }

    /**
     * se carga la tasa de cambio desde la variable de entorno
     * @return float la tasa de cambio
     * @throws RuntimeException si PRECIO_USD no está definido o es inválido
     */
    private function loadExchangeRate(): float
    {
        $precioUSD = $_ENV['PRECIO_USD'] ?? getenv('PRECIO_USD');
        
        if ($precioUSD === false || $precioUSD === '') {
            throw new RuntimeException("El precio en dolares no esta definido");
        }
        
        if (!is_numeric($precioUSD)) {
            throw new RuntimeException("El precio debe estar en entero");
        }
        
        $precioUSD = (float) $precioUSD;
        
        if ($precioUSD <= 0) {
            throw new RuntimeException("El precio debe ser mayor a 0");
        }
        
        return $precioUSD;
    }
}
