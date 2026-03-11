<?php
$page_title = "Logs de Execução";
require 'header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; color: var(--text-secondary);">Histórico de Atividades</h3>
        <div>
            <button id="refresh-btn" onclick="loadLogs()" style="background-color: var(--bg-color); color: var(--text-primary); border: 1px solid var(--border-color); margin-right:8px;">🔄 Atualizar</button>
            <button id="cancel-btn" onclick="cancelPending()" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin-right:8px;">✖ Cancelar Pendentes</button>
            <button id="clear-btn" onclick="clearAllLogs()" style="background-color: #d32f2f; color: white; border: 1px solid #c62828;">🗑 Limpar Todos</button>
        </div>
    </div>
    <table id="log-table">
        <thead><tr><th>Hora</th><th>Remetente</th><th>Mensagem</th><th>Ação</th><th>Status</th></tr></thead>
        <tbody></tbody>
    </table>
</div>

<script>
    async function loadLogs() {
        const logs = await fetchData('api.php?action=get_logs&limit=100');
        const tbody = document.querySelector('#log-table tbody');
        tbody.innerHTML = '';
        logs.forEach(log => {
                let statusClass = '';
            let statusLabel = log.status;
            let prefix = '';
            if (log.status === 'sent') {
                statusClass = 'status-sent';
            } else if (log.status === 'processing') {
                statusClass = 'status-processing';
                prefix = '<span class="spinner"></span>';
            } else if (log.status === 'failed') {
                statusClass = 'status-failed';
            } else {
                statusClass = 'status-pending';
            }
            const roleBadge = log.sender_role === 'admin' ? 'background:#ff6d5a;color:white;' : '';
            
            // garantir assinatura em logs de agente
            let displayMsg = log.message;
            if (log.sender_role === 'agent' && !/^\*Claus:\*/i.test(displayMsg)) {
                displayMsg = '*Claus:* ' + displayMsg;
            }
            // aplicar negrito na assinatura
            displayMsg = displayMsg.replace(/\*([^\*]+):\*/g, '<strong>$1:</strong>');
            tbody.innerHTML += `<tr>
                <td style="font-size: 12px; color: var(--text-secondary);">${new Date(log.timestamp).toLocaleString('pt-BR')}</td>
                <td>
                    <div>${log.sender_number}</div>
                    <span class="role-badge" style="${roleBadge}">${log.sender_role}</span>
                </td>
                <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${log.message}">${displayMsg}</td>
                <td>${log.agent_action}</td>
                <td><span class="log-status ${statusClass}">${prefix}${statusLabel}</span></td>
            </tr>`;
        });
    }

    async function cancelPending() {
        if (!confirm('Tem certeza de que deseja cancelar todos os processos pendentes/processing?')) return;
        const res = await fetch('api.php?action=cancel_pending');
        const data = await res.json();
        alert(data.status === 'success' ? `${data.updated} registros atualizados.` : 'Falha ao cancelar.');
        loadLogs();
    }

    async function clearAllLogs() {
        if (!confirm('⚠️ CUIDADO! Isto vai deletar TODOS os logs. Tem certeza?')) return;
        if (!confirm('Essa ação é irreversível. Confirmar novamente?')) return;
        const res = await fetch('api.php?action=clear_all_logs');
        const data = await res.json();
        alert(data.status === 'success' ? `${data.deleted} registros deletados.` : 'Falha ao limpar.');
        loadLogs();
    }

    window.addEventListener('load', () => {
        loadLogs();
        setInterval(loadLogs, 3000);
    });
</script>

<?php require_once 'footer.php'; ?>
