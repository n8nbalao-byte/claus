<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$page_name = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['loggedin']) && $page_name !== 'index.php' && $page_name !== '') {
    header("Location: index.php"); exit;
}
$current_page = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Claus'; ?> | Agent Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --wa-green:      #00a884;
            --wa-green-dk:   #008069;
            --wa-green-lt:   #d9fdd3;
            --wa-bg:         #f0f2f5;
            --wa-sidebar:    #ffffff;
            --wa-topbar:     #202c33;
            --wa-topbar-txt: #e9edef;
            --wa-panel-hdr:  #f0f2f5;
            --wa-msg-out:    #d9fdd3;
            --wa-msg-in:     #ffffff;
            --wa-chat-bg:    #efeae2;
            --wa-txt:        #111b21;
            --wa-txt2:       #54656f;
            --wa-border:     #e9edef;
            --wa-icon:       #54656f;
            --wa-hover:      #f5f6f6;
            --wa-tick:       #53bdeb;
            --danger:        #ef4444;
            --warning:       #f59e0b;
            --bg-color:      var(--wa-bg);
            --card-bg:       #ffffff;
            --text-primary:  var(--wa-txt);
            --text-secondary:var(--wa-txt2);
            --primary:       var(--wa-green);
            --border-color:  var(--wa-border);
            --success:       #25d366;
        }
        [data-theme="dark"] {
            --wa-bg:         #111b21;
            --wa-sidebar:    #202c33;
            --wa-topbar:     #202c33;
            --wa-panel-hdr:  #202c33;
            --wa-msg-out:    #005c4b;
            --wa-msg-in:     #202c33;
            --wa-chat-bg:    #0b141a;
            --wa-txt:        #e9edef;
            --wa-txt2:       #8696a0;
            --wa-border:     #2a3942;
            --wa-icon:       #8696a0;
            --wa-hover:      #2a3942;
            --bg-color:      var(--wa-bg);
            --card-bg:       var(--wa-sidebar);
            --text-primary:  var(--wa-txt);
            --text-secondary:var(--wa-txt2);
            --border-color:  var(--wa-border);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--wa-bg); color: var(--wa-txt); display: flex; height: 100vh; overflow: hidden; }

        /* ── SVG icons ── */
        .ico { width: 20px; height: 20px; display: block; flex-shrink: 0; fill: none; stroke: currentColor; stroke-width: 1.65; stroke-linecap: round; stroke-linejoin: round; }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 56px; min-width: 56px;
            background: var(--wa-sidebar); border-right: 1px solid var(--wa-border);
            display: flex; flex-direction: column;
            transition: width .22s cubic-bezier(.4,0,.2,1), min-width .22s cubic-bezier(.4,0,.2,1);
            overflow: hidden; z-index: 200; flex-shrink: 0;
        }
        .sidebar.open { width: 230px; min-width: 230px; }

        .sb-brand {
            height: 56px; background: var(--wa-topbar);
            display: flex; align-items: center; padding: 0 13px; gap: 10px; flex-shrink: 0;
        }
        .sb-brand-mark {
            width: 27px; height: 27px; background: var(--wa-green); border-radius: 6px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .sb-brand-mark svg { width: 15px; height: 15px; fill: white; }
        .sb-brand-name { font-size: 14.5px; font-weight: 600; color: var(--wa-topbar-txt); white-space: nowrap; opacity: 0; transition: opacity .13s; }
        .sidebar.open .sb-brand-name { opacity: 1; }

        .sb-nav { flex: 1; padding: 5px 0; overflow: hidden; }
        .nav-a {
            display: flex; align-items: center; gap: 11px; padding: 10px 17px;
            color: var(--wa-txt2); font-size: 13.5px; font-weight: 500;
            text-decoration: none; white-space: nowrap;
            border-left: 2px solid transparent; overflow: hidden;
            transition: background .13s, color .13s, border-color .13s;
        }
        .nav-a:hover  { background: var(--wa-hover); color: var(--wa-txt); }
        .nav-a.active { background: var(--wa-hover); color: var(--wa-green); border-left-color: var(--wa-green); }
        .nav-lbl { opacity: 0; transition: opacity .11s; flex: 1; }
        .sidebar.open .nav-lbl { opacity: 1; }
        .sb-sep { height: 1px; background: var(--wa-border); margin: 3px 0; }

        /* ── FLIP CARDS ── */
        .sb-footer { padding: 7px; border-top: 1px solid var(--wa-border); flex-shrink: 0; display: flex; flex-direction: column; gap: 3px; }
        .flip-card { height: 44px; perspective: 800px; cursor: pointer; user-select: none; }
        .flip-inner { position: relative; width: 100%; height: 100%; transition: transform .48s cubic-bezier(.4,0,.2,1); transform-style: preserve-3d; }
        .flip-card.on .flip-inner { transform: rotateY(180deg); }
        .flip-face { position: absolute; inset: 0; border-radius: 6px; display: flex; align-items: center; padding: 0 9px; gap: 8px; overflow: hidden; backface-visibility: hidden; -webkit-backface-visibility: hidden; }
        .ff { background: var(--wa-hover); border: 1px solid var(--wa-border); }
        .fb { transform: rotateY(180deg); background: var(--wa-green); color: white; flex-direction: column; align-items: flex-start; justify-content: center; gap: 0; padding: 0 11px; }

        .fc-ico-wrap { color: var(--wa-txt2); flex-shrink: 0; }
        .fc-info { opacity: 0; transition: opacity .11s; flex: 1; min-width: 0; overflow: hidden; }
        .sidebar.open .fc-info { opacity: 1; }
        .fc-t { font-size: 11.5px; font-weight: 600; color: var(--wa-txt); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fc-s { font-size: 10.5px; color: var(--wa-txt2); display: flex; align-items: center; gap: 3px; white-space: nowrap; overflow: hidden; }
        .fb .bl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .72; }
        .fb .bv { font-size: 11px; font-weight: 600; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .fb .bs { font-size: 10px; opacity: .78; white-space: nowrap; overflow: hidden; }

        .sdot { width: 6px; height: 6px; border-radius: 50%; background: #888; flex-shrink: 0; }
        .sdot.ok  { background: #25d366; box-shadow: 0 0 4px #25d36699; }
        .sdot.err { background: var(--danger); box-shadow: 0 0 4px #ef444499; }

        .theme-row {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 7px 9px; border-radius: 6px; border: 1px solid var(--wa-border);
            background: none; color: var(--wa-txt2); cursor: pointer;
            font-size: 12.5px; font-family: inherit; width: 100%; overflow: hidden;
            transition: background .13s; white-space: nowrap;
        }
        .theme-row:hover { background: var(--wa-hover); }
        .theme-lbl { opacity: 0; transition: opacity .11s; }
        .sidebar.open .theme-lbl { opacity: 1; }

        /* ── MAIN ── */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .page-topbar { height: 56px; background: var(--wa-topbar); padding: 0 20px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
        .page-topbar-title { font-size: 15px; font-weight: 600; color: var(--wa-topbar-txt); }
        .page-body { flex: 1; overflow-y: auto; padding: 20px; }

        /* ── SHARED ── */
        .card { background: var(--card-bg); border-radius: 8px; padding: 20px; border: 1px solid var(--border-color); margin-bottom: 16px; }
        textarea, input[type="text"], input[type="password"], select { width: 100%; padding: 9px 12px; border-radius: 7px; border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-primary); font-family: inherit; font-size: 14px; outline: none; margin-bottom: 11px; transition: border-color .2s; }
        textarea:focus, input:focus, select:focus { border-color: var(--wa-green); }
        textarea { min-height: 110px; resize: vertical; line-height: 1.5; }
        button { background: var(--wa-green); color: white; padding: 8px 16px; border: none; border-radius: 7px; cursor: pointer; font-weight: 600; font-size: 14px; font-family: inherit; transition: background .14s; }
        button:hover { background: var(--wa-green-dk); }
        .btn-outline { background: transparent; border: 1px solid var(--danger); color: var(--danger); }
        .btn-outline:hover { background: var(--danger); color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 11px 14px; border-bottom: 1px solid var(--border-color); }
        th { color: var(--text-secondary); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
        td { font-size: 13.5px; }
        .role-badge { padding: 2px 7px; border-radius: 10px; font-size: 11px; background: var(--border-color); color: var(--text-secondary); }
        .log-status { padding: 2px 7px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-sent       { background:rgba(37,211,102,.12); color:#25d366; }
        .status-pending    { background:rgba(245,158,11,.12);  color:var(--warning); }
        .status-processing { background:rgba(83,189,235,.12);  color:#53bdeb; }
        .status-failed     { background:rgba(239,68,68,.12);   color:var(--danger); }
        @keyframes spin { to{transform:rotate(360deg)} }
        .spinner { display:inline-block; width:11px; height:11px; border:2px solid currentColor; border-top-color:transparent; border-radius:50%; animation:spin 1s linear infinite; vertical-align:middle; margin-right:3px; }
        .provider-btn { background:none; border:1px solid var(--border-color); color:var(--text-primary); padding:6px 13px; border-radius:20px; cursor:pointer; font-size:13px; font-family:inherit; transition:all .14s; }
        .provider-btn.active { background:var(--wa-green); color:white; border-color:var(--wa-green); }
        ::-webkit-scrollbar { width:4px; height:4px; }
        ::-webkit-scrollbar-thumb { background:var(--border-color); border-radius:3px; }
    </style>
</head>
<body>

<aside class="sidebar" id="sidebar"
    onmouseenter="document.getElementById('sidebar').classList.add('open')"
    onmouseleave="document.getElementById('sidebar').classList.remove('open')">

    <div class="sb-brand">
        <div class="sb-brand-mark">
            <svg viewBox="0 0 24 24"><path d="M13 3L6 14h6l-1 7 7-11h-6l1-10z"/></svg>
        </div>
        <span class="sb-brand-name">Claus Admin</span>
    </div>

    <nav class="sb-nav">
        <a href="chat.php" class="nav-a <?= $current_page==='chat'?'active':'' ?>">
            <svg class="ico" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span class="nav-lbl">Chat</span>
        </a>
        <a href="identidade.php" class="nav-a <?= $current_page==='identidade'?'active':'' ?>">
            <svg class="ico" viewBox="0 0 24 24"><path d="M12 2a5 5 0 1 1 0 10A5 5 0 0 1 12 2zm0 12c5.33 0 8 2.67 8 4v2H4v-2c0-1.33 2.67-4 8-4z"/></svg>
            <span class="nav-lbl">Identidade</span>
        </a>
        <a href="agenda.php" class="nav-a <?= $current_page==='agenda'?'active':'' ?>">
            <svg class="ico" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/></svg>
            <span class="nav-lbl">Agenda</span>
        </a>
        <a href="conversas.php" class="nav-a <?= $current_page==='conversas'?'active':'' ?>">
            <svg class="ico" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span class="nav-lbl">Conversas</span>
        </a>
        <a href="log.php" class="nav-a <?= $current_page==='log'?'active':'' ?>">
            <svg class="ico" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            <span class="nav-lbl">Logs</span>
        </a>
        <a href="configuracoes.php" class="nav-a <?= $current_page==='configuracoes'?'active':'' ?>">
            <svg class="ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span class="nav-lbl">Configurações</span>
        </a>
        <div class="sb-sep"></div>
        <a href="index.php?logout=1" class="nav-a" style="color:var(--danger)">
            <svg class="ico" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span class="nav-lbl">Sair</span>
        </a>
    </nav>

    <div class="sb-footer">

        <!-- DB flip card -->
        <div class="flip-card" onclick="this.classList.toggle('on')">
            <div class="flip-inner">
                <div class="flip-face ff">
                    <div class="fc-ico-wrap">
                        <svg class="ico" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                    </div>
                    <div class="fc-info">
                        <div class="fc-t">MySQL DB</div>
                        <div class="fc-s"><span id="db-dot" class="sdot"></span><span id="db-text">Verificando…</span></div>
                    </div>
                </div>
                <div class="flip-face fb">
                    <div class="bl">Banco de Dados</div>
                    <div class="bv">u770915504_openclaw</div>
                    <div class="bs">Host: localhost · PDO</div>
                </div>
            </div>
        </div>

        <!-- AI flip card -->
        <div class="flip-card" onclick="this.classList.toggle('on')">
            <div class="flip-inner">
                <div class="flip-face ff">
                    <div class="fc-ico-wrap">
                        <svg class="ico" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                    </div>
                    <div class="fc-info">
                        <div class="fc-t" id="ai-card-name">IA: OpenAI</div>
                        <div class="fc-s" id="ai-card-model">gpt-4o-mini</div>
                    </div>
                </div>
                <div class="flip-face fb">
                    <div class="bl">Provedor Ativo</div>
                    <div class="bv" id="ai-back-provider">OpenAI</div>
                    <div class="bs" id="ai-back-key">Chave não configurada</div>
                </div>
            </div>
        </div>

        <!-- Version flip card -->
        <div class="flip-card" onclick="this.classList.toggle('on')">
            <div class="flip-inner">
                <div class="flip-face ff">
                    <div class="fc-ico-wrap">
                        <svg class="ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div class="fc-info">
                        <div class="fc-t">Versão 1.16</div>
                        <div class="fc-s">Admin-Claus</div>
                    </div>
                </div>
                <div class="flip-face fb">
                    <div class="bl">Sistema</div>
                    <div class="bv">Admin-Claus v1.16</div>
                    <div class="bs">WhatsApp AI Agent</div>
                </div>
            </div>
        </div>

        <!-- Theme -->
        <button class="theme-row" onclick="toggleTheme()" id="theme-btn">
            <svg class="ico" style="width:16px;height:16px" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            <span class="theme-lbl">Alternar tema</span>
        </button>
    </div>
</aside>

<div class="main-content" id="main-content">
<?php if (!isset($hide_page_header) || !$hide_page_header): ?>
<div class="page-topbar">
    <span class="page-topbar-title"><?= htmlspecialchars($page_title ?? 'Claus') ?></span>
    <a href="index.php?logout=1" style="text-decoration:none">
        <button style="background:var(--danger);padding:6px 14px;font-size:13px;">Sair</button>
    </a>
</div>
<div class="page-body">
<?php endif; ?>
