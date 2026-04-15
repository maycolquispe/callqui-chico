<?php

session_start();
require_once __DIR__ . '/../config/conexion.php';

/* ===============================
   VALIDAR SESIÓN Y ROL
================================ */
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'secretario') {
    die("Acceso denegado");
}

/* ===============================
   VALIDAR MÉTODO POST
================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso inválido: método no POST");
}

/* ===============================
   CAPTURAR DATOS
================================ */
$sector = trim($_POST['sector'] ?? '');
$manzana = trim($_POST['manzana'] ?? '');
$lote = trim($_POST['lote'] ?? '');

$usuario_id = $_POST['usuario_id'] ?? NULL;
$area_m2 = $_POST['area_m2'] ?? NULL;
$area_excedente_m2 = $_POST['area_excedente_m2'] ?? NULL;
$estado = $_POST['estado'] ?? 'LIBRE';

/* ===============================
   VALIDACIÓN OBLIGATORIA
================================ */
if ($sector === '' || $manzana === '' || $lote === '') {
    die("Error: Sector, Manzana y Lote son obligatorios");
}

/* ===============================
   INSERTAR EN BD
================================ */
$sql = "INSERT INTO lotes 
        (sector, manzana, lote, usuario_id, area_m2, area_excedente_m2, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssidds",
    $sector,
    $manzana,
    $lote,
    $usuario_id,
    $area_m2,
    $area_excedente_m2,
    $estado
);

if ($stmt->execute()) {
    header("Location: listar_lotes.php?ok=1");
    exit;
} else {
    die("Error al registrar lote: " . $stmt->error);
}