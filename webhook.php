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

if (isset($_GET['send_test'])) {
    // Endpoint para testar mensagens manualmente (via GET para fácil teste)
    logEvent("[TESTE] Webhook chamado com send_test");
    echo json_encode(['status' => 'online', 'timestamp' => date('Y-m-d H:i:s')]);
    die();
}

// --- INÍCIO DO PROCESSAMENTO ---

// Função auxiliar para logs
function logEvent($message, $isError = false) {
    $dir = __DIR__;
    $file = $isError ? $dir . DIRECTORY_SEPARATOR . 'webhook_error.txt' : $dir . DIRECTORY_SEPARATOR . 'webhook_log.txt';
    // Usar offset -3 horas para São Paulo (UTC-3)
    $timestamp = date('Y-m-d H:i:s', time() - 3*3600);
    file_put_contents($file, $timestamp . " - " . $message . "\n", FILE_APPEND);
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

// Extrair texto da mensagem (necessário para distinguir comandos do bot)
$messageText = '';
if (isset($msgData['message']['conversation'])) {
    $messageText = $msgData['message']['conversation'];
} elseif (isset($msgData['message']['extendedTextMessage']['text'])) {
    $messageText = $msgData['message']['extendedTextMessage']['text'];
}

if (empty($messageText)) {
    logEvent("Mensagem sem texto, ignorando");
    die('Mensagem sem texto');
}

logEvent("MENSAGEM DE $number: $messageText (fromMe=$fromMe)");

// Verificar se é mensagem DO próprio bot (respostas que ele enviou)
$isOwnBotResponse = false;
if ($fromMe && (preg_match('/^\*?Claus:/i', $messageText) || stripos($messageText, 'mensagem enviada para') !== false)) {
    $isOwnBotResponse = true;
}

// Se for resposta do próprio bot, ignorar (não processar novamente)
if ($isOwnBotResponse) {
    logEvent("Mensagem do bot detectada, ignorando para evitar loop");
    die('Resposta do bot, ignorando para evitar loop');
}

// Se for fromMe=true MAS não for resposta do bot, é um comando do admin para si mesmo
// Continua processamento normal (será tratado como admin mais adiante)

// --- LÓGICA DE AGRUPAMENTO DE MENSAGENS (ANTISPAM/DELAY) ---

// 1. Salvar a mensagem atual como pendente no banco
try {
    // Verificar se o admin foi identificado antes de salvar
    $clean_number = preg_replace('/\D/', '', $number);
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE ? LIKE CONCAT('%', phone_number) OR phone_number LIKE CONCAT('%', ?)");
    $stmt->execute([$clean_number, $clean_number]);
    $isAdminCheck = $stmt->fetch();
    $roleToSave = $isAdminCheck ? 'admin' : 'user';

    $stmt = $conn->prepare("INSERT INTO agent_logs (sender_number, sender_role, message, agent_action, status, timestamp) VALUES (?, ?, ?, 'received', 'pending', ?)");
    $stmt->execute([$number, $roleToSave, $messageText, getLocalTime()]);
} catch (Exception $e) {
    logEvent("Erro ao salvar log inicial: " . $e->getMessage(), true);
}

// --- GERENCIAMENTO DE SESSÕES ---
$session_id = $remoteJid; // Usar remoteJid como session_id
try {
    // Verificar se já existe uma sessão ativa
    $stmt_session = $conn->prepare("SELECT current_state FROM active_sessions WHERE session_id = ?");
    $stmt_session->execute([$session_id]);
    $existing_session = $stmt_session->fetch(PDO::FETCH_ASSOC);

    if (!$existing_session) {
        // Criar nova sessão se não existir
        $stmt_insert = $conn->prepare("INSERT INTO active_sessions (session_id, remote_jid, current_state) VALUES (?, ?, 'ANALYZING_REQUEST')");
        $stmt_insert->execute([$session_id, $remoteJid]);
        logEvent("Nova sessão criada para $remoteJid");
    } else {
        // Sessão já existe, manter estado atual
        logEvent("Sessão existente para $remoteJid, estado: " . $existing_session['current_state']);
    }
} catch (Exception $e) {
    logEvent("Erro ao gerenciar sessão: " . $e->getMessage(), true);
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
$wait_seconds = 3; // Reduzido de 12 para 3 segundos para resposta mais rápida
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
    $groq_apikey = $configs['groq_apikey'] ?? '';
    $groq_model = $configs['groq_model'] ?? 'llama-3.3-70b-versatile';
    $gemini_apikey = $configs['gemini_apikey'] ?? '';
    $gemini_model = $configs['gemini_model'] ?? 'gemini-1.5-flash';
    $claude_apikey = $configs['claude_apikey'] ?? '';
    $claude_model = $configs['claude_model'] ?? 'claude-3-haiku-20240307';
    $huggingface_apikey = $configs['huggingface_apikey'] ?? '';
    $huggingface_model = $configs['huggingface_model'] ?? 'microsoft/DialoGPT-medium';
    $together_apikey = $configs['together_apikey'] ?? '';
    $together_model = $configs['together_model'] ?? 'meta-llama/Llama-3.3-70B-Instruct-Turbo';
    $openai_apikey = $configs['openai_apikey'] ?? '';
    $openai_model = $configs['openai_model'] ?? 'gpt-4o-mini';
    $evolution_url = $configs['evolution_url'] ?? 'http://72.61.56.104:63633/message/sendText/claus';
    $evolution_apikey = $configs['evolution_apikey'] ?? '185BD7822A0E-4C76-AF03-82957D439B1D';

    logEvent("=== INÍCIO PROCESSAMENTO ===");
    logEvent("Mensagem: '$messageText' de $userName ($role)");
    logEvent("Provider: $provider, OpenAI Key: " . (!empty($openai_apikey) ? 'Configurada' : 'Vazia'));
    logEvent("Groq Key: " . (!empty($groq_apikey) ? 'Configurada' : 'Vazia'));
    logEvent("Gemini Key: " . (!empty($gemini_apikey) ? 'Configurada' : 'Vazia'));
    logEvent("Claude Key: " . (!empty($claude_apikey) ? 'Configurada' : 'Vazia'));
    logEvent("HuggingFace Key: " . (!empty($huggingface_apikey) ? 'Configurada' : 'Vazia'));
    logEvent("Together AI Key: " . (!empty($together_apikey) ? 'Configurada' : 'Vazia'));
    
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
    // Se o admin interveio nos últimos 20 minutos, o Claus fica calado APENAS para números que NÃO sejam do admin.
    if (!$isAdmin) {
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
    } else {
        logEvent("Admin detectado - ignorando pausa de 20 minutos, respondendo sempre.");
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
        $systemPrompt .= "- [ADD_CONTACT: nome, telefone, relação, notas] -> Adiciona um novo contato à memória.\n";
        $systemPrompt .= "- [SAVE_BUSINESS_RULE: key, value, description] -> Salva uma regra de negócio.\n";
        $systemPrompt .= "- [SEARCH_CONTACT: relationship, search_term] -> Busca contato por relação e termo.\n";
        $systemPrompt .= "- [SCHEDULE_TASK: cron_expression, task_description] -> Agenda uma tarefa recorrente.\n";
        $systemPrompt .= "- [LOG_EVENT: event_type, details_json] -> Registra um evento importante.\n";
        
        $systemPrompt .= "\nSempre mantenha as informações anteriores ao atualizar USUÁRIO ou AGENTE, a menos que seja uma correção. Adicione novas informações ao contexto existente.\n";
        $systemPrompt .= $contacts_context;
    } else {
        $systemPrompt .= "Este é um cliente/usuário comum. Você é o Assistente do [Nome do Admin], focado em vendas e suporte.\n";
        $systemPrompt .= "- Identifique intenções de compra (ex: 'quero HD') e inicie o fluxo de intermediação.\n";
        $systemPrompt .= "- Use [SEARCH_CONTACT] para fornecedores/motoboys.\n";
        $systemPrompt .= "- Calcule preços aplicando regras de negócio (margens).\n";
        $systemPrompt .= "- Atualize active_sessions conforme o estado.\n";
        $systemPrompt .= "- Use [LOG_EVENT] para registrar vendas.\n";
        $systemPrompt .= "- Responda de forma educada e proativa, assinando como 'Claus:'.\n";
    }
    
    $aiResponse = null;

    if ($provider === 'groq') {
        if (empty($groq_apikey)) {
            logEvent("ERRO: Provedor Groq selecionado, mas sem chave API.", true);
        } else {
            logEvent("Usando Groq ($groq_model)...");
            $aiResponse = callGroq($groq_apikey, $groq_model, $systemPrompt, $history);
            logEvent("Resposta Groq recebida: " . substr($aiResponse ?? 'NULL', 0, 100));
        }
    } elseif ($provider === 'gemini') {
        if (empty($gemini_apikey)) {
            logEvent("ERRO: Provedor Gemini selecionado, mas sem chave API.", true);
        } else {
            logEvent("Usando Gemini ($gemini_model)...");
            $aiResponse = callGemini($gemini_apikey, $gemini_model, $systemPrompt, $history);
            logEvent("Resposta Gemini recebida: " . substr($aiResponse ?? 'NULL', 0, 100));
        }
    } elseif ($provider === 'claude') {
        if (empty($claude_apikey)) {
            logEvent("ERRO: Provedor Claude selecionado, mas sem chave API.", true);
        } else {
            logEvent("Usando Claude ($claude_model)...");
            $aiResponse = callClaude($claude_apikey, $claude_model, $systemPrompt, $history);
            logEvent("Resposta Claude recebida: " . substr($aiResponse ?? 'NULL', 0, 100));
        }
    } elseif ($provider === 'huggingface') {
        if (empty($huggingface_apikey)) {
            logEvent("ERRO: Provedor HuggingFace selecionado, mas sem chave API.", true);
        } else {
            logEvent("Usando HuggingFace ($huggingface_model)...");
            $aiResponse = callHuggingFace($huggingface_apikey, $huggingface_model, $systemPrompt, $history);
            logEvent("Resposta HuggingFace recebida: " . substr($aiResponse ?? 'NULL', 0, 100));
        }
    } elseif ($provider === 'together') {
        if (empty($together_apikey)) {
            logEvent("ERRO: Provedor Together AI selecionado, mas sem chave API.", true);
        } else {
            logEvent("Usando Together AI ($together_model)...");
            $aiResponse = callTogether($together_apikey, $together_model, $systemPrompt, $history);
            logEvent("Resposta Together AI recebida: " . substr($aiResponse ?? 'NULL', 0, 100));
        }
    } else {
        // Default OpenAI
        logEvent("Usando OpenAI ($openai_model)...");
        $aiResponse = callOpenAI($openai_apikey, $openai_model, $systemPrompt, $history);
        logEvent("Resposta OpenAI recebida: " . substr($aiResponse ?? 'NULL', 0, 100));
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
            // Enviar Mensagem para Outro Número (suporta múltiplas mensagens)
            if (preg_match('/\[SEND_MESSAGE:\s*(.*?),\s*(.*?)\]/s', $aiResponse, $matches)) {
                $rawNumber = trim($matches[1]);
                $messageToSend = trim($matches[2]);
                // remover aspas envolventes caso existam
                $messageToSend = trim($messageToSend, " \t\n\r\0\x0B\"'");

                // Formatação do número: remover caracteres não numéricos e garantir formato internacional
                $targetNumber = preg_replace('/\D/', '', $rawNumber);
                // Se o número começar com 0, remover (ex: 019...)
                if (strpos($targetNumber, '0') === 0) {
                    $targetNumber = substr($targetNumber, 1);
                }
                // Se o número tiver 10 ou 11 dígitos (sem 55), adicionar 55 (Brasil)
                if (strlen($targetNumber) == 10 || strlen($targetNumber) == 11) {
                    $targetNumber = '55' . $targetNumber;
                }

                logEvent("Tentando enviar via comando SEND_MESSAGE para: $targetNumber");

                // dividir em potenciais múltiplas mensagens
                $parts = preg_split('/[\r\n]+/', $messageToSend);
                $sentCount = 0;
                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part === '') continue;
                    // dividir ainda por sentenças pontuadas para humanizar
                    $submsgs = preg_split('/(?<=[\?\!\.]\s+)/', $part);
                    foreach ($submsgs as $sub) {
                        $sub = trim($sub);
                        if ($sub === '') continue;
                        $result = sendWhatsApp($evolution_url, $evolution_apikey, $targetNumber, $sub);
                        if ($result) {
                            $sentCount++;
                        }
                    }
                }

                if ($sentCount > 0) {
                    $aiResponse = str_replace($matches[0], "📲 Enviadas $sentCount mensagem(es) para $targetNumber!", $aiResponse);
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
            // Adicionar Contato
            if (preg_match('/\[ADD_CONTACT:\s*(.*?),\s*(.*?),\s*(.*?),\s*(.*?)\]/s', $aiResponse, $matches)) {
                $name = trim($matches[1]);
                $phone = trim($matches[2]);
                $relationship = trim($matches[3]);
                $notes = trim($matches[4]);
                
                try {
                    $stmt = $conn->prepare("INSERT INTO contacts (name, phone_number, relationship, notes) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE relationship = VALUES(relationship), notes = VALUES(notes)");
                    $stmt->execute([$name, $phone, $relationship, $notes]);
                    $aiResponse = str_replace($matches[0], "📞 Contato '$name' adicionado/atualizado!", $aiResponse);
                } catch (Exception $e) {
                    logEvent("Erro ao adicionar contato: " . $e->getMessage(), true);
                    $aiResponse = str_replace($matches[0], "❌ Erro ao adicionar contato.", $aiResponse);
                }
            }
            // Salvar Regra de Negócio
            if (preg_match('/\[SAVE_BUSINESS_RULE:\s*(.*?),\s*(.*?),\s*(.*?)\]/s', $aiResponse, $matches)) {
                $key = trim($matches[1]);
                $value = trim($matches[2]);
                $description = trim($matches[3]);
                
                try {
                    $stmt = $conn->prepare("INSERT INTO business_rules (rule_key, rule_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rule_value = VALUES(rule_value), description = VALUES(description)");
                    $stmt->execute([$key, $value, $description]);
                    $aiResponse = str_replace($matches[0], "📋 Regra '$key' salva!", $aiResponse);
                } catch (Exception $e) {
                    logEvent("Erro ao salvar regra: " . $e->getMessage(), true);
                    $aiResponse = str_replace($matches[0], "❌ Erro ao salvar regra.", $aiResponse);
                }
            }
            // Buscar Contato por Relação
            if (preg_match('/\[SEARCH_CONTACT:\s*(.*?),\s*(.*?)\]/s', $aiResponse, $matches)) {
                $relationship = trim($matches[1]);
                $search_term = trim($matches[2]);
                
                $stmt = $conn->prepare("SELECT name, phone_number, relationship, notes FROM contacts WHERE relationship LIKE ? AND (name LIKE ? OR notes LIKE ?)");
                $stmt->execute(["%$relationship%", "%$search_term%", "%$search_term%"]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $extra_context = "\n\n🔍 RESULTADOS DA BUSCA (CONTATO - '$relationship', '$search_term'):\n";
                if ($results) {
                    foreach ($results as $c) {
                        $extra_context .= "- {$c['name']}: {$c['phone_number']} ({$c['relationship']}) {$c['notes']}\n";
                    }
                } else {
                    $extra_context .= "Nenhum contato encontrado.";
                }
                $needs_rethink = true;
                // Adicionar ao histórico para reavaliação
                $history[] = ['role' => 'assistant', 'content' => $aiResponse];
                $history[] = ['role' => 'system', 'content' => $extra_context . "\nUse os dados para prosseguir."];
                
                // Chamar IA novamente
                if ($provider === 'groq') {
                    $aiResponse = callGroq($groq_apikey, $groq_model, $systemPrompt, $history);
                } else {
                    $aiResponse = callOpenAI($openai_apikey, $openai_model, $systemPrompt, $history);
                }
            }
            // Agendar Tarefa
            if (preg_match('/\[SCHEDULE_TASK:\s*(.*?),\s*(.*?)\]/s', $aiResponse, $matches)) {
                $cron = trim($matches[1]);
                $description = trim($matches[2]);
                
                try {
                    $stmt = $conn->prepare("INSERT INTO scheduled_tasks (cron_expression, task_description) VALUES (?, ?)");
                    $stmt->execute([$cron, $description]);
                    $aiResponse = str_replace($matches[0], "⏰ Tarefa agendada!", $aiResponse);
                } catch (Exception $e) {
                    logEvent("Erro ao agendar tarefa: " . $e->getMessage(), true);
                    $aiResponse = str_replace($matches[0], "❌ Erro ao agendar tarefa.", $aiResponse);
                }
            }
            // Logar Evento
            if (preg_match('/\[LOG_EVENT:\s*(.*?),\s*(.*?)\]/s', $aiResponse, $matches)) {
                $event_type = trim($matches[1]);
                $details_json = trim($matches[2]);
                
                try {
                    $stmt = $conn->prepare("INSERT INTO recent_events (event_type, event_details) VALUES (?, ?)");
                    $stmt->execute([$event_type, $details_json]);
                    $aiResponse = str_replace($matches[0], "📝 Evento '$event_type' registrado!", $aiResponse);
                } catch (Exception $e) {
                    logEvent("Erro ao logar evento: " . $e->getMessage(), true);
                    $aiResponse = str_replace($matches[0], "❌ Erro ao registrar evento.", $aiResponse);
                }
            }
        }

        // 5. Enviar resposta via Evolution API
        // garantir assinatura Claus
        if (!preg_match('/^\*(Claus|claus):\*/', $aiResponse)) {
            $aiResponse = "*Claus:* " . $aiResponse;
        }
        logEvent("Enviando resposta para $number: " . substr($aiResponse, 0, 100));
        $sent = sendWhatsApp($evolution_url, $evolution_apikey, $number, $aiResponse);
        $status = $sent ? 'sent' : 'failed';
        
        if ($sent) {
            logEvent("Enviado para WhatsApp com sucesso.");
        } else {
            logEvent("FALHA ao enviar para WhatsApp.", true);
        }

        // Atualizar sessão se aplicável
        if (!$isAdmin) {
            // Lógica simples: se resposta contém palavras-chave, atualizar estado
            $new_state = 'IDLE';
            if (strpos($aiResponse, 'consultar') !== false) {
                $new_state = 'WAITING_SUPPLIER_RESPONSE';
            } elseif (strpos($aiResponse, 'endereço') !== false) {
                $new_state = 'WAITING_CUSTOMER_CONFIRMATION';
            } elseif (strpos($aiResponse, 'motoboy') !== false) {
                $new_state = 'ARRANGING_DELIVERY';
            }
            $stmt_update_session = $conn->prepare("UPDATE active_sessions SET current_state = ?, updated_at = ? WHERE session_id = ?");
            $stmt_update_session->execute([$new_state, getLocalTime(), $session_id]);
        }

        // 6. Logar a resposta enviada no DB
        $stmt = $conn->prepare("INSERT INTO agent_logs (sender_number, sender_role, message, agent_action, status, timestamp) VALUES (?, 'agent', ?, 'replied', ?, ?)");
        $stmt->execute([$number, $aiResponse, $status, getLocalTime()]);
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);

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

function callGemini($apiKey, $model, $system, $history) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apiKey;

    // Converter histórico para formato Gemini
    $contents = [];
    $contents[] = ['role' => 'user', 'parts' => [['text' => $system]]];
    $contents[] = ['role' => 'model', 'parts' => [['text' => 'Entendido. Vou seguir essas instruções.']]];

    foreach ($history as $msg) {
        $role = $msg['role'] === 'assistant' ? 'model' : 'user';
        $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
    }

    $data = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 2048
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        logEvent('Erro cURL Gemini: ' . curl_error($ch), true);
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    $json = json_decode($response, true);

    if (isset($json['error'])) {
        logEvent('Erro API Gemini: ' . json_encode($json['error']), true);
        return null;
    }

    return $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
}

function callClaude($apiKey, $model, $system, $history) {
    $url = 'https://api.anthropic.com/v1/messages';

    // Converter histórico para formato Claude
    $messages = [];
    foreach ($history as $msg) {
        $role = $msg['role'] === 'assistant' ? 'assistant' : 'user';
        $messages[] = ['role' => $role, 'content' => $msg['content']];
    }

    $data = [
        'model' => $model,
        'max_tokens' => 2048,
        'system' => $system,
        'messages' => $messages
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        logEvent('Erro cURL Claude: ' . curl_error($ch), true);
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    $json = json_decode($response, true);

    if (isset($json['error'])) {
        logEvent('Erro API Claude: ' . json_encode($json['error']), true);
        return null;
    }

    return $json['content'][0]['text'] ?? null;
}

function callHuggingFace($apiKey, $model, $system, $history) {
    $url = 'https://api-inference.huggingface.co/models/' . $model;

    // Para modelos de conversação, usar o último input do usuário
    $lastUserMessage = '';
    foreach (array_reverse($history) as $msg) {
        if ($msg['role'] === 'user') {
            $lastUserMessage = $msg['content'];
            break;
        }
    }

    $data = [
        'inputs' => $system . "\n\n" . $lastUserMessage,
        'parameters' => [
            'max_length' => 512,
            'temperature' => 0.7
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        logEvent('Erro cURL HuggingFace: ' . curl_error($ch), true);
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    $json = json_decode($response, true);

    if (isset($json['error'])) {
        logEvent('Erro API HuggingFace: ' . json_encode($json), true);
        return null;
    }

    // HuggingFace retorna array de objetos com 'generated_text'
    if (is_array($json) && isset($json[0]['generated_text'])) {
        return $json[0]['generated_text'];
    }

    return null;
}

function callTogether($apiKey, $model, $system, $history) {
    $url = 'https://api.together.xyz/v1/chat/completions';

    $messages = [['role' => 'system', 'content' => $system]];
    foreach ($history as $msg) {
        $messages[] = $msg;
    }

    $data = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 2048,
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        logEvent('Erro cURL Together: ' . curl_error($ch), true);
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    $json = json_decode($response, true);

    if (isset($json['error'])) {
        logEvent('Erro API Together: ' . json_encode($json['error']), true);
        return null;
    }

    return $json['choices'][0]['message']['content'] ?? null;
}

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
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

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
