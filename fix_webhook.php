<?php
/**
 * fix_webhook.php — Corrige webhook Evolution API + atualiza config do banco
 * Acesse: https://n8nbalao.com/fix_webhook.php
 * ⚠️ DELETE após usar!
 */

// ── CREDENCIAIS HARDCODED ────────────────────────────────────────
$EVO_HOST    = 'http://72.61.56.104:63633';
$EVO_APIKEY  = '185BD7822A0E-4C76-AF03-82957D439B1D';
$EVO_INST    = 'claus';
$WEBHOOK_URL = 'https://n8nbalao.com/webhook.php';

// ── TENTAR CONECTAR AO BANCO (lendo config.php real do servidor) ─
$conn    = null;
$dbErr   = null;
$dbCreds = null;

// Ler as credenciais reais do config.php do servidor
$cfgPaths = ['config.php','db.php','includes/config.php','includes/db.php','app/config.php'];
foreach ($cfgPaths as $cp) {
    $full = __DIR__.'/'.$cp;
    if (file_exists($full)) {
        $src = file_get_contents($full);
        // Extrair DSN / host / dbname / user / pass via regex
        preg_match('/host\s*[=:]\s*["\']?([^"\';\s,)]+)/i', $src, $mh);
        preg_match('/dbname\s*[=:]\s*["\']?([^"\';\s,)]+)/i', $src, $md);
        preg_match('/(?:user|username)\s*[=:]\s*["\']([^"\']+)/i', $src, $mu);
        preg_match('/(?:password|pass|passwd)\s*[=:]\s*["\']([^"\']+)/i', $src, $mp);
        // Also try PDO DSN style
        preg_match('/mysql:host=([^;]+);dbname=([^;"\'\s]+)/i', $src, $mdsn);
        if ($mdsn) {
            $dbCreds = ['host'=>$mdsn[1],'db'=>$mdsn[2],'user'=>$mu[1]??'','pass'=>$mp[1]??'','file'=>$cp];
        } elseif ($mh && $md) {
            $dbCreds = ['host'=>$mh[1],'db'=>$md[1],'user'=>$mu[1]??'','pass'=>$mp[1]??'','file'=>$cp];
        }
        if ($dbCreds) break;
    }
}

// Try connecting with detected creds
if ($dbCreds && $dbCreds['user'] && $dbCreds['pass']) {
    try {
        $conn = new PDO(
            "mysql:host={$dbCreds['host']};dbname={$dbCreds['db']};charset=utf8mb4",
            $dbCreds['user'], $dbCreds['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch(Exception $e) { $dbErr = $e->getMessage(); }
}

// Fallback: try common credential patterns found in hostinger projects
if (!$conn) {
    $tryList = [
        ['localhost', 'u770915504_openclaw', 'u770915504_openclaw', 'Balao2024@'],
        ['localhost', 'u770915504_openclaw', 'u770915504_openclaw', 'balao2024'],
        ['localhost', 'u770915504_openclaw', 'u770915504_openclaw', 'Balao@2024'],
    ];
    foreach ($tryList as [$h,$db,$u,$p]) {
        try {
            $conn = new PDO("mysql:host=$h;dbname=$db;charset=utf8mb4",$u,$p,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $dbCreds = ['host'=>$h,'db'=>$db,'user'=>$u,'pass'=>$p,'file'=>'hardcoded fallback'];
            $dbErr = null;
            break;
        } catch(Exception $e) { $dbErr = $e->getMessage(); }
    }
}

$log = [];

// ── 1. CHECK CURRENT WEBHOOK ────────────────────────────────────
function evo($method, $path, $apikey, $payload=null) {
    global $EVO_HOST;
    $ch = curl_init("$EVO_HOST/$path");
    $headers = ['Content-Type: application/json', 'apikey: '.$apikey];
    $opts = [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>$headers, CURLOPT_TIMEOUT=>12];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($payload);
    }
    curl_setopt_array($ch, $opts);
    $r = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $e = curl_error($ch);
    curl_close($ch);
    return ['http'=>$c,'body'=>json_decode($r,true)?:$r,'raw'=>$r,'err'=>$e];
}

// GET current webhook config
$current = evo('GET', "webhook/find/$EVO_INST", $EVO_APIKEY);
$curUrl   = $current['body']['url'] ?? $current['body']['webhook']['url'] ?? null;
$curEnabled = $current['body']['enabled'] ?? $current['body']['webhook']['enabled'] ?? null;
$curEvents  = $current['body']['events']  ?? $current['body']['webhook']['events'] ?? [];

// ── 2. SET WEBHOOK ──────────────────────────────────────────────
$setResult = evo('POST', "webhook/set/$EVO_INST", $EVO_APIKEY, [
    'url'               => $WEBHOOK_URL,
    'webhook_by_events' => false,
    'webhook_base64'    => false,
    'enabled'           => true,
    'events'            => ['MESSAGES_UPSERT', 'MESSAGES_UPDATE', 'CONNECTION_UPDATE']
]);
$webhookOk = $setResult['http'] >= 200 && $setResult['http'] < 300 && !$setResult['err'];

// ── 3. VERIFY WEBHOOK SET ───────────────────────────────────────
$verify = evo('GET', "webhook/find/$EVO_INST", $EVO_APIKEY);
$newUrl  = $verify['body']['url'] ?? $verify['body']['webhook']['url'] ?? null;
$verified = trim($newUrl ?? '', '/') === trim($WEBHOOK_URL, '/');

// ── 4. UPDATE agent_config IN DB ───────────────────────────────
$dbUpdates = [];
if ($conn) {
    $toSet = [
        'evolution_url'      => $EVO_HOST,
        'evolution_apikey'   => $EVO_APIKEY,
        'evolution_instance' => $EVO_INST,
    ];
    foreach ($toSet as $k => $v) {
        try {
            // Try UPDATE first, then INSERT
            $stmt = $conn->prepare("INSERT INTO agent_config (config_key, config_value) VALUES (?,?) ON DUPLICATE KEY UPDATE config_value=VALUES(config_value)");
            $stmt->execute([$k, $v]);
            $dbUpdates[] = "✓ $k atualizado";
        } catch(Exception $e) {
            $dbUpdates[] = "✗ $k: ".$e->getMessage();
        }
    }
}

// ── 5. SEND TEST MESSAGE ────────────────────────────────────────
$adminNum = null;
$testMsg  = null;
if ($conn) {
    try {
        $an = $conn->query("SELECT phone_number FROM admin_users LIMIT 1")->fetchColumn();
        $adminNum = $an ?: null;
    } catch(Exception $e) {}
}

if ($adminNum && $webhookOk) {
    $testMsg = evo('POST', "message/sendText/$EVO_INST", $EVO_APIKEY, [
        'number' => $adminNum,
        'text'   => "✅ Claus: webhook configurado com sucesso!\n🔗 URL: $WEBHOOK_URL\n⏰ ".date('d/m/Y H:i:s'),
        'delay'  => 500
    ]);
}

// ── 6. CHECK INSTANCE STATUS ────────────────────────────────────
$instStatus = evo('GET', "instance/fetchInstances", $EVO_APIKEY);

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Fix Webhook — Claus</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px;max-width:780px;margin:0 auto}
h1{color:#00a884;font-size:20px;margin-bottom:20px}
h2{font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#64748b;margin:20px 0 6px;border-bottom:1px solid #1e293b;padding-bottom:5px}
.card{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:14px 16px;margin-bottom:10px;font-size:13.5px;line-height:1.8}
.ok  {color:#22c55e;font-weight:700}
.bad {color:#ef4444;font-weight:700}
.warn{color:#f59e0b;font-weight:700}
pre{background:#0f172a;border:1px solid #2d3f50;border-radius:6px;padding:10px;font-size:11.5px;overflow-x:auto;white-space:pre-wrap;margin-top:8px;line-height:1.6}
code{background:#0f172a;padding:1px 5px;border-radius:4px;font-size:12.5px;font-family:monospace}
.big{font-size:17px;margin-bottom:4px}
.row{display:flex;gap:14px;align-items:center;margin:3px 0}
.tag{padding:2px 8px;border-radius:10px;font-size:11.5px;font-weight:600}
.tg{background:rgba(34,197,94,.12);color:#22c55e}
.tr{background:rgba(239,68,68,.12);color:#ef4444}
.ty{background:rgba(245,158,11,.12);color:#f59e0b}
a.btn{display:inline-block;margin-top:14px;background:#00a884;color:#fff;padding:10px 20px;border-radius:7px;text-decoration:none;font-weight:600;font-size:13.5px}
a.btn:hover{background:#008069}
</style>
</head>
<body>
<h1>⚙️ Fix Webhook — Claus Admin</h1>

<!-- ── BANCO ── -->
<h2>Banco de Dados</h2>
<div class="card">
<?php if ($conn): ?>
    <span class="ok">✓ Conectado</span>
    <?php if ($dbCreds): ?>
        — via <code><?= htmlspecialchars($dbCreds['file']) ?></code>
        (host: <code><?= htmlspecialchars($dbCreds['host']) ?></code>, db: <code><?= htmlspecialchars($dbCreds['db']) ?></code>)
    <?php endif; ?>
<?php else: ?>
    <span class="bad">✗ Falha na conexão:</span> <?= htmlspecialchars($dbErr) ?><br>
    <span class="warn">⚠ A config do banco não foi atualizada. Configure manualmente em identidade.php.</span>
<?php endif; ?>
</div>

<!-- ── WEBHOOK ANTES ── -->
<h2>Webhook Antes da Correção</h2>
<div class="card">
    URL configurada: <strong><?= $curUrl ? htmlspecialchars($curUrl) : '<span class="bad">Nenhuma</span>' ?></strong><br>
    Enabled: <?= $curEnabled === true ? '<span class="ok">true</span>' : ($curEnabled === false ? '<span class="bad">false</span>' : '<span class="warn">desconhecido</span>') ?><br>
    Eventos: <code><?= htmlspecialchars(implode(', ', (array)$curEvents) ?: 'nenhum') ?></code>
</div>

<!-- ── SET WEBHOOK RESULT ── -->
<h2>Configuração do Webhook</h2>
<div class="card">
    <?php if ($webhookOk): ?>
        <div class="big"><span class="ok">✓ Webhook configurado com sucesso!</span></div>
        URL definida: <code><?= htmlspecialchars($WEBHOOK_URL) ?></code><br>
        HTTP: <code><?= $setResult['http'] ?></code><br>
        <?php if ($verified): ?>
            <span class="ok">✓ Verificado — Evolution API confirmou a nova URL</span>
        <?php else: ?>
            <span class="warn">⚠ Não foi possível verificar — cheque manualmente</span>
        <?php endif; ?>
    <?php else: ?>
        <div class="big"><span class="bad">✗ Falha ao configurar webhook</span></div>
        HTTP: <code><?= $setResult['http'] ?></code><br>
        Erro cURL: <code><?= htmlspecialchars($setResult['err']) ?></code><br>
        <pre><?= htmlspecialchars(json_encode($setResult['body'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
        <br>
        <span class="warn">⚠ A Evolution API pode estar inacessível pelo servidor web (porta 63633 bloqueada). Use o cURL manual abaixo.</span>
    <?php endif; ?>
</div>

<!-- ── VERIFY ── -->
<h2>Webhook Após Configuração</h2>
<div class="card">
    URL atual: <strong><?= htmlspecialchars($newUrl ?? '—') ?></strong>
    <?= $verified ? '<span class="ok">✓</span>' : '<span class="bad">✗ diferente do esperado</span>' ?><br>
    <pre><?= htmlspecialchars(json_encode($verify['body'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
</div>

<!-- ── DB UPDATES ── -->
<?php if ($dbUpdates): ?>
<h2>Config do Banco Atualizada</h2>
<div class="card">
<?php foreach ($dbUpdates as $u) echo htmlspecialchars($u).'<br>'; ?>
</div>
<?php endif; ?>

<!-- ── TEST MSG ── -->
<h2>Mensagem de Teste</h2>
<div class="card">
<?php if ($testMsg): ?>
    Enviada para: <code><?= htmlspecialchars($adminNum) ?></code><br>
    HTTP: <code><?= $testMsg['http'] ?></code>
    <?= ($testMsg['http'] >= 200 && $testMsg['http'] < 300) ? '<span class="ok"> ✓ Enviada!</span>' : '<span class="bad"> ✗ Falha</span>' ?>
    <?php if ($testMsg['err']): ?><br><span class="bad">Erro: <?= htmlspecialchars($testMsg['err']) ?></span><?php endif; ?>
<?php elseif (!$adminNum): ?>
    <span class="warn">Número admin não encontrado — não foi possível enviar teste</span>
<?php else: ?>
    <span class="warn">Webhook falhou — teste não enviado</span>
<?php endif; ?>
</div>

<!-- ── INSTANCE STATUS ── -->
<h2>Status das Instâncias (Evolution API)</h2>
<div class="card">
    HTTP: <code><?= $instStatus['http'] ?></code>
    <?php if ($instStatus['err']): ?>
        <br><span class="bad">⚠ Não foi possível conectar na Evolution API: <?= htmlspecialchars($instStatus['err']) ?></span>
        <br><span class="warn">A porta 63633 pode estar bloqueada para conexões externas do servidor web. Use o cURL manual abaixo.</span>
    <?php else: ?>
        <pre><?= htmlspecialchars(json_encode($instStatus['body'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
    <?php endif; ?>
</div>

<!-- ── MANUAL CURL ── -->
<h2>Configuração Manual (se o automático falhou)</h2>
<div class="card">
    <strong>Execute no terminal do servidor VPS onde está a Evolution API:</strong>
    <pre><?= htmlspecialchars(
"curl -X POST 'http://72.61.56.104:63633/webhook/set/claus' \\
  -H 'apikey: 185BD7822A0E-4C76-AF03-82957D439B1D' \\
  -H 'Content-Type: application/json' \\
  -d '{
    \"url\": \"https://n8nbalao.com/webhook.php\",
    \"webhook_by_events\": false,
    \"webhook_base64\": false,
    \"enabled\": true,
    \"events\": [\"MESSAGES_UPSERT\", \"MESSAGES_UPDATE\", \"CONNECTION_UPDATE\"]
  }'"
) ?></pre>
    <br>
    <strong>Verificar se ficou correto:</strong>
    <pre><?= htmlspecialchars("curl -H 'apikey: 185BD7822A0E-4C76-AF03-82957D439B1D' \\\n  'http://72.61.56.104:63633/webhook/find/claus'") ?></pre>
</div>

<!-- ── NEXT STEPS ── -->
<h2>Próximos Passos</h2>
<div class="card">
    <?php if ($verified): ?>
        <span class="ok">✅ Tudo configurado!</span> Agora:<br>
        1. Envie uma mensagem no WhatsApp para o número <strong>5519981470446</strong><br>
        2. Verifique se aparece em <a href="chat.php" style="color:#00a884">chat.php</a> e <a href="conversas.php" style="color:#00a884">conversas.php</a><br>
        3. Verifique os logs em <a href="log.php" style="color:#00a884">log.php</a><br>
    <?php else: ?>
        <span class="warn">⚠ Webhook não pôde ser configurado automaticamente.</span><br>
        O motivo mais comum: a porta <strong>63633</strong> da Evolution API não é acessível a partir do servidor web Hostinger (firewall ou NAT).<br><br>
        <strong>Solução:</strong> Execute o cURL manual acima <strong>diretamente no VPS</strong> onde a Evolution API está rodando (72.61.56.104).
    <?php endif; ?>
</div>

<br>
<p style="color:#475569;font-size:12px;font-family:monospace">⚠️ DELETE este arquivo: <code>fix_webhook.php</code> e <code>setup_webhook.php</code></p>
</body>
</html>
