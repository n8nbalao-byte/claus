<?php
/**
 * set_webhook_now.php — v2 (Evolution API v2.3 payload correto)
 * Acesse UMA VEZ: https://n8nbalao.com/set_webhook_now.php
 * ⚠️ DELETE após usar!
 */

$EVO_URL     = 'http://72.61.56.104:42199';
$EVO_APIKEY  = 'MRfty5LcqF2IDGdHD7CmvXS2p6xhZ2FC';
$EVO_INST    = 'claus';
$EVO_TOKEN   = '0FED3972ADDE-43AD-A8A6-D75CB3D4F244';
$EVO_NUMBER  = '5519981470446';
$WEBHOOK_URL = 'https://n8nbalao.com/webhook.php';

$EVENTS = [
    'MESSAGES_UPSERT','MESSAGES_UPDATE','SEND_MESSAGE','CONNECTION_UPDATE',
    'QRCODE_UPDATED','CONTACTS_UPSERT','CONTACTS_UPDATE',
    'CHATS_UPSERT','CHATS_UPDATE','GROUPS_UPSERT','GROUP_UPDATE',
    'GROUP_PARTICIPANTS_UPDATE','PRESENCE_UPDATE'
];

function evo(string $method, string $path, string $apikey, ?array $body = null): array {
    global $EVO_URL;
    $ch = curl_init("$EVO_URL/$path");
    $headers = ['Content-Type: application/json', 'apikey: ' . $apikey];
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 15];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($ch, $opts);
    $r = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $e = curl_error($ch);
    curl_close($ch);
    return ['http' => $c, 'body' => json_decode($r, true) ?? $r, 'err' => $e, 'ok' => !$e && $c >= 200 && $c < 300];
}

$log = [];

// 1. Check instance status
$log[] = ['step' => 'Status da instância', 'r' => evo('GET', "instance/connectionState/$EVO_INST", $EVO_APIKEY)];

// 2. Get current webhook
$log[] = ['step' => 'Webhook atual', 'r' => evo('GET', "webhook/find/$EVO_INST", $EVO_APIKEY)];

// 3. Try Evolution v2 wrapped payload first
$payloadV2 = [
    'webhook' => [
        'url'      => $WEBHOOK_URL,
        'byEvents' => false,
        'base64'   => false,
        'enabled'  => true,
        'events'   => $EVENTS,
    ]
];
$setR = evo('POST', "webhook/set/$EVO_INST", $EVO_APIKEY, $payloadV2);

// 4. If v2 fails (400), try flat payload (v1 compat)
$usedFallback = false;
if (!$setR['ok']) {
    $payloadV1 = [
        'url'               => $WEBHOOK_URL,
        'webhook_by_events' => false,
        'webhook_base64'    => false,
        'enabled'           => true,
        'events'            => $EVENTS,
    ];
    $setRv1 = evo('POST', "webhook/set/$EVO_INST", $EVO_APIKEY, $payloadV1);
    $log[] = ['step' => 'Configurar webhook (v2 wrapped)', 'r' => $setR];
    $setR = $setRv1;
    $usedFallback = true;
}
$log[] = ['step' => 'Configurar webhook' . ($usedFallback ? ' (v1 flat fallback)' : ' (v2 wrapped)'), 'r' => $setR];

// 5. Verify
$verR     = evo('GET', "webhook/find/$EVO_INST", $EVO_APIKEY);
$log[]    = ['step' => 'Verificação final', 'r' => $verR];
$newUrl   = $verR['body']['url'] ?? $verR['body']['webhook']['url'] ?? null;
$verified = trim((string)$newUrl, '/') === trim($WEBHOOK_URL, '/');

// 6. Save to DB
$dbSaved = false; $dbErr = null; $conn = null;
foreach (['config.php','db.php','includes/config.php','includes/db.php'] as $cp) {
    if (!file_exists(__DIR__.'/'.$cp)) continue;
    $src = file_get_contents(__DIR__.'/'.$cp);
    preg_match('/mysql:host=([^;]+);dbname=([^;"\';\s]+)/i', $src, $dsn);
    preg_match('/(?:user|username)\s*[=:,\s]+["\']([^"\']+)/i', $src, $mu);
    preg_match('/(?:password|pass|passwd)\s*[=:,\s]+["\']([^"\']+)/i', $src, $mp);
    if ($dsn && $mu && $mp) {
        try {
            $conn = new PDO("mysql:host={$dsn[1]};dbname={$dsn[2]};charset=utf8mb4", $mu[1], $mp[1],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            break;
        } catch (Exception $e) { $dbErr = $e->getMessage(); }
    }
}
if ($conn) {
    $toSave = [
        'evolution_url'      => $EVO_URL,
        'evolution_apikey'   => $EVO_APIKEY,
        'evolution_instance' => $EVO_INST,
        'evolution_token'    => $EVO_TOKEN,
        'evolution_number'   => $EVO_NUMBER,
        'evolution_channel'  => 'evolution',
    ];
    foreach ($toSave as $k => $v) {
        try {
            $conn->prepare("INSERT INTO agent_config(config_key,config_value) VALUES(?,?) ON DUPLICATE KEY UPDATE config_value=VALUES(config_value)")->execute([$k, $v]);
        } catch (Exception $e) { $dbErr = $e->getMessage(); }
    }
    $dbSaved = true;
}

// 7. Send test message
$testR = null;
if ($setR['ok']) {
    $testR = evo('POST', "message/sendText/$EVO_INST", $EVO_APIKEY, [
        'number' => $EVO_NUMBER,
        'text'   => "✅ Claus: webhook configurado! URL: $WEBHOOK_URL\n" . date('d/m/Y H:i:s'),
        'delay'  => 800,
    ]);
}

?><!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Set Webhook v2</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px;max-width:760px;margin:0 auto}
h1{color:#00a884;font-size:20px;margin-bottom:20px}
h2{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#64748b;margin:18px 0 6px;border-bottom:1px solid #1e293b;padding-bottom:4px}
.card{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:14px 16px;margin-bottom:10px;font-size:13.5px;line-height:1.9}
.ok{color:#22c55e;font-weight:700;font-size:15px} .bad{color:#ef4444;font-weight:700;font-size:15px} .warn{color:#f59e0b}
pre{background:#0f172a;border:1px solid #2d3f50;border-radius:5px;padding:10px;font-size:11.5px;overflow-x:auto;white-space:pre-wrap;margin-top:6px}
code{background:#0f172a;padding:2px 5px;border-radius:4px;font-family:monospace;font-size:12px}
.step{border-left:3px solid #334155;padding:8px 12px;margin-bottom:8px;border-radius:0 6px 6px 0}
.step.ok{border-left-color:#22c55e} .step.bad{border-left-color:#ef4444}
a{color:#00a884}
</style>
</head>
<body>
<h1>⚙️ Set Webhook — Evolution API v2.3</h1>

<div class="card">
    <div class="<?= $verified ? 'ok' : 'bad' ?>">
        <?= $verified ? '✓ Webhook configurado e verificado!' : '✗ Falha na configuração' ?>
    </div>
    URL ativa: <code><?= htmlspecialchars($newUrl ?? '—') ?></code><br>
    DB salvo: <?= $dbSaved ? '<span style="color:#22c55e">✓ sim</span>' : '<span class="warn">⚠ não (' . htmlspecialchars((string)$dbErr) . ')</span>' ?><br>
    Payload usado: <code><?= $usedFallback ? 'v1 flat (fallback)' : 'v2 wrapped {webhook:{...}}' ?></code>
</div>

<?php if ($testR): ?>
<div class="card">
    Mensagem de teste:
    <?= $testR['ok'] ? '<span style="color:#22c55e">✓ Enviada para ' . $EVO_NUMBER . '</span>' : '<span style="color:#ef4444">✗ Falha HTTP ' . $testR['http'] . '</span>' ?>
    <pre><?= htmlspecialchars(json_encode($testR['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
</div>
<?php elseif (!$setR['ok']): ?>
<div class="card">
    <span class="warn">⚠ Webhook não configurado — mensagem de teste não enviada.</span><br><br>
    Tente executar no terminal do VPS <code>72.61.56.104</code>:<br>
    <pre><?= htmlspecialchars(
"curl -X POST 'http://72.61.56.104:42199/webhook/set/claus' \\
  -H 'apikey: MRfty5LcqF2IDGdHD7CmvXS2p6xhZ2FC' \\
  -H 'Content-Type: application/json' \\
  -d '{\"webhook\":{\"url\":\"https://n8nbalao.com/webhook.php\",\"byEvents\":false,\"base64\":false,\"enabled\":true,\"events\":[\"MESSAGES_UPSERT\",\"MESSAGES_UPDATE\",\"SEND_MESSAGE\",\"CONNECTION_UPDATE\"]}}'") ?></pre>
</div>
<?php endif; ?>

<h2>Log detalhado</h2>
<?php foreach ($log as $item):
    $ok = $item['r']['ok'] ?? false;
?>
<div class="step <?= $ok ? 'ok' : 'bad' ?>">
    <strong><?= htmlspecialchars($item['step']) ?></strong> — HTTP <?= $item['r']['http'] ?>
    <?= $item['r']['err'] ? '<br><span style="color:#ef4444">cURL: ' . htmlspecialchars($item['r']['err']) . '</span>' : '' ?>
    <pre><?= htmlspecialchars(json_encode($item['r']['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
</div>
<?php endforeach; ?>

<?php if ($verified): ?>
<div class="card">
    <span class="ok">✅ Pronto!</span> Envie uma mensagem para <strong><?= $EVO_NUMBER ?></strong> e veja em
    <a href="conversas.php">conversas.php</a> e <a href="log.php">log.php</a>.<br><br>
    <strong style="color:#ef4444">Delete este arquivo e auto_patch_api.php depois!</strong>
</div>
<?php endif; ?>

<br><p style="color:#475569;font-size:12px">⚠️ Delete: <code>set_webhook_now.php</code>, <code>auto_patch_api.php</code></p>
</body>
</html>
