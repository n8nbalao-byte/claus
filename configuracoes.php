<?php
$page_title = "Configurações";
require_once 'header.php';
?>
<style>
/* ── TABS ── */
.cfg-tabs { display:flex; gap:0; border-bottom:2px solid var(--border-color); margin-bottom:20px; flex-wrap:wrap; }
.cfg-tab {
    padding:10px 18px; font-size:13.5px; font-weight:500; cursor:pointer;
    color:var(--text-secondary); border-bottom:2px solid transparent; margin-bottom:-2px;
    transition:color .13s, border-color .13s; white-space:nowrap;
    background:none; border-left:none; border-right:none; border-top:none; border-radius:0;
    display:flex; align-items:center; gap:7px;
}
.cfg-tab:hover { color:var(--text-primary); background:var(--wa-hover); }
.cfg-tab.active { color:var(--wa-green); border-bottom-color:var(--wa-green); font-weight:600; background:none; }
.cfg-tab svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }

.tab-pane { display:none; }
.tab-pane.active { display:block; }

/* ── SECTION CARDS ── */
.cfg-section { background:var(--card-bg); border:1px solid var(--border-color); border-radius:8px; margin-bottom:16px; overflow:hidden; }
.cfg-sec-hdr { padding:14px 18px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between; gap:10px; }
.cfg-sec-title { font-size:14px; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:8px; }
.cfg-sec-title svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; color:var(--wa-green); }
.cfg-sec-body { padding:18px; }

/* ── FORM FIELDS ── */
.field-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.field-grid.cols-1 { grid-template-columns:1fr; }
.field-grid.cols-3 { grid-template-columns:1fr 1fr 1fr; }
.field { display:flex; flex-direction:column; gap:5px; }
.field label { font-size:12px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.4px; }
.field input, .field select, .field textarea {
    width:100%; padding:9px 12px; border-radius:7px;
    border:1px solid var(--border-color); background:var(--bg-color);
    color:var(--text-primary); font-family:inherit; font-size:13.5px;
    outline:none; margin:0; transition:border-color .18s;
}
.field input:focus, .field select:focus, .field textarea:focus { border-color:var(--wa-green); }
.field textarea { min-height:80px; resize:vertical; }
.field .hint { font-size:11px; color:var(--text-secondary); }

/* Password toggle */
.pw-wrap { position:relative; }
.pw-wrap input { padding-right:38px; }
.pw-eye { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-secondary); padding:0; display:flex; }
.pw-eye svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; }
.pw-eye:hover { color:var(--text-primary); background:none; }

/* ── STATUS BADGES ── */
.status-row { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:7px; font-size:13px; }
.status-row.ok   { background:rgba(37,211,102,.08); border:1px solid rgba(37,211,102,.2); color:#25d366; }
.status-row.err  { background:rgba(239,68,68,.08);  border:1px solid rgba(239,68,68,.2);  color:#ef4444; }
.status-row.warn { background:rgba(245,158,11,.08); border:1px solid rgba(245,158,11,.2); color:#f59e0b; }
.status-row.info { background:rgba(83,189,235,.08); border:1px solid rgba(83,189,235,.2); color:#53bdeb; }
.status-row svg  { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; flex-shrink:0; }

/* ── BUTTONS ── */
.btn-row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:14px; }
.cfg-btn { display:flex; align-items:center; gap:6px; padding:9px 18px; border-radius:7px; font-size:13.5px; font-weight:600; cursor:pointer; font-family:inherit; border:none; transition:all .13s; }
.cfg-btn svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; }
.btn-primary  { background:var(--wa-green); color:#fff; }
.btn-primary:hover { background:var(--wa-green-dk); }
.btn-sec      { background:var(--wa-hover); color:var(--text-primary); border:1px solid var(--border-color); }
.btn-sec:hover{ background:var(--border-color); }
.btn-danger   { background:transparent; color:var(--danger); border:1px solid var(--danger); }
.btn-danger:hover { background:var(--danger); color:#fff; }
.btn-blue     { background:#3b82f6; color:#fff; }
.btn-blue:hover { background:#2563eb; }
.cfg-btn:disabled { opacity:.5; cursor:default; }

/* ── PROVIDER SELECTOR ── */
.prov-grid { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
.prov-btn { padding:7px 15px; border-radius:20px; border:1px solid var(--border-color); background:none; color:var(--text-secondary); font-size:13px; cursor:pointer; font-family:inherit; transition:all .13s; }
.prov-btn:hover { border-color:var(--wa-green); color:var(--wa-green); }
.prov-btn.active { background:var(--wa-green); color:#fff; border-color:var(--wa-green); }

/* ── EVENTS GRID ── */
.evt-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:6px; }
.evt-item { display:flex; align-items:center; gap:8px; padding:7px 10px; border-radius:6px; border:1px solid var(--border-color); background:var(--bg-color); cursor:pointer; transition:border-color .13s; font-size:12.5px; user-select:none; }
.evt-item:hover { border-color:var(--wa-green); }
.evt-item input[type=checkbox] { accent-color:var(--wa-green); width:14px; height:14px; }
.evt-item.checked { border-color:var(--wa-green); background:rgba(0,168,132,.05); }

/* ── RESPONSE LOG ── */
.res-log { background:var(--bg-color); border:1px solid var(--border-color); border-radius:7px; padding:12px 14px; font-family:monospace; font-size:12px; line-height:1.7; white-space:pre-wrap; word-break:break-word; max-height:220px; overflow-y:auto; display:none; margin-top:10px; }
.res-log.show { display:block; }
.res-ok   { color:#22c55e; }
.res-err  { color:#ef4444; }
.res-info { color:#53bdeb; }

/* ── SPINNER ── */
@keyframes spin { to{transform:rotate(360deg)} }
.spinning { animation:spin .8s linear infinite; }

/* ── DIVIDER ── */
.cfg-divider { height:1px; background:var(--border-color); margin:16px 0; }
</style>

<!-- TABS -->
<div class="cfg-tabs">
    <button class="cfg-tab active" onclick="showTab('evo',this)">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Evolution API
    </button>
    <button class="cfg-tab" onclick="showTab('ia',this)">
        <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
        Provedores IA
    </button>
    <button class="cfg-tab" onclick="showTab('db',this)">
        <svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
        Banco de Dados
    </button>
    <button class="cfg-tab" onclick="showTab('wh',this)">
        <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        Webhook
    </button>
</div>

<!-- ══════════════════ TAB: EVOLUTION API ══════════════════ -->
<div class="tab-pane active" id="tab-evo">

    <div class="cfg-section">
        <div class="cfg-sec-hdr">
            <div class="cfg-sec-title">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                Conexão Evolution API
            </div>
            <div id="evo-status-badge"></div>
        </div>
        <div class="cfg-sec-body">
            <div class="field-grid">
                <div class="field">
                    <label>Server URL</label>
                    <input type="text" id="evo_url" placeholder="http://IP:PORTA" value="">
                    <span class="hint">Ex: http://72.61.56.104:42199</span>
                </div>
                <div class="field">
                    <label>Global API Key</label>
                    <div class="pw-wrap">
                        <input type="password" id="evo_apikey" placeholder="Chave global da Evolution API">
                        <button class="pw-eye" type="button" onclick="togglePw('evo_apikey',this)"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    </div>
                </div>
                <div class="field">
                    <label>Instância</label>
                    <input type="text" id="evo_instance" placeholder="claus" value="">
                </div>
                <div class="field">
                    <label>Token da Instância</label>
                    <div class="pw-wrap">
                        <input type="password" id="evo_token" placeholder="Token específico da instância">
                        <button class="pw-eye" type="button" onclick="togglePw('evo_token',this)"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    </div>
                </div>
                <div class="field">
                    <label>Número WhatsApp</label>
                    <input type="text" id="evo_number" placeholder="5519999999999">
                    <span class="hint">Número completo com código do país, sem +</span>
                </div>
                <div class="field">
                    <label>Channel</label>
                    <select id="evo_channel">
                        <option value="evolution">evolution</option>
                        <option value="baileys">baileys</option>
                        <option value="cloud-api">cloud-api</option>
                    </select>
                </div>
            </div>

            <div class="btn-row">
                <button class="cfg-btn btn-primary" onclick="saveEvo()">
                    <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Salvar
                </button>
                <button class="cfg-btn btn-blue" onclick="testEvo()">
                    <svg id="evo-test-ico" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Testar Conexão
                </button>
                <button class="cfg-btn btn-sec" onclick="checkEvoStatus()">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Status Instância
                </button>
            </div>
            <div class="res-log" id="evo-log"></div>
        </div>
    </div>

</div>

<!-- ══════════════════ TAB: PROVEDORES IA ══════════════════ -->
<div class="tab-pane" id="tab-ia">

    <div class="cfg-section">
        <div class="cfg-sec-hdr">
            <div class="cfg-sec-title">
                <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                Provedor de IA Ativo
            </div>
        </div>
        <div class="cfg-sec-body">
            <label style="font-size:12px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;display:block">Selecionar Provedor</label>
            <div class="prov-grid" id="prov-grid">
                <button class="prov-btn" onclick="selectProv('openai',this)">OpenAI</button>
                <button class="prov-btn" onclick="selectProv('groq',this)">Groq</button>
                <button class="prov-btn" onclick="selectProv('gemini',this)">Gemini</button>
                <button class="prov-btn" onclick="selectProv('claude',this)">Claude</button>
                <button class="prov-btn" onclick="selectProv('together',this)">Together AI</button>
                <button class="prov-btn" onclick="selectProv('huggingface',this)">HuggingFace</button>
            </div>

            <div id="prov-fields"></div>

            <div class="btn-row">
                <button class="cfg-btn btn-primary" onclick="saveIA()">
                    <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Salvar
                </button>
                <button class="cfg-btn btn-blue" onclick="testIA()">
                    <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Testar Chave
                </button>
            </div>
            <div class="res-log" id="ia-log"></div>
        </div>
    </div>

</div>

<!-- ══════════════════ TAB: BANCO DE DADOS ══════════════════ -->
<div class="tab-pane" id="tab-db">

    <div class="cfg-section">
        <div class="cfg-sec-hdr">
            <div class="cfg-sec-title">
                <svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                Conexão MySQL
            </div>
        </div>
        <div class="cfg-sec-body">
            <div class="field-grid cols-3">
                <div class="field">
                    <label>Host</label>
                    <input type="text" id="db_host" placeholder="localhost">
                </div>
                <div class="field">
                    <label>Banco (dbname)</label>
                    <input type="text" id="db_name" placeholder="nome_do_banco">
                </div>
                <div class="field">
                    <label>Usuário</label>
                    <input type="text" id="db_user" placeholder="usuario_mysql">
                </div>
            </div>
            <div class="field-grid">
                <div class="field">
                    <label>Senha</label>
                    <div class="pw-wrap">
                        <input type="password" id="db_pass" placeholder="senha do banco">
                        <button class="pw-eye" type="button" onclick="togglePw('db_pass',this)"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    </div>
                </div>
                <div class="field">
                    <label>Porta</label>
                    <input type="text" id="db_port" placeholder="3306" value="3306">
                </div>
            </div>
            <div class="btn-row">
                <button class="cfg-btn btn-primary" onclick="saveDB()">
                    <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Salvar
                </button>
                <button class="cfg-btn btn-blue" onclick="testDB()">
                    <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Testar Conexão
                </button>
            </div>
            <div class="res-log" id="db-log"></div>
        </div>
    </div>

</div>

<!-- ══════════════════ TAB: WEBHOOK ══════════════════ -->
<div class="tab-pane" id="tab-wh">

    <div class="cfg-section">
        <div class="cfg-sec-hdr">
            <div class="cfg-sec-title">
                <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                Gerenciar Webhook
            </div>
        </div>
        <div class="cfg-sec-body">
            <div id="wh-current" class="status-row info" style="margin-bottom:14px">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Carregando status do webhook…
            </div>

            <div class="field-grid">
                <div class="field">
                    <label>URL do Webhook</label>
                    <input type="text" id="wh_url" value="https://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'n8nbalao.com') ?>/webhook.php" readonly style="background:var(--bg-color);color:var(--text-secondary)">
                    <span class="hint">URL fixa do servidor — não editar</span>
                </div>
                <div class="field">
                    <label>Instância</label>
                    <input type="text" id="wh_inst" readonly placeholder="(carregando da config)">
                </div>
            </div>

            <div style="margin:12px 0 8px">
                <label style="font-size:12px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:8px">Eventos Ativos</label>
                <div class="evt-grid" id="evt-grid">
                    <?php
                    $events = ['MESSAGES_UPSERT','MESSAGES_UPDATE','SEND_MESSAGE','CONNECTION_UPDATE',
                               'QRCODE_UPDATED','CONTACTS_UPSERT','CONTACTS_UPDATE','CHATS_UPSERT',
                               'CHATS_UPDATE','GROUPS_UPSERT','GROUP_UPDATE','GROUP_PARTICIPANTS_UPDATE','PRESENCE_UPDATE'];
                    $recommended = ['MESSAGES_UPSERT','MESSAGES_UPDATE','SEND_MESSAGE','CONNECTION_UPDATE'];
                    foreach($events as $ev):
                        $checked = in_array($ev, $recommended);
                    ?>
                    <label class="evt-item <?= $checked?'checked':'' ?>" id="evt-<?= $ev ?>">
                        <input type="checkbox" name="events[]" value="<?= $ev ?>" <?= $checked?'checked':'' ?> onchange="this.closest('.evt-item').classList.toggle('checked',this.checked)">
                        <?= $ev ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="btn-row">
                <button class="cfg-btn btn-primary" onclick="setWebhook()">
                    <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    Aplicar Webhook
                </button>
                <button class="cfg-btn btn-blue" onclick="checkWebhook()">
                    <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    Verificar Status
                </button>
                <button class="cfg-btn btn-sec" onclick="sendTestMsg()">
                    <svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Enviar Msg Teste
                </button>
            </div>
            <div class="res-log" id="wh-log"></div>
        </div>
    </div>

</div>

<script>
/* ── TABS ── */
function showTab(id,btn){
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.cfg-tab').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+id).classList.add('active');
    btn.classList.add('active');
}

/* ── PASSWORD TOGGLE ── */
function togglePw(id,btn){
    const el=document.getElementById(id);
    el.type=el.type==='password'?'text':'password';
    btn.style.opacity=el.type==='text'?'1':'.6';
}

/* ── LOG ── */
function log(id,html,type='info'){
    const el=document.getElementById(id);
    el.classList.add('show');
    el.innerHTML+='<span class="res-'+type+'">'+html+'</span>\n';
    el.scrollTop=el.scrollHeight;
}
function clearLog(id){ const el=document.getElementById(id); el.innerHTML=''; el.classList.remove('show'); }

/* ── LOAD CONFIG ── */
async function loadConfig(){
    try{
        const d=await fetchData('api.php?action=get_config');

        // EVO
        setVal('evo_url',      d.evolution_url||'');
        setVal('evo_apikey',   d.evolution_apikey||'');
        setVal('evo_instance', d.evolution_instance||'claus');
        setVal('evo_token',    d.evolution_token||'');
        setVal('evo_number',   d.evolution_number||d.admin_number||'');
        setVal('evo_channel',  d.evolution_channel||'evolution');

        // IA
        const prov=d.ai_provider||'openai';
        document.querySelectorAll('.prov-btn').forEach(b=>{
            b.classList.toggle('active', b.textContent.trim().toLowerCase()===prov.toLowerCase()||b.getAttribute('data-p')===prov);
        });
        renderProvFields(prov, d);

        // Webhook inst
        setVal('wh_inst', d.evolution_instance||'claus');

    }catch(e){ console.warn('loadConfig',e); }

    // DB credentials come from db.php, not agent_config
    try{
        const db = await fetchData('api.php?action=get_db_info');
        setVal('db_host', db.host || 'localhost');
        setVal('db_name', db.name || '');
        setVal('db_user', db.user || '');
        setVal('db_port', db.port || '3306');
        if (db.pass_set) {
            const passEl = document.getElementById('db_pass');
            if (passEl) passEl.placeholder = '••••••••  (já configurada)';
        }
        if (db.file_found) {
            const hint = document.getElementById('db-file-hint');
            if (hint) hint.textContent = 'db.php encontrado — campos preenchidos automaticamente';
        }
    }catch(e){ console.warn('loadDbInfo',e); }
}
function setVal(id,v){ const el=document.getElementById(id); if(el) el.value=v; }

/* ── IA PROVIDER ── */
let activeProv='openai';
const provMeta={
    openai:      {label:'OpenAI',      models:['gpt-4o','gpt-4o-mini','gpt-4-turbo','gpt-3.5-turbo'], url:'https://api.openai.com/v1'},
    groq:        {label:'Groq',        models:['llama-3.3-70b-versatile','llama-3.1-8b-instant','mixtral-8x7b-32768'], url:'https://api.groq.com/openai/v1'},
    gemini:      {label:'Gemini',      models:['gemini-1.5-pro','gemini-1.5-flash','gemini-2.0-flash'], url:'https://generativelanguage.googleapis.com/v1beta'},
    claude:      {label:'Claude',      models:['claude-sonnet-4-6','claude-haiku-4-5-20251001','claude-opus-4-6'], url:'https://api.anthropic.com/v1'},
    together:    {label:'Together AI', models:['meta-llama/Llama-3-70b-chat-hf','mistralai/Mixtral-8x7B-Instruct-v0.1'], url:'https://api.together.xyz/v1'},
    huggingface: {label:'HuggingFace', models:['mistralai/Mistral-7B-Instruct-v0.3','meta-llama/Meta-Llama-3-8B-Instruct'], url:'https://api-inference.huggingface.co'},
};

function selectProv(p,btn){
    activeProv=p;
    document.querySelectorAll('.prov-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    renderProvFields(p,{});
}
function renderProvFields(p,d){
    activeProv=p;
    const m=provMeta[p]||{models:[],url:''};
    const modelOpts=m.models.map(md=>`<option value="${md}" ${(d[p+'_model']||'')==md?'selected':''}>${md}</option>`).join('');
    document.getElementById('prov-fields').innerHTML=`
    <div class="field-grid" style="margin-top:10px">
        <div class="field">
            <label>API Key — ${m.label}</label>
            <div class="pw-wrap">
                <input type="password" id="ia_key" placeholder="sk-..." value="${escAttr(d[p+'_apikey']||'')}">
                <button class="pw-eye" type="button" onclick="togglePw('ia_key',this)"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
        </div>
        <div class="field">
            <label>Modelo</label>
            <select id="ia_model"><option value="">— selecionar —</option>${modelOpts}</select>
        </div>
        <div class="field cols-1" style="grid-column:1/-1">
            <label>Custom Model (opcional)</label>
            <input type="text" id="ia_model_custom" placeholder="Digite um modelo personalizado">
            <span class="hint">Substitui o selecionado acima se preenchido</span>
        </div>
    </div>`;
}
function escAttr(s){ return String(s).replace(/"/g,'&quot;'); }

/* ── SAVE / TEST helpers ── */
async function apiPost(action, data){
    return postData('api.php?action='+action, data);
}

/* ── SAVE EVO ── */
async function saveEvo(){
    clearLog('evo-log');
    const data={
        evolution_url:      document.getElementById('evo_url').value.trim(),
        evolution_apikey:   document.getElementById('evo_apikey').value.trim(),
        evolution_instance: document.getElementById('evo_instance').value.trim()||'claus',
        evolution_token:    document.getElementById('evo_token').value.trim(),
        evolution_number:   document.getElementById('evo_number').value.trim(),
        evolution_channel:  document.getElementById('evo_channel').value,
    };
    log('evo-log','Salvando configurações…','info');
    try{
        const r=await apiPost('save_config', data);
        if(r.status==='success') log('evo-log','✓ Salvo com sucesso!','ok');
        else log('evo-log','✗ Erro: '+(r.message||JSON.stringify(r)),'err');
    }catch(e){ log('evo-log','✗ Erro de comunicação: '+e.message,'err'); }
}

/* ── TEST EVO ── */
async function testEvo(){
    clearLog('evo-log');
    const ico=document.getElementById('evo-test-ico');
    ico.classList.add('spinning');
    const url=(document.getElementById('evo_url').value||'').trim();
    const key=(document.getElementById('evo_apikey').value||'').trim();
    const inst=(document.getElementById('evo_instance').value||'claus').trim();
    if(!url||!key){ log('evo-log','⚠ Preencha a URL e a API Key antes de testar.','err'); ico.classList.remove('spinning'); return; }
    log('evo-log','Testando conexão com '+url+' …','info');
    try{
        const r=await postData('api.php?action=test_evolution',{url,apikey:key,instance:inst});
        if(r.status==='ok'||r.connected){
            log('evo-log','✓ Conectado! Instância: '+inst+' | Status: '+(r.state||'connected'),'ok');
            document.getElementById('evo-status-badge').innerHTML='<span style="color:#22c55e;font-size:12px;font-weight:600">● Conectado</span>';
        } else {
            log('evo-log','✗ Falha: '+(r.message||JSON.stringify(r)),'err');
        }
    }catch(e){ log('evo-log','✗ Erro: '+e.message,'err'); }
    ico.classList.remove('spinning');
}

async function checkEvoStatus(){
    clearLog('evo-log');
    const url=(document.getElementById('evo_url').value||'').trim();
    const key=(document.getElementById('evo_apikey').value||'').trim();
    const inst=(document.getElementById('evo_instance').value||'claus').trim();
    log('evo-log','Consultando status da instância '+inst+' …','info');
    try{
        const r=await postData('api.php?action=test_evolution',{url,apikey:key,instance:inst,full:true});
        log('evo-log',JSON.stringify(r,null,2),'info');
    }catch(e){ log('evo-log','✗ '+e.message,'err'); }
}

/* ── SAVE DB ── */
async function saveDB(){
    clearLog('db-log');
    const pass = document.getElementById('db_pass').value.trim();
    const data={
        db_host: document.getElementById('db_host').value.trim(),
        db_name: document.getElementById('db_name').value.trim(),
        db_user: document.getElementById('db_user').value.trim(),
        db_pass: pass,
        db_port: document.getElementById('db_port').value.trim()||'3306',
    };
    if(!data.db_name||!data.db_user){ log('db-log','⚠ Banco e Usuário são obrigatórios','err'); return; }
    if(!pass){ log('db-log','⚠ Senha não preenchida — deixe em branco para manter a atual, ou preencha para alterar.','info'); }
    log('db-log','Salvando credenciais em db.php…','info');
    try{
        // Only send pass if user typed something; otherwise preserve existing
        const payload = pass ? data : {...data, db_pass: '__KEEP__'};
        const r=await postData('api.php?action=save_db_config', payload);
        if(r.status==='success'){
            log('db-log','✓ db.php atualizado! Backup: '+(r.backup||'—'),'ok');
            log('db-log','⚠ Recarregue a página para confirmar que a conexão continua ativa.','info');
        } else {
            log('db-log','✗ '+(r.message||'erro'),'err');
        }
    }catch(e){ log('db-log','✗ '+e.message,'err'); }
}

async function testDB(){
    clearLog('db-log');
    log('db-log','Testando conexão com banco…','info');
    try{
        const r=await fetchData('api.php?action=check_db_status');
        if(r.status==='connected') log('db-log','✓ Banco conectado!','ok');
        else log('db-log','✗ Erro: '+(r.message||'desconectado'),'err');
    }catch(e){ log('db-log','✗ '+e.message,'err'); }
}

/* ── SAVE IA ── */
async function saveIA(){
    clearLog('ia-log');
    const key=(document.getElementById('ia_key')?.value||'').trim();
    const model=(document.getElementById('ia_model_custom')?.value||document.getElementById('ia_model')?.value||'').trim();
    const data={
        ai_provider: activeProv,
        [activeProv+'_apikey']: key,
        [activeProv+'_model']:  model,
    };
    log('ia-log','Salvando provedor '+activeProv+' …','info');
    try{
        const r=await apiPost('save_config',data);
        log('ia-log', r.status==='success'?'✓ Salvo!':'✗ '+(r.message||''), r.status==='success'?'ok':'err');
    }catch(e){ log('ia-log','✗ '+e.message,'err'); }
}

async function testIA(){
    clearLog('ia-log');
    const key=(document.getElementById('ia_key')?.value||'').trim();
    if(!key){ log('ia-log','⚠ Preencha a API Key primeiro.','err'); return; }
    log('ia-log','Testando chave '+activeProv+' …','info');
    try{
        const r=await postData('api.php?action=test_ai_key',{provider:activeProv,apikey:key});
        log('ia-log', r.status==='ok'?'✓ Chave válida! Modelo: '+(r.model||'—'):'✗ '+(r.message||'inválida'), r.status==='ok'?'ok':'err');
    }catch(e){ log('ia-log','Não foi possível testar automaticamente. Verifique manualmente.','info'); }
}

/* ── WEBHOOK ── */
async function checkWebhook(){
    clearLog('wh-log');
    const url=(document.getElementById('evo_url').value||'').trim();
    const key=(document.getElementById('evo_apikey').value||'').trim();
    const inst=(document.getElementById('wh_inst').value||'claus').trim();
    log('wh-log','Verificando webhook da instância '+inst+' …','info');
    try{
        const r=await postData('api.php?action=get_webhook_status',{url,apikey:key,instance:inst});
        const whUrl=r.url||r.webhook?.url;
        const enabled=r.enabled??r.webhook?.enabled;
        const evts=(r.events||r.webhook?.events||[]).join(', ');
        const expected='https://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'n8nbalao.com') ?>/webhook.php';
        const match=whUrl&&whUrl.replace(/\/$/,'')===expected.replace(/\/$/,'');
        const statusEl=document.getElementById('wh-current');
        if(match&&enabled!==false){
            statusEl.className='status-row ok';
            statusEl.innerHTML='<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Webhook ativo e apontando para este servidor';
        } else if(whUrl) {
            statusEl.className='status-row warn';
            statusEl.innerHTML='<svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Webhook aponta para URL diferente: '+whUrl;
        } else {
            statusEl.className='status-row err';
            statusEl.innerHTML='<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Nenhum webhook configurado';
        }
        log('wh-log','URL: '+(whUrl||'não configurada')+'\nEnabled: '+enabled+'\nEventos: '+(evts||'nenhum'),'info');
    }catch(e){ log('wh-log','✗ '+e.message,'err'); }
}

async function setWebhook(){
    clearLog('wh-log');
    const url=(document.getElementById('evo_url').value||'').trim();
    const key=(document.getElementById('evo_apikey').value||'').trim();
    const inst=(document.getElementById('wh_inst').value||document.getElementById('evo_instance').value||'claus').trim();
    const whUrl=document.getElementById('wh_url').value;
    const events=[...document.querySelectorAll('#evt-grid input:checked')].map(c=>c.value);
    if(!url||!key){ log('wh-log','⚠ Configure a Evolution API primeiro (aba Evolution API).','err'); return; }
    if(!events.length){ log('wh-log','⚠ Selecione pelo menos um evento.','err'); return; }
    log('wh-log','Configurando webhook na instância '+inst+' …\nURL: '+whUrl+'\nEventos: '+events.join(', '),'info');
    try{
        const r=await postData('api.php?action=set_webhook',{url,apikey:key,instance:inst,webhook_url:whUrl,events});
        if(r.status==='ok'||r.webhook?.url){
            log('wh-log','✓ Webhook configurado com sucesso!\n'+JSON.stringify(r,null,2),'ok');
            document.getElementById('wh-current').className='status-row ok';
            document.getElementById('wh-current').innerHTML='<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Webhook ativo!';
        } else {
            log('wh-log','✗ Resposta inesperada:\n'+JSON.stringify(r,null,2),'err');
        }
    }catch(e){ log('wh-log','✗ '+e.message,'err'); }
}

async function sendTestMsg(){
    clearLog('wh-log');
    const url=(document.getElementById('evo_url').value||'').trim();
    const key=(document.getElementById('evo_apikey').value||'').trim();
    const inst=(document.getElementById('evo_instance').value||'claus').trim();
    const num=(document.getElementById('evo_number').value||'').trim();
    if(!num){ log('wh-log','⚠ Configure o Número WhatsApp na aba Evolution API.','err'); return; }
    log('wh-log','Enviando mensagem de teste para '+num+' …','info');
    try{
        const r=await postData('api.php?action=send_test_message',{url,apikey:key,instance:inst,number:num});
        log('wh-log', r.status==='ok'||r.key?'✓ Enviado! Verifique o WhatsApp.':'✗ '+(r.message||JSON.stringify(r)), r.status==='ok'||r.key?'ok':'err');
    }catch(e){ log('wh-log','✗ '+e.message,'err'); }
}

/* ── SYNC wh_inst when evo_instance changes ── */
document.getElementById('evo_instance')?.addEventListener('input',function(){ setVal('wh_inst',this.value); });

/* ── INIT ── */
window.addEventListener('load',()=>{
    loadConfig();
    // Auto-check webhook status after load
    setTimeout(()=>checkWebhook(),1200);
});
</script>

<?php require_once 'footer.php'; ?>
