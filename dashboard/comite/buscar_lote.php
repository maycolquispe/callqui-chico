<?php
require_once '../../config/database.php';

$conn = getDB();
$conn->set_charset("utf8mb4");

$manzana = $_GET['manzana'] ?? '';
$lote = $_GET['lote'] ?? '';

header('Content-Type: application/json');

if ($manzana && $lote) {
    // Buscar lote específico
    $stmt = $conn->prepare("SELECT id, usuario_id, manzana, lote, sector, propietario, estado, area_m2 FROM lotes WHERE manzana = ? AND lote = ? LIMIT 1");
    $stmt->bind_param("ss", $manzana, $lote);
    $stmt->execute();
    $result = $stmt->get_result();
    $loteData = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode($loteData);
} elseif ($manzana) {
    // Buscar manzanas que coincidan
    $stmt = $conn->prepare("SELECT DISTINCT manzana, sector FROM lotes WHERE manzana LIKE ? ORDER BY manzana LIMIT 20");
    $likeManzana = $manzana . '%';
    $stmt->bind_param("s", $likeManzana);
    $stmt->execute();
    $result = $stmt->get_result();
    $manzanas = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode($manzanas);
} else {
    echo json_encode([]);
}