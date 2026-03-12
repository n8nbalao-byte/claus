<?php
$page_title       = "Conversas";
$hide_page_header = true;
require_once 'header.php';
?>
<style>
.wl { display:flex; flex:1; height:100%; overflow:hidden; }

/* LEFT PANEL */
.wll { width:340px; min-width:340px; background:#fff; border-right:1px solid #e9edef; display:flex; flex-direction:column; }
[data-theme="dark"] .wll { background:#202c33; border-right-color:#2a3942; }

.wll-hdr { height:58px; background:#f0f2f5; padding:0 16px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #d1d7db; flex-shrink:0; }
[data-theme="dark"] .wll-hdr { background:#202c33; border-bottom-color:#2a3942; }
.wll-title { font-size:17px; font-weight:600; color:#111b21; }
[data-theme="dark"] .wll-title { color:#e9edef; }

.wll-srch { padding:6px 10px; border-bottom:1px solid #e9edef; flex-shrink:0; background:#f0f2f5; }
[data-theme="dark"] .wll-srch { background:#202c33; border-bottom-color:#2a3942; }
.wll-srch input { width:100%; background:#fff; border:none; border-radius:8px; padding:8px 14px; font-size:13.5px; color:#111b21; outline:none; margin:0; }
[data-theme="dark"] .wll-srch input { background:#2a3942; color:#e9edef; }
.wll-srch input::placeholder { color:#8696a0; }

.conv-ul { flex:1; overflow-y:auto; list-style:none; }
.conv-li { display:flex; padding:12px 14px; cursor:pointer; border-bottom:1px solid #e9edef; gap:11px; align-items:center; transition:background .12s; }
[data-theme="dark"] .conv-li { border-bottom-color:#2a3942; }
.conv-li:hover,.conv-li.active { background:#f5f6f6; }
[data-theme="dark"] .conv-li:hover,[data-theme="dark"] .conv-li.active { background:#2a3942; }
.conv-av { width:46px; height:46px; border-radius:50%; background:#dfe5e7; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:700; color:#546e7a; flex-shrink:0; }
[data-theme="dark"] .conv-av { background:#374045; color:#8696a0; }
.conv-det { flex:1; min-width:0; }
.conv-num { font-size:14.5px; font-weight:600; color:#111b21; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:1px; }
[data-theme="dark"] .conv-num { color:#e9edef; }
.conv-pre { font-size:12.5px; color:#54656f; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
[data-theme="dark"] .conv-pre { color:#8696a0; }
.conv-ts  { font-size:11px; color:#8696a0; white-space:nowrap; align-self:flex-start; padding-top:2px; }
.conv-empty { padding:30px 16px; text-align:center; color:#8696a0; font-size:13.5px; line-height:1.6; }

/* RIGHT PANEL */
.wlr { flex:1; display:flex; flex-direction:column; background:#ece5dd; }
[data-theme="dark"] .wlr { background:#0b141a; }

.wlr-ph { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:14px; color:#8696a0; }
.wlr-ph svg { opacity:.22; }
.wlr-ph p { font-size:15px; }

.wlr-hdr { height:58px; background:#f0f2f5; padding:0 14px; display:flex; align-items:center; gap:12px; border-bottom:1px solid #d1d7db; flex-shrink:0; }
[data-theme="dark"] .wlr-hdr { background:#202c33; border-bottom-color:#2a3942; }
.wlr-av { width:38px; height:38px; border-radius:50%; background:#dfe5e7; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#546e7a; flex-shrink:0; }
[data-theme="dark"] .wlr-av { background:#374045; color:#8696a0; }
.wlr-num { font-size:15px; font-weight:600; color:#111b21; }
[data-theme="dark"] .wlr-num { color:#e9edef; }

#cvmsgs { flex:1; overflow-y:auto; padding:14px 8%; display:flex; flex-direction:column; gap:2px; }
.cdate { display:flex; justify-content:center; margin:8px 0; }
.cdate span { background:rgba(255,255,255,.92); color:#54656f; font-size:11.5px; padding:3px 12px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,.1); }
[data-theme="dark"] .cdate span { background:rgba(32,44,51,.92); color:#8696a0; }

.crow { display:flex; flex-direction:column; margin-bottom:1px; }
.crow.out { align-items:flex-end; }
.crow.in  { align-items:flex-start; }
.cbub { max-width:66%; min-width:68px; padding:6px 11px 5px; border-radius:7.5px; position:relative; font-size:14px; line-height:1.5; word-break:break-word; box-shadow:0 1px 2px rgba(0,0,0,.12); }
.crow.out .cbub { background:#d9fdd3; border-bottom-right-radius:2px; color:#111b21; }
.crow.in  .cbub { background:#fff;    border-bottom-left-radius:2px;  color:#111b21; }
[data-theme="dark"] .crow.out .cbub { background:#005c4b; color:#e9edef; }
[data-theme="dark"] .crow.in  .cbub { background:#202c33; color:#e9edef; }
.crow.out .cbub::after { content:''; position:absolute; right:-7px; bottom:0; border:7px solid transparent; border-left-color:#d9fdd3; border-right:0; border-bottom:0; }
.crow.in  .cbub::after { content:''; position:absolute; left:-7px;  bottom:0; border:7px solid transparent; border-right-color:#fff;    border-left:0; border-bottom:0; }
[data-theme="dark"] .crow.out .cbub::after { border-left-color:#005c4b; }
[data-theme="dark"] .crow.in  .cbub::after { border-right-color:#202c33; }
.csender { font-size:12.5px; font-weight:700; color:#00a884; margin-bottom:2px; display:block; }
.ctext   { color:inherit; }
.ctext strong { font-weight:700; }
.cmeta   { font-size:11px; color:#8696a0; margin-top:3px; text-align:right; }
.crow.in .cmeta { text-align:left; }
.cimg { max-width:240px; max-height:180px; border-radius:5px; display:block; cursor:pointer; margin-bottom:3px; }

/* Input */
.wlr-inp { background:#f0f2f5; padding:8px 10px; display:flex; align-items:flex-end; gap:6px; border-top:1px solid #d1d7db; flex-shrink:0; }
[data-theme="dark"] .wlr-inp { background:#202c33; border-top-color:#2a3942; }
.wlr-box { flex:1; background:#fff; border-radius:22px; padding:9px 14px; border:1px solid #d1d7db; display:flex; transition:border-color .18s; }
[data-theme="dark"] .wlr-box { background:#2a3942; border-color:#374f5c; }
.wlr-box:focus-within { border-color:#00a884; }
.wlr-box textarea { flex:1; border:none; outline:none; background:transparent; color:#111b21; font-family:inherit; font-size:14.5px; resize:none; line-height:1.5; min-height:24px; max-height:100px; overflow-y:auto; padding:0; width:100%; margin:0; }
[data-theme="dark"] .wlr-box textarea { color:#e9edef; }
.wlr-box textarea::placeholder { color:#8696a0; }
.wlr-send { width:48px; height:48px; border-radius:50%; background:#00a884; border:none; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; cursor:pointer; transition:background .13s; }
.wlr-send:hover { background:#008069; }
.wlr-send:disabled { opacity:.5; }
.wlr-send svg { width:20px; height:20px; fill:none; stroke:#fff; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }

.btn-sm-red { background:#ef4444; color:#fff; border:none; border-radius:6px; padding:6px 14px; font-size:12px; cursor:pointer; font-family:inherit; transition:background .13s; }
.btn-sm-red:hover { background:#dc2626; }

/* Lightbox */
#cvlb { display:none; position:fixed; inset:0; background:rgba(0,0,0,.87); z-index:9999; align-items:center; justify-content:center; }
#cvlb.show { display:flex; }
#cvlb img { max-width:92vw; max-height:92vh; border-radius:8px; }
#cvlbc { position:fixed; top:14px; right:14px; background:rgba(255,255,255,.13); color:#fff; border:none; border-radius:50%; width:36px; height:36px; font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
</style>

<div class="wl">
    <div class="wll">
        <div class="wll-hdr">
            <span class="wll-title">Conversas</span>
            <button class="btn-sm-red" onclick="clearAll()">Limpar tudo</button>
        </div>
        <div class="wll-srch">
            <input type="text" id="srch" placeholder="🔍 Pesquisar número…" oninput="filterList(this.value)">
        </div>
        <ul class="conv-ul" id="clist"><li class="conv-empty">Carregando…</li></ul>
    </div>

    <div class="wlr" id="wlr">
        <div class="wlr-ph" id="cvph">
            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <p>Selecione uma conversa</p>
        </div>
        <div id="cvview" style="display:none;flex:1;flex-direction:column;overflow:hidden;">
            <div class="wlr-hdr">
                <div class="wlr-av" id="cvav">?</div>
                <div><div class="wlr-num" id="cvnum">—</div><div style="font-size:12px;color:#8696a0">cliente</div></div>
            </div>
            <div id="cvmsgs" style="flex:1;overflow-y:auto;padding:14px 8%;display:flex;flex-direction:column;gap:2px;"></div>
            <div class="wlr-inp">
                <div class="wlr-box">
                    <textarea id="cvinp" placeholder="Digite uma mensagem…" rows="1"
                        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();cvSend();}"
                        oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"></textarea>
                </div>
                <button class="wlr-send" id="cvsend" onclick="cvSend()">
                    <svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<div id="cvlb" onclick="closeLb()"><button id="cvlbc" onclick="closeLb()">✕</button><img id="cvlbi" src="" alt=""></div>

<script>
let cvNum=null, cvSending=false;

function esc(s){ const d=document.createElement('div'); d.textContent=String(s); return d.innerHTML; }
function fmt(raw){
    let s=String(raw).trim();
    s=s.replace(/^\*{0,2}Claus:\*{0,2}\s*/i,'');
    s=s.replace(/^Claus:\s*/i,'');
    s=esc(s);
    s=s.replace(/\*([^*\n]+)\*/g,'<strong>$1</strong>');
    s=s.replace(/\n/g,'<br>');
    return s;
}

async function loadList(){
    try{
        const convs=await fetchData('api.php?action=get_conversations');
        const ul=document.getElementById('clist');
        if(!convs||!convs.length){
            ul.innerHTML='<li class="conv-empty">Nenhuma conversa encontrada.<br><small style="font-size:12px">Conversas com clientes aparecem aqui.</small></li>';
            return;
        }
        ul.innerHTML='';
        convs.forEach(c=>{
            const num=c.sender_number||'—';
            const init=num.replace(/\D/g,'').slice(-2).toUpperCase()||'??';
            const time=new Date(c.timestamp).toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});
            let prev=(c.message||'').replace(/^\*{0,2}Claus:\*{0,2}\s*/i,'');
            const pfx=c.sender_role==='agent'?'Claus: ':c.sender_role==='admin_manual'?'Admin: ':'';
            prev=(pfx+prev).substring(0,52);

            const li=document.createElement('li');
            li.className='conv-li'+(cvNum===num?' active':'');
            li.dataset.n=num;
            li.innerHTML=`
                <div class="conv-av">${esc(init)}</div>
                <div class="conv-det">
                    <div class="conv-num">${esc(num)}</div>
                    <div class="conv-pre">${esc(prev)}${(c.message||'').length>52?'…':''}</div>
                </div>
                <div class="conv-ts">${time}</div>`;
            li.onclick=()=>selectConv(num);
            ul.appendChild(li);
        });
    }catch(e){ console.error('loadList',e); }
}

function filterList(q){
    q=q.toLowerCase();
    document.querySelectorAll('.conv-li').forEach(li=>{ li.style.display=li.dataset.n?.toLowerCase().includes(q)?'':'none'; });
}

function selectConv(num){
    cvNum=num;
    document.getElementById('cvph').style.display='none';
    const v=document.getElementById('cvview'); v.style.display='flex';
    document.getElementById('cvnum').textContent=num;
    document.getElementById('cvav').textContent=num.replace(/\D/g,'').slice(-2).toUpperCase();
    document.querySelectorAll('.conv-li').forEach(li=>li.classList.toggle('active',li.dataset.n===num));
    loadHistory(num);
}

async function loadHistory(num){
    try{
        const msgs=await fetchData('api.php?action=get_conversation_history&number='+encodeURIComponent(num));
        const box=document.getElementById('cvmsgs');
        box.innerHTML='';
        let lastDate='';
        msgs.forEach(log=>{
            const isOut=log.sender_role==='agent'||log.sender_role==='admin_manual';
            const ts=new Date(log.timestamp);
            const dateS=ts.toLocaleDateString('pt-BR');
            const timeS=ts.toLocaleString('pt-BR');
            const sName=log.sender_role==='agent'?'Claus':log.sender_role==='admin_manual'?'Admin':'';
            if(dateS!==lastDate){ lastDate=dateS; box.insertAdjacentHTML('beforeend',`<div class="cdate"><span>${dateS}</span></div>`); }
            let raw=log.message||'';
            let imgs='';
            raw=raw.replace(/\[IMG:(.*?)\]/g,(_,src)=>{ imgs+=`<img class="cimg" src="${esc(src)}" onclick="openLb('${esc(src)}')" alt="">`;return''; });
            raw=raw.trim();
            const lbl=sName?`<span class="csender">${esc(sName)}</span>`:'';
            box.insertAdjacentHTML('beforeend',`<div class="crow ${isOut?'out':'in'}"><div class="cbub">${lbl}${imgs}${raw?`<span class="ctext">${fmt(raw)}</span>`:''}<div class="cmeta">${timeS}</div></div></div>`);
        });
        setTimeout(()=>{ box.scrollTop=box.scrollHeight; },80);
    }catch(e){ console.error('loadHistory',e); }
}

async function cvSend(){
    if(cvSending||!cvNum) return;
    const inp=document.getElementById('cvinp'), btn=document.getElementById('cvsend');
    const msg=inp.value.trim(); if(!msg) return;
    cvSending=true; btn.disabled=true; inp.value=''; inp.style.height='auto';
    try{
        const r=await postData('api.php?action=send_manual_message',{number:cvNum,message:msg});
        if(r.status==='success') await loadHistory(cvNum);
        else{ inp.value=msg; alert('Erro: '+(r.message||'falha')); }
    }catch(e){ inp.value=msg; alert('Erro de comunicação.'); }
    finally{ cvSending=false; btn.disabled=false; document.getElementById('cvinp').focus(); }
}

async function clearAll(){
    if(!confirm('⚠️ Deletar TODOS os chats?')) return;
    if(!confirm('Confirmar — irreversível.')) return;
    const r=await fetchData('api.php?action=clear_all_chats');
    if(r.status==='success'){ cvNum=null; document.getElementById('cvph').style.display='flex'; document.getElementById('cvview').style.display='none'; loadList(); }
}

function openLb(src){ document.getElementById('cvlbi').src=src; document.getElementById('cvlb').classList.add('show'); }
function closeLb()  { document.getElementById('cvlb').classList.remove('show'); }

window.addEventListener('load',()=>{ loadList(); setInterval(()=>{ loadList(); if(cvNum) loadHistory(cvNum); },15000); });
</script>

<?php require_once 'footer.php'; ?>
