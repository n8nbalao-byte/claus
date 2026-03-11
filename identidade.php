<?php
$page_title = "Identidade do Agente";
require 'header.php';
?>

<div class="card">
    <h3 style="margin-top: 0; color: var(--text-secondary);">Cérebro do Agente (IA)</h3>
    
    <div style="margin-bottom: 20px;">
        <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Provedor de IA</label>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button id="btn-openai" class="provider-btn active" onclick="setProvider('openai')">OpenAI</button>
            <button id="btn-groq" class="provider-btn" onclick="setProvider('groq')">Groq (Gratuito)</button>
            <button id="btn-gemini" class="provider-btn" onclick="setProvider('gemini')">Gemini (Gratuito)</button>
            <button id="btn-claude" class="provider-btn" onclick="setProvider('claude')">Claude (Gratuito)</button>
            <button id="btn-huggingface" class="provider-btn" onclick="setProvider('huggingface')">Hugging Face (Gratuito)</button>
            <button id="btn-together" class="provider-btn" onclick="setProvider('together')">Together AI (Gratuito)</button>
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
                </select>
            </div>
        </div>
    </div>

    <!-- Gemini Configs -->
    <div id="config-gemini" class="provider-config" style="display: none;">
        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 2;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Google AI API Key</label>
                <input type="password" id="gemini-apikey" placeholder="AIza..." style="margin-bottom: 0;">
                <small style="color: var(--text-secondary);">Obtenha grátis em <a href="https://makersuite.google.com/app/apikey" target="_blank">makersuite.google.com</a></small>
            </div>
            <div style="flex: 1;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Modelo</label>
                <select id="gemini-model" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--bg-color); color: var(--text-primary);">
                    <option value="gemini-1.5-flash">Gemini 1.5 Flash (Recomendado)</option>
                    <option value="gemini-1.5-pro">Gemini 1.5 Pro</option>
                    <option value="gemini-1.0-pro">Gemini 1.0 Pro</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Claude Configs -->
    <div id="config-claude" class="provider-config" style="display: none;">
        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 2;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Anthropic API Key</label>
                <input type="password" id="claude-apikey" placeholder="sk-ant-..." style="margin-bottom: 0;">
                <small style="color: var(--text-secondary);">Obtenha grátis em <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></small>
            </div>
            <div style="flex: 1;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Modelo</label>
                <select id="claude-model" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--bg-color); color: var(--text-primary);">
                    <option value="claude-3-haiku-20240307">Claude 3 Haiku (Rápido)</option>
                    <option value="claude-3-sonnet-20240229">Claude 3 Sonnet (Recomendado)</option>
                    <option value="claude-3-opus-20240229">Claude 3 Opus</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Hugging Face Configs -->
    <div id="config-huggingface" class="provider-config" style="display: none;">
        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 2;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Hugging Face API Key</label>
                <input type="password" id="huggingface-apikey" placeholder="hf_..." style="margin-bottom: 0;">
                <small style="color: var(--text-secondary);">Obtenha grátis em <a href="https://huggingface.co/settings/tokens" target="_blank">huggingface.co/settings/tokens</a></small>
            </div>
            <div style="flex: 1;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Modelo</label>
                <select id="huggingface-model" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--bg-color); color: var(--text-primary);">
                    <option value="microsoft/DialoGPT-medium">DialoGPT Medium</option>
                    <option value="facebook/blenderbot-400M-distill">BlenderBot</option>
                    <option value="microsoft/DialoGPT-large">DialoGPT Large</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Together AI Configs -->
    <div id="config-together" class="provider-config" style="display: none;">
        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 2;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Together AI API Key</label>
                <input type="password" id="together-apikey" placeholder="..." style="margin-bottom: 0;">
                <small style="color: var(--text-secondary);">Obtenha grátis em <a href="https://api.together.xyz/settings/api-keys" target="_blank">api.together.xyz</a></small>
            </div>
            <div style="flex: 1;">
                <label style="font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 5px;">Modelo</label>
                <select id="together-model" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--bg-color); color: var(--text-primary);">
                    <option value="meta-llama/Llama-3.3-70B-Instruct-Turbo">Llama 3.3 70B (Recomendado)</option>
                    <option value="meta-llama/Llama-4-Maverick-17B-128E-Instruct-FP8">Llama 4 Maverick 17B</option>
                    <option value="meta-llama/Meta-Llama-3.1-8B-Instruct-Turbo">Llama 3.1 8B Turbo</option>
                    <option value="meta-llama/Meta-Llama-3-8B-Instruct-Lite">Llama 3 8B Lite</option>
                    <option value="meta-llama/Llama-3.1-70B-Instruct-Turbo">Llama 3.1 70B</option>
                    <option value="meta-llama/Llama-3.1-8B-Instruct-Turbo">Llama 3.1 8B</option>
                </select>
            </div>
        </div>
    </div>
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
        document.getElementById('gemini-apikey').value = data.gemini_apikey || '';
        document.getElementById('gemini-model').value = data.gemini_model || 'gemini-1.5-flash';
        document.getElementById('claude-apikey').value = data.claude_apikey || '';
        document.getElementById('claude-model').value = data.claude_model || 'claude-3-haiku-20240307';
        document.getElementById('huggingface-apikey').value = data.huggingface_apikey || '';
        document.getElementById('huggingface-model').value = data.huggingface_model || 'microsoft/DialoGPT-medium';
        document.getElementById('together-apikey').value = data.together_apikey || '';
        document.getElementById('together-model').value = data.together_model || 'meta-llama/Llama-3.3-70B-Instruct-Turbo';
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
            gemini_apikey: document.getElementById('gemini-apikey').value,
            gemini_model: document.getElementById('gemini-model').value,
            claude_apikey: document.getElementById('claude-apikey').value,
            claude_model: document.getElementById('claude-model').value,
            huggingface_apikey: document.getElementById('huggingface-apikey').value,
            huggingface_model: document.getElementById('huggingface-model').value,
            together_apikey: document.getElementById('together-apikey').value,
            together_model: document.getElementById('together-model').value,
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
