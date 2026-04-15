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
$usuario_id = $_SESSION['usuario_id'];

if (!$acta_id) {
    echo json_encode(['success' => false, 'error' => 'ID de acta inválido']);
    exit;
}

$check = $conn->prepare("SELECT id FROM acta_likes WHERE acta_id = ? AND usuario_id = ?");
$check->bind_param("ii", $acta_id, $usuario_id);
$check->execute();
$check_result = $check->get_result();

if ($check_result->num_rows > 0) {
    $delete = $conn->prepare("DELETE FROM acta_likes WHERE acta_id = ? AND usuario_id = ?");
    $delete->bind_param("ii", $acta_id, $usuario_id);
    $delete->execute();
} else {
    $insert = $conn->prepare("INSERT INTO acta_likes (acta_id, usuario_id) VALUES (?, ?)");
    $insert->bind_param("ii", $acta_id, $usuario_id);
    $insert->execute();
}

$count = $conn->prepare("SELECT COUNT(*) as total FROM acta_likes WHERE acta_id = ?");
$count->bind_param("i", $acta_id);
$count->execute();
$result = $count->get_result()->fetch_assoc();

echo json_encode(['success' => true, 'likes' => $result['total']]);
