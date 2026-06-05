<?php

declare(strict_types=1);

namespace Api\Utils;

use InvalidArgumentException;
use Api\Database\Database;

class Validator
{
    public static function assertProjectData(array $data, bool $partial = false): void
    {
        if (!$partial) {
            $required = ['nombre', 'descripcion', 'fecha_inicio', 'fecha_entrega', 'responsable_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new InvalidArgumentException('Datos de proyecto incompletos.');
                }
            }
        }

        if (!empty($data['fecha_inicio']) && !empty($data['fecha_entrega']) && $data['fecha_inicio'] > $data['fecha_entrega']) {
            throw new InvalidArgumentException('La fecha de entrega debe ser posterior a la fecha de inicio.');
        }
    }

    public static function assertProjectManager(int $responsableId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT rol FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $responsableId]);
        $user = $stmt->fetch();

        if (!$user || ($user['rol'] !== 'jefe_proyecto' && $user['rol'] !== 'administrador')) {
            throw new InvalidArgumentException('El responsable debe ser un jefe de proyecto o administrador.');
        }
    }

    public static function assertPhaseData(array $data): void
    {
        if (empty($data['nombre']) || trim((string)$data['nombre']) === '') {
            throw new InvalidArgumentException('El nombre de la fase es obligatorio.');
        }
    }
}
