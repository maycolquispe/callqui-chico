<?php
/**
 * Script de diagnóstico para cambio de propietario
 */

require_once '../config/database.php';

$conn = getDB();

echo "<h2>Diagnóstico de Cambio de Propietario</h2>";

echo "<h3>1. Estructura de la tabla lotes:</h3>";
$result = $conn->query("DESCRIBE lotes");
echo "<table border='1'>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
}
echo "</table>";

echo "<h3>2. Estructura de la tabla transferencias_lote:</h3>";
$result = $conn->query("DESCRIBE transferencias_lote");
echo "<table border='1'>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
}
echo "</table>";

echo "<h3>3. Últimas transferencias:</h3>";
$result = $conn->query("SELECT * FROM transferencias_lote ORDER BY id DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h3>4. Algunos lotes:</h3>";
$result = $conn->query("SELECT id, lote, manzana, usuario_id, propietario, copropietario, estado FROM lotes LIMIT 10");
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Lote</th><th>Manzana</th><th>Usuario ID</th><th>Propietario</th><th>Copropietario</th><th>Estado</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['lote']}</td><td>{$row['manzana']}</td><td>{$row['usuario_id']}</td><td>{$row['propietario']}</td><td>{$row['copropietario']}</td><td>{$row['estado']}</td></tr>";
}
echo "</table>";

echo "<p><a href='../index.html'>Volver</a></p>";