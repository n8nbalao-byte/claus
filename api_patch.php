<?php
/**
 * ============================================================
 * PATCH para api.php — caso 'send_chat_message'
 * ============================================================
 *
 * PROBLEMA DAS MENSAGENS DUPLICADAS:
 * api.php salva a mensagem do admin no banco com status='processing'
 * e depois o webhook.php TAMBÉM salva a mesma mensagem quando
 * processa o payload enviado internamente via cURL.
 *
 * SOLUÇÃO: Remover o INSERT antecipado do api.php e deixar apenas
 * o webhook.php salvar no momento correto.
 *
 * Substitua o bloco do case 'send_chat_message' em api.php por:
 */

/*
case 'send_chat_message':
    $data = json_decode(file_get_contents('php://input'), true);
    $message = $data['message'] ?? '';

    if (empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Mensagem vazia.']);
        exit;
    }

    // Pegar o primeiro admin cadastrado para usar o número e nome dele
    $stmt = $conn->query("SELECT phone_number, name FROM admin_users LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo json_encode(['status' => 'error', 'message' => 'Usuário admin não encontrado no banco.']);
        exit;
    }
    $admin_number = $admin['phone_number'];
    $admin_name   = $admin['name'];

    // *** NÃO salvar aqui — o webhook.php salva quando processa ***
    // (O INSERT antigo causava duplicação porque o webhook
    //  também salvava ao receber o payload via cURL interno)

    // Montar payload que simula a Evolution API
    $payload = [
        'event'    => 'messages.upsert',
        'instance' => 'claus',
        'data'     => [
            'key'      => [
                'remoteJid' => $admin_number . '@s.whatsapp.net',
                'fromMe'    => false,
                'id'        => 'PAINEL_' . strtoupper(bin2hex(random_bytes(10)))
            ],
            'pushName' => $admin_name,
            'message'  => [
                'conversation' => $message
            ]
        ]
    ];

    // Chamar o webhook.php localmente (fire-and-forget)
    $dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $dir . '/webhook.php';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);

    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err && strpos($err, 'timeout') === false) {
        file_put_contents('api_error.txt', date('Y-m-d H:i:s') . " - cURL error: " . $err . "\n", FILE_APPEND);
    }

    echo json_encode(['status' => 'success', 'message' => 'Mensagem enviada para processamento.']);
    break;
*/

/**
 * ============================================================
 * PATCH para webhook.php — envio em múltiplas mensagens
 * ============================================================
 *
 * No webhook.php, após gerar $aiResponse, substitua o bloco
 * de envio único por este (que divide em múltiplas mensagens):
 *
 * require_once __DIR__ . '/message_splitter.php';
 *
 * $parts = splitMessages($aiResponse);
 *
 * // Garantir assinatura apenas na primeira mensagem
 * if (!empty($parts) && !preg_match('/^\*(Claus|claus):\*/', $parts[0])) {
 *     $parts[0] = "*Claus:* " . $parts[0];
 * }
 *
 * $sentOk = false;
 * $delay  = 400; // ms entre mensagens
 *
 * foreach ($parts as $i => $part) {
 *     $part = trim($part);
 *     if ($part === '') continue;
 *     $ok = sendWhatsApp($evolution_url, $evolution_apikey, $number, $part);
 *     if ($ok) $sentOk = true;
 *     if ($i < count($parts) - 1) usleep($delay * 1000);
 * }
 *
 * // Logar APENAS a resposta completa (não cada pedaço separado)
 * $status = $sentOk ? 'sent' : 'failed';
 * $stmt = $conn->prepare("INSERT INTO agent_logs (sender_number, sender_role, message, agent_action, status, timestamp) VALUES (?, 'agent', ?, 'replied', ?, ?)");
 * $stmt->execute([$number, $aiResponse, $status, getLocalTime()]);
 */

echo "<!-- Este arquivo é apenas um guia de patches, não deve ser acessado diretamente -->";
