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
        $stmt_config = $conn->query("SELECT config_key, config_value FROM agent_config WHERE config_key IN ('evolution_url', 'evolution_apikey')");
        $configs = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
        $evolution_url = $configs['evolution_url'] ?? 'http://72.61.56.104:63633/message/sendText/claus';
        $evolution_apikey = $configs['evolution_apikey'] ?? '185BD7822A0E-4C76-AF03-82957D439B1D';

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
