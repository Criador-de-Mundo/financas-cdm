<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// 🔑 CONFIGURAÇÃO DO BANCO LOCAL DO TERMUX
$db_host = '127.0.0.1';             
$db_name = 'financas_pro';     
$db_user = 'root';     
$db_pass = '';      

try {
    // O PDO é o sistema nativo do PHP para conversar com o MySQL com segurança
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Falha ao conectar no banco de dados local."]);
    exit;
}

// Captura o que o celular enviou
$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['endpoint'])) {
    
    // 🔍 1. Evita duplicados: Verifica se esse celular já está cadastrado
    $stmt = $pdo->prepare("SELECT id FROM push_subscriptions WHERE endpoint = ?");
    $stmt->execute([$data['endpoint']]);
    
    if (!$stmt->fetch()) {
        // 📥 2. Se for novo, insere na tabela do banco de dados
        $stmt = $pdo->prepare("INSERT INTO push_subscriptions (endpoint, public_key, auth_token) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['endpoint'],
            $data['keys']['p256dh'] ?? '',
            $data['keys']['auth'] ?? ''
        ]);
        echo json_encode(["status" => "sucesso", "mensagem" => "Celular salvo no MySQL com sucesso!"]);
        exit;
    }
    echo json_encode(["status" => "sucesso", "mensagem" => "Este celular já estava cadastrado."]);
    exit;
}

echo json_encode(["status" => "erro", "mensagem" => "Dados inválidos."]);
