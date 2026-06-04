<?php

declare(strict_types=1);

namespace Api\Models;

class ProjectMember
{
    public int $proyecto_id;
    public int $usuario_id;
    public string $rol_especifico;
    public string $fecha_add;

    public function __construct(array $data)
    {
        $this->proyecto_id = (int)$data['proyecto_id'];
        $this->usuario_id = (int)$data['usuario_id'];
        $this->rol_especifico = $data['rol_especifico'];
        $this->fecha_add = $data['fecha_add'];
    }
}
