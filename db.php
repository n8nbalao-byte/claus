<?php
// db.php
// Configurações de conexão com o banco de dados na Hostinger
$servername = "localhost"; // Geralmente localhost funciona quando o script roda no próprio servidor. 
// Alternativas se localhost falhar: "srv1889.hstgr.io" ou "193.203.175.202"

$username = "u770915504_openclaw"; // Usuário do Banco de Dados
$password = "Aa366560402@";        // Senha do Banco de Dados
$dbname = "u770915504_openclaw";   // Nome do Banco de Dados

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    // Define o modo de erro do PDO para exceção
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
