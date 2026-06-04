<?php

declare(strict_types=1);

namespace Api\Models;

class User
{
    public int $id;
    public string $nombre;
    public string $apellidos;
    public string $correo;
    public string $rol;
    public ?string $departamento;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->nombre = $data['nombre'];
        $this->apellidos = $data['apellidos'];
        $this->correo = $data['correo'];
        $this->rol = $data['rol'];
        $this->departamento = $data['departamento'] ?? null;
    }
}
