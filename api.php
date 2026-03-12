<?php
// api.php
header('Content-Type: application/json');
require 'db.php';

$action = $_GET['action'] ?? '';

// Endpoint para testar envio de mensagens do webhook
if ($action === 'test_webhook_message') {
    $number = $_GET['number'] ?? '';
    $message = $_GET['message'] ?? '';
    
    if (empty($number) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Parâmetros número e message são obrigatórios']);
        exit;
    }
    
    // Simular payload da Evolution API
    $payload = [
        'event' => 'messages.upsert',
        'instance' => 'claus',
        'data' => [
            'key' => [
                'remoteJid' => $number . '@s.whatsapp.net',
                'fromMe' => isset($_GET['fromMe']) ? (bool)$_GET['fromMe'] : false,
                'id' => 'TEST_' . strtoupper(bin2hex(random_bytes(5)))
            ],
            'pushName' => $_GET['pushName'] ?? 'Teste',
            'message' => [
                'conversation' => $message
            ]
        ]
    ];
    
    // Chamar webhook
    $dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $dir . '/webhook.php';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo json_encode([
        'status' => 'sent',
        'message' => 'Webhook chamado para teste',
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error ?: null
    ]);
    exit;
}

// Endpoint para diagnosticar status das APIs
if ($action === 'api_status') {
    $stmt = $conn->query("SELECT config_key, config_value FROM agent_config");
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $status = [
        'openai' => ['configured' => !empty($configs['openai_apikey'] ?? ''), 'key' => substr($configs['openai_apikey'] ?? '', 0, 10)],
        'groq' => ['configured' => !empty($configs['groq_apikey'] ?? ''), 'key' => substr($configs['groq_apikey'] ?? '', 0, 10)],
        'gemini' => ['configured' => !empty($configs['gemini_apikey'] ?? ''), 'key' => substr($configs['gemini_apikey'] ?? '', 0, 10)],
        'claude' => ['configured' => !empty($configs['claude_apikey'] ?? ''), 'key' => substr($configs['claude_apikey'] ?? '', 0, 10)],
        'huggingface' => ['configured' => !empty($configs['huggingface_apikey'] ?? ''), 'key' => substr($configs['huggingface_apikey'] ?? '', 0, 10)],
        'together' => ['configured' => !empty($configs['together_apikey'] ?? ''), 'key' => substr($configs['together_apikey'] ?? '', 0, 10)],
        'evolution_api' => ['configured' => !empty($configs['evolution_url'] ?? ''), 'url' => $configs['evolution_url'] ?? 'Não configurada'],
        'current_provider' => $configs['ai_provider'] ?? 'openai'
    ];
    
    echo json_encode($status);
    exit;
}

// ── CONFIG HELPER ────────────────────────────────────────────────
function getConfigVal(PDO $conn, string $key): ?string {
    try {
        $s = $conn->prepare("SELECT config_value FROM agent_config WHERE config_key=? LIMIT 1");
        $s->execute([$key]);
        $v = $s->fetchColumn();
        return $v !== false ? (string)$v : null;
    } catch (Exception $e) { return null; }
}

switch ($action) {
    case 'check_db_status':
        try {
            $conn->query("SELECT 1");
            echo json_encode(['status' => 'connected', 'message' => 'Conectado']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Desconectado: ' . $e->getMessage()]);
        }
        break;

    case 'get_config':
        $stmt = $conn->query("SELECT config_key, config_value FROM agent_config");
        echo json_encode($stmt->fetchAll(PDO::FETCH_KEY_PAIR));
        break;

    case 'save_prompt':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("UPDATE agent_config SET config_value = ? WHERE config_key = 'main_prompt'");
        $stmt->execute([$data['prompt']]);
        
        // Salvar configs de IA se existirem no payload
        if (isset($data['openai_apikey'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('openai_apikey', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['openai_apikey'], $data['openai_apikey']]);
        }
        if (isset($data['openai_model'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('openai_model', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['openai_model'], $data['openai_model']]);
        }
        
        // Salvar configs da Groq
        if (isset($data['ai_provider'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('ai_provider', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['ai_provider'], $data['ai_provider']]);
        }
        if (isset($data['groq_apikey'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('groq_apikey', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['groq_apikey'], $data['groq_apikey']]);
        }
        if (isset($data['groq_model'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('groq_model', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['groq_model'], $data['groq_model']]);
        }

        // Salvar configs do Gemini
        if (isset($data['gemini_apikey'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('gemini_apikey', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['gemini_apikey'], $data['gemini_apikey']]);
        }
        if (isset($data['gemini_model'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('gemini_model', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['gemini_model'], $data['gemini_model']]);
        }

        // Salvar configs do Claude
        if (isset($data['claude_apikey'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('claude_apikey', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['claude_apikey'], $data['claude_apikey']]);
        }
        if (isset($data['claude_model'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('claude_model', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['claude_model'], $data['claude_model']]);
        }

        // Salvar configs do HuggingFace
        if (isset($data['huggingface_apikey'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('huggingface_apikey', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['huggingface_apikey'], $data['huggingface_apikey']]);
        }
        if (isset($data['huggingface_model'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('huggingface_model', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['huggingface_model'], $data['huggingface_model']]);
        }

        // Salvar configs do Together AI
        if (isset($data['together_apikey'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('together_apikey', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['together_apikey'], $data['together_apikey']]);
        }
        if (isset($data['together_model'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('together_model', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['together_model'], $data['together_model']]);
        }

        // Salvar campos de Identidade
        if (isset($data['usuario_info'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('usuario_info', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['usuario_info'], $data['usuario_info']]);
        }
        if (isset($data['agente_info'])) {
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES ('agente_info', ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$data['agente_info'], $data['agente_info']]);
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Configurações salvas com sucesso!']);
        break;

    case 'get_admins':
        $stmt = $conn->query("SELECT id, phone_number, name FROM admin_users ORDER BY id");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'add_admin':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['name']) || empty($data['phone'])) {
            echo json_encode(['status' => 'error', 'message' => 'Nome e telefone são obrigatórios.']);
            exit;
        }
        try {
            $stmt = $conn->prepare("INSERT INTO admin_users (name, phone_number) VALUES (?, ?)");
            $stmt->execute([$data['name'], $data['phone']]);
            echo json_encode(['status' => 'success', 'message' => 'Admin adicionado!']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao adicionar: ' . $e->getMessage()]);
        }
        break;
    
    case 'delete_admin':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['status' => 'success', 'message' => 'Administrador removido.']);
        break;

    case 'get_contacts':
        $stmt = $conn->query("SELECT id, name, phone_number, relationship, notes FROM contacts ORDER BY name");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'add_contact':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['name']) || empty($data['phone'])) {
            echo json_encode(['status' => 'error', 'message' => 'Nome e telefone são obrigatórios.']);
            exit;
        }
        try {
            $stmt = $conn->prepare("INSERT INTO contacts (name, phone_number, relationship, notes) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE relationship = VALUES(relationship), notes = VALUES(notes)");
            $stmt->execute([$data['name'], $data['phone'], $data['relationship'] ?? '', $data['notes'] ?? '']);
            echo json_encode(['status' => 'success', 'message' => 'Contato adicionado/atualizado!']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao adicionar contato: ' . $e->getMessage()]);
        }
        break;

    case 'get_admin_chat':
        // Primeiro, pegar o número do primeiro admin cadastrado
        $stmt_admin = $conn->query("SELECT phone_number FROM admin_users ORDER BY id LIMIT 1");
        $admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
        $admin_number = $admin ? $admin['phone_number'] : 'admin_not_found';

        // Agora, buscar logs apenas dessa conversa
        $limit = $_GET['limit'] ?? 50;
        $stmt = $conn->prepare("SELECT timestamp, sender_number, sender_role, message FROM agent_logs WHERE sender_number = ? ORDER BY timestamp DESC LIMIT ?");
        $stmt->bindParam(1, $admin_number, PDO::PARAM_STR);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_logs':
        $limit = $_GET['limit'] ?? 100;
        $stmt = $conn->prepare("SELECT timestamp, sender_number, sender_role, message, agent_action, status FROM agent_logs ORDER BY timestamp DESC LIMIT ?");
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_conversations':
        // Esta query é mais complexa. Ela pega a última mensagem de cada conversa que não seja com o admin.
        $stmt_admin = $conn->query("SELECT phone_number FROM admin_users ORDER BY id LIMIT 1");
        $admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
        $admin_number = $admin ? $admin['phone_number'] : '';

        $sql = "
            SELECT l.sender_number, l.message, l.timestamp, l.sender_role
            FROM agent_logs l
            INNER JOIN (
                SELECT sender_number, MAX(id) as max_id
                FROM agent_logs
                GROUP BY sender_number
            ) lm ON l.sender_number = lm.sender_number AND l.id = lm.max_id
            WHERE l.sender_number != ?
            ORDER BY l.timestamp DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$admin_number]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_conversation_history':
        $number = $_GET['number'] ?? '';
        if (empty($number)) {
            echo json_encode([]);
            exit;
        }
        // Buscar todas as mensagens dessa conversa (incluindo admin_manual)
        $stmt = $conn->prepare("SELECT timestamp, sender_role, message FROM agent_logs WHERE sender_number = ? ORDER BY timestamp ASC");
        $stmt->execute([$number]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'cancel_pending':
        // marca todos os registros pendentes ou em processamento como falha para liberar a fila
        $stmt = $conn->prepare("UPDATE agent_logs SET status = 'failed' WHERE status IN ('pending','processing')");
        $stmt->execute();
        $count = $stmt->rowCount();
        echo json_encode(['status' => 'success', 'updated' => $count]);
        break;

    case 'clear_all_logs':
        // Limpar todos os logs
        $stmt = $conn->prepare("DELETE FROM agent_logs");
        $stmt->execute();
        $count = $stmt->rowCount();
        echo json_encode(['status' => 'success', 'deleted' => $count]);
        break;

    case 'clear_all_chats':
        // Limpar todos os logs de chat (mas manter dados de admin/sistema)
        $stmt = $conn->prepare("DELETE FROM agent_logs WHERE sender_role IN ('user', 'agent', 'admin_manual')");
        $stmt->execute();
        $count = $stmt->rowCount();
        echo json_encode(['status' => 'success', 'deleted' => $count]);
        break;

    case 'send_manual_message':
        $data = json_decode(file_get_contents('php://input'), true);
        $number = $data['number'] ?? '';
        $message = $data['message'] ?? '';

        if (empty($number) || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Número ou mensagem em falta.']);
            exit;
        }

        // Pegar o nome do primeiro admin para assinar a mensagem
        $stmt_admin = $conn->query("SELECT name FROM admin_users ORDER BY id LIMIT 1");
        $admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
        $admin_name = $admin ? $admin['name'] : 'Admin';

        $signed_message = "*{$admin_name}:* {$message}";

        // Ativar MUTE para o agente nesta conversa por 20 minutos
        $mute_key = "mute_agent_" . $number;
        $stmt_mute = $conn->prepare("REPLACE INTO agent_config (config_key, config_value) VALUES (?, ?)");
        $stmt_mute->execute([$mute_key, time()]);

        // Pegar configs da Evolution API
        $stmt_config = $conn->query("SELECT config_key, config_value FROM agent_config WHERE config_key IN ('evolution_url', 'evolution_apikey', 'evolution_instance')");
        $configs = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
        $evo_base         = rtrim($configs['evolution_url'] ?? 'http://72.61.56.104:42199', '/');
        $evo_inst         = $configs['evolution_instance'] ?? 'claus';
        $evolution_url    = "$evo_base/message/sendText/$evo_inst";
        $evolution_apikey = $configs['evolution_apikey'] ?? 'MRfty5LcqF2IDGdHD7CmvXS2p6xhZ2FC';

        // Enviar a mensagem
        $sent = sendWhatsApp($evolution_url, $evolution_apikey, $number, $signed_message);

        if ($sent) {
            // Logar a mensagem manual no banco de dados
            $stmt_log = $conn->prepare("INSERT INTO agent_logs (sender_number, sender_role, message, agent_action, status, timestamp) VALUES (?, 'admin_manual', ?, 'manual_reply', 'sent', ?)");
            $stmt_log->execute([$number, $signed_message, getLocalTime()]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Falha ao enviar a mensagem via Evolution API.']);
        }
        break;

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
        $admin_name = $admin['name'];

        // SALVAR A MENSAGEM DO ADMIN NO BANCO antes de processar (para aparecer no chat)
        try {
            $stmt_log = $conn->prepare("INSERT INTO agent_logs (sender_number, sender_role, message, agent_action, status, timestamp) VALUES (?, 'admin', ?, 'sent_from_panel', 'processing', ?)");
            $stmt_log->execute([$admin_number, $message, date('Y-m-d H:i:s')]);
        } catch (Exception $e) {
            // log error but continue
            file_put_contents('api_error.txt', date('Y-m-d H:i:s') . " - Erro ao salvar msg do admin: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        // Montar um payload que simula o da Evolution API
        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'claus',
            'data' => [
                'key' => [
                    'remoteJid' => $admin_number . '@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'PAINEL_' . strtoupper(bin2hex(random_bytes(10)))
                ],
                'pushName' => $admin_name,
                'message' => [
                    'conversation' => $message
                ]
            ]
        ];

        // Chamar o webhook.php localmente via cURL (fire-and-forget)
        $dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $dir . '/webhook.php';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500); // Aumentado para 500ms para maior garantia

        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Se houver erro de cURL que não seja timeout, logar
        if ($err && strpos($err, 'timeout') === false) {
            // Tentar registrar o erro num arquivo de log da API
            file_put_contents('api_error.txt', date('Y-m-d H:i:s') . " - Erro cURL ao chamar webhook: " . $err . "\n", FILE_APPEND);
        }

        // Sempre retornar sucesso para a UI para não bloquear o usuário
        echo json_encode(['status' => 'success', 'message' => 'Mensagem enviada para processamento.']);
        break;

    // ── save_config ──────────────────────────────────────────────
    case 'save_config':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $saved = 0;
        foreach ($input as $k => $v) {
            $k = preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string)$k)));
            if (!$k) continue;
            try {
                $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES (?,?) ON DUPLICATE KEY UPDATE config_value=VALUES(config_value)")
                     ->execute([$k, (string)$v]);
                $saved++;
            } catch (Exception $e) {}
        }
        echo json_encode(['status' => 'success', 'saved' => $saved]);
        break;

    // ── test_evolution ───────────────────────────────────────────
    case 'test_evolution':
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $evoUrl  = rtrim($input['url'] ?? '', '/');
        $evoKey  = $input['apikey'] ?? '';
        $evoInst = $input['instance'] ?? 'claus';

        if (!$evoUrl || !$evoKey) {
            echo json_encode(['status' => 'error', 'message' => 'url e apikey obrigatórios']); break;
        }
        $ch = curl_init("$evoUrl/instance/fetchInstances");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['apikey: '.$evoKey, 'Content-Type: application/json']]);
        $resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);

        if ($err || $code < 200 || $code >= 300) {
            echo json_encode(['status' => 'error', 'message' => "HTTP $code | $err"]); break;
        }
        $data = json_decode($resp, true) ?? [];
        $found = null;
        foreach ($data as $inst) {
            $name = $inst['name'] ?? $inst['instance']['instanceName'] ?? '';
            if (strtolower($name) === strtolower($evoInst)) { $found = $inst; break; }
        }
        if (!empty($input['full'])) {
            echo json_encode(['status' => 'ok', 'instances' => $data, 'target' => $found]);
        } else {
            $state = $found['connectionStatus'] ?? $found['instance']['status'] ?? 'unknown';
            echo json_encode(['status' => 'ok', 'connected' => true, 'state' => $state]);
        }
        break;

    // ── get_webhook_status ───────────────────────────────────────
    case 'get_webhook_status':
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $evoUrl  = rtrim($input['url']      ?? getConfigVal($conn,'evolution_url')      ?? '', '/');
        $evoKey  = $input['apikey']         ?? getConfigVal($conn,'evolution_apikey')  ?? '';
        $evoInst = $input['instance']       ?? getConfigVal($conn,'evolution_instance') ?? 'claus';

        $ch = curl_init("$evoUrl/webhook/find/$evoInst");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['apikey: '.$evoKey]]);
        $resp = curl_exec($ch); curl_close($ch);
        echo $resp ?: json_encode(['error' => 'no response']);
        break;

    // ── set_webhook ──────────────────────────────────────────────
    case 'set_webhook':
        $input      = json_decode(file_get_contents('php://input'), true) ?? [];
        $evoUrl     = rtrim($input['url']         ?? getConfigVal($conn,'evolution_url')      ?? '', '/');
        $evoKey     = $input['apikey']             ?? getConfigVal($conn,'evolution_apikey')  ?? '';
        $evoInst    = $input['instance']           ?? getConfigVal($conn,'evolution_instance') ?? 'claus';
        $webhookUrl = $input['webhook_url']        ?? 'https://'.$_SERVER['HTTP_HOST'].'/webhook.php';
        $events     = $input['events']             ?? ['MESSAGES_UPSERT','MESSAGES_UPDATE','SEND_MESSAGE','CONNECTION_UPDATE'];

        // Evolution v2.3: payload wrapped in "webhook" key
        $payload = json_encode(['webhook' => [
            'url'      => $webhookUrl,
            'byEvents' => false,
            'base64'   => false,
            'enabled'  => true,
            'events'   => $events,
        ]]);

        $ch = curl_init("$evoUrl/webhook/set/$evoInst");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json','apikey: '.$evoKey],
            CURLOPT_TIMEOUT => 15]);
        $resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);

        if ($err || $code < 200 || $code >= 300) {
            // Fallback: flat payload (older versions)
            $payload2 = json_encode(['url'=>$webhookUrl,'webhook_by_events'=>false,'webhook_base64'=>false,'enabled'=>true,'events'=>$events]);
            $ch2 = curl_init("$evoUrl/webhook/set/$evoInst");
            curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
                CURLOPT_POSTFIELDS=>$payload2,CURLOPT_HTTPHEADER=>['Content-Type: application/json','apikey: '.$evoKey],CURLOPT_TIMEOUT=>15]);
            $resp2 = curl_exec($ch2); $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE); $err2 = curl_error($ch2); curl_close($ch2);
            if (!$err2 && $code2 >= 200 && $code2 < 300) {
                $r = json_decode($resp2, true) ?? []; $r['status'] = 'ok'; echo json_encode($r);
            } else {
                echo json_encode(['status'=>'error','message'=>"HTTP $code (v2) / $code2 (v1)",'raw'=>$resp]);
            }
        } else {
            $r = json_decode($resp, true) ?? []; $r['status'] = 'ok'; echo json_encode($r);
        }
        break;

    // ── send_test_message ────────────────────────────────────────
    case 'send_test_message':
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $evoBase2 = rtrim($input['url']     ?? getConfigVal($conn,'evolution_url')      ?? '', '/');
        $evoKey2  = $input['apikey']        ?? getConfigVal($conn,'evolution_apikey')  ?? '';
        $evoInst2 = $input['instance']      ?? getConfigVal($conn,'evolution_instance') ?? 'claus';
        $toNum    = $input['number']        ?? getConfigVal($conn,'evolution_number')  ?? '';

        if (!$toNum) { echo json_encode(['status'=>'error','message'=>'Número não configurado']); break; }
        $payload = json_encode(['number'=>$toNum,'text'=>'✅ Claus Admin: conexão OK! '.date('d/m/Y H:i:s'),'delay'=>500]);
        $ch = curl_init("$evoBase2/message/sendText/$evoInst2");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>['Content-Type: application/json','apikey: '.$evoKey2],CURLOPT_TIMEOUT=>12]);
        $resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        $r = json_decode($resp, true) ?? ['raw'=>$resp];
        $r['http'] = $code; $r['status'] = (!$err && $code>=200 && $code<300) ? 'ok' : 'error';
        if ($err) $r['message'] = $err;
        echo json_encode($r);
        break;

    // ── test_ai_key ──────────────────────────────────────────────
    case 'test_ai_key':
        $input    = json_decode(file_get_contents('php://input'), true) ?? [];
        $provider = $input['provider'] ?? 'openai';
        $apikey   = $input['apikey'] ?? '';
        if (!$apikey) { echo json_encode(['status'=>'error','message'=>'apikey obrigatório']); break; }
        $eps = [
            'openai'      => ['https://api.openai.com/v1/models',                                      'Authorization: Bearer '.$apikey],
            'groq'        => ['https://api.groq.com/openai/v1/models',                                 'Authorization: Bearer '.$apikey],
            'gemini'      => ['https://generativelanguage.googleapis.com/v1beta/models?key='.$apikey,   ''],
            'claude'      => ['https://api.anthropic.com/v1/models',                                   'x-api-key: '.$apikey],
            'together'    => ['https://api.together.xyz/v1/models',                                    'Authorization: Bearer '.$apikey],
            'huggingface' => ['https://huggingface.co/api/models',                                     'Authorization: Bearer '.$apikey],
        ];
        $ep = $eps[$provider] ?? null;
        if (!$ep) { echo json_encode(['status'=>'error','message'=>'Provedor desconhecido']); break; }
        $hdrs = ['Content-Type: application/json'];
        if ($ep[1]) $hdrs[] = $ep[1];
        $ch = curl_init($ep[0]);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_HTTPHEADER=>$hdrs]);
        $resp = curl_exec($ch); $code = curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
        $d = json_decode($resp, true);
        if ($code === 200) {
            $models = array_slice(array_column($d['data'] ?? $d['models'] ?? [], 'id'), 0, 3);
            echo json_encode(['status'=>'ok','model'=>implode(', ',$models)?:'OK','http'=>$code]);
        } else {
            echo json_encode(['status'=>'error','message'=>"HTTP $code",'http'=>$code]);
        }
        break;

    // ── get_db_info ──────────────────────────────────────────────
    // Reads db.php and returns parsed (masked) credentials for the settings UI
    case 'get_db_info':
        $dbFile = __DIR__ . '/db.php';
        $info = ['host'=>'','name'=>'','user'=>'','pass_masked'=>'','port'=>'3306','file_found'=>false];
        if (file_exists($dbFile)) {
            $src = file_get_contents($dbFile);
            $info['file_found'] = true;
            preg_match('/host=([^;"\'\s]+)/i',   $src, $mh); $info['host'] = $mh[1] ?? '';
            preg_match('/dbname=([^;"\'\s]+)/i',  $src, $md); $info['name'] = $md[1] ?? '';
            preg_match('/port=([^;"\'\s]+)/i',    $src, $mp); $info['port'] = $mp[1] ?? '3306';
            // Try multiple user patterns
            if (preg_match('/["\']user["\'\s]*=>\s*["\']([^"\']+)/i',   $src, $mu)) $info['user'] = $mu[1];
            elseif (preg_match('/PDO\([^,]+,\s*["\']([^"\']+)["\'],\s*["\']([^"\']*)/i', $src, $mu)) {
                $info['user'] = $mu[1]; // pass hint
            }
            // Mask password — just show it's set
            if (preg_match('/["\']password["\'\s]*=>\s*["\']([^"\']+)/i', $src, $mpw) ||
                preg_match('/PDO\([^,]+,[^,]+,\s*["\']([^"\']+)/i',        $src, $mpw)) {
                $info['pass_masked'] = strlen($mpw[1]) ? str_repeat('*', min(strlen($mpw[1]),8)) : '';
                $info['pass_set'] = !empty($mpw[1]);
            }
        }
        echo json_encode($info);
        break;

    // ── save_db_config ───────────────────────────────────────────
    // Rewrites db.php with new credentials
    case 'save_db_config':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $host  = $input['db_host'] ?? 'localhost';
        $name  = $input['db_name'] ?? '';
        $user  = $input['db_user'] ?? '';
        $pass  = $input['db_pass'] ?? '';
        $port  = $input['db_port'] ?? '3306';
        if (!$name || !$user) { echo json_encode(['status'=>'error','message'=>'dbname e user obrigatórios']); break; }
        // If pass is __KEEP__, read existing password from current db.php
        if ($pass === '__KEEP__') {
            $existing = __DIR__ . '/db.php';
            if (file_exists($existing)) {
                $src = file_get_contents($existing);
                if (preg_match("/'([^']+)'\s*,\s*\[/s", $src, $m)) $pass = $m[1] ?? '';
                // More robust: find 3rd string arg to new PDO(...)
                if (preg_match('/new PDO\(\s*["\'][^"\']+["\'],\s*["\'][^"\']+["\'],\s*["\']([^"\']*)["\']/', $src, $mpw)) {
                    $pass = $mpw[1];
                }
            }
        }
        $dbPhp = "<?php\n// db.php — gerado por configuracoes.php em ".date('Y-m-d H:i:s')."\ntry {\n    \$conn = new PDO(\n        'mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4',\n        '$user',\n        '$pass',\n        [\n            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n        ]\n    );\n} catch (PDOException \$e) {\n    http_response_code(500);\n    die(json_encode(['status' => 'error', 'message' => 'DB connection failed: ' . \$e->getMessage()]));\n}\n";
        $dbFile = __DIR__ . '/db.php';
        $backup = __DIR__ . '/db_backup_' . date('Ymd_His') . '.php';
        if (file_exists($dbFile)) copy($dbFile, $backup);
        if (file_put_contents($dbFile, $dbPhp) !== false) {
            echo json_encode(['status'=>'success','message'=>'db.php atualizado','backup'=>basename($backup)]);
        } else {
            echo json_encode(['status'=>'error','message'=>'Sem permissão para escrever db.php']);
        }
        break;

    // ── get_memory ───────────────────────────────────────────────
    // Returns all agent memory sections (soul, identity, user, longterm, daily logs)
    case 'get_memory':
        $keys = ['mem_soul','mem_identity','mem_user','mem_longterm'];
        $mem  = [];
        foreach ($keys as $k) {
            $v = getConfigVal($conn, $k);
            $mem[$k] = $v ?? '';
        }
        // Daily logs: last 7 days
        $mem['daily_logs'] = [];
        try {
            $stmt = $conn->prepare(
                "SELECT config_key, config_value FROM agent_config
                 WHERE config_key LIKE 'mem_daily_%'
                 ORDER BY config_key DESC LIMIT 14"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($rows as $k => $v) {
                $date = str_replace('mem_daily_', '', $k);
                $mem['daily_logs'][] = ['date' => $date, 'content' => $v];
            }
        } catch (Exception $e) {}
        echo json_encode($mem);
        break;

    // ── save_memory ──────────────────────────────────────────────
    // Structured sections: replace. Daily logs: APPEND ONLY (never overwrite)
    case 'save_memory':
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $section = $input['section'] ?? '';    // soul|identity|user|longterm|daily
        $content = $input['content'] ?? '';
        $date    = $input['date']    ?? date('Y-m-d');
        $allowed = ['mem_soul','mem_identity','mem_user','mem_longterm'];

        if ($section === 'daily') {
            // APPEND ONLY — never replace daily log
            $key     = "mem_daily_$date";
            $current = getConfigVal($conn, $key) ?? '';
            $ts      = date('H:i');
            $newLine = "\n[$ts] " . trim($content);
            $updated = $current . $newLine;
            $conn->prepare("INSERT INTO agent_config(config_key,config_value) VALUES(?,?) ON DUPLICATE KEY UPDATE config_value=VALUES(config_value)")
                 ->execute([$key, $updated]);
            echo json_encode(['status'=>'success','action'=>'appended','key'=>$key]);
        } elseif (in_array($section, $allowed)) {
            // Structured sections: save new version but keep change history in daily log
            $old = getConfigVal($conn, $section) ?? '';
            $conn->prepare("INSERT INTO agent_config(config_key,config_value) VALUES(?,?) ON DUPLICATE KEY UPDATE config_value=VALUES(config_value)")
                 ->execute([$section, $content]);
            // Log the change in today's daily memory
            if (trim($old) !== trim($content)) {
                $logKey = 'mem_daily_' . date('Y-m-d');
                $logCurrent = getConfigVal($conn, $logKey) ?? '';
                $logLine = "\n[" . date('H:i') . "] [SISTEMA] Seção '$section' atualizada.";
                $conn->prepare("INSERT INTO agent_config(config_key,config_value) VALUES(?,?) ON DUPLICATE KEY UPDATE config_value=VALUES(config_value)")
                     ->execute([$logKey, $logCurrent . $logLine]);
            }
            echo json_encode(['status'=>'success','action'=>'saved','section'=>$section]);
        } else {
            echo json_encode(['status'=>'error','message'=>'Seção inválida']);
        }
        break;

    // ── get_agenda ───────────────────────────────────────────────
    case 'get_agenda':
        try {
            // Try extended contacts table first
            $stmt = $conn->query(
                "SELECT id, name, phone_number, relationship,
                        notes, created_at,
                        COALESCE(last_seen, '') as last_seen,
                        COALESCE(source, 'manual') as source
                 FROM contacts ORDER BY name ASC"
            );
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            // Fallback to basic contacts table
            try {
                $stmt = $conn->query("SELECT id, name, phone_number, relationship, notes FROM contacts ORDER BY name");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } catch (Exception $e2) {
                echo json_encode([]);
            }
        }
        break;

    // ── save_contact ─────────────────────────────────────────────
    // Always additive: UPDATE if phone exists, INSERT if new. Never silent delete.
    case 'save_contact':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $phone = trim($input['phone_number'] ?? $input['phone'] ?? '');
        $name  = trim($input['name'] ?? '');
        $rel   = trim($input['relationship'] ?? '');
        $notes = trim($input['notes'] ?? '');
        $source= $input['source'] ?? 'manual';
        if (!$phone) { echo json_encode(['status'=>'error','message'=>'phone_number obrigatório']); break; }

        // Ensure columns exist (add if missing — safe migration)
        try {
            $conn->exec("ALTER TABLE contacts ADD COLUMN IF NOT EXISTS last_seen DATETIME NULL");
            $conn->exec("ALTER TABLE contacts ADD COLUMN IF NOT EXISTS source VARCHAR(50) DEFAULT 'manual'");
        } catch (Exception $e) {}

        try {
            // Check if exists
            $existing = $conn->prepare("SELECT id, notes FROM contacts WHERE phone_number=? LIMIT 1");
            $existing->execute([$phone]);
            $row = $existing->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // ADDITIVE: merge notes, don't discard old ones
                $mergedNotes = $row['notes'];
                if ($notes && trim($notes) !== trim($row['notes'])) {
                    $mergedNotes = $row['notes'] ? $row['notes']."\n".$notes : $notes;
                }
                $stmt = $conn->prepare(
                    "UPDATE contacts SET name=?, relationship=?, notes=?, last_seen=NOW(), source=? WHERE id=?"
                );
                $stmt->execute([$name ?: $row['name'] ?? $phone, $rel, $mergedNotes, $source, $row['id']]);
                echo json_encode(['status'=>'success','action'=>'updated','id'=>$row['id']]);
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO contacts (name, phone_number, relationship, notes, last_seen, source)
                     VALUES (?,?,?,?,NOW(),?)"
                );
                $stmt->execute([$name ?: $phone, $phone, $rel, $notes, $source]);
                echo json_encode(['status'=>'success','action'=>'inserted','id'=>$conn->lastInsertId()]);
            }
        } catch (PDOException $e) {
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
        break;

    // ── delete_contact ───────────────────────────────────────────
    // Only deletes when explicitly requested
    case 'delete_contact':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = intval($input['id'] ?? 0);
        if (!$id) { echo json_encode(['status'=>'error','message'=>'id obrigatório']); break; }
        try {
            $conn->prepare("DELETE FROM contacts WHERE id=?")->execute([$id]);
            echo json_encode(['status'=>'success','deleted'=>$id]);
        } catch (Exception $e) {
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
        break;

    // ── extract_contacts ─────────────────────────────────────────
    // Scans recent agent_logs for phone numbers not yet in contacts
    case 'extract_contacts':
        try {
            $adminPhone = getConfigVal($conn, 'evolution_number') ?? '';
            // Get all unique sender_numbers from logs that aren't admin
            $stmt = $conn->prepare(
                "SELECT DISTINCT sender_number,
                        MAX(timestamp) as last_seen,
                        COUNT(*) as msg_count,
                        MAX(CASE WHEN sender_role='user' THEN message END) as last_msg
                 FROM agent_logs
                 WHERE sender_number != ?
                   AND sender_number NOT LIKE '%admin%'
                   AND sender_number IS NOT NULL
                   AND sender_number != ''
                   AND LENGTH(sender_number) >= 8
                 GROUP BY sender_number"
            );
            $stmt->execute([$adminPhone]);
            $numbers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Check which ones aren't in contacts
            $existingStmt = $conn->query("SELECT phone_number FROM contacts");
            $existing = $existingStmt->fetchAll(PDO::FETCH_COLUMN);

            $new = array_filter($numbers, fn($n) => !in_array($n['sender_number'], $existing));
            echo json_encode(['status'=>'ok','new_contacts'=>array_values($new),'total_found'=>count($numbers),'already_saved'=>count($existing)]);
        } catch (Exception $e) {
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Ação inválida.']);
        break;
}

// Função auxiliar para enviar mensagens, necessária para a intervenção manual
function sendWhatsApp($url, $apiKey, $number, $text) {
    $data = [
        'number' => $number,
        'text' => $text,
        'delay' => 300  // Reduzido de 1200ms para 300ms
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $apiKey
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode >= 200 && $httpCode < 300);
}
?>
