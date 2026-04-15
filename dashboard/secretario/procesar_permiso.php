<?php
/**
 * Procesar permisos - API para aprobar/rechazar solicitudes
 */

require_once '../../includes/verificar_sesion.php';

$conn = getDB();

$id = intval($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';
$obs = $_POST['observacion'] ?? '';

if (empty($id) || empty($estado)) {
    header("Location: permisos.php?error=datos_incompletos");
    exit;
}

if (!in_array($estado, ['Aprobado', 'Rechazado'])) {
    header("Location: permisos.php?error=estado_invalido");
    exit;
}

$stmt = $conn->prepare("UPDATE permisos SET estado = ?, observacion_secretario = ? WHERE id = ?");
$stmt->bind_param("ssi", $estado, $obs, $id);

if ($stmt->execute()) {
    $msg = ($estado == 'Aprobado') ? 'permiso_aprobado' : 'permiso_rechazado';
    header("Location: permisos.php?msg=$msg");
} else {
    header("Location: permisos.php?error=error_db");
}
$stmt->close();
exit;