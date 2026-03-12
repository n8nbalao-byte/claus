<?php
$page_title   = "Agenda";
$current_page = 'agenda';
require_once 'header.php';
?>
<style>
/* ── TOOLBAR ── */
.ag-toolbar { display:flex; align-items:center; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.ag-search  { flex:1; min-width:200px; padding:9px 13px; border-radius:7px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-primary); font-size:13.5px; font-family:inherit; outline:none; }
.ag-search:focus { border-color:var(--wa-green); }
.ag-btn { display:flex; align-items:center; gap:6px; padding:8px 16px; border-radius:7px; font-size:13.5px; font-weight:600; cursor:pointer; font-family:inherit; border:none; transition:all .13s; white-space:nowrap; }
.ag-btn svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; }
.btn-green  { background:var(--wa-green); color:#fff; }
.btn-green:hover { background:var(--wa-green-dk); }
.btn-blue   { background:#3b82f6; color:#fff; }
.btn-blue:hover { background:#2563eb; }
.btn-sec    { background:var(--wa-hover); color:var(--text-primary); border:1px solid var(--border-color); }
.btn-sec:hover { background:var(--border-color); }
.btn-red    { background:transparent; color:var(--danger); border:1px solid var(--danger); padding:6px 12px; font-size:12px; }
.btn-red:hover { background:var(--danger); color:#fff; }

/* ── STATS ROW ── */
.ag-stats { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.stat-pill { padding:5px 14px; border-radius:20px; font-size:12.5px; background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-secondary); }
.stat-pill strong { color:var(--wa-green); }

/* ── CONTACT GRID ── */
.ag-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:12px; }

/* ── CONTACT CARD ── */
.ag-card { background:var(--card-bg); border:1px solid var(--border-color); border-radius:8px; overflow:hidden; transition:border-color .13s; }
.ag-card:hover { border-color:var(--wa-green); }
.ag-card-top { padding:14px 14px 10px; display:flex; align-items:flex-start; gap:12px; }
.ag-avatar { width:42px; height:42px; border-radius:50%; background:var(--wa-green); display:flex; align-items:center; justify-content:center; font-size:17px; font-weight:700; color:#fff; flex-shrink:0; text-transform:uppercase; }
.ag-info { flex:1; min-width:0; }
.ag-name { font-size:14px; font-weight:600; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ag-phone { font-size:12.5px; color:var(--text-secondary); font-family:monospace; margin-top:1px; }
.ag-rel { display:inline-block; margin-top:4px; padding:2px 8px; border-radius:10px; font-size:11.5px; font-weight:600; background:rgba(0,168,132,.1); color:var(--wa-green); }
.ag-notes { padding:0 14px 10px; font-size:12.5px; color:var(--text-secondary); line-height:1.5; border-top:1px solid var(--border-color); padding-top:10px; white-space:pre-wrap; }
.ag-footer { padding:8px 14px; border-top:1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between; background:var(--wa-hover); }
.ag-source { font-size:11px; color:var(--text-secondary); display:flex; align-items:center; gap:4px; }
.ag-source svg { width:11px; height:11px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; }
.ag-actions { display:flex; gap:6px; }
.icon-btn { background:none; border:none; cursor:pointer; color:var(--text-secondary); padding:3px; border-radius:4px; transition:color .13s; display:flex; }
.icon-btn:hover { color:var(--wa-green); }
.icon-btn.del:hover { color:var(--danger); }
.icon-btn svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; }

/* ── MODAL ── */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:100; display:none; align-items:center; justify-content:center; padding:20px; }
.modal-overlay.show { display:flex; }
.modal { background:var(--card-bg); border:1px solid var(--border-color); border-radius:10px; width:100%; max-width:480px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.3); }
.modal-hdr { padding:16px 18px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between; }
.modal-title { font-size:15px; font-weight:600; color:var(--text-primary); }
.modal-close { background:none; border:none; cursor:pointer; color:var(--text-secondary); font-size:20px; line-height:1; }
.modal-body { padding:18px; }
.field { display:flex; flex-direction:column; gap:5px; margin-bottom:12px; }
.field label { font-size:12px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.4px; }
.field input, .field select, .field textarea {
    padding:9px 12px; border-radius:7px; border:1px solid var(--border-color);
    background:var(--bg-color); color:var(--text-primary); font-family:inherit; font-size:13.5px; outline:none;
}
.field input:focus, .field select:focus, .field textarea:focus { border-color:var(--wa-green); }
.field textarea { min-height:80px; resize:vertical; }
.modal-footer { padding:12px 18px; border-top:1px solid var(--border-color); display:flex; gap:8px; justify-content:flex-end; }

/* ── EXTRACT PANEL ── */
.extract-card { background:var(--card-bg); border:1px dashed var(--wa-green); border-radius:8px; padding:14px 16px; margin-bottom:16px; display:none; }
.extract-card.show { display:block; }
.ext-row { display:flex; align-items:center; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border-color); font-size:13px; }
.ext-row:last-child { border-bottom:none; }

/* ── EMPTY STATE ── */
.empty-state { text-align:center; padding:48px 20px; color:var(--text-secondary); }
.empty-state svg { width:48px; height:48px; fill:none; stroke:currentColor; stroke-width:1.2; margin-bottom:12px; opacity:.4; }

/* ── TOAST ── */
.ag-toast { position:fixed; bottom:24px; right:24px; padding:10px 18px; border-radius:8px; font-size:13.5px; font-weight:600; color:#fff; z-index:200; opacity:0; transform:translateY(10px); transition:all .25s; pointer-events:none; }
.ag-toast.show { opacity:1; transform:translateY(0); }
.t-ok  { background:#00a884; }
.t-err { background:#ef4444; }
</style>

<!-- TOOLBAR -->
<div class="ag-toolbar">
    <input type="text" class="ag-search" id="ag-search" placeholder="Buscar por nome, telefone ou anotação…" oninput="filterContacts(this.value)">
    <button class="ag-btn btn-blue" onclick="extractFromLogs()">
        <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Extrair das Conversas
    </button>
    <button class="ag-btn btn-green" onclick="openModal()">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Novo Contato
    </button>
</div>

<!-- EXTRACT SUGGESTIONS -->
<div class="extract-card" id="extract-panel">
    <div style="font-size:13.5px;font-weight:600;color:var(--wa-green);margin-bottom:10px">
        📥 Novos números encontrados nas conversas — deseja salvar na agenda?
    </div>
    <div id="extract-list"></div>
    <div style="margin-top:10px;display:flex;gap:8px">
        <button class="ag-btn btn-green" onclick="saveAllExtracted()">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            Salvar Todos
        </button>
        <button class="ag-btn btn-sec" onclick="document.getElementById('extract-panel').classList.remove('show')">Fechar</button>
    </div>
</div>

<!-- STATS -->
<div class="ag-stats" id="ag-stats"></div>

<!-- GRID -->
<div class="ag-grid" id="ag-grid">
    <div style="color:var(--text-secondary);grid-column:1/-1;padding:20px 0">Carregando agenda…</div>
</div>

<!-- MODAL: ADD / EDIT -->
<div class="modal-overlay" id="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-hdr">
            <span class="modal-title" id="modal-title">Novo Contato</span>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit-id">
            <div class="field">
                <label>Nome</label>
                <input type="text" id="f-name" placeholder="Nome do contato">
            </div>
            <div class="field">
                <label>Telefone *</label>
                <input type="text" id="f-phone" placeholder="5519999999999">
            </div>
            <div class="field">
                <label>Relação / Categoria</label>
                <select id="f-rel">
                    <option value="">— selecionar —</option>
                    <option value="cliente">Cliente</option>
                    <option value="fornecedor">Fornecedor</option>
                    <option value="parceiro">Parceiro</option>
                    <option value="admin">Administrador</option>
                    <option value="outro">Outro</option>
                </select>
            </div>
            <div class="field">
                <label>Anotações</label>
                <textarea id="f-notes" placeholder="Informações relevantes sobre este contato…&#10;Cada linha é preservada e adicionada ao histórico."></textarea>
            </div>
            <div style="font-size:11.5px;color:var(--text-secondary);padding:6px 0">
                💡 Anotações são SEMPRE adicionadas — nunca substituídas por edições anteriores.
            </div>
        </div>
        <div class="modal-footer">
            <button class="ag-btn btn-sec" onclick="closeModal()">Cancelar</button>
            <button class="ag-btn btn-green" onclick="saveContact()">
                <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                Salvar
            </button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="ag-toast" id="ag-toast"></div>

<script>
let allContacts   = [];
let extractedNew  = [];

/* ── TOAST ── */
function toast(msg, type='ok') {
    const el = document.getElementById('ag-toast');
    el.textContent = msg;
    el.className = 'ag-toast show ' + (type==='ok' ? 't-ok' : 't-err');
    setTimeout(() => el.classList.remove('show'), 2800);
}

/* ── LOAD ── */
async function loadContacts() {
    try {
        allContacts = await fetchData('api.php?action=get_agenda');
        renderGrid(allContacts);
        renderStats(allContacts);
    } catch(e) { console.warn(e); }
}

/* ── RENDER GRID ── */
function renderGrid(contacts) {
    const grid = document.getElementById('ag-grid');
    if (!contacts.length) {
        grid.innerHTML = `
        <div class="empty-state" style="grid-column:1/-1">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            <div style="font-size:14px;font-weight:600;margin-bottom:6px">Agenda vazia</div>
            <div style="font-size:13px">Use "Extrair das Conversas" para importar automaticamente os contatos que já interagiram com o Claus.</div>
        </div>`;
        return;
    }
    grid.innerHTML = contacts.map(c => {
        const initials = (c.name||c.phone_number||'?').split(' ').slice(0,2).map(w=>w[0]).join('').toUpperCase();
        const colorIdx = (c.phone_number||'').length % 6;
        const colors   = ['#00a884','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#06b6d4'];
        const color    = colors[colorIdx];
        const relLabel = c.relationship || '';
        const sourceIcon = c.source === 'auto' 
            ? '<svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Auto-extraído'
            : '<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Manual';
        return `
        <div class="ag-card" id="card-${c.id}">
            <div class="ag-card-top">
                <div class="ag-avatar" style="background:${color}">${initials}</div>
                <div class="ag-info">
                    <div class="ag-name" title="${escHtml(c.name||'')}">
                        ${escHtml(c.name || c.phone_number || '—')}
                    </div>
                    <div class="ag-phone">${escHtml(c.phone_number||'')}</div>
                    ${relLabel ? `<span class="ag-rel">${escHtml(relLabel)}</span>` : ''}
                </div>
            </div>
            ${c.notes ? `<div class="ag-notes">${escHtml(c.notes)}</div>` : ''}
            <div class="ag-footer">
                <span class="ag-source">${sourceIcon}</span>
                <div class="ag-actions">
                    <button class="icon-btn" onclick='openWhatsApp("${c.phone_number}")' title="Abrir no WhatsApp">
                        <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    </button>
                    <button class="icon-btn" onclick='editContact(${JSON.stringify(c).replace(/'/g,"&#39;")})' title="Editar">
                        <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="icon-btn del" onclick="deleteContact(${c.id},'${escHtml(c.name||c.phone_number)}')" title="Excluir">
                        <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </button>
                </div>
            </div>
        </div>`;
    }).join('');
}

/* ── STATS ── */
function renderStats(contacts) {
    const stats = document.getElementById('ag-stats');
    const total  = contacts.length;
    const auto   = contacts.filter(c => c.source === 'auto').length;
    const manual = contacts.filter(c => c.source !== 'auto').length;
    const rels   = {};
    contacts.forEach(c => { if(c.relationship) rels[c.relationship] = (rels[c.relationship]||0)+1; });
    let html = `<span class="stat-pill">Total: <strong>${total}</strong></span>`;
    if (auto)   html += `<span class="stat-pill">Auto: <strong>${auto}</strong></span>`;
    if (manual) html += `<span class="stat-pill">Manual: <strong>${manual}</strong></span>`;
    Object.entries(rels).forEach(([k,v]) => { html += `<span class="stat-pill">${k}: <strong>${v}</strong></span>`; });
    stats.innerHTML = html;
}

/* ── FILTER ── */
function filterContacts(q) {
    if (!q.trim()) { renderGrid(allContacts); return; }
    const lq = q.toLowerCase();
    renderGrid(allContacts.filter(c =>
        (c.name||'').toLowerCase().includes(lq) ||
        (c.phone_number||'').includes(lq) ||
        (c.notes||'').toLowerCase().includes(lq) ||
        (c.relationship||'').toLowerCase().includes(lq)
    ));
}

/* ── MODAL ── */
function openModal(data=null) {
    document.getElementById('modal-title').textContent = data ? 'Editar Contato' : 'Novo Contato';
    document.getElementById('edit-id').value   = data?.id || '';
    document.getElementById('f-name').value    = data?.name || '';
    document.getElementById('f-phone').value   = data?.phone_number || '';
    document.getElementById('f-rel').value     = data?.relationship || '';
    document.getElementById('f-notes').value   = '';  // always blank for additive notes
    if (data?.notes) {
        document.querySelector('#modal-body .field:last-of-type')?.querySelector('label')
        document.getElementById('f-notes').placeholder = 'Adicionar nova anotação (mantém as anteriores):\n\n' + data.notes.substring(0,100) + (data.notes.length>100?'…':'');
    }
    document.getElementById('modal-overlay').classList.add('show');
    document.getElementById('f-name').focus();
}
function editContact(c) { openModal(c); }
function closeModal() { document.getElementById('modal-overlay').classList.remove('show'); }

/* ── SAVE CONTACT ── */
async function saveContact() {
    const id    = document.getElementById('edit-id').value;
    const phone = document.getElementById('f-phone').value.trim().replace(/\D/g,'');
    const name  = document.getElementById('f-name').value.trim();
    const rel   = document.getElementById('f-rel').value;
    const notes = document.getElementById('f-notes').value.trim();
    if (!phone) { toast('Telefone é obrigatório', 'err'); return; }
    try {
        const r = await postData('api.php?action=save_contact', {id, phone_number:phone, name, relationship:rel, notes, source:'manual'});
        if (r.status === 'success') {
            toast(r.action === 'inserted' ? '✓ Contato adicionado!' : '✓ Contato atualizado!');
            closeModal();
            loadContacts();
        } else {
            toast('Erro: ' + (r.message||''), 'err');
        }
    } catch(e) { toast('Erro de comunicação', 'err'); }
}

/* ── DELETE ── */
async function deleteContact(id, name) {
    if (!confirm(`Excluir "${name}" da agenda? Esta ação não pode ser desfeita.`)) return;
    try {
        const r = await postData('api.php?action=delete_contact', {id});
        if (r.status === 'success') { toast('Contato excluído'); loadContacts(); }
        else toast('Erro: ' + (r.message||''), 'err');
    } catch(e) { toast('Erro', 'err'); }
}

/* ── WHATSAPP ── */
function openWhatsApp(phone) {
    window.open('https://wa.me/' + phone.replace(/\D/g,''), '_blank');
}

/* ── EXTRACT FROM LOGS ── */
async function extractFromLogs() {
    toast('Analisando conversas…');
    try {
        const r = await fetchData('api.php?action=extract_contacts');
        if (r.status !== 'ok') { toast('Erro: ' + (r.message||''), 'err'); return; }
        if (!r.new_contacts.length) {
            toast(`Nenhum número novo. ${r.already_saved} já salvos na agenda.`);
            return;
        }
        extractedNew = r.new_contacts;
        const list = document.getElementById('extract-list');
        list.innerHTML = r.new_contacts.map((c,i) => `
        <div class="ext-row">
            <span style="font-family:monospace;font-size:13px">${escHtml(c.sender_number)}</span>
            <span style="color:var(--text-secondary);font-size:12px">${c.msg_count} msgs · último: ${c.last_seen?.substring(0,10)||'—'}</span>
            <input type="text" placeholder="Nome (opcional)" id="ext-name-${i}"
                   style="width:130px;padding:5px 8px;border-radius:5px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-primary);font-size:12px;outline:none">
        </div>`).join('');
        document.getElementById('extract-panel').classList.add('show');
        toast(`${r.new_contacts.length} novo(s) número(s) encontrado(s)!`);
    } catch(e) { toast('Erro ao extrair', 'err'); }
}

/* ── SAVE ALL EXTRACTED ── */
async function saveAllExtracted() {
    let saved = 0;
    for (let i = 0; i < extractedNew.length; i++) {
        const c    = extractedNew[i];
        const name = document.getElementById('ext-name-' + i)?.value?.trim() || '';
        try {
            const r = await postData('api.php?action=save_contact', {
                phone_number: c.sender_number,
                name: name || c.sender_number,
                relationship: 'cliente',
                source: 'auto',
                notes: `Extraído automaticamente. ${c.msg_count} mensagens. Último contato: ${c.last_seen||'—'}`
            });
            if (r.status === 'success') saved++;
        } catch(e) {}
    }
    toast(`✓ ${saved} contato(s) salvos na agenda!`);
    document.getElementById('extract-panel').classList.remove('show');
    loadContacts();
}

/* ── HELPERS ── */
function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── INIT ── */
window.addEventListener('load', loadContacts);
</script>

<?php require_once 'footer.php'; ?>
