<?php

namespace App\Models;

class Product
{
    public int $id;
    public string $nombre;
    public ?string $descripcion;
    public float $precio_ars;
    public float $precio_usd;

    public function __construct(
        int $id,
        string $nombre,
        ?string $descripcion,
        float $precio_ars,
        float $precio_usd
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->precio_ars = $precio_ars;
        $this->precio_usd = $precio_usd;
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio_ars'  => $this->precio_ars,
            'precio_usd'  => $this->precio_usd,
        ];
    }
}
