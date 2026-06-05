<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>Teste de Login</h2>";
echo "<pre>";

$credentials = [
    ['email' => 'admin@origami.test', 'password' => 'Admin@123'],
    ['email' => 'jefe@origami.test', 'password' => 'Jefe@123'],
    ['email' => 'colaborador1@origami.test', 'password' => 'Colab@123'],
];

echo "Testando credenciais:\n\n";

foreach ($credentials as $cred) {
    echo "Testando: {$cred['email']} / {$cred['password']}\n";

    $ch = curl_init('http://localhost:8000/api/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cred));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_COOKIE, '');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "  Status HTTP: $httpCode\n";
    echo "  Resposta: ";

    if ($response) {
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            if (isset($decoded['error'])) {
                echo "❌ Erro: {$decoded['error']}\n";
            } elseif (isset($decoded['user'])) {
                echo "✓ Sucesso!\n";
                echo "  Usuário: {$decoded['user']['nombre']} {$decoded['user']['apellidos']}\n";
                echo "  Rol: {$decoded['user']['rol']}\n";
            } else {
                echo json_encode($decoded) . "\n";
            }
        } else {
            echo $response . "\n";
        }
    } else {
        echo "Nenhuma resposta\n";
    }
    echo "\n";
}

echo "---\n\n";
echo "📝 Agora você pode fazer login em: http://localhost:8000\n";
echo "   Use uma das credenciais acima\n";

echo "</pre>";
