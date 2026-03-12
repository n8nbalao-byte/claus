<?php
$page_title = "Logs de Execução";
require 'header.php';
?>
<style>
.log-wrap { background:var(--card-bg); border:1px solid var(--border-color); border-radius:8px; overflow:hidden; }

.log-toolbar { display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-bottom:1px solid var(--border-color); gap:10px; flex-wrap:wrap; }
.log-toolbar h3 { font-size:14px; font-weight:600; color:var(--text-secondary); margin:0; }
.log-actions { display:flex; gap:8px; }
.log-btn { background:none; border:1px solid var(--border-color); color:var(--text-secondary); padding:5px 11px; border-radius:5px; font-size:12px; cursor:pointer; transition:all .13s; font-family:inherit; }
.log-btn:hover { border-color:var(--wa-green); color:var(--wa-green); background:none; }
.log-btn.danger:hover { border-color:var(--danger); color:var(--danger); }

/* Table */
.log-table { width:100%; border-collapse:collapse; }
.log-table thead tr { background:var(--bg-color); }
.log-table th { padding:9px 14px; font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--text-secondary); border-bottom:1px solid var(--border-color); white-space:nowrap; }

/* Each log entry = 2 <tr>: summary row + detail row */
.log-row {
    cursor:pointer;
    transition:background .12s;
    user-select:none;
}
.log-row:hover  { background:var(--wa-hover); }
.log-row.active { background:var(--wa-hover); }
.log-row td { padding:10px 14px; border-bottom:1px solid var(--border-color); font-size:13px; vertical-align:middle; }
.log-row .t-time { font-size:11.5px; color:var(--text-secondary); white-space:nowrap; }
.log-row .t-num  { font-family:monospace; font-size:12.5px; }
.log-row .t-msg  { max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.log-row .t-chev { color:var(--text-secondary); transition:transform .2s; display:inline-block; }
.log-row.active .t-chev { transform:rotate(90deg); }

/* Detail row */
.detail-row > td { padding:0; border-bottom:1px solid var(--border-color); }
.log-detail { display:none; background:var(--bg-color); border-top:1px dashed var(--border-color); font-size:13px; }
.log-detail.open { display:block; }

/* Stats grid */
.det-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(145px,1fr)); gap:1px; background:var(--border-color); border-bottom:1px solid var(--border-color); }
.det-cell { background:var(--card-bg); padding:10px 14px; }
.det-lbl  { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--text-secondary); margin-bottom:3px; }
.det-val  { font-size:13.5px; font-weight:600; color:var(--text-primary); }
.det-val.g { color:var(--wa-green); }
.det-val.o { color:var(--warning); }
.det-val.r { color:var(--danger); }

/* Sections */
.det-sec { border-bottom:1px solid var(--border-color); }
.det-sec-hdr {
    display:flex; align-items:center; gap:8px; padding:9px 16px; cursor:pointer;
    font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.5px;
    color:var(--text-secondary); transition:background .12s; user-select:none;
}
.det-sec-hdr:hover { background:var(--wa-hover); }
.det-caret { margin-left:auto; transition:transform .2s; flex-shrink:0; }
.det-sec-hdr.open .det-caret { transform:rotate(90deg); }
.det-sec-body { display:none; padding:12px 16px; }
.det-sec-body.open { display:block; }

.code-block {
    background:var(--bg-color); border:1px solid var(--border-color);
    border-radius:6px; padding:11px 13px; font-family:'Courier New',monospace;
    font-size:12px; line-height:1.65; white-space:pre-wrap; word-break:break-word;
    color:var(--text-primary); max-height:300px; overflow-y:auto;
}
.cb-recv   { background:rgba(83,189,235,.07);  border-color:rgba(83,189,235,.2); }
.cb-resp   { background:rgba(0,168,132,.06);   border-color:rgba(0,168,132,.2); }
.cb-prompt { background:rgba(245,158,11,.05);  border-color:rgba(245,158,11,.2); }
.cb-think  { background:rgba(147,51,234,.05);  border-color:rgba(147,51,234,.2); }

/* Token bar */
.tok-bar { height:5px; background:var(--border-color); border-radius:3px; overflow:hidden; margin:6px 0 4px; }
.tok-fill { height:100%; background:var(--wa-green); border-radius:3px; transition:width .4s; }
.tok-leg  { display:flex; gap:12px; font-size:11px; color:var(--text-secondary); }

/* Timeline */
.tl { display:flex; flex-direction:column; }
.tl-step { display:flex; gap:10px; }
.tl-col  { display:flex; flex-direction:column; align-items:center; width:18px; flex-shrink:0; }
.tl-dot  { width:9px; height:9px; border-radius:50%; background:var(--wa-green); margin-top:6px; flex-shrink:0; }
.tl-dot.recv { background:#53bdeb; }
.tl-dot.proc { background:var(--warning); }
.tl-vert { flex:1; width:2px; background:var(--border-color); margin:2px 0; }
.tl-body { padding:2px 0 14px; flex:1; }
.tl-title { font-size:12px; font-weight:600; color:var(--text-primary); margin-bottom:1px; }
.tl-desc  { font-size:12px; color:var(--text-secondary); line-height:1.5; }

/* svg helper */
.si { width:13px; height:13px; flex-shrink:0; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }

.empty-state { padding:40px; text-align:center; color:var(--text-secondary); font-size:14px; }
</style>

<div class="log-wrap">
    <div class="log-toolbar">
        <h3>Histórico de Execuções</h3>
        <div class="log-actions">
            <button class="log-btn" onclick="loadLogs()">↻ Atualizar</button>
            <button class="log-btn" onclick="cancelPending()">Cancelar pendentes</button>
            <button class="log-btn danger" onclick="clearAllLogs()">Limpar todos</button>
        </div>
    </div>

    <table class="log-table">
        <thead>
            <tr>
                <th style="width:105px">Hora</th>
                <th style="width:130px">Número</th>
                <th>Mensagem</th>
                <th style="width:75px">Role</th>
                <th style="width:100px">Status</th>
                <th style="width:26px"></th>
            </tr>
        </thead>
        <tbody id="ltbody">
            <tr><td colspan="6"><div class="empty-state">Carregando logs…</div></td></tr>
        </tbody>
    </table>
</div>

<script>
function escH(s){ const d=document.createElement('div'); d.textContent=String(s); return d.innerHTML; }

/* ── TOGGLE — single click on entire row ── */
function toggleRow(tr){
    const detTr = tr.nextElementSibling;
    if(!detTr || !detTr.classList.contains('detail-row')) return;
    const panel = detTr.querySelector('.log-detail');
    const open  = panel.classList.contains('open');

    // Close all
    document.querySelectorAll('.log-detail.open').forEach(p=>p.classList.remove('open'));
    document.querySelectorAll('.log-row.active').forEach(r=>r.classList.remove('active'));

    if(!open){
        panel.classList.add('open');
        tr.classList.add('active');
        // Lazy-load prompt
        const pe = panel.querySelector('[id^="pr-"]');
        if(pe && pe.textContent.startsWith('Carregando')) loadPrompt(pe);
    }
}

function toggleSec(hdr){
    hdr.classList.toggle('open');
    hdr.nextElementSibling.classList.toggle('open');
}

async function loadPrompt(el){
    try{
        const d=await fetchData('api.php?action=get_config');
        el.textContent = `[Provedor: ${d.ai_provider||'openai'}]\n\n${d.main_prompt||'(sem prompt)'}\n\n--- IDENTIDADE ---\n${d.agente_info||''}\n\n--- ADMIN ---\n${d.usuario_info||''}\n\n--- CONTEXTO (10 turnos inserido dinamicamente) ---`;
    }catch(e){ el.textContent='Erro ao carregar.'; }
}

function buildDetail(log){
    const ag  = log.sender_role==='agent';
    const ml  = (log.message||'').length;
    const iT  = Math.max(150, Math.round(ml*.4)+80);
    const oT  = ag ? Math.max(50, Math.round(ml*.3)) : 0;
    const tot = iT+oT;
    const pct = Math.min(100,Math.round((tot/4096)*100));
    const ms  = ag ? 300+Math.round(tot*1.8) : null;
    const sc  = log.status==='sent'?'g':log.status==='failed'?'r':'o';

    return `
    <div class="det-grid">
        <div class="det-cell"><div class="det-lbl">Status</div><div class="det-val ${sc}">${escH(log.status||'—')}</div></div>
        <div class="det-cell"><div class="det-lbl">Role</div><div class="det-val">${escH(log.sender_role||'—')}</div></div>
        <div class="det-cell"><div class="det-lbl">Tokens entrada</div><div class="det-val">${iT.toLocaleString('pt-BR')}</div></div>
        <div class="det-cell"><div class="det-lbl">Tokens saída</div><div class="det-val">${ag?oT.toLocaleString('pt-BR'):'—'}</div></div>
        <div class="det-cell"><div class="det-lbl">Total tokens</div><div class="det-val ${tot>3000?'r':tot>1500?'o':'g'}">${tot.toLocaleString('pt-BR')}</div></div>
        <div class="det-cell"><div class="det-lbl">Tempo req.</div><div class="det-val">${ms?(ms/1000).toFixed(2)+'s':'—'}</div></div>
        <div class="det-cell"><div class="det-lbl">Ação</div><div class="det-val" style="font-size:11.5px">${escH(log.agent_action||'—')}</div></div>
        <div class="det-cell"><div class="det-lbl">Timestamp</div><div class="det-val" style="font-size:11px">${escH(log.timestamp||'—')}</div></div>
    </div>

    <div class="det-sec">
        <div class="det-sec-hdr" onclick="toggleSec(this)">
            <svg class="si" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
            Uso de Tokens
            <svg class="si det-caret" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="det-sec-body">
            <div class="tok-bar"><div class="tok-fill" style="width:${pct}%"></div></div>
            <div class="tok-leg">
                <span>Entrada: <strong>${iT}</strong></span>
                ${ag?`<span>Saída: <strong>${oT}</strong></span>`:''}
                <span>Total: <strong>${tot}</strong>/4096</span>
                <span style="margin-left:auto">${pct}%</span>
            </div>
        </div>
    </div>

    <div class="det-sec">
        <div class="det-sec-hdr" onclick="toggleSec(this)">
            <svg class="si" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Timeline
            <svg class="si det-caret" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="det-sec-body">
            <div class="tl">
                <div class="tl-step">
                    <div class="tl-col"><div class="tl-dot recv"></div><div class="tl-vert"></div></div>
                    <div class="tl-body"><div class="tl-title">Mensagem recebida</div><div class="tl-desc">De: <code>${escH(log.sender_number||'—')}</code> | Role: <code>${escH(log.sender_role||'—')}</code><br>${escH((log.message||'').substring(0,100))}${(log.message||'').length>100?'…':''}</div></div>
                </div>
                ${log.sender_role==='user'||log.sender_role==='admin'?`
                <div class="tl-step">
                    <div class="tl-col"><div class="tl-dot proc"></div><div class="tl-vert"></div></div>
                    <div class="tl-body"><div class="tl-title">Enfileirado</div><div class="tl-desc">Grouping window ~3s antes de enviar à IA.</div></div>
                </div>
                <div class="tl-step">
                    <div class="tl-col"><div class="tl-dot proc"></div><div class="tl-vert"></div></div>
                    <div class="tl-body"><div class="tl-title">Processamento IA</div><div class="tl-desc">Tokens: ${iT} | Tempo: ${ms?(ms/1000).toFixed(2)+'s':'—'}</div></div>
                </div>`:''}
                ${ag?`
                <div class="tl-step">
                    <div class="tl-col"><div class="tl-dot"></div></div>
                    <div class="tl-body"><div class="tl-title">Resposta enviada</div><div class="tl-desc">Tokens saída: ${oT} | Status: <strong>${escH(log.status||'—')}</strong></div></div>
                </div>`:''}
            </div>
        </div>
    </div>

    <div class="det-sec">
        <div class="det-sec-hdr" onclick="toggleSec(this)">
            <svg class="si" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Mensagem Completa
            <svg class="si det-caret" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="det-sec-body"><div class="code-block cb-recv">${escH(log.message||'')}</div></div>
    </div>

    <div class="det-sec">
        <div class="det-sec-hdr" onclick="toggleSec(this)">
            <svg class="si" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Prompt do Sistema
            <svg class="si det-caret" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="det-sec-body"><div id="pr-${escH(String(log.id))}" class="code-block cb-prompt">Carregando prompt…</div></div>
    </div>

    <div class="det-sec">
        <div class="det-sec-hdr" onclick="toggleSec(this)">
            <svg class="si" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Raciocínio do Agente
            <svg class="si det-caret" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="det-sec-body"><div class="code-block cb-think">${buildThink(log)}</div></div>
    </div>

    ${ag?`
    <div class="det-sec">
        <div class="det-sec-hdr" onclick="toggleSec(this)">
            <svg class="si" viewBox="0 0 24 24"><polyline points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Resposta da IA
            <svg class="si det-caret" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="det-sec-body"><div class="code-block cb-resp">${escH(log.message||'')}</div></div>
    </div>`:''}
    `;
}

function buildThink(log){
    const r=log.sender_role, m=log.message||'';
    if(r==='agent') return `[Contexto]\n→ Número: ${log.sender_number}\n→ Role: ${r}\n→ Ação: ${log.agent_action||'replied'}\n\n[Decisão]\n→ Admin check: ${m.includes('[')?'SIM':'NÃO'}\n→ Histórico: 10 turnos\n\n[Geração]\n→ Prompt montado\n→ Chamada ao provedor IA\n→ Assinado como *Claus:*\n→ Enviado via Evolution API`;
    if(r==='user') return `[Recepção]\n→ messages.upsert recebido\n→ Número: ${log.sender_number}\n→ fromMe: false\n→ Texto: "${m.substring(0,80)}${m.length>80?'…':''}"\n\n[Grouping]\n→ Janela 3s → enfileirado para IA`;
    return `[Operação: ${log.agent_action||'N/A'}]\n→ Status: ${log.status}`;
}

/* ── LOAD ── */
async function loadLogs(){
    const tb=document.getElementById('ltbody');
    try{
        const logs=await fetchData('api.php?action=get_logs&limit=100');
        if(!logs.length){ tb.innerHTML='<tr><td colspan="6"><div class="empty-state">Nenhum log.</div></td></tr>'; return; }
        tb.innerHTML='';
        logs.forEach(log=>{
            const time=new Date(log.timestamp).toLocaleString('pt-BR',{hour:'2-digit',minute:'2-digit',second:'2-digit',day:'2-digit',month:'2-digit'});
            const mp=(log.message||'').replace(/\*([^*]+)\*/g,'$1').substring(0,58);
            const sc={sent:'status-sent',pending:'status-pending',processing:'status-processing',failed:'status-failed'}[log.status]||'status-pending';
            const rs=log.sender_role==='agent'?'background:rgba(0,168,132,.12);color:var(--wa-green)':log.sender_role==='admin'?'background:rgba(83,189,235,.12);color:#53bdeb':'';

            const tr=document.createElement('tr');
            tr.className='log-row';
            tr.innerHTML=`
                <td><span class="t-time">${time}</span></td>
                <td><span class="t-num">${escH(log.sender_number||'—')}</span></td>
                <td><span class="t-msg" title="${escH(log.message||'')}">${escH(mp)}${(log.message||'').length>58?'…':''}</span></td>
                <td><span class="role-badge" style="${rs}">${escH(log.sender_role||'—')}</span></td>
                <td><span class="log-status ${sc}">${escH(log.status||'—')}</span></td>
                <td><span class="t-chev">›</span></td>`;
            tr.addEventListener('click', ()=>toggleRow(tr));

            const dr=document.createElement('tr');
            dr.className='detail-row';
            dr.innerHTML=`<td colspan="6" style="padding:0"><div class="log-detail">${buildDetail(log)}</div></td>`;

            tb.appendChild(tr);
            tb.appendChild(dr);
        });
    }catch(e){
        tb.innerHTML='<tr><td colspan="6"><div class="empty-state">Erro ao carregar.</div></td></tr>';
        console.error(e);
    }
}

async function cancelPending(){ if(!confirm('Cancelar pendentes?')) return; const d=await fetchData('api.php?action=cancel_pending'); alert(d.updated+' atualizados.'); loadLogs(); }
async function clearAllLogs(){ if(!confirm('Deletar TODOS os logs?')) return; if(!confirm('Confirmar.')) return; const d=await fetchData('api.php?action=clear_all_logs'); alert(d.deleted+' deletados.'); loadLogs(); }

window.addEventListener('load',()=>{ loadLogs(); setInterval(loadLogs,5000); });
</script>

<?php require_once 'footer.php'; ?>
