<?php
session_start();

$valid_user = "n8nbalao.com";
$valid_pass = "Aa366560402@";

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (isset($_SESSION['loggedin'])) {
    header("Location: chat.php");
    exit;
}

if (isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] === $valid_user && $_POST['password'] === $valid_pass) {
        $_SESSION['loggedin'] = true;
        header("Location: chat.php");
        exit;
    } else {
        $error = "Credenciais inválidas";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login | Claus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f9f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        h2 { color: #ff6d5a; margin-top: 0; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #ff6d5a; border: none; color: white; border-radius: 6px; cursor: pointer; font-weight: bold; margin-top: 10px; }
        button:hover { background: #e65e4d; }
        .error { color: #f5365c; font-size: 14px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div style="font-size: 40px; margin-bottom: 10px;">⚡</div>
        <h2>Claus Admin</h2>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Usuário" required autofocus>
            <input type="password" name="password" placeholder="Senha" required>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
