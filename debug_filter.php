<?php
require __DIR__ . '/src/api/config.php';
require __DIR__ . '/src/api/autoload.php';

use Api\Database\Database;
use Api\Services\ProjectService;

$pdo = Database::getConnection();

$filter = [
    'page' => 1,
    'limit' => 5,
    'date_start' => '2026-12-30'
];

$user = [
    'rol' => 'administrador',
    'id' => 1
];

$service = new ProjectService();
$ref = new ReflectionMethod($service, 'buildWhereClause');
$ref->setAccessible(true);

[$where, $params] = $ref->invoke($service, $filter, $user);

$sql = 'SELECT p.*, u.nombre AS responsable_nombre FROM proyectos p JOIN usuarios u ON u.id = p.responsable_id';
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

echo "SQL: $sql\n";
echo "PARAMS:\n";
print_r($params);

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', 5, PDO::PARAM_INT);
    $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "ROWS: " . count($rows) . "\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
