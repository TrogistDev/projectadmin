<?php

declare(strict_types=1);

namespace Api\Services;

use Api\Database\Database;
use InvalidArgumentException;
use PDO;

class UserService
{
    public function list(array $filter = []): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, nombre, apellidos, correo, rol, departamento FROM usuarios');
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function create(array $data): array
    {
        // 1. Validação estrita dos campos obrigatórios baseados na tabela do banco
        if (empty($data['nombre']) || empty($data['correo']) || empty($data['rol']) || empty($data['contrasena'])) {
            throw new InvalidArgumentException('Todos os campos obrigatórios devem ser preenchidos.');
        }

        // Validação: senha mínima 8 caracteres (requisito da prova)
        if (strlen($data['contrasena']) < 8) {
            throw new InvalidArgumentException('A senha deve ter no mínimo 8 caracteres.');
        }

        // 2. Conexão com o banco de dados (ajuste para a sua classe global de persistência)
        $db = Database::getConnection();

        // 3. Verifica se o e-mail já está cadastrado para evitar duplicidade (Unique Key)
        $stmtCheck = $db->prepare("SELECT id FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmtCheck->execute([':correo' => $data['correo']]);
        if ($stmtCheck->fetch()) {
            throw new InvalidArgumentException('Este e-mail já está cadastrado no sistema.');
        }

        // 4. Criptografia segura da senha (nunca salvar em texto limpo)
        $passwordHash = password_hash($data['contrasena'], PASSWORD_BCRYPT);

        // 5. Query de inserção casando com a estrutura da imagem image_249138.png
        $sql = "INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) 
                VALUES (:nombre, :apellidos, :correo, :contrasena, :rol, :departamento)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':nombre'     => $data['nombre'],
            ':apellidos'  => $data['apellidos'] ?? '',
            ':correo'     => $data['correo'],
            ':contrasena' => $passwordHash,
            ':rol'        => $data['rol'], // enum('administrador', 'jefe_proyecto', 'colaborador')
            ':departamento' => $data['departamento'] ?? null,
        ]);

        // 6. Retorna o usuário recém-criado (sem a senha por segurança) para o Controller responder com 201
        $newId = (int)$db->lastInsertId();
        
        return [
            'id'        => $newId,
            'nombre'    => $data['nombre'],
            'apellidos' => $data['apellidos'] ?? '',
            'correo'    => $data['correo'],
            'rol'       => $data['rol'],
            'departamento' => $data['departamento'] ?? null
        ];
    }
}
