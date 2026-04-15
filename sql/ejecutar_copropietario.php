<?php
/**
 * Script para agregar columnas de copropietario
 * Ejecutar desde el navegador: http://localhost/2026/sql/ejecutar_copropietario.php
 */

require_once '../config/database.php';

$conn = getDB();

echo "<h2>Agregando columnas de copropietario...</h2>";

$errors = [];
$success = [];

// 1. Agregar copropietario a lotes
$sql1 = "ALTER TABLE lotes ADD COLUMN copropietario VARCHAR(255) NULL AFTER propietario";
if ($conn->query($sql1)) {
    $success[] = "✅ Columna 'copropietario' agregada a tabla 'lotes'";
} else {
    if (strpos($conn->error, 'Duplicate') !== false || strpos($conn->error, 'ya existe') !== false) {
        $success[] = "ℹ️ Columna 'copropietario' ya existe en 'lotes'";
    } else {
        $errors[] = "❌ Error en lotes: " . $conn->error;
    }
}

// 2. Agregar copropietario_anterior a transferencias_lote
$sql2 = "ALTER TABLE transferencias_lote ADD COLUMN copropietario_anterior VARCHAR(255) NULL AFTER propietario_anterior";
if ($conn->query($sql2)) {
    $success[] = "✅ Columna 'copropietario_anterior' agregada a tabla 'transferencias_lote'";
} else {
    if (strpos($conn->error, 'Duplicate') !== false || strpos($conn->error, 'ya existe') !== false) {
        $success[] = "ℹ️ Columna 'copropietario_anterior' ya existe en 'transferencias_lote'";
    } else {
        $errors[] = "❌ Error en transferencias (anterior): " . $conn->error;
    }
}

// 3. Agregar copropietario_nuevo a transferencias_lote
$sql3 = "ALTER TABLE transferencias_lote ADD COLUMN copropietario_nuevo VARCHAR(255) NULL AFTER propietario_nuevo";
if ($conn->query($sql3)) {
    $success[] = "✅ Columna 'copropietario_nuevo' agregada a tabla 'transferencias_lote'";
} else {
    if (strpos($conn->error, 'Duplicate') !== false || strpos($conn->error, 'ya existe') !== false) {
        $success[] = "ℹ️ Columna 'copropietario_nuevo' ya existe en 'transferencias_lote'";
    } else {
        $errors[] = "❌ Error en transferencias (nuevo): " . $conn->error;
    }
}

echo "<h3>Resultados:</h3>";
foreach ($success as $msg) echo "<p style='color: green;'>$msg</p>";
foreach ($errors as $msg) echo "<p style='color: red;'>$msg</p>";

echo "<h3>Verificación:</h3>";
$result = $conn->query("SHOW COLUMNS FROM lotes LIKE 'copropietario'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Columna 'copropietario' existe en lotes</p>";
} else {
    echo "<p style='color: red;'>✗ Columna 'copropietario' NO existe en lotes</p>";
}

$result2 = $conn->query("SHOW COLUMNS FROM transferencias_lote LIKE 'copropietario_nuevo'");
if ($result2->num_rows > 0) {
    echo "<p style='color: green;'>✓ Columnas existen en transferencias_lote</p>";
} else {
    echo "<p style='color: red;'>✗ Columnas NO existen en transferencias_lote</p>";
}

echo "<p><a href='../index.html'>Volver al inicio</a></p>";