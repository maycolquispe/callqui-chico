<?php

require_once "../config/conexion.php";

$sql = "SELECT promocion, COUNT(*) as total
FROM usuarios
WHERE rol='comunero'
GROUP BY promocion
ORDER BY promocion";

$result = $conn->query($sql);

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);