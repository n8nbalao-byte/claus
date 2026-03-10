<?php
// webhook.php
// Versão 3: Debug Extremo OpenAI
// Este arquivo substitui o n8n. Ele recebe o webhook da Evolution API e processa tudo.

// --- CONFIGURAÇÕES ---
// Evolution API
$evolution_url = 'http://72.61.56.104:63633/message/sendText/claus'; // Instância Claus
$evolution_apikey = 'SUA_CHAVE_EVOLUTION_AQUI'; // Chave fornecida

// OpenAI (Valores padrão de fallback, caso o DB falhe ou esteja vazio)
$default_openai_apikey = 'SUA_CHAVE_OPENAI_AQUI';
$default_openai_model = 'gpt-4o-mini';

if (isset($_GET['test'])) {
    die('Webhook está online e funcionando!');
}

// --- INÍCIO DO PROCESSAMENTO ---

// Função auxiliar para logs
function logEvent($message, $isError = false) {
    $dir = __DIR__;
    $file = $isError ? $dir . DIRECTORY_SEPARATOR . 'webhook_error.txt' : $dir . DIRECTORY_SEPARATOR . 'webhook_log.txt';
    file_put_contents($file, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Carregar conexão com banco de dados
try {
    require 'db.php';
    logEvent("DB conectado.");
} catch (Exception $e) {
    logEvent("Erro fatal ao conectar DB: " . $e->getMessage(), true);
    die('Erro DB');
}

// Receber o JSON do Webhook
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// LOG DEPURATIVO FORÇADO - Registra TUDO que chega
logEvent("RECEBIDO (RAW): " . substr($json, 0, 1000));

// Verificar se é uma mensagem recebida (messages.upsert)
if (!isset($data['event']) || $data['event'] !== 'messages.upsert') {
    logEvent("Evento IGNORADO: " . ($data['event'] ?? 'Sem evento') . " | Type: " . ($data['type'] ?? 'Sem type'));
    die('Evento ignorado');
}

// Extrair dados principais
$msgData = $data['data'];
$remoteJid = $msgData['key']['remoteJid'];

// Ignorar atualizações de status
if ($remoteJid === 'status@broadcast') {
    die('Ignorado: Status@broadcast');
}

$fromMe = $msgData['key']['fromMe'];
$number = explode('@', $remoteJid)[0];
$pushName = $data['data']['pushName'] ?? 'Usuário';

// Ignorar mensagens enviadas pelo próprio bot
if ($fromMe) {
    die('Mensagem enviada por mim');
}

// Extrair texto da mensagem
$messageText = '';
if (isset($msgData['message']['conversation'])) {
    $messageText = $msgData['message']['conversation'];
} elseif (isset($msgData['message']['extendedTextMessage']['text'])) {
    $messageText = $msgData['message']['extendedTextMessage']['text'];
}

if (empty($messageText)) {
    die('Mensagem sem texto');
}

logEvent("MENSAGEM DE $number: $messageText");

// --- LÓGICA DE AGRUPAMENTO DE MENSAGENS (ANTISPAM/DELAY) ---

// 1. Salvar a mensagem atual como pendente no banco
try {
    // Verificar se o admin foi identificado antes de salvar
    $clean_number = preg_replace('/\D/', '', $number);
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE ? LIKE CONCAT('%', phone_number) OR phone_number LIKE CONCAT('%', ?)");
    $stmt->execute([$clean_number, $clean_number]);
    $isAdminCheck = $stmt->fetch();
    $roleToSave = $isAdminCheck ? 'admin' : 'user';

    $stmt = $conn->prepare("INSERT INTO agent_logs (sender_number, sender_role, message, agent_action, status) VALUES (?, ?, ?, 'received', 'pending')");
    $stmt->execute([$number, $roleToSave, $messageText]);
} catch (Exception $e) {
    logEvent("Erro ao salvar log inicial: " . $e->getMessage(), true);
}

// 2. Verificar se já existe um processo de espera rodando para este número
$stmt = $conn->prepare("SELECT config_value FROM agent_config WHERE config_key = ?");
$wait_key = "waiting_process_" . $number;
$stmt->execute([$wait_key]);
$is_waiting = $stmt->fetchColumn();

if ($is_waiting && (time() - (int)$is_waiting) < 30) {
    // Se já existe um processo esperando e ele tem menos de 30 segundos, apenas saímos.
    // O processo que já está rodando vai pegar esta nova mensagem que acabamos de salvar.
    die('Já existe um processo aguardando por este número.');
}

// 3. Se não existe, marcamos que este processo vai cuidar das mensagens
$stmt = $conn->prepare("REPLACE INTO agent_config (config_key, config_value) VALUES (?, ?)");
$stmt->execute([$wait_key, time()]);

logEvent("Iniciando espera de grouping para $number...");

// 4. Responder ao WhatsApp e fechar a conexão para rodar em background
// Isso evita que o WhatsApp/Evolution API ache que o webhook falhou por demora
if (php_sapi_name() !== 'cli') {
    ob_start();
    echo json_encode(['status' => 'waiting', 'message' => 'Agrupando mensagens...']);
    header('Connection: close');
    header('Content-Length: ' . ob_get_length());
    ob_end_flush();
    ob_flush();
    flush();
}

// A partir daqui o script roda em "segundo plano"
ignore_user_abort(true);
set_time_limit(120);

// 5. Loop de espera (Aguardar o usuário parar de digitar)
$wait_seconds = 12; // Tempo total de espera (ajustado de 8 para 12)
sleep($wait_seconds);

// 6. Buscar todas as mensagens pendentes deste número para processar de uma vez
$stmt = $conn->prepare("SELECT id, message FROM agent_logs WHERE sender_number = ? AND status = 'pending' AND sender_role != 'agent' ORDER BY timestamp ASC");
$stmt->execute([$number]);
$pending_msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($pending_msgs)) {
    // Se por algum motivo não houver mensagens (ex: outro processo já pegou), limpamos e saímos
    $stmt = $conn->prepare("DELETE FROM agent_config WHERE config_key = ?");
    $stmt->execute([$wait_key]);
    exit;
}

// Agrupar as mensagens
$combined_message = "";
$msg_ids = [];
foreach ($pending_msgs as $m) {
    $combined_message .= $m['message'] . " ";
    $msg_ids[] = $m['id'];
}
$combined_message = trim($combined_message);

// Marcar estas mensagens como 'processing' para evitar que outro processo as pegue
$placeholders = implode(',', array_fill(0, count($msg_ids), '?'));
$stmt = $conn->prepare("UPDATE agent_logs SET status = 'processing' WHERE id IN ($placeholders)");
$stmt->execute($msg_ids);

// Limpar o sinalizador de espera antes de começar a falar com a IA
$stmt = $conn->prepare("DELETE FROM agent_config WHERE config_key = ?");
$stmt->execute([$wait_key]);

// Substituir a mensagem original pela combinada para o restante do script
$messageText = $combined_message;

// --- LÓGICA DO AGENTE (IA) ---

try {
    // 1. Pegar Configurações do Banco
    $stmt = $conn->query("SELECT config_key, config_value FROM agent_config");
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $prompt = $configs['main_prompt'] ?? "Você é um assistente útil.";
    $provider = $configs['ai_provider'] ?? 'openai';
    
    $usuario_info = $configs['usuario_info'] ?? '';
    $agente_info = $configs['agente_info'] ?? '';
    
    // Configurações OpenAI
    $db_apikey_openai = $configs['openai_apikey'] ?? '';
    $openai_apikey = !empty($db_apikey_openai) ? $db_apikey_openai : $default_openai_apikey;
    $openai_model = $configs['openai_model'] ?? $default_openai_model;

    // Configurações Groq
    $db_apikey_groq = $configs['groq_apikey'] ?? '';
    $groq_apikey = !empty($db_apikey_groq) ? $db_apikey_groq : 'SUA_CHAVE_GROQ_AQUI'; // Fallback Hardcoded
    $groq_model = $configs['groq_model'] ?? 'llama-3.3-70b-versatile';

    // Auto-upgrade de modelos descontinuados
    if (strpos($groq_model, 'llama3-70b') !== false || strpos($groq_model, 'llama3-8b') !== false) {
        logEvent("Aviso: Modelo Groq descontinuado ($groq_model) detectado. Fazendo upgrade automático para llama-3.3-70b-versatile.");
        $groq_model = 'llama-3.3-70b-versatile';
    }

    // 2. Verificar se é Admin (Re-verificar para garantir nome correto no log)
    $clean_number = preg_replace('/\D/', '', $number);
    $stmt = $conn->prepare("SELECT id, name FROM admin_users WHERE ? LIKE CONCAT('%', phone_number) OR phone_number LIKE CONCAT('%', ?)");
    $stmt->execute([$clean_number, $clean_number]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isAdmin = $admin ? true : false;
    $role = $isAdmin ? 'admin' : 'user';
    $userName = $admin ? $admin['name'] : $pushName;

    // --- VERIFICAR SE O AGENTE ESTÁ SILENCIADO (MUTE) ---
    // Se o admin interveio nos últimos 20 minutos, o Claus fica calado para este número.
    $mute_key = "mute_agent_" . $number;
    $stmt = $conn->prepare("SELECT config_value FROM agent_config WHERE config_key = ?");
    $stmt->execute([$mute_key]);
    $last_intervention = $stmt->fetchColumn();
    
    if ($last_intervention && (time() - (int)$last_intervention) < (20 * 60)) {
        logEvent("Agente silenciado para $number devido à intervenção recente do Admin.");
        // Marcar mensagem como processada (mas sem resposta do agente)
        $placeholders = implode(',', array_fill(0, count($msg_ids), '?'));
        $stmt = $conn->prepare("UPDATE agent_logs SET status = 'muted' WHERE id IN ($placeholders)");
        $stmt->execute($msg_ids);
        exit;
    }

    logEvent("Identificado como: $role ($userName) - Número: $number");

    // 4. Buscar histórico da conversa
    $stmt = $conn->prepare("SELECT sender_role, message FROM agent_logs WHERE sender_number = ? ORDER BY timestamp DESC LIMIT 10");
    $stmt->execute([$number]);
    $history_rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    $history = [];
    foreach ($history_rows as $row) {
        $role_history = ($row['sender_role'] === 'agent') ? 'assistant' : 'user';
        $history[] = ['role' => $role_history, 'content' => $row['message']];
    }

    // 5. Buscar contatos se necessário (para o agente ter em memória)
    $contacts_context = "";
    if ($isAdmin) {
        $stmt = $conn->query("SELECT name, phone_number, relationship, notes FROM contacts ORDER BY id DESC LIMIT 5");
        $all_contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($all_contacts) {
            $contacts_context = "\n--- CONTATOS RECENTES ---\n";
            foreach ($all_contacts as $c) {
                $contacts_context .= "- {$c['name']}: {$c['phone_number']} ({$c['relationship']})\n";
            }
            $contacts_context .= "(Use [SEARCH_CONTACTS: nome] para buscar outros contatos)\n";
        }
    }

    // 5. Gerar resposta com IA
    $systemPrompt = $prompt . "\n\n";
    $systemPrompt .= "--- CONTEXTO ATUAL ---\n";
    $systemPrompt .= "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
    
    $systemPrompt .= "\n--- IDENTIDADE DO AGENTE ---\n";
    $systemPrompt .= "AGENTE: " . $agente_info . "\n";
    $systemPrompt .= "USUÁRIO ADMIN: " . $usuario_info . "\n\n";
    
    $systemPrompt .= "--- CONTEXTO DA CONVERSA ---\n";
    $systemPrompt .= "Você está falando com um: " . strtoupper($isAdmin ? 'admin' : 'user') . ".\n";
    $systemPrompt .= "Nome: " . $userName . ".\n";

    if ($isAdmin) {
        $systemPrompt .= "--- REGRAS DE COMUNICAÇÃO ---\n";
        $systemPrompt .= "- Responda de forma natural e concisa.\n";
        $systemPrompt .= "- Inicie TODAS as suas respostas com seu nome formatado assim: '*Claus:* '.\n";
        $systemPrompt .= "- Se o admin pedir algo que exija busca, use as tags de busca abaixo.\n\n";

        $systemPrompt .= "--- AÇÕES DE ADMIN (TAGS ESPECIAIS) ---\n";
        $systemPrompt .= "Use estas tags no início ou fim da sua resposta para executar ações:\n";
        $systemPrompt .= "- [UPDATE_PROMPT: novo texto] -> Altera seu comportamento principal.\n";
        $systemPrompt .= "- [UPDATE_USUARIO: info] -> Atualiza fatos sobre o admin.\n";
        $systemPrompt .= "- [UPDATE_AGENTE: info] -> Atualiza fatos sobre você (Claus).\n";
        $systemPrompt .= "- [SEND_MESSAGE: número, texto] -> Envia mensagem para qualquer número.\n";
        $systemPrompt .= "- [POST_STATUS: texto] -> Publica EXATAMENTE o texto fornecido no seu status do WhatsApp.\n";
        $systemPrompt .= "- [SEARCH_CONTACTS: termo] -> Busca contatos por nome ou nota.\n";
        $systemPrompt .= "- [SEARCH_LOGS: termo] -> Busca no histórico de conversas (logs).\n";
        
        $systemPrompt .= "\nSempre mantenha as informações anteriores ao atualizar USUÁRIO ou AGENTE, a menos que seja uma correção. Adicione novas informações ao contexto existente.\n";
        $systemPrompt .= $contacts_context;
    } else {
        $systemPrompt .= "Este é um cliente/usuário comum. Siga estritamente suas diretrizes de atendimento. Assine todas as suas respostas com seu nome, por exemplo: 'Claus: Olá, como posso ajudar?'";
    }
    
    $aiResponse = null;

    if ($provider === 'groq') {
        if (empty($groq_apikey)) {
            logEvent("ERRO: Provedor Groq selecionado, mas sem chave API.", true);
        } else {
            logEvent("Usando Groq ($groq_model)...");
            $aiResponse = callGroq($groq_apikey, $groq_model, $systemPrompt, $history);
        }
    } else {
        // Default OpenAI
        logEvent("Usando OpenAI ($openai_model)...");
        $aiResponse = callOpenAI($openai_apikey, $openai_model, $systemPrompt, $history);
    }

    if ($aiResponse) {
        logEvent("Resposta da IA recebida: " . substr($aiResponse, 0, 50) . "...");
        
        // --- PROCESSAR ATUALIZAÇÕES DE SISTEMA (ADMIN) ---
        if ($isAdmin) {
            // Se houver tags de busca, vamos processar e chamar a IA novamente para uma resposta final fundamentada
            $needs_rethink = false;
            $extra_context = "";

            // Busca de Contatos (Suporta múltiplas buscas)
            if (preg_match_all('/\[SEARCH_CONTACTS:\s*(.*?)\]/s', $aiResponse, $matches_list, PREG_SET_ORDER)) {
                foreach ($matches_list as $matches) {
                    $term = trim($matches[1]);
                    $stmt = $conn->prepare("SELECT name, phone_number, relationship, notes FROM contacts WHERE name LIKE ? OR notes LIKE ?");
                    $stmt->execute(["%$term%", "%$term%"]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $extra_context .= "\n\n🔍 RESULTADOS DA BUSCA (CONTATOS - '$term'):\n";
                    if ($results) {
                        foreach ($results as $c) {
                            $extra_context .= "- {$c['name']}: {$c['phone_number']} ({$c['relationship']}) {$c['notes']}\n";
                        }
                    } else {
                        $extra_context .= "Nenhum contato encontrado para '$term'.";
                    }
                    $needs_rethink = true;
                }
            }

            // Busca de Logs (Suporta múltiplas buscas)
            if (preg_match_all('/\[SEARCH_LOGS:\s*(.*?)\]/s', $aiResponse, $matches_list, PREG_SET_ORDER)) {
                foreach ($matches_list as $matches) {
                    $term = trim($matches[1]);
                    $stmt = $conn->prepare("SELECT timestamp, sender_role, message FROM agent_logs WHERE message LIKE ? ORDER BY timestamp DESC LIMIT 5");
                    $stmt->execute(["%$term%"]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $extra_context .= "\n\n📖 RESULTADOS DA BUSCA (LOGS - '$term'):\n";
                    if ($results) {
                        foreach ($results as $l) {
                            $role_log = $l['sender_role'] === 'agent' ? '🤖 Claus' : '👤 Usuário';
                            $extra_context .= "[{$l['timestamp']}] $role_log: {$l['message']}\n";
                        }
                    } else {
                        $extra_context .= "Nenhum registro encontrado para '$term'.";
                    }
                    $needs_rethink = true;
                }
            }

            if ($needs_rethink) {
                logEvent("Processando busca e repensando resposta...");
                // Adicionar o pensamento atual e o resultado da busca ao histórico temporário
                $history[] = ['role' => 'assistant', 'content' => $aiResponse];
                $history[] = ['role' => 'system', 'content' => "Aqui estão os dados solicitados:\n" . $extra_context . "\nAgora, forneça a resposta final ao usuário baseada nestes dados."];
                
                // Chamar IA novamente
                if ($provider === 'groq') {
                    $aiResponse = callGroq($groq_apikey, $groq_model, $systemPrompt, $history);
                } else {
                    $aiResponse = callOpenAI($openai_apikey, $openai_model, $systemPrompt, $history);
                }
                logEvent("Nova resposta da IA (pós-busca): " . substr($aiResponse, 0, 50) . "...");
            }

            // Processar atualizações de configuração (tags de escrita)
            // Atualizar Prompt
            if (preg_match('/\[UPDATE_PROMPT:\s*(.*?)\]/s', $aiResponse, $matches)) {
                $newVal = trim($matches[1]);
                $stmt = $conn->prepare("UPDATE agent_config SET config_value = ? WHERE config_key = 'main_prompt'");
                $stmt->execute([$newVal]);
                $aiResponse = str_replace($matches[0], "✅ Prompt atualizado!", $aiResponse);
            }
            // Atualizar Usuário
            if (preg_match('/\[UPDATE_USUARIO:\s*(.*?)\]/s', $aiResponse, $matches)) {
                $newVal = trim($matches[1]);
                $stmt = $conn->prepare("UPDATE agent_config SET config_value = ? WHERE config_key = 'usuario_info'");
                $stmt->execute([$newVal]);
                $aiResponse = str_replace($matches[0], "👤 Info usuário atualizada!", $aiResponse);
            }
            // Atualizar Agente
            if (preg_match('/\[UPDATE_AGENTE:\s*(.*?)\]/s', $aiResponse, $matches)) {
                $newVal = trim($matches[1]);
                $stmt = $conn->prepare("UPDATE agent_config SET config_value = ? WHERE config_key = 'agente_info'");
                $stmt->execute([$newVal]);
                $aiResponse = str_replace($matches[0], "🤖 Info agente atualizada!", $aiResponse);
            }
            // Enviar Mensagem para Outro Número
            if (preg_match('/\[SEND_MESSAGE:\s*(.*?),\s*(.*?)\]/s', $aiResponse, $matches)) {
                $rawNumber = trim($matches[1]);
                $messageToSend = trim($matches[2]);
                
                // Formatação do número: remover caracteres não numéricos e garantir formato internacional
                $targetNumber = preg_replace('/\D/', '', $rawNumber);
                
                // Se o número começar com 0, remover (ex: 019...)
                if (strpos($targetNumber, '0') === 0) {
                    $targetNumber = substr($targetNumber, 1);
                }
                
                // Se o número tiver 10 ou 11 dígitos, adicionar 55 (Brasil)
                if (strlen($targetNumber) == 10 || strlen($targetNumber) == 11) {
                    $targetNumber = '55' . $targetNumber;
                }
                
                logEvent("Tentando enviar via comando SEND_MESSAGE para: $targetNumber");
                $sent = sendWhatsApp($evolution_url, $evolution_apikey, $targetNumber, $messageToSend);
                if ($sent) {
                    $aiResponse = str_replace($matches[0], "📲 Mensagem enviada para $targetNumber!", $aiResponse);
                } else {
                    $aiResponse = str_replace($matches[0], "❌ Falha ao enviar para $targetNumber. Verifique os logs.", $aiResponse);
                }
            }
            // Postar Status
            if (preg_match('/\[POST_STATUS:\s*(.*?)\]/s', $aiResponse, $matches)) {
                $statusText = trim($matches[1]);
                logEvent("Tentando postar status: $statusText");
                $posted = postWhatsAppStatus($evolution_url, $evolution_apikey, $statusText);
                if ($posted) {
                    $aiResponse = str_replace($matches[0], "✅ Status publicado: '$statusText'", $aiResponse);
                } else {
                    $aiResponse = str_replace($matches[0], "❌ Falha ao publicar o status. Verifique os logs.", $aiResponse);
                }
            }
        }

        // 5. Enviar resposta via Evolution API
        $sent = sendWhatsApp($evolution_url, $evolution_apikey, $number, $aiResponse);
        $status = $sent ? 'sent' : 'failed';
        
        if ($sent) {
            logEvent("Enviado para WhatsApp com sucesso.");
        } else {
            logEvent("FALHA ao enviar para WhatsApp.", true);
        }

        // 6. Logar a resposta enviada no DB
        $stmt = $conn->prepare("INSERT INTO agent_logs (sender_number, sender_role, message, agent_action, status) VALUES (?, 'agent', ?, 'replied', ?)");
        $stmt->execute([$number, $aiResponse, $status]);
    } else {
        logEvent("FALHA: A IA (Provedor: $provider) retornou resposta vazia ou nula. Verifique as chaves de API e limites.", true);
    }

} catch (Exception $e) {
    logEvent("EXCEÇÃO: " . $e->getMessage(), true);
}

// --- FUNÇÕES AUXILIARES ---

function callOpenAI($apiKey, $model, $system, $history) {
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $messages = [['role' => 'system', 'content' => $system]];
    foreach ($history as $msg) {
        $messages[] = $msg;
    }
    
    $data = [
        'model' => $model,
        'messages' => $messages
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        logEvent('Erro cURL OpenAI: ' . curl_error($ch), true);
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);

    $json = json_decode($response, true);
    
    if (isset($json['error'])) {
        logEvent('Erro API OpenAI Detalhado: ' . json_encode($json), true);
        return null;
    }
    
    if (!isset($json['choices'][0]['message']['content'])) {
        logEvent('Resposta OpenAI inesperada: ' . substr($response, 0, 200), true);
        return null;
    }

    return $json['choices'][0]['message']['content'];
}

function callGroq($apiKey, $model, $system, $history) {
    $url = 'https://api.groq.com/openai/v1/chat/completions';
    
    $messages = [['role' => 'system', 'content' => $system]];
    foreach ($history as $msg) {
        $messages[] = $msg;
    }
    
    $data = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        logEvent('Erro cURL Groq: ' . curl_error($ch), true);
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);

    $json = json_decode($response, true);
    
    if (isset($json['error'])) {
        logEvent('Erro API Groq Detalhado: ' . json_encode($json), true);
        return null;
    }

    if (!isset($json['choices'][0]['message']['content'])) {
        logEvent('Resposta Groq inesperada: ' . substr($response, 0, 200), true);
        return null;
    }

    return $json['choices'][0]['message']['content'] ?? null;
}

function sendWhatsApp($url, $apiKey, $number, $text) {
    $data = [
        'number' => $number,
        'text' => $text,
        'delay' => 1200
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
    
    if (curl_errno($ch)) {
        logEvent('Erro cURL Evolution: ' . curl_error($ch), true);
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        logEvent("Erro Evolution API (HTTP $httpCode): " . $response, true);
        return false;
    }

    return true;
}

function postWhatsAppStatus($url, $apiKey, $text) {
    $baseUrl = substr($url, 0, strrpos($url, '/')); 
    $baseUrl = substr($baseUrl, 0, strrpos($baseUrl, '/')); 
    $statusUrl = $baseUrl . '/message/sendStatus/claus'; 

    $data = [
        'type' => 'text',
        'content' => $text
    ];

    $ch = curl_init($statusUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $apiKey
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        logEvent('Erro cURL Evolution (Status): ' . curl_error($ch), true);
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        logEvent("Erro Evolution API (Status - HTTP $httpCode): " . $response, true);
        return false;
    }

    return true;
}
?>
