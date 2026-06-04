<?php

declare(strict_types=1);

namespace Api\Models;

class Phase
{
    public int $id;
    public string $nombre;
    public string $descripcion;
    public int $orden;
    public bool $completada;
    public ?string $fecha_completado;
    public int $proyecto_id;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->nombre = $data['nombre'];
        $this->descripcion = $data['descripcion'];
        $this->orden = (int)$data['orden'];
        $this->completada = (bool)$data['completada'];
        $this->fecha_completado = $data['fecha_completado'] ?? null;
        $this->proyecto_id = (int)$data['proyecto_id'];
    }
}
