<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();

$stmt = $conn->prepare(
    "INSERT INTO usuarios (dni,nombres,apellidos,rol) VALUES (?,?,?,?)"
);

$stmt->bind_param(
    "ssss",
    $_POST['dni'],
    $_POST['nombres'],
    $_POST['apellidos'],
    $_POST['rol']
);

$stmt->execute();

header("Location: comuneros.php?msg=guardado");
exit;
