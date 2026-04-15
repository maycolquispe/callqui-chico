<?php
/**
 * API: Eliminar Certificado Digital
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/config/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$usuario_id = isset($input['usuario_id']) ? intval($input['usuario_id']) : 0;

if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Usuario no válido']);
    exit;
}

$conn = getDB();

// Obtener info del certificado actual
$stmt = $conn->prepare("SELECT certificado_digital FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

// Eliminar archivo físico si existe
if (!empty($user['certificado_digital'])) {
    $filepath = __DIR__ . '/../' . $user['certificado_digital'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

// Eliminar de BD
$stmt = $conn->prepare("UPDATE usuarios SET certificado_digital = NULL, password_certificado = NULL WHERE id = ?");
$stmt->bind_param("i", $usuario_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Certificado eliminado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar certificado']);
}
$stmt->close();