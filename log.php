<?php
$page_title = "Logs de Execução";
require 'header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; color: var(--text-secondary);">Histórico de Atividades</h3>
        <button onclick="loadLogs()" style="background-color: var(--bg-color); color: var(--text-primary); border: 1px solid var(--border-color);">🔄 Atualizar</button>
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
            const statusClass = log.status === 'sent' ? 'status-sent' : 'status-pending';
            const roleBadge = log.sender_role === 'admin' ? 'background:#ff6d5a;color:white;' : '';
            
            tbody.innerHTML += `<tr>
                <td style="font-size: 12px; color: var(--text-secondary);">${new Date(log.timestamp).toLocaleString('pt-BR')}</td>
                <td>
                    <div>${log.sender_number}</div>
                    <span class="role-badge" style="${roleBadge}">${log.sender_role}</span>
                </td>
                <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${log.message}">${log.message}</td>
                <td>${log.agent_action}</td>
                <td><span class="log-status ${statusClass}">${log.status}</span></td>
            </tr>`;
        });
    }

    window.addEventListener('load', () => {
        loadLogs();
        setInterval(loadLogs, 10000);
    });
</script>

<?php require_once 'footer.php'; ?>
