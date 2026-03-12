<?php if (!isset($hide_page_header) || !$hide_page_header): ?>
    </div><!-- /page-body -->
<?php endif; ?>
</div><!-- /main-content -->

<script>
/* ── THEME ── */
function toggleTheme() {
    const html  = document.documentElement;
    const dark  = html.getAttribute('data-theme') === 'dark';
    html.setAttribute('data-theme', dark ? 'light' : 'dark');
    localStorage.setItem('theme', dark ? 'light' : 'dark');
}
(function() {
    if (localStorage.getItem('theme') === 'dark')
        document.documentElement.setAttribute('data-theme','dark');
})();

/* ── DB STATUS ── */
async function checkDB() {
    const dot  = document.getElementById('db-dot');
    const txt  = document.getElementById('db-text');
    try {
        const r = await fetch('api.php?action=check_db_status');
        if (!r.ok) throw new Error('http ' + r.status);
        const d = await r.json();
        if (d.status === 'connected') {
            dot && (dot.className = 'sdot ok');
            txt && (txt.textContent = 'Conectado');
        } else throw new Error(d.message);
    } catch(e) {
        dot && (dot.className = 'sdot err');
        txt && (txt.textContent = 'Desconectado');
    }
}

/* ── AI CARD ── */
async function loadAICard() {
    try {
        const d  = await (await fetch('api.php?action=get_config')).json();
        const pr = d.ai_provider || 'openai';
        const names = { openai:'OpenAI', groq:'Groq', gemini:'Gemini', claude:'Claude', huggingface:'HuggingFace', together:'Together AI' };
        const name  = names[pr] || pr;
        const model = d[pr+'_model'] || '—';
        const key   = d[pr+'_apikey'] || '';
        document.getElementById('ai-card-name')    ?.setText?.(name + ' · ' + model) || setTxt('ai-card-name', name);
        document.getElementById('ai-card-model')   ?.setText?.(model)                || setTxt('ai-card-model', model);
        document.getElementById('ai-back-provider')?.setText?.(name + ' / ' + model) || setTxt('ai-back-provider', name + ' · ' + model);
        setTxt('ai-back-key', key ? 'Chave: ' + key.slice(0,10) + '***' : 'Chave não configurada');
    } catch(e) {}
}
function setTxt(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

/* ── SHARED HELPERS ── */
async function fetchData(url) { return (await fetch(url)).json(); }
async function postData(url, data) {
    return (await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) })).json();
}
function filterTable(tid, q) {
    q = q.toLowerCase();
    document.querySelectorAll('#'+tid+' tbody tr').forEach(r => {
        r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
}

window.addEventListener('load', () => {
    checkDB();
    loadAICard();
    setInterval(checkDB, 30000);
});
</script>
</body>
</html>
