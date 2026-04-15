<?php
require_once "../../config/config.php";
session_start();

if ($_SESSION['rol'] !== 'secretario' && $_SESSION['rol'] !== 'presidente') {
    die("Acceso no autorizado");
}

$titulo = $_POST['titulo'];
$descripcion = $_POST['descripcion'];
$creado_por = $_SESSION['usuario_id']; // ESTE ID ES CLAVE

$archivo = $_FILES['archivo'];
$nombre_original = $archivo['name'];
$tmp = $archivo['tmp_name'];

$ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
$tipo = ($ext === 'pdf') ? 'pdf' : 'imagen';

$nombre_final = time() . "_" . $nombre_original;
$ruta = "../uploads/" . $nombre_final;

/* 1️⃣ SUBIR ARCHIVO */
if (!move_uploaded_file($tmp, $ruta)) {
    die("Error al subir archivo");
}

/* 2️⃣ GUARDAR EN BD */
$sql = "INSERT INTO actas 
(titulo, descripcion, archivo, tipo, fecha, creado_por)
VALUES (?,?,?,?,NOW(),?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssi",
    $titulo,
    $descripcion,
    $nombre_final,
    $tipo,
    $creado_por
);

if ($stmt->execute()) {
    header("Location: actas.php?ok=1");
} else {
    echo "Error BD: " . $conn->error;
}
