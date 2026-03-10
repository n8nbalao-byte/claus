<?php
// view_logs.php
// Arquivo simples para visualizar os logs gerados pelo webhook
// ACESSO: https://n8nbalao.com/view_logs.php

$logFile = 'webhook_log.txt';
$errorFile = 'webhook_error.txt';

echo "<h1>Logs do Webhook</h1>";

echo "<h2>Erros (webhook_error.txt)</h2>";
if (file_exists($errorFile)) {
    echo "<pre style='background:#ffdddd; padding:10px; border:1px solid red; overflow:auto; max-height:300px;'>" . htmlspecialchars(file_get_contents($errorFile)) . "</pre>";
} else {
    echo "<p>Nenhum erro registrado.</p>";
}

echo "<h2>Atividade (webhook_log.txt)</h2>";
if (file_exists($logFile)) {
    // Ler as últimas 50 linhas para não travar se o arquivo for grande
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    echo "<pre style='background:#f0f0f0; padding:10px; border:1px solid #ccc; overflow:auto; max-height:500px;'>" . htmlspecialchars(implode("", $lastLines)) . "</pre>";
} else {
    echo "<p>Nenhum log de atividade registrado.</p>";
}
?>
<script>
    // Auto-refresh a cada 5 segundos
    setTimeout(() => location.reload(), 5000);
</script>
<br>
<button onclick="location.reload()">Atualizar Logs Agora</button>
