<?php
require_once "../../config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$acta_id = $_POST['acta_id'] ?? 0;
$comentario = trim($_POST['comentario'] ?? '');
$usuario_id = $_SESSION['usuario_id'];

if (!$acta_id) {
    echo json_encode(['success' => false, 'error' => 'ID de acta inválido']);
    exit;
}

if (empty($comentario)) {
    echo json_encode(['success' => false, 'error' => 'El comentario no puede estar vacío']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO acta_comentarios (acta_id, usuario_id, comentario) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $acta_id, $usuario_id, $comentario);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Comentario guardado']);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar comentario']);
}
