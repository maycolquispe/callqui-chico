<?php

require_once "../config/conexion.php";

if(isset($_POST['guardar_lote'])){

$usuario_id = $_POST['usuario_id'];
$lote = $_POST['lote'];
$sector = $_POST['sector'];
$manzana = $_POST['manzana'];
$area_m2 = $_POST['area_m2'];
$area_excedente_m2 = $_POST['area_excedente_m2'];

$buscar = $conn->query("SELECT nombres,apellidos FROM usuarios WHERE id=$usuario_id");
$row = $buscar->fetch_assoc();

$propietario = $row['nombres']." ".$row['apellidos'];

$sql="INSERT INTO lotes
(usuario_id,propietario,lote,sector,manzana,area_m2,area_excedente_m2,estado,created_at)
VALUES
('$usuario_id','$propietario','$lote','$sector','$manzana','$area_m2','$area_excedente_m2','OCUPADO',NOW())";

$conn->query($sql);

echo "Lote registrado";

}

?>