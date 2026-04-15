<?php
require_once "../../includes/verificar_sesion.php";

$conn = getDB();

/* CONSULTAS */
$comuneros = $conn->query("SELECT COUNT(*) total FROM usuarios")->fetch_assoc()['total'];
$lotes = $conn->query("SELECT COUNT(*) total FROM lotes")->fetch_assoc()['total'];
$disponibles = $conn->query("SELECT COUNT(*) total FROM lotes WHERE estado='DISPONIBLE'")->fetch_assoc()['total'];
$ocupados = $conn->query("SELECT COUNT(*) total FROM lotes WHERE estado='OCUPADO'")->fetch_assoc()['total'];

$pageTitle = "Dashboard - Lotes";
$mostrarNavbar = true;
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<title>Dashboard Comunidad</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

.card-dashboard{
padding:25px;
color:white;
border-radius:10px;
}

.bg1{background:#007bff;}
.bg2{background:#28a745;}
.bg3{background:#ffc107;color:black;}
.bg4{background:#dc3545;}

</style>

</head>

<body class="bg-light">

<div class="container mt-5">

<h2 class="mb-4">Dashboard - Comunidad</h2>

<div class="row">

<div class="col-md-3">
<div class="card-dashboard bg1">
<h5>Total Comuneros</h5>
<h2><?php echo $comuneros; ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card-dashboard bg2">
<h5>Total Lotes</h5>
<h2><?php echo $lotes; ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card-dashboard bg3">
<h5>Lotes Disponibles</h5>
<h2><?php echo $disponibles; ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card-dashboard bg4">
<h5>Lotes Ocupados</h5>
<h2><?php echo $ocupados; ?></h2>
</div>
</div>

</div>

<hr class="mt-5">

<h4>Lista de Comuneros y Lotes</h4>

<button onclick="window.print()" class="btn btn-danger mb-3">
Descargar PDF
</button>

<table class="table table-bordered table-striped">

<tr>
<th>DNI</th>
<th>Nombre</th>
<th>Lote</th>
<th>Manzana</th>
<th>Sector</th>
<th>Área</th>
</tr>

<?php

$sql = "SELECT u.dni,u.nombres,u.apellidos,
l.numero_lote,l.manzana,l.sector,l.area
FROM usuarios u
LEFT JOIN usuarios_lotes ul
ON u.id_usuario=ul.id_usuario
LEFT JOIN callqui_chico_lotes l
ON ul.id_lote=l.id_lote
ORDER BY u.apellidos";

$result = $conn->query($sql);

while($row=$result->fetch_assoc()){

echo "<tr>
<td>".$row['dni']."</td>
<td>".$row['apellidos']." ".$row['nombres']."</td>
<td>".$row['numero_lote']."</td>
<td>".$row['manzana']."</td>
<td>".$row['sector']."</td>
<td>".$row['area']."</td>
</tr>";

}

?>

</table>

</div>

</body>

</html>