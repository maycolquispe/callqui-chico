<?php
require_once '../../includes/verificar_sesion.php';
require_once '../../config/database.php';

SessionManager::init();
$conn = getDB();

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID no válido']);
    exit;
}

$stmt = $conn->prepare("SELECT a.*, u.nombres as nombre_usuario, u.apellidos as apellido_usuario, u.dni as dni_usuario
        FROM adjudicaciones a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$adj = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$adj) {
    echo json_encode(['success' => false, 'message' => 'Adjudicación no encontrada']);
    exit;
}

echo json_encode([
    'success' => true,
    'adjudicacion' => $adj
]);
