<?php
require 'vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// 🔑 CONFIGURAÇÃO DO BANCO LOCAL DO TERMUX
$db_host = '127.0.0.1';
$db_name = 'financas_pro';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar no banco local: " . $e->getMessage());
}

// 🔑 SUAS CHAVES VAPID MANIFESTADAS
$auth = [
    'VAPID' => [
        'subject' => 'mailto:criadordemundo1@gmail.com',
        'publicKey' => 'BOTANNrAtcULc2IP3PwFYC_i53qpxMzwuSYEuk6FmYANhAYZeYnSg8cFxtwuSlLMLm2dvV_MHI9iVMNyRJQjka8',
        'privateKey' => 'yOKJI-uMSdtEJHV2EP5MSVsF2GjHcSqbj7GX3QrN8T8',
    ],
];

$mensagemSucesso = "";

// 📋 Busca quantos dispositivos estão cadastrados no MySQL neste momento
$totalDispositivos = $pdo->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mensagem'])) {
    $titulo = $_POST['titulo'] ?? 'Finanças PRO ⚠️';
    $textoMensagem = $_POST['mensagem'];

    if ($totalDispositivos > 0) {
        $webPush = new WebPush($auth);
        
        $payload = json_encode([
            'title' => $titulo,
            'body' => $textoMensagem
        ]);

        // 🔍 Puxa todos os tokens direto do MySQL
        $stmt = $pdo->query("SELECT endpoint, public_key, auth_token FROM push_subscriptions");
        $inscritos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Adiciona cada celular na fila de envio da biblioteca
        foreach ($inscritos as $inscrito) {
            $subscription = Subscription::create([
                'endpoint' => $inscrito['endpoint'],
                'publicKey' => $inscrito['public_key'],
                'authToken' => $inscrito['auth_token'],
            ]);
            $webPush->queueNotification($subscription, $payload);
        }

        // Executa o disparo em massa para os servidores do Android/iOS
        $contagem = 0;
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $contagem++;
            }
        }
        $mensagemSucesso = "Notificação disparada com sucesso via MySQL para {$contagem} celulares!";
        
        // Atualiza a contagem após o envio
        $totalDispositivos = $pdo->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn();
    } else {
        $mensagemSucesso = "Nenhum celular encontrado no banco de dados para enviar.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Finanças PRO</title>
    <style>
        body { background: #070a13; color: #fff; font-family: sans-serif; padding: 40px; display: flex; justify-content: center; }
        .panel { background: #111524; padding: 30px; border-radius: 16px; border: 1px solid #1e2640; width: 100%; max-width: 500px; }
        h2 { color: #6366f1; margin-top: 0; }
        .badge { background: #10b981; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; gap: 6px; }
        input, textarea { width: 100%; padding: 12px; background: #070a13; border: 1px solid #1e2640; border-radius: 8px; color: #fff; box-sizing: border-box; }
        textarea { height: 100px; resize: none; }
        button { background: #6366f1; color: #fff; border: none; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; }
        .alert { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #10b981; padding: 12px; border-radius: 8px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="panel">
    <h2>Controle de Notificações (MySQL Ativo)</h2>
    <p>Celulares no banco de dados: <span class="badge"><?= $totalDispositivos ?></span></p>

    <?php if(!empty($mensagemSucesso)): ?>
        <div class="alert"><?= $mensagemSucesso ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Título do Alerta:</label>
            <input type="text" name="titulo" value="⚠️ Alerta do Sistema">
        </div>
        <div class="form-group">
            <label>Mensagem:</label>
            <textarea name="mensagem" required>Estamos em manutenção nativa. Nada será perdido, mas o app está fora do ar. Espere até chegar a próxima notificação avisando.</textarea>
        </div>
        <button type="submit">🚀 Disparar para Todos os Celulares</button>
    </form>
</div>
</body>
</html>
