<?php
$page_title       = "Chat";
$hide_page_header = true;
require_once 'header.php';
?>
<style>
/* ── WRAPPER ── */
#cw { flex:1; display:flex; flex-direction:column; overflow:hidden; background:#ece5dd; }
[data-theme="dark"] #cw { background:#0b141a; }

/* ── TOP BAR — clara, como WhatsApp ── */
#ct {
    height:58px; background:#f0f2f5; padding:0 4px 0 14px;
    display:flex; align-items:center; gap:12px; flex-shrink:0;
    border-bottom:1px solid #d1d7db;
}
[data-theme="dark"] #ct { background:#202c33; border-bottom-color:#2a3942; }

.ct-av {
    width:40px; height:40px; border-radius:50%; background:#00a884;
    display:flex; align-items:center; justify-content:center;
    font-size:14px; font-weight:700; color:#fff; flex-shrink:0;
}
.ct-info { flex:1; }
.ct-name { font-size:15px; font-weight:600; color:#111b21; line-height:1.25; }
.ct-sub  { font-size:12px; color:#667781; }
[data-theme="dark"] .ct-name { color:#e9edef; }
[data-theme="dark"] .ct-sub  { color:#8696a0; }

/* Topbar buttons — 46×46, icons 24px */
.ct-actions { display:flex; }
.ct-btn {
    width:46px; height:46px; border-radius:50%;
    background:none; border:none; color:#54656f; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    transition:color .13s, background .13s;
}
.ct-btn:hover { color:#111b21; background:rgba(0,0,0,.06); }
[data-theme="dark"] .ct-btn       { color:#aebac1; }
[data-theme="dark"] .ct-btn:hover { color:#e9edef; background:rgba(255,255,255,.08); }
.ct-btn svg { width:24px; height:24px; fill:none; stroke:currentColor; stroke-width:1.7; stroke-linecap:round; stroke-linejoin:round; }

/* ── MESSAGES ── */
#msgs { flex:1; overflow-y:auto; padding:16px 10%; display:flex; flex-direction:column; gap:2px; }
.date-sep { display:flex; justify-content:center; margin:10px 0; }
.date-sep span { background:rgba(255,255,255,.92); color:#54656f; font-size:11.5px; padding:4px 13px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
[data-theme="dark"] .date-sep span { background:rgba(32,44,51,.92); color:#8696a0; }

/* Bubbles */
.mrow { display:flex; flex-direction:column; margin-bottom:1px; }
.mrow.out { align-items:flex-end; }
.mrow.in  { align-items:flex-start; }
.bub { max-width:66%; min-width:76px; padding:7px 11px 6px; border-radius:7.5px; position:relative; font-size:14.2px; line-height:1.5; word-break:break-word; box-shadow:0 1px 2px rgba(0,0,0,.13); }
.mrow.out .bub { background:#d9fdd3; border-bottom-right-radius:2px; color:#111b21; }
.mrow.in  .bub { background:#fff;    border-bottom-left-radius:2px;  color:#111b21; }
[data-theme="dark"] .mrow.out .bub { background:#005c4b; color:#e9edef; }
[data-theme="dark"] .mrow.in  .bub { background:#202c33; color:#e9edef; }
.mrow.out .bub::after { content:''; position:absolute; right:-7px; bottom:0; border:7px solid transparent; border-left-color:#d9fdd3; border-right:0; border-bottom:0; }
.mrow.in  .bub::after { content:''; position:absolute; left:-7px;  bottom:0; border:7px solid transparent; border-right-color:#fff;    border-left:0; border-bottom:0; }
[data-theme="dark"] .mrow.out .bub::after { border-left-color:#005c4b; }
[data-theme="dark"] .mrow.in  .bub::after { border-right-color:#202c33; }
.bub-sender { font-size:12.5px; font-weight:700; color:#00a884; margin-bottom:2px; display:block; }
.bub-text   { color:inherit; }
.bub-text strong { font-weight:700; }
.bub-meta   { display:flex; align-items:center; justify-content:flex-end; gap:3px; margin-top:3px; font-size:11px; color:#8696a0; }
.mrow.in .bub-meta { justify-content:flex-start; }
.tick { color:#53bdeb; font-size:12px; }
.bub-img { max-width:260px; max-height:200px; border-radius:6px; display:block; cursor:pointer; margin-bottom:4px; }

/* ── INPUT AREA ── */
#ia { background:#f0f2f5; padding:8px 10px; display:flex; align-items:flex-end; gap:6px; flex-shrink:0; border-top:1px solid #d1d7db; }
[data-theme="dark"] #ia { background:#202c33; border-top-color:#2a3942; }

/* Input icon buttons — 46×46, icons 24px */
.iico {
    width:46px; height:46px; border-radius:50%;
    background:none; border:none; color:#54656f; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; transition:color .13s, background .13s;
}
.iico:hover { color:#111b21; background:rgba(0,0,0,.06); }
[data-theme="dark"] .iico       { color:#8696a0; }
[data-theme="dark"] .iico:hover { color:#e9edef; background:rgba(255,255,255,.08); }
.iico svg { width:24px; height:24px; fill:none; stroke:currentColor; stroke-width:1.7; stroke-linecap:round; stroke-linejoin:round; }
.iico.rec svg { stroke:#ef4444; animation:pulse 1.2s ease-in-out infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }

.ibox { flex:1; background:#fff; border-radius:22px; display:flex; align-items:flex-end; padding:9px 14px; border:1px solid #d1d7db; transition:border-color .18s; }
[data-theme="dark"] .ibox { background:#2a3942; border-color:#374f5c; }
.ibox:focus-within { border-color:#00a884; }
#ci { flex:1; border:none; outline:none; background:transparent; color:#111b21; font-family:inherit; font-size:14.5px; resize:none; line-height:1.5; min-height:24px; max-height:120px; overflow-y:auto; padding:0; width:100%; margin:0; }
[data-theme="dark"] #ci { color:#e9edef; }
#ci::placeholder { color:#8696a0; }

#sb { width:48px; height:48px; border-radius:50%; background:#00a884; border:none; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; cursor:pointer; transition:background .13s, transform .1s; }
#sb:hover  { background:#008069; }
#sb:active { transform:scale(.9); }
#sb:disabled { opacity:.5; cursor:default; transform:none; }
#sb svg { width:21px; height:21px; fill:none; stroke:#fff; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }

/* Recording wave */
#rwave { display:none; align-items:center; gap:5px; color:#ef4444; font-size:12px; font-weight:600; white-space:nowrap; }
.wbars { display:flex; gap:2px; align-items:center; height:18px; }
.wbars span { display:block; width:3px; background:#ef4444; border-radius:3px; animation:wv 1s ease-in-out infinite; }
.wbars span:nth-child(2){animation-delay:.12s}
.wbars span:nth-child(3){animation-delay:.24s}
.wbars span:nth-child(4){animation-delay:.36s}
.wbars span:nth-child(5){animation-delay:.48s}
@keyframes wv{0%,100%{height:4px}50%{height:16px}}

/* Attach preview */
#ap { display:none; position:absolute; bottom:74px; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #d1d7db; border-radius:10px; padding:10px; box-shadow:0 4px 20px rgba(0,0,0,.15); z-index:50; max-width:280px; }
[data-theme="dark"] #ap { background:#2a3942; border-color:#374f5c; }
#api { max-width:240px; max-height:160px; border-radius:7px; display:block; }
#apn { font-size:11px; color:#54656f; margin-top:5px; text-align:center; }
#apc { position:absolute; top:5px; right:5px; background:rgba(0,0,0,.4); color:#fff; border:none; border-radius:50%; width:20px; height:20px; font-size:11px; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; }

/* Mic toast */
#mic-toast { display:none; position:absolute; bottom:74px; left:50%; transform:translateX(-50%); background:#1f2937; color:#f3f4f6; font-size:12.5px; padding:9px 16px; border-radius:8px; white-space:nowrap; z-index:90; box-shadow:0 4px 14px rgba(0,0,0,.3); }

/* Lightbox */
#lb { display:none; position:fixed; inset:0; background:rgba(0,0,0,.87); z-index:9999; align-items:center; justify-content:center; }
#lb.show { display:flex; }
#lb img { max-width:92vw; max-height:92vh; border-radius:8px; }
#lbc { position:fixed; top:14px; right:14px; background:rgba(255,255,255,.13); color:#fff; border:none; border-radius:50%; width:38px; height:38px; font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
</style>

<div id="cw">

    <!-- TOP BAR -->
    <div id="ct">
        <div class="ct-av">CL</div>
        <div class="ct-info">
            <div class="ct-name">Claus — Agente IA</div>
            <div class="ct-sub">online</div>
        </div>
        <div class="ct-actions">
            <button class="ct-btn" onclick="toggleTheme()" title="Alternar tema">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            </button>
            <button class="ct-btn" onclick="clearScreen()" title="Limpar tela">
                <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            </button>
            <a href="index.php?logout=1" style="text-decoration:none">
                <button class="ct-btn" title="Sair">
                    <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </a>
        </div>
    </div>

    <!-- MESSAGES -->
    <div id="msgs"><div style="text-align:center;color:#8696a0;padding:40px 0;font-size:13px;">Carregando…</div></div>

    <!-- OVERLAYS (positioned above input) -->
    <div style="position:relative;">
        <div id="ap"><button id="apc" onclick="clearAttach()">✕</button><img id="api" src="" alt=""><div id="apn"></div></div>
        <div id="mic-toast"></div>
    </div>

    <!-- INPUT -->
    <div id="ia">
        <label class="iico" title="Enviar imagem" style="cursor:pointer">
            <svg viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            <input type="file" id="fi" accept="image/*" style="display:none" onchange="onFile(event)">
        </label>

        <div class="ibox">
            <textarea id="ci" placeholder="Digite uma mensagem para o Claus…" rows="1"
                onkeydown="onKey(event)" oninput="grow(this)"></textarea>
        </div>

        <div id="rwave">
            <div class="wbars"><span></span><span></span><span></span><span></span><span></span></div>
            Ouvindo…
        </div>

        <button class="iico" id="mic" onclick="toggleMic()" title="Gravar mensagem de voz">
            <svg viewBox="0 0 24 24">
                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                <line x1="12" y1="19" x2="12" y2="23"/>
                <line x1="8"  y1="23" x2="16" y2="23"/>
            </svg>
        </button>

        <button id="sb" onclick="doSend()" title="Enviar">
            <svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
    </div>
</div>

<div id="lb" onclick="closeLb()"><button id="lbc" onclick="closeLb()">✕</button><img id="lbi" src="" alt=""></div>

<script>
function grow(el){ el.style.height='auto'; el.style.height=Math.min(el.scrollHeight,120)+'px'; }
function onKey(e){ if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); doSend(); } }

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

/* Dedup: 8-sec window to catch api.php + webhook.php double saves */
function dedup(logs){
    const seen=new Set();
    return logs.filter(l=>{
        const b=Math.floor(new Date(l.timestamp).getTime()/8000);
        const k=(l.sender_role||'')+'|'+(l.sender_number||'')+'|'+(l.message||'').substring(0,100)+'|'+b;
        if(seen.has(k)) return false;
        seen.add(k); return true;
    });
}

let lastSig='', chatLock=false;
async function loadChat(force=false){
    if(chatLock) return; chatLock=true;
    try{
        const raw=await fetchData('api.php?action=get_admin_chat&limit=120');
        const logs=dedup(raw.filter(l=>['admin','agent','admin_manual'].includes(l.sender_role)).reverse());
        const sig=logs.map(l=>l.timestamp+'|'+l.status).join();
        const box=document.getElementById('msgs');
        if(!force&&sig===lastSig) return;
        lastSig=sig;
        const atBot=box.scrollHeight-box.scrollTop<=box.clientHeight+80;
        box.innerHTML='';
        let prevDate='';
        logs.forEach(log=>{
            const isOut=log.sender_role!=='agent';
            const ts=new Date(log.timestamp);
            const dateS=ts.toLocaleDateString('pt-BR');
            const timeS=ts.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});
            if(dateS!==prevDate){ prevDate=dateS; box.insertAdjacentHTML('beforeend',`<div class="date-sep"><span>${dateS}</span></div>`); }
            let raw=log.message||'';
            let imgs='';
            raw=raw.replace(/\[IMG:(.*?)\]/g,(_,src)=>{ imgs+=`<img class="bub-img" src="${esc(src)}" onclick="openLb('${esc(src)}')" alt="">`;return''; });
            raw=raw.trim();
            const lbl=log.sender_role==='agent'?`<span class="bub-sender">Claus</span>`:'';
            const tick=isOut?`<span class="tick">✓✓</span>`:'';
            box.insertAdjacentHTML('beforeend',`<div class="mrow ${isOut?'out':'in'}"><div class="bub">${lbl}${imgs}${raw?`<span class="bub-text">${fmt(raw)}</span>`:''}<div class="bub-meta">${timeS} ${tick}</div></div></div>`);
        });
        if(force||atBot) scrollBot();
    }catch(e){ console.error(e); }
    finally{ chatLock=false; }
}
function scrollBot(){ const b=document.getElementById('msgs'); requestAnimationFrame(()=>b.scrollTop=b.scrollHeight); }
function clearScreen(){ document.getElementById('msgs').innerHTML='<div style="text-align:center;color:#8696a0;padding:40px 0;font-size:13px;">Tela limpa. Histórico preservado.</div>'; lastSig=''; }

let sending=false, pendingFile=null;
async function doSend(){
    if(sending) return;
    const inp=document.getElementById('ci'), btn=document.getElementById('sb');
    const msg=inp.value.trim();
    if(!msg&&!pendingFile) return;
    sending=true; btn.disabled=true;
    try{
        if(pendingFile){ await uploadAndSend(pendingFile,msg); clearAttach(); }
        else{ inp.value=''; inp.style.height='auto'; const r=await postData('api.php?action=send_chat_message',{message:msg}); if(r.status!=='success'){inp.value=msg;alert('Erro: '+(r.message||''));} }
        await new Promise(r=>setTimeout(r,400));
        await loadChat(true); scrollBot();
    }catch(e){ alert('Erro ao enviar.'); }
    finally{ sending=false; btn.disabled=false; document.getElementById('ci').focus(); }
}

function onFile(e){ const f=e.target.files[0]; if(!f) return; pendingFile=f; const r=new FileReader(); r.onload=ev=>{ document.getElementById('api').src=ev.target.result; document.getElementById('apn').textContent=f.name; document.getElementById('ap').style.display='block'; }; r.readAsDataURL(f); e.target.value=''; }
function clearAttach(){ pendingFile=null; document.getElementById('ap').style.display='none'; document.getElementById('api').src=''; }
async function uploadAndSend(file,caption){ const fd=new FormData(); fd.append('image',file); fd.append('caption',caption); const r=await fetch('upload.php',{method:'POST',body:fd}); const d=await r.json(); if(d.status==='success') await postData('api.php?action=send_chat_message',{message:`[IMG:${d.url}]${caption?' '+caption:''}`}); else alert('Erro upload: '+(d.message||'')); }

/* ── MIC ── */
let recog=null, recActive=false;
function toggleMic(){ recActive?stopMic():startMic(); }

function startMic(){
    const SR=window.SpeechRecognition||window.webkitSpeechRecognition;
    if(!SR){ toast('Reconhecimento de voz não suportado. Use Chrome ou Edge.'); return; }
    if(!navigator.mediaDevices?.getUserMedia){ launchSR(SR); return; }
    navigator.mediaDevices.getUserMedia({audio:true})
        .then(stream=>{ stream.getTracks().forEach(t=>t.stop()); launchSR(SR); })
        .catch(()=>toast('Permissão de microfone negada. Libere nas configurações do navegador.'));
}

function launchSR(SR){
    recog=new SR();
    recog.lang='pt-BR'; recog.continuous=false; recog.interimResults=false; recog.maxAlternatives=1;
    recog.onstart=()=>{ recActive=true; document.getElementById('mic').classList.add('rec'); document.getElementById('rwave').style.display='flex'; };
    recog.onresult=e=>{ const t=e.results[0][0].transcript.trim(); const inp=document.getElementById('ci'); inp.value=t; grow(inp); stopMic(); setTimeout(doSend,150); };
    recog.onerror=ev=>{ toast(ev.error==='not-allowed'?'Microfone bloqueado.':ev.error==='network'?'Erro de rede no reconhecimento.':'Erro: '+ev.error); stopMic(); };
    recog.onend=()=>stopMic();
    try{ recog.start(); }catch(e){ toast('Erro ao iniciar microfone: '+e.message); }
}
function stopMic(){ recActive=false; try{recog?.stop();}catch(e){} document.getElementById('mic').classList.remove('rec'); document.getElementById('rwave').style.display='none'; }
function toast(msg){ const el=document.getElementById('mic-toast'); el.textContent=msg; el.style.display='block'; setTimeout(()=>el.style.display='none',4500); }

function openLb(src){ document.getElementById('lbi').src=src; document.getElementById('lb').classList.add('show'); }
function closeLb()  { document.getElementById('lb').classList.remove('show'); }

window.addEventListener('load',()=>{ loadChat(true); setInterval(()=>loadChat(),4500); });
</script>
<?php require_once 'footer.php'; ?>
