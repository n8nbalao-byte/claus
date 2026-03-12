<?php
// upload.php — Image upload & agent memory
header('Content-Type: application/json');

require 'db.php';

$uploadDir = __DIR__ . '/uploads/agent_images/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Método inválido']);
    exit;
}

$file    = $_FILES['image'] ?? null;
$caption = trim($_POST['caption'] ?? '');

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status'=>'error','message'=>'Nenhum arquivo ou erro de upload']);
    exit;
}

$allowed = ['image/jpeg','image/png','image/gif','image/webp'];
$mime    = mime_content_type($file['tmp_name']);
if (!in_array($mime, $allowed)) {
    echo json_encode(['status'=>'error','message'=>'Tipo de arquivo não permitido: '.$mime]);
    exit;
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
$stored   = uniqid('img_', true) . '.' . strtolower($ext);
$destPath = $uploadDir . $stored;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['status'=>'error','message'=>'Falha ao salvar arquivo']);
    exit;
}

// Determine public URL
$dir  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$url  = $base . $dir . '/uploads/agent_images/' . $stored;

// Save to DB (agent_images table — create if needed)
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS agent_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255),
        url TEXT,
        description TEXT,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $conn->prepare(
        "INSERT INTO agent_images (filename, original_name, url, description) VALUES (?,?,?,?)"
    );
    $stmt->execute([$stored, $file['name'], $url, $caption]);
} catch (Exception $e) {
    // Non-fatal — file already saved
}

echo json_encode([
    'status'   => 'success',
    'url'      => $url,
    'filename' => $stored,
    'caption'  => $caption
]);
