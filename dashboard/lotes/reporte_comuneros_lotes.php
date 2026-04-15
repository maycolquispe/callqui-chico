<?php
require_once "../config/conexion.php";

$sql = "
SELECT 
u.id,
u.dni,
u.nombres,
u.apellidos,
u.promocion,
COUNT(lp.id) as total_lotes

FROM usuarios u

LEFT JOIN lotes_propietarios lp 
ON lp.usuario_id = u.id 
AND lp.estado='ACTIVO'

WHERE u.rol='comunero'

GROUP BY u.id

ORDER BY u.promocion
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">

<title>Reporte de Comuneros</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<div class="container mt-5">

<h3 class="mb-4">

Reporte de Comuneros y Lotes  
Comunidad Campesina Callqui Chico

</h3>

<a href="reporte_comuneros_pdf.php" class="btn btn-danger">
Descargar PDF
</a>

<a href="reporte_comuneros_excel.php" class="btn btn-success">
Descargar Excel
</a>

<table class="table table-bordered table-striped mt-3">

<thead class="table-dark">

<tr>

<th>Comunero</th>
<th>DNI</th>
<th>Promoción</th>
<th>Total Lotes</th>

</tr>

</thead>

<tbody>

<?php while($row = $result->fetch_assoc()): ?>

<tr>

<td><?= $row['nombres']." ".$row['apellidos'] ?></td>
<td><?= $row['dni'] ?></td>
<td><?= $row['promocion'] ?></td>
<td><?= $row['total_lotes'] ?></td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

</body>
</html>