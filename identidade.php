<?php
$page_title    = "Identidade & Memória";
$current_page  = 'identidade';
require_once 'header.php';
?>
<style>
/* ── MEMORY TABS ── */
.mem-tabs { display:flex; gap:0; border-bottom:2px solid var(--border-color); margin-bottom:20px; flex-wrap:wrap; }
.mem-tab {
    padding:9px 16px; font-size:13px; font-weight:500; cursor:pointer;
    color:var(--text-secondary); border-bottom:2px solid transparent; margin-bottom:-2px;
    background:none; border-left:none; border-right:none; border-top:none; border-radius:0;
    display:flex; align-items:center; gap:6px; white-space:nowrap; transition:color .13s;
}
.mem-tab:hover { color:var(--text-primary); background:var(--wa-hover); }
.mem-tab.active { color:var(--wa-green); border-bottom-color:var(--wa-green); font-weight:600; background:none; }
.mem-tab svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
.tab-pane { display:none; }
.tab-pane.active { display:block; }

/* ── SECTION CARD ── */
.mem-card { background:var(--card-bg); border:1px solid var(--border-color); border-radius:8px; margin-bottom:16px; overflow:hidden; }
.mem-hdr  { padding:13px 16px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between; }
.mem-title { font-size:14px; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:8px; }
.mem-title svg { width:16px; height:16px; fill:none; stroke:var(--wa-green); stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.mem-body  { padding:16px; }
.mem-meta  { font-size:11px; color:var(--text-secondary); }

/* ── EDITOR ── */
.mem-editor {
    width:100%; min-height:180px; padding:12px 14px;
    background:var(--bg-color); border:1px solid var(--border-color);
    border-radius:7px; color:var(--text-primary); font-family:'Courier New',monospace;
    font-size:13px; line-height:1.7; resize:vertical; outline:none;
    transition:border-color .18s;
}
.mem-editor:focus { border-color:var(--wa-green); }
.mem-editor.soul-ed  { min-height:220px; }
.mem-editor.short-ed { min-height:100px; }

/* ── DAILY LOG ── */
.day-entry { background:var(--bg-color); border:1px solid var(--border-color); border-radius:7px; margin-bottom:10px; overflow:hidden; }
.day-hdr   { padding:8px 14px; background:var(--wa-hover); display:flex; align-items:center; justify-content:space-between; cursor:pointer; user-select:none; }
.day-date  { font-size:12.5px; font-weight:600; color:var(--wa-green); }
.day-body  { padding:12px 14px; font-family:'Courier New',monospace; font-size:12.5px; line-height:1.7; color:var(--text-primary); white-space:pre-wrap; display:none; }
.day-body.open { display:block; }
.day-chevron { width:14px; height:14px; fill:none; stroke:var(--text-secondary); stroke-width:2; stroke-linecap:round; transition:transform .2s; }
.day-entry.open .day-chevron { transform:rotate(180deg); }

/* ── ADD LOG FORM ── */
.log-input-row { display:flex; gap:8px; margin-top:12px; }
.log-input { flex:1; padding:9px 12px; border-radius:7px; border:1px solid var(--border-color); background:var(--bg-color); color:var(--text-primary); font-size:13px; font-family:inherit; outline:none; }
.log-input:focus { border-color:var(--wa-green); }

/* ── BUTTONS ── */
.mem-btn { display:flex; align-items:center; gap:6px; padding:8px 16px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; border:none; transition:all .13s; }
.mem-btn svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; }
.btn-save   { background:var(--wa-green); color:#fff; }
.btn-save:hover { background:var(--wa-green-dk); }
.btn-sec    { background:var(--wa-hover); color:var(--text-primary); border:1px solid var(--border-color); }
.btn-sec:hover { background:var(--border-color); }
.btn-row    { display:flex; gap:8px; margin-top:12px; align-items:center; flex-wrap:wrap; }

/* ── TOAST ── */
.mem-toast { position:fixed; bottom:24px; right:24px; padding:10px 18px; border-radius:8px; font-size:13.5px; font-weight:600; color:#fff; z-index:999; opacity:0; transform:translateY(10px); transition:all .25s; pointer-events:none; }
.mem-toast.show { opacity:1; transform:translateY(0); }
.toast-ok  { background:#00a884; }
.toast-err { background:#ef4444; }

/* ── INFO PILL ── */
.info-pill { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:10px; font-size:11.5px; background:rgba(0,168,132,.1); color:var(--wa-green); border:1px solid rgba(0,168,132,.2); margin-bottom:10px; }
.info-pill svg { width:12px; height:12px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; }
.warn-pill { background:rgba(245,158,11,.1); color:#f59e0b; border-color:rgba(245,158,11,.2); }

/* ── CHAR COUNT ── */
.char-count { font-size:11px; color:var(--text-secondary); text-align:right; margin-top:4px; }
</style>

<!-- TABS -->
<div class="mem-tabs">
    <button class="mem-tab active" onclick="showMemTab('soul',this)">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
        Alma (SOUL)
    </button>
    <button class="mem-tab" onclick="showMemTab('identity',this)">
        <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
        Identidade
    </button>
    <button class="mem-tab" onclick="showMemTab('user',this)">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Contexto do Usuário
    </button>
    <button class="mem-tab" onclick="showMemTab('longterm',this)">
        <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Memória Longa
    </button>
    <button class="mem-tab" onclick="showMemTab('daily',this)">
        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Diário (Logs)
    </button>
</div>

<!-- ══════ SOUL ══════ -->
<div class="tab-pane active" id="tab-soul">
    <div class="mem-card">
        <div class="mem-hdr">
            <div class="mem-title">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                SOUL.md — Personalidade & Filosofia do Agente
            </div>
        </div>
        <div class="mem-body">
            <div class="info-pill">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Define como o Claus pensa, fala e age. Carregado em cada sessão.
            </div>
            <textarea class="mem-editor soul-ed" id="soul-content" placeholder="# SOUL — Personalidade do Claus

## Identidade
- Nome: Claus
- Personalidade: Direto, profissional, amigável
- Tom: Casual mas eficiente

## Filosofia de Atendimento
- Responda sempre com clareza
- Priorize resolver o problema do cliente
- Seja humano, não robótico

## Regras de Comportamento
- Nunca ignore uma pergunta
- Confirme o entendimento antes de agir
- Reconheça erros sem rodeios"></textarea>
            <div class="char-count" id="soul-chars">0 chars</div>
            <div class="btn-row">
                <button class="mem-btn btn-save" onclick="saveSection('soul')">
                    <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Salvar SOUL
                </button>
                <span id="soul-saved" class="mem-meta"></span>
            </div>
        </div>
    </div>
</div>

<!-- ══════ IDENTITY ══════ -->
<div class="tab-pane" id="tab-identity">
    <div class="mem-card">
        <div class="mem-hdr">
            <div class="mem-title">
                <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                IDENTITY.md — Dados do Agente
            </div>
        </div>
        <div class="mem-body">
            <div class="info-pill">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Informações de identidade persistentes. Carregadas em toda sessão.
            </div>
            <textarea class="mem-editor" id="identity-content" placeholder="# IDENTITY

name: Claus
type: Assistente de WhatsApp
emoji: 🤖
avatar: /assets/claus-avatar.png
version: 1.0
created: 2024-01-01
owner: Balão da Informática Laboratório
phone: 5519981470446

## Capacidades
- Atendimento ao cliente via WhatsApp
- Tirar dúvidas sobre produtos
- Agendar visitas técnicas
- Encaminhar para atendimento humano"></textarea>
            <div class="char-count" id="identity-chars">0 chars</div>
            <div class="btn-row">
                <button class="mem-btn btn-save" onclick="saveSection('identity')">
                    <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Salvar Identidade
                </button>
                <span id="identity-saved" class="mem-meta"></span>
            </div>
        </div>
    </div>
</div>

<!-- ══════ USER CONTEXT ══════ -->
<div class="tab-pane" id="tab-user">
    <div class="mem-card">
        <div class="mem-hdr">
            <div class="mem-title">
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                USER.md — Contexto do Administrador
            </div>
        </div>
        <div class="mem-body">
            <div class="info-pill">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                O que o Claus sabe sobre você e sua empresa. Usado para personalizar respostas.
            </div>
            <textarea class="mem-editor" id="user-content" placeholder="# USER — Contexto do Administrador

## Sobre o Negócio
name: Balão da Informática Laboratório
type: Assistência técnica / informática
phone: 5519981470446
timezone: America/Sao_Paulo (UTC-3)

## Preferências de Atendimento
- Horário: seg-sex 9h-18h, sáb 9h-13h
- Fora do horário: informar prazo e deixar mensagem
- Prioridade: clientes com orçamento aprovado

## Serviços Oferecidos
- Formatação e instalação de sistemas
- Manutenção preventiva e corretiva
- Venda de peças e acessórios
- Suporte remoto

## Informações Importantes
- Não fazer orçamentos por mensagem sem diagnóstico
- Prazo médio de entrega: 2-3 dias úteis
- Formas de pagamento: PIX, cartão, dinheiro"></textarea>
            <div class="char-count" id="user-chars">0 chars</div>
            <div class="btn-row">
                <button class="mem-btn btn-save" onclick="saveSection('user')">
                    <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Salvar Contexto
                </button>
                <span id="user-saved" class="mem-meta"></span>
            </div>
        </div>
    </div>
</div>

<!-- ══════ LONG TERM MEMORY ══════ -->
<div class="tab-pane" id="tab-longterm">
    <div class="mem-card">
        <div class="mem-hdr">
            <div class="mem-title">
                <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                MEMORY.md — Memória de Longo Prazo
            </div>
        </div>
        <div class="mem-body">
            <div class="info-pill warn-pill">
                <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Conhecimento consolidado e persistente. Edite com cuidado — cada mudança é registrada no diário.
            </div>
            <textarea class="mem-editor" style="min-height:260px" id="longterm-content" placeholder="# MEMORY — Memória de Longo Prazo

## Clientes Importantes
- (extraído automaticamente das conversas)

## Lições Aprendidas
- (registre insights valiosos aqui)

## Decisões Permanentes
- (decisões que o agente deve sempre lembrar)

## Informações Técnicas
- (dados que o agente precisa ter à mão)

## Histórico Relevante
- (eventos passados importantes)"></textarea>
            <div class="char-count" id="longterm-chars">0 chars</div>
            <div class="btn-row">
                <button class="mem-btn btn-save" onclick="saveSection('longterm')">
                    <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Salvar Memória
                </button>
                <button class="mem-btn btn-sec" onclick="appendToLongterm()">
                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Adicionar Nota
                </button>
                <span id="longterm-saved" class="mem-meta"></span>
            </div>
            <!-- Quick add note -->
            <div id="append-form" style="display:none;margin-top:12px">
                <textarea class="mem-editor short-ed" id="append-note" placeholder="Digite a nota para adicionar à memória..."></textarea>
                <div class="btn-row">
                    <button class="mem-btn btn-save" onclick="confirmAppend()">
                        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        Confirmar Adição
                    </button>
                    <button class="mem-btn btn-sec" onclick="document.getElementById('append-form').style.display='none'">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ DAILY LOGS ══════ -->
<div class="tab-pane" id="tab-daily">
    <div class="mem-card">
        <div class="mem-hdr">
            <div class="mem-title">
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Diário — Logs Diários de Memória
            </div>
            <span class="mem-meta">Sempre aditivo — nunca sobrescreve</span>
        </div>
        <div class="mem-body">
            <div class="info-pill">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Registros diários do agente. Cada entrada é ADICIONADA, nunca substituída.
            </div>

            <!-- Add entry for today -->
            <div style="margin-bottom:16px">
                <label style="font-size:12px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:6px">
                    Adicionar ao diário de hoje (<?= date('d/m/Y') ?>)
                </label>
                <div class="log-input-row">
                    <input type="text" class="log-input" id="daily-new-entry" placeholder="Registre um evento, decisão ou observação…">
                    <button class="mem-btn btn-save" onclick="addDailyLog()">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Adicionar
                    </button>
                </div>
            </div>

            <!-- Daily log entries -->
            <div id="daily-list">
                <div style="color:var(--text-secondary);font-size:13px">Carregando diário…</div>
            </div>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="mem-toast" id="mem-toast"></div>

<script>
const TODAY = '<?= date('Y-m-d') ?>';

/* ── TABS ── */
function showMemTab(id, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.mem-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}

/* ── TOAST ── */
function toast(msg, type='ok') {
    const el = document.getElementById('mem-toast');
    el.textContent = msg;
    el.className = 'mem-toast show ' + (type==='ok' ? 'toast-ok' : 'toast-err');
    setTimeout(() => el.classList.remove('show'), 2800);
}

/* ── CHAR COUNT ── */
function bindCharCount(textareaId, countId) {
    const ta = document.getElementById(textareaId);
    const ct = document.getElementById(countId);
    if (!ta || !ct) return;
    const update = () => { ct.textContent = ta.value.length.toLocaleString('pt-BR') + ' chars'; };
    ta.addEventListener('input', update);
    update();
}

/* ── LOAD ALL MEMORY ── */
async function loadMemory() {
    try {
        const d = await fetchData('api.php?action=get_memory');

        const sections = {
            soul:     'soul-content',
            identity: 'identity-content',
            user:     'user-content',
            longterm: 'longterm-content',
        };
        for (const [key, elId] of Object.entries(sections)) {
            const val = d['mem_' + key] || '';
            const el  = document.getElementById(elId);
            if (el) { el.value = val; }
            const ct = document.getElementById(key + '-chars');
            if (ct) ct.textContent = val.length.toLocaleString('pt-BR') + ' chars';
        }

        // Daily logs
        renderDailyLogs(d.daily_logs || []);

    } catch(e) { console.warn('loadMemory', e); }
}

/* ── SAVE SECTION ── */
async function saveSection(section) {
    const contentMap = {
        soul:     'soul-content',
        identity: 'identity-content',
        user:     'user-content',
        longterm: 'longterm-content',
    };
    const content = document.getElementById(contentMap[section])?.value || '';
    try {
        const r = await postData('api.php?action=save_memory', { section, content });
        if (r.status === 'success') {
            toast('✓ ' + section.toUpperCase() + ' salvo!');
            const ts = document.getElementById(section + '-saved');
            if (ts) ts.textContent = 'Salvo às ' + new Date().toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
        } else {
            toast('Erro: ' + (r.message || 'falha ao salvar'), 'err');
        }
    } catch(e) { toast('Erro de comunicação', 'err'); }
}

/* ── APPEND TO LONGTERM ── */
function appendToLongterm() {
    const form = document.getElementById('append-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
function confirmAppend() {
    const note = document.getElementById('append-note').value.trim();
    if (!note) return;
    const ta   = document.getElementById('longterm-content');
    const ts   = new Date().toLocaleDateString('pt-BR') + ' ' + new Date().toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
    ta.value   = ta.value.trimEnd() + '\n\n## Nota adicionada em ' + ts + '\n' + note;
    document.getElementById('append-form').style.display = 'none';
    document.getElementById('append-note').value = '';
    // Auto-save
    saveSection('longterm');
}

/* ── DAILY LOG ── */
async function addDailyLog() {
    const input = document.getElementById('daily-new-entry');
    const entry = input.value.trim();
    if (!entry) return;
    try {
        const r = await postData('api.php?action=save_memory', { section: 'daily', content: entry, date: TODAY });
        if (r.status === 'success') {
            input.value = '';
            toast('✓ Entrada adicionada ao diário de hoje');
            loadMemory();
        } else {
            toast('Erro: ' + (r.message || ''), 'err');
        }
    } catch(e) { toast('Erro de comunicação', 'err'); }
}
document.getElementById('daily-new-entry')?.addEventListener('keydown', e => { if (e.key === 'Enter') addDailyLog(); });

/* ── RENDER DAILY LOGS ── */
function renderDailyLogs(logs) {
    const container = document.getElementById('daily-list');
    if (!logs.length) {
        container.innerHTML = '<div style="color:var(--text-secondary);font-size:13px;padding:12px 0">Nenhum log diário ainda.</div>';
        return;
    }
    container.innerHTML = logs.map((log, i) => {
        const isToday = log.date === TODAY;
        const lines   = (log.content || '').split('\n').filter(l => l.trim()).length;
        return `
        <div class="day-entry ${isToday ? 'open' : ''}" id="day-${i}">
            <div class="day-hdr" onclick="toggleDay(${i})">
                <span class="day-date">
                    ${isToday ? '📌 Hoje — ' : ''}${formatDate(log.date)}
                </span>
                <span style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:11px;color:var(--text-secondary)">${lines} entrada${lines!==1?'s':''}</span>
                    <svg class="day-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </span>
            </div>
            <div class="day-body ${isToday ? 'open' : ''}">${escHtml(log.content || '(vazio)')}</div>
        </div>`;
    }).join('');
}

function toggleDay(i) {
    const el   = document.getElementById('day-' + i);
    const body = el.querySelector('.day-body');
    el.classList.toggle('open');
    body.classList.toggle('open');
}
function formatDate(d) {
    if (!d) return '—';
    const [y,m,day] = d.split('-');
    return `${day}/${m}/${y}`;
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* ── CHAR COUNT BINDINGS ── */
['soul','identity','user','longterm'].forEach(s => bindCharCount(s+'-content', s+'-chars'));

/* ── INIT ── */
window.addEventListener('load', loadMemory);
</script>

<?php require_once 'footer.php'; ?>
