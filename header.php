<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirecionar para index.php se não estiver logado
$page_name = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['loggedin']) && $page_name !== 'index.php' && $page_name !== '') {
    header("Location: index.php");
    exit;
}

// Pegar o nome da página atual para marcar no menu
$current_page = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Painel Claus'; ?> | Agente WhatsApp</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f9f9fa;
            --card-bg: #ffffff;
            --text-primary: #111111;
            --text-secondary: #666666;
            --primary: #ff6d5a;
            --primary-hover: #e65e4d;
            --border-color: #e0e0e0;
            --success: #2dce89;
            --danger: #f5365c;
            --warning: #fb6340;
            --sidebar-bg: #ffffff;
            --sidebar-text: #444444;
            --sidebar-active: #ffece9;
            --sidebar-active-text: #ff6d5a;
        }

        [data-theme="dark"] {
            --bg-color: #222222;
            --card-bg: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #aaaaaa;
            --primary: #ff6d5a;
            --primary-hover: #e65e4d;
            --border-color: #444444;
            --success: #2dce89;
            --danger: #f5365c;
            --sidebar-bg: #2d2d2d;
            --sidebar-text: #cccccc;
            --sidebar-active: #3a3a3a;
            --sidebar-active-text: #ff6d5a;
        }

        * { box-sizing: border-box; transition: background-color 0.3s, color 0.3s; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-primary); margin: 0; display: flex; height: 100vh; overflow: hidden; }

        .sidebar { width: 250px; background-color: var(--sidebar-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px; }
        .logo { font-size: 24px; font-weight: 700; color: var(--primary); margin-bottom: 40px; display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo span { color: var(--text-primary); }
        
        .nav-item { padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; cursor: pointer; color: var(--sidebar-text); font-weight: 500; display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .nav-item:hover { background-color: var(--bg-color); }
        .nav-item.active { background-color: var(--sidebar-active); color: var(--sidebar-active-text); }

        .db-status { margin-top: auto; padding: 15px; background-color: var(--bg-color); border-radius: 8px; font-size: 13px; display: flex; align-items: center; gap: 8px; border: 1px solid var(--border-color); }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background-color: #ccc; }
        .status-dot.connected { background-color: var(--success); box-shadow: 0 0 5px var(--success); }
        .status-dot.error { background-color: var(--danger); }

        .main-content { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-shrink: 0; }
        .page-title { font-size: 24px; font-weight: 600; }
        
        .theme-toggle { cursor: pointer; background: none; border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; color: var(--text-primary); font-size: 14px; }
        
        /* Shared components */
        .card { background-color: var(--card-bg); border-radius: 10px; padding: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid var(--border-color); margin-bottom: 20px; }
        textarea, input[type="text"], input[type="password"], select { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--bg-color); color: var(--text-primary); font-family: inherit; margin-bottom: 15px; font-size: 14px; }
        textarea { min-height: 150px; resize: vertical; line-height: 1.5; }
        button { background-color: var(--primary); color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; }
        button:hover { background-color: var(--primary-hover); }
        .btn-outline { background-color: transparent; border: 1px solid var(--danger); color: var(--danger); padding: 6px 12px; font-size: 12px; }
        .btn-outline:hover { background-color: var(--danger); color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 15px; border-bottom: 1px solid var(--border-color); }
        th { color: var(--text-secondary); font-weight: 600; font-size: 13px; text-transform: uppercase; }
        td { color: var(--text-primary); font-size: 14px; }
        .role-badge { padding: 2px 6px; border-radius: 4px; font-size: 11px; background-color: var(--border-color); color: var(--text-secondary); }
        .log-status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-sent { background-color: rgba(45, 206, 137, 0.1); color: var(--success); }
        .status-pending { background-color: rgba(251, 99, 64, 0.1); color: var(--warning); }
        .status-processing { background-color: rgba(0, 123, 255, 0.1); color: var(--info); }
        .status-failed { background-color: rgba(220, 53, 69, 0.1); color: var(--danger); }
        /* spinner for processing indicator */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { display: inline-block; width: 12px; height: 12px; border: 2px solid currentColor; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite; vertical-align: middle; margin-right: 4px; }
        .provider-btn { background: none; border: 1px solid var(--border-color); color: var(--text-primary); padding: 8px 16px; border-radius: 6px; cursor: pointer; }
        .provider-btn.active { background-color: var(--primary); color: white; border-color: var(--primary); }

        /* Chat styles */
        .chat-container { flex: 1; display: flex; flex-direction: column; background: var(--card-bg); border-radius: 10px; border: 1px solid var(--border-color); overflow: hidden; height: 0; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 10px; }
        .message { max-width: 80%; padding: 10px 15px; border-radius: 15px; font-size: 14px; line-height: 1.4; position: relative; }
        .message.admin { align-self: flex-end; background: var(--primary); color: white; border-bottom-right-radius: 2px; }
        .message.agent { align-self: flex-start; background: var(--bg-color); color: var(--text-primary); border: 1px solid var(--border-color); border-bottom-left-radius: 2px; }
        .message-info { font-size: 10px; margin-top: 5px; opacity: 0.7; display: block; }

        /* Estilos para entrada de voz */
        .input-container { display: flex; align-items: flex-end; gap: 8px; }
        .chat-input-area textarea { flex: 1; }
        #voice-btn.recording { color: var(--danger); animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="chat.php" class="logo">⚡ <span>Claus</span></a>
    
    <a href="chat.php" class="nav-item <?php echo $current_page === 'chat' ? 'active' : ''; ?>">
        💬 Chat
    </a>
    <a href="identidade.php" class="nav-item <?php echo $current_page === 'identidade' ? 'active' : ''; ?>">
        🧠 Identidade
    </a>
    <a href="memoria.php" class="nav-item <?php echo $current_page === 'memoria' ? 'active' : ''; ?>">
        📚 Memória
    </a>
    <a href="conversas.php" class="nav-item <?php echo $current_page === 'conversas' ? 'active' : ''; ?>">
        📂 Conversas
    </a>
    <a href="log.php" class="nav-item <?php echo $current_page === 'log' ? 'active' : ''; ?>">
        📊 Logs
    </a>

    <div class="db-status" style="flex-direction: column; align-items: flex-start; gap: 5px;">
        <div style="font-size: 11px; color: var(--text-secondary); margin-bottom: 2px;">Versão 1.16</div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <div id="db-dot" class="status-dot"></div>
            <span id="db-text">Verificando DB...</span>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="header-top">
        <div class="page-title"><?php echo $page_title ?? 'Claus'; ?></div>
        <div style="display: flex; gap: 10px;">
            <button class="theme-toggle" onclick="toggleTheme()">🌙 Modo Escuro</button>
            <a href="index.php?logout=1" style="text-decoration: none;"><button style="background-color: var(--danger);">Sair</button></a>
        </div>
    </div>
