<?php
$page_title = "Identidade do Agente";
require 'header.php';
?>

<div class="card">
    <h3 style="margin-top: 0; color: var(--text-secondary);">Cérebro do Agente (IA)</h3>
    
    <div style="margin-bottom: 20px;">
        <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Provedor de IA</label>
        <div style="display: flex; gap: 10px;">
            <button id="btn-openai" class="provider-btn active" onclick="setProvider('openai')">OpenAI</button>
            <button id="btn-groq" class="provider-btn" onclick="setProvider('groq')">Groq (Gratuito)</button>
        </div>
        <input type="hidden" id="ai-provider" value="openai">
    </div>

    <!-- OpenAI Configs -->
    <div id="config-openai" class="provider-config">
        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 2;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">OpenAI API Key</label>
                <input type="password" id="openai-apikey" placeholder="sk-..." style="margin-bottom: 0;">
            </div>
            <div style="flex: 1;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Modelo</label>
                <select id="openai-model" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--bg-color); color: var(--text-primary);">
                    <option value="gpt-4o-mini">GPT-4o Mini</option>
                    <option value="gpt-4o">GPT-4o</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Groq Configs -->
    <div id="config-groq" class="provider-config" style="display: none;">
        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 2;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Groq API Key</label>
                <input type="password" id="groq-apikey" placeholder="gsk_..." style="margin-bottom: 0;">
                <small style="color: var(--text-secondary);">Obtenha grátis em <a href="https://console.groq.com/keys" target="_blank">console.groq.com</a></small>
            </div>
            <div style="flex: 1;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Modelo</label>
                <select id="groq-model" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--bg-color); color: var(--text-primary);">
                    <option value="llama-3.3-70b-versatile">Llama 3.3 70B (Recomendado)</option>
                    <option value="llama-3.1-70b-versatile">Llama 3.1 70B</option>
                    <option value="llama-3.1-8b-instant">Llama 3.1 8B (Rápido)</option>
                    <option value="mixtral-8x7b-32768">Mixtral 8x7B</option>
                </select>
            </div>
        </div>
    </div>

    <h3 style="margin-top: 20px; color: var(--text-secondary);">Prompt do Sistema</h3>
    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 15px;">Defina a personalidade e as regras principais do Claus.</p>
    <textarea id="main-prompt" placeholder="Carregando prompt..."></textarea>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <h3 style="color: var(--text-secondary);">Informações do Usuário (Admin)</h3>
            <textarea id="usuario-info" placeholder="Info do Admin..." style="min-height: 100px;"></textarea>
        </div>
        <div style="flex: 1;">
            <h3 style="color: var(--text-secondary);">Dados do Agente</h3>
            <textarea id="agente-info" placeholder="Info do Agente..." style="min-height: 100px;"></textarea>
        </div>
    </div>

    <div style="text-align: right;">
        <button onclick="savePrompt()">Salvar Alterações</button>
    </div>
</div>

<div class="card">
    <h3 style="margin-top: 0; color: var(--text-secondary);">Administradores</h3>
    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <input type="text" id="admin-name" placeholder="Nome" style="flex: 1; margin-bottom: 0;">
        <input type="text" id="admin-phone" placeholder="Número (ex: 5511999999999)" style="flex: 1; margin-bottom: 0;">
        <button onclick="addAdmin()">+ Adicionar</button>
    </div>
    <table id="admin-table">
        <thead><tr><th>Nome</th><th>Número</th><th>Ação</th></tr></thead>
        <tbody></tbody>
    </table>
</div>

<script>
    async function loadPrompt() {
        const data = await fetchData('api.php?action=get_config');
        document.getElementById('main-prompt').value = data.main_prompt || '';
        document.getElementById('usuario-info').value = data.usuario_info || '';
        document.getElementById('agente-info').value = data.agente_info || '';
        document.getElementById('openai-apikey').value = data.openai_apikey || '';
        document.getElementById('openai-model').value = data.openai_model || 'gpt-4o-mini';
        document.getElementById('groq-apikey').value = data.groq_apikey || '';
        document.getElementById('groq-model').value = data.groq_model || 'llama-3.3-70b-versatile';
        setProvider(data.ai_provider || 'openai');
    }

    function setProvider(provider) {
        document.getElementById('ai-provider').value = provider;
        document.querySelectorAll('.provider-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`btn-${provider}`).classList.add('active');
        document.querySelectorAll('.provider-config').forEach(div => div.style.display = 'none');
        document.getElementById(`config-${provider}`).style.display = 'block';
    }

    async function savePrompt() {
        const btn = event.target;
        const originalText = btn.innerText;
        btn.innerText = 'Salvando...';
        const payload = {
            prompt: document.getElementById('main-prompt').value,
            usuario_info: document.getElementById('usuario-info').value,
            agente_info: document.getElementById('agente-info').value,
            openai_apikey: document.getElementById('openai-apikey').value,
            openai_model: document.getElementById('openai-model').value,
            groq_apikey: document.getElementById('groq-apikey').value,
            groq_model: document.getElementById('groq-model').value,
            ai_provider: document.getElementById('ai-provider').value
        };
        await postData('api.php?action=save_prompt', payload);
        btn.innerText = 'Salvo!';
        setTimeout(() => btn.innerText = originalText, 2000);
    }

    async function loadAdmins() {
        const admins = await fetchData('api.php?action=get_admins');
        const tbody = document.querySelector('#admin-table tbody');
        tbody.innerHTML = '';
        admins.forEach(admin => {
            tbody.innerHTML += `<tr>
                <td><b>${admin.name}</b></td>
                <td style="font-family: monospace;">${admin.phone_number}</td>
                <td><button class="btn-outline" onclick="deleteAdmin(${admin.id})">Remover</button></td>
            </tr>`;
        });
    }

    async function addAdmin() {
        const name = document.getElementById('admin-name').value;
        const phone = document.getElementById('admin-phone').value;
        if (!name || !phone) return alert('Preencha todos os campos');
        const result = await postData('api.php?action=add_admin', { name, phone });
        if (result.status === 'success') {
            alert(result.message);
            loadAdmins();
            document.getElementById('admin-name').value = '';
            document.getElementById('admin-phone').value = '';
        } else { alert('Erro: ' + (result.message || 'Falha desconhecida')); }
    }

    async function deleteAdmin(id) {
        if(confirm('Remover admin?')) {
            await postData('api.php?action=delete_admin', { id });
            loadAdmins();
        }
    }

    window.addEventListener('load', () => {
        loadPrompt();
        loadAdmins();
    });
</script>

<?php require_once 'footer.php'; ?>
