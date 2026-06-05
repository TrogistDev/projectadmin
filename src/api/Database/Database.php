<?php

declare(strict_types=1);

namespace Api\Database;

use Api\Utils\Response;
use PDO;
use PDOException;
use Throwable;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config.php';
            
            // Injeção direta do charset na DSN mata o problema de codificação/acentos de forma nativa
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $config['db_host'],
                $config['db_name'],
                $config['db_charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 5, // Evita estouro de pool de conexões
                PDO::ATTR_EMULATE_PREPARES   => false, // Mantém os tipos de dados reais (int, float) intactos
            ];

            try {
                self::$instance = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
            } catch (PDOException $exception) {
                error_log('Database connection error: ' . $exception->getMessage());
                
                // Utiliza a arquitetura centralizada de respostas do sistema
                Response::error('Database connection failed.', 500);
            }
        }

        return self::$instance;
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::getConnection();
        $pdo->beginTransaction();

        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}