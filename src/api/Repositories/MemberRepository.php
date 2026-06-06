<?php

declare(strict_types=1);

namespace Api\Repositories;

use Api\Database\Database;
use PDO;

class MemberRepository
{
  
    public function findByProject(int $projectId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT pm.usuario_id, u.nombre, u.apellidos, u.correo, pm.rol_especifico, pm.fecha_add
             FROM proyecto_miembro pm
             JOIN usuarios u ON u.id = pm.usuario_id
             WHERE pm.proyecto_id = :id'
        );
        $stmt->execute(['id' => $projectId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   
    public function insert(PDO $pdo, int $projectId, int $userId, string $role): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO proyecto_miembro (proyecto_id, usuario_id, fecha_add, rol_especifico)
             VALUES (:proyecto_id, :usuario_id, :fecha_add, :rol_especifico)'
        );
        $stmt->execute([
            'proyecto_id'    => $projectId,
            'usuario_id'     => $userId,
            'fecha_add'      => date('Y-m-d H:i:s'),
            'rol_especifico' => $role,
        ]);
    }

  
    public function delete(int $projectId, int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'DELETE FROM proyecto_miembro WHERE proyecto_id = :proyecto_id AND usuario_id = :usuario_id'
        );
        $stmt->execute([
            'proyecto_id' => $projectId,
            'usuario_id'  => $userId,
        ]);
    }
}