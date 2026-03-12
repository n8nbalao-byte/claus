<?php
/**
 * WEBHOOK PATCH — Adicione estas funções ao seu webhook.php
 * ============================================================
 * 1. Substitua a função sendWhatsApp existente por esta versão
 * 2. Adicione a função splitResponseIntoMessages abaixo
 * 3. Substitua o bloco "Enviar resposta via Evolution API" pelo novo bloco
 * 4. Adicione o check de dedup antes do INSERT inicial do log
 */

// ================================================================
// BLOCO 1: CHECK DE DEDUPLICAÇÃO
// Substitua o trecho que faz INSERT de 'pending' por este:
// ================================================================
/*
// Dedup: verificar se esta mesma mensagem já foi salva nos últimos 15 segundos
$stmt_dedup = $conn->prepare(
    "SELECT id FROM agent_logs
     WHERE sender_number = ? AND message = ?
     AND timestamp >= DATE_SUB(NOW(), INTERVAL 15 SECOND)
     LIMIT 1"
);
$stmt_dedup->execute([$number, $messageText]);
$alreadySaved = $stmt_dedup->fetch();

if (!$alreadySaved) {
    // Salvar a mensagem atual como pendente no banco
    $stmt = $conn->prepare(
        "INSERT INTO agent_logs
         (sender_number, sender_role, message, agent_action, status, timestamp)
         VALUES (?, ?, ?, 'received', 'pending', ?)"
    );
    $stmt->execute([$number, $roleToSave, $messageText, getLocalTime()]);
} else {
    logEvent("DEDUP: mensagem duplicada ignorada para $number");
    exit; // Já está sendo processada
}
*/

// ================================================================
// BLOCO 2: FUNÇÃO splitResponseIntoMessages
// Adicione esta função na seção de funções auxiliares (após callGroq etc.)
// ================================================================
function splitResponseIntoMessages(string $text): array {
    // 1. Remove prefixo *Claus:* em todas as variações
    $text = preg_replace('/^\s*\*?\s*Claus\s*:\s*\*?\s*/iu', '', $text);
    $text = trim($text);
    if ($text === '') return [];

    // 2. Normalizar quebras de linha
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    $messages = [];

    // 3. Dividir por parágrafos (linhas duplas)
    $paragraphs = preg_split('/\n{2,}/', $text, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($paragraphs as $para) {
        $para = trim($para);
        if ($para === '') continue;

        // 4. Dividir por item de lista (linhas começando com -, *, •, número.)
        $listLines = preg_split('/\n(?=[\-\*•]|\d+[\.\)])/u', $para, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($listLines as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') continue;

            // 5. Dividir por sentença dentro do chunk
            // NÃO quebrar: números decimais (ex: 3.14, R$100,00)
            // QUEBRAR em: [.!?] seguido de espaço + letra maiúscula
            $sents = preg_split(
                '/(?<![0-9,])([.!?]+)(?=\s+[A-ZÁÉÍÓÚÀÂÊÔÃÕÜ\*\["(])/u',
                $chunk,
                -1,
                PREG_SPLIT_DELIM_CAPTURE
            );

            $buf = '';
            for ($i = 0; $i < count($sents); $i++) {
                $part = $sents[$i];
                // Se for um delimitador [.!?], anexar ao buffer e flush
                if (preg_match('/^[.!?]+$/u', $part)) {
                    $buf .= $part;
                    $clean = trim($buf);
                    if ($clean !== '') {
                        $messages[] = $clean;
                    }
                    $buf = '';
                } else {
                    $buf .= $part;
                }
            }
            if (trim($buf) !== '') {
                $messages[] = trim($buf);
            }
        }
    }

    // 6. Fallback: se nada foi dividido, devolver o texto original
    return empty($messages) ? [$text] : $messages;
}


// ================================================================
// BLOCO 3: NOVA LÓGICA DE ENVIO
// Substitua o bloco "5. Enviar resposta via Evolution API" por este:
// ================================================================
/*
    if ($aiResponse) {
        // --- Remover prefixo *Claus:* da resposta (UI já mostra o nome em verde) ---
        $cleanResponse = preg_replace('/^\s*\*?\s*Claus\s*:\s*\*?\s*/iu', '', $aiResponse);
        $cleanResponse = trim($cleanResponse);

        // --- Dividir em múltiplas mensagens ---
        $msgParts = splitResponseIntoMessages($aiResponse);

        logEvent("Resposta dividida em " . count($msgParts) . " partes");

        $anyFailed  = false;
        $sentParts  = [];

        foreach ($msgParts as $idx => $part) {
            $part = trim($part);
            if ($part === '') continue;

            // Delay crescente: 400ms base + 300ms por mensagem anterior
            // (a própria Evolution API tem delay interno, não usar sleep PHP)
            $delay = 400 + ($idx * 300);

            $data_send = [
                'number' => $number,
                'text'   => $part,
                'delay'  => $delay
            ];

            $ch = curl_init($evolution_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_send));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $evolution_apikey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            $resp    = curl_exec($ch);
            $http    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr || $http < 200 || $http >= 300) {
                logEvent("FALHA envio parte " . ($idx+1) . ": $curlErr | HTTP $http | $resp", true);
                $anyFailed = true;
            } else {
                $sentParts[] = $part;
                logEvent("Enviado parte " . ($idx+1) . ": " . substr($part, 0, 60));
            }
        }

        $status = $anyFailed ? 'failed' : 'sent';

        // Logar a resposta completa (sem prefixo) no banco
        $fullLog = implode("\n", $msgParts);
        $stmt    = $conn->prepare(
            "INSERT INTO agent_logs
             (sender_number, sender_role, message, agent_action, status, timestamp)
             VALUES (?, 'agent', ?, 'replied', ?, ?)"
        );
        $stmt->execute([$number, $cleanResponse, $status, getLocalTime()]);

        if (!$anyFailed) {
            logEvent("Todas as " . count($msgParts) . " partes enviadas com sucesso.");
        } else {
            logEvent("Algumas partes falharam ao enviar.", true);
        }

    } else {
        logEvent("FALHA: IA retornou resposta vazia. Verifique as chaves de API.", true);
    }
*/

// ================================================================
// BLOCO 4: TAMBÉM ATUALIZAR sendWhatsApp para suporte de split
// Esta função continua igual, pois o BLOCO 3 chama a Evolution API diretamente.
// Mantenha a função sendWhatsApp existente para outros usos (send_manual_message etc.)
// ================================================================

echo "Arquivo de patch do webhook. NÃO executar diretamente.\n";
echo "Copie os blocos comentados para o webhook.php conforme as instruções.\n";
