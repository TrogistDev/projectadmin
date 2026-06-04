<?php

declare(strict_types=1);

namespace Api\Models;

class Project
{
    public int $id;
    public string $nombre;
    public string $descripcion;
    public string $fecha_inicio;
    public string $fecha_entrega;
    public string $estado;
    public int $responsable_id;
    public int $porcentaje_avance;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->nombre = $data['nombre'];
        $this->descripcion = $data['descripcion'];
        $this->fecha_inicio = $data['fecha_inicio'];
        $this->fecha_entrega = $data['fecha_entrega'];
        $this->estado = $data['estado'];
        $this->responsable_id = (int)$data['responsable_id'];
        $this->porcentaje_avance = (int)$data['porcentaje_avance'];
    }
}
