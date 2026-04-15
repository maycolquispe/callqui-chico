<?php
require_once '../../includes/verificar_sesion.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $conn = getDB();
    
    // Primero eliminar registros relacionados (asistencias)
    $stmt = $conn->prepare("DELETE FROM asistencias WHERE usuario_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // También eliminar comentarios y likes en actas
    $stmt = $conn->prepare("DELETE FROM acta_comentarios WHERE usuario_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM acta_likes WHERE usuario_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Ahora eliminar el usuario
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: comuneros.php?msg=eliminado");
exit;
