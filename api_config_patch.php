<?php
/**
 * api_config_patch.php
 * ══════════════════════════════════════════════════════════════════
 * Adicione estes cases dentro do switch($action) do seu api.php
 * ANTES do "default:" final.
 *
 * Copie os blocos case abaixo para o api.php, dentro do switch($action){...}
 * ══════════════════════════════════════════════════════════════════
 */

// ══ COPY FROM HERE INTO api.php switch($action) ══════════════════

/*

    // ── SAVE CONFIG ──────────────────────────────────────────────
    case 'save_config':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $saved = 0;
        foreach ($input as $k => $v) {
            if (!is_string($k) || trim($k) === '') continue;
            $k = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($k)));
            if (!$k) continue;
            $stmt = $conn->prepare(
                "INSERT INTO agent_config (config_key, config_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)"
            );
            $stmt->execute([$k, (string)$v]);
            $saved++;
        }
        echo json_encode(['status'=>'success','saved'=>$saved]);
        break;

    // ── TEST EVOLUTION API ────────────────────────────────────────
    case 'test_evolution':
        $input    = json_decode(file_get_contents('php://input'), true) ?? [];
        $evoUrl   = rtrim($input['url'] ?? '', '/');
        $evoKey   = $input['apikey'] ?? '';
        $evoInst  = $input['instance'] ?? 'claus';
        $full     = !empty($input['full']);

        if (!$evoUrl || !$evoKey) {
            echo json_encode(['status'=>'error','message'=>'url e apikey obrigatórios']);
            break;
        }

        $ch = curl_init("$evoUrl/instance/fetchInstances");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['apikey: '.$evoKey, 'Content-Type: application/json'],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || $code < 200 || $code >= 300) {
            echo json_encode(['status'=>'error','message'=>"HTTP $code | $err",'raw'=>$resp]);
            break;
        }

        $data = json_decode($resp, true) ?? [];
        // Find our instance
        $found = null;
        foreach ($data as $inst) {
            $name = $inst['name'] ?? $inst['instance']['instanceName'] ?? '';
            if (strtolower($name) === strtolower($evoInst)) {
                $found = $inst; break;
            }
        }

        if ($full) {
            echo json_encode(['status'=>'ok','instances'=>$data,'target'=>$found]);
        } else {
            $state = $found['connectionStatus'] ?? $found['instance']['status'] ?? 'unknown';
            echo json_encode(['status'=>'ok','connected'=>true,'state'=>$state,'instance'=>$evoInst]);
        }
        break;

    // ── GET WEBHOOK STATUS ────────────────────────────────────────
    case 'get_webhook_status':
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $evoUrl  = rtrim($input['url'] ?? '', '/');
        $evoKey  = $input['apikey'] ?? '';
        $evoInst = $input['instance'] ?? 'claus';

        if (!$evoUrl || !$evoKey) {
            // Fallback: read from config
            $evoUrl  = rtrim(getConfig($conn, 'evolution_url') ?? '', '/');
            $evoKey  = getConfig($conn, 'evolution_apikey') ?? '';
            $evoInst = getConfig($conn, 'evolution_instance') ?? 'claus';
        }

        $ch = curl_init("$evoUrl/webhook/find/$evoInst");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>8,CURLOPT_HTTPHEADER=>['apikey: '.$evoKey]]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $wh = json_decode($resp, true) ?? [];
        echo json_encode($wh);
        break;

    // ── SET WEBHOOK ───────────────────────────────────────────────
    case 'set_webhook':
        $input      = json_decode(file_get_contents('php://input'), true) ?? [];
        $evoUrl     = rtrim($input['url']      ?? getConfig($conn,'evolution_url')     ?? '', '/');
        $evoKey     = $input['apikey']          ?? getConfig($conn,'evolution_apikey') ?? '';
        $evoInst    = $input['instance']        ?? getConfig($conn,'evolution_instance') ?? 'claus';
        $webhookUrl = $input['webhook_url']     ?? 'https://'.$_SERVER['HTTP_HOST'].'/webhook.php';
        $events     = $input['events']          ?? ['MESSAGES_UPSERT','MESSAGES_UPDATE','SEND_MESSAGE','CONNECTION_UPDATE'];

        $payload = json_encode([
            'url'               => $webhookUrl,
            'webhook_by_events' => false,
            'webhook_base64'    => false,
            'enabled'           => true,
            'events'            => $events,
        ]);

        $ch = curl_init("$evoUrl/webhook/set/$evoInst");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json','apikey: '.$evoKey],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || $code < 200 || $code >= 300) {
            echo json_encode(['status'=>'error','message'=>"HTTP $code | $err",'raw'=>$resp]);
        } else {
            $result = json_decode($resp, true) ?? [];
            $result['status'] = 'ok';
            echo json_encode($result);
        }
        break;

    // ── SEND TEST MESSAGE ─────────────────────────────────────────
    case 'send_test_message':
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $evoUrl  = rtrim($input['url']    ?? getConfig($conn,'evolution_url')     ?? '', '/');
        $evoKey  = $input['apikey']       ?? getConfig($conn,'evolution_apikey') ?? '';
        $evoInst = $input['instance']     ?? getConfig($conn,'evolution_instance') ?? 'claus';
        $toNum   = $input['number']       ?? getConfig($conn,'evolution_number')  ?? '';

        if (!$toNum) {
            echo json_encode(['status'=>'error','message'=>'Número não configurado']);
            break;
        }

        $payload = json_encode([
            'number' => $toNum,
            'text'   => '✅ Claus Admin: conexão OK! '.date('d/m/Y H:i:s'),
            'delay'  => 500,
        ]);

        $ch = curl_init("$evoUrl/message/sendText/$evoInst");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json','apikey: '.$evoKey],
            CURLOPT_TIMEOUT        => 12,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        $result = json_decode($resp, true) ?? ['raw'=>$resp];
        $result['http'] = $code;
        $result['status'] = (!$err && $code >= 200 && $code < 300) ? 'ok' : 'error';
        if ($err) $result['message'] = $err;
        echo json_encode($result);
        break;

    // ── TEST AI KEY ───────────────────────────────────────────────
    case 'test_ai_key':
        $input    = json_decode(file_get_contents('php://input'), true) ?? [];
        $provider = $input['provider'] ?? 'openai';
        $apikey   = $input['apikey']   ?? '';
        if (!$apikey) { echo json_encode(['status'=>'error','message'=>'apikey obrigatório']); break; }

        // Simple validation: make a minimal API call
        $endpoints = [
            'openai'      => ['url'=>'https://api.openai.com/v1/models','header'=>'Authorization: Bearer '.$apikey],
            'groq'        => ['url'=>'https://api.groq.com/openai/v1/models','header'=>'Authorization: Bearer '.$apikey],
            'gemini'      => ['url'=>'https://generativelanguage.googleapis.com/v1beta/models?key='.$apikey,'header'=>''],
            'claude'      => ['url'=>'https://api.anthropic.com/v1/models','header'=>'x-api-key: '.$apikey],
            'together'    => ['url'=>'https://api.together.xyz/v1/models','header'=>'Authorization: Bearer '.$apikey],
            'huggingface' => ['url'=>'https://api-inference.huggingface.co/models','header'=>'Authorization: Bearer '.$apikey],
        ];

        $ep = $endpoints[$provider] ?? null;
        if (!$ep) { echo json_encode(['status'=>'error','message'=>'Provedor desconhecido']); break; }

        $headers = ['Content-Type: application/json'];
        if ($ep['header']) $headers[] = $ep['header'];
        $ch = curl_init($ep['url']);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_HTTPHEADER=>$headers]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $data = json_decode($resp, true);
            $models = array_slice(array_column($data['data'] ?? [], 'id'), 0, 3);
            echo json_encode(['status'=>'ok','model'=>implode(', ',$models)?:'OK','http'=>$code]);
        } else {
            echo json_encode(['status'=>'error','message'=>"HTTP $code",'http'=>$code]);
        }
        break;

*/

// ══════════════════════════════════════════════════════════════════
// ALSO ADD this helper function outside the switch, near the top of api.php
// if it doesn't already exist:
// ══════════════════════════════════════════════════════════════════

/*

function getConfig(PDO $conn, string $key): ?string {
    try {
        $stmt = $conn->prepare("SELECT config_value FROM agent_config WHERE config_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $v = $stmt->fetchColumn();
        return $v !== false ? $v : null;
    } catch (Exception $e) {
        return null;
    }
}

*/

echo "Este é um arquivo de instruções de patch. Copie os blocos acima para o api.php.\n";
echo "Não execute diretamente.\n";
