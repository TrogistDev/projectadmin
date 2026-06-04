<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;

class MemberService
{
    public function listByProject(int $projectId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT pm.usuario_id, u.nombre, u.apellidos, u.correo, pm.rol_especifico, pm.fecha_add
             FROM proyecto_miembro pm
             JOIN usuarios u ON u.id = pm.usuario_id
             WHERE pm.proyecto_id = :id'
        );
        $stmt->execute(['id' => $projectId]);

        return $stmt->fetchAll();
    }

    public function add(int $projectId, array $data): void
    {
        if (empty($data['usuario_id']) || empty($data['rol_especifico'])) {
            throw new \InvalidArgumentException('usuario_id y rol_especifico son obligatorios.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO proyecto_miembro (proyecto_id, usuario_id, fecha_add, rol_especifico)
             VALUES (:proyecto_id, :usuario_id, :fecha_add, :rol_especifico)'
        );
        $stmt->execute([
            'proyecto_id' => $projectId,
            'usuario_id' => $data['usuario_id'],
            'fecha_add' => date('Y-m-d H:i:s'),
            'rol_especifico' => $data['rol_especifico'],
        ]);
    }

    public function remove(int $projectId, int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'DELETE FROM proyecto_miembro WHERE proyecto_id = :proyecto_id AND usuario_id = :usuario_id'
        );
        $stmt->execute([
            'proyecto_id' => $projectId,
            'usuario_id' => $userId,
        ]);
    }
}
