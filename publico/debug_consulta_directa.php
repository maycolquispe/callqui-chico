<?php
/**
 * Debug: Mostrar resultado directo de consulta
 */
require_once __DIR__ . '/../config/database.php';

$conn = getDB();
$codigo = 'ADJ-2026-289733';

$stmt = $conn->prepare("SELECT * FROM adjudicaciones WHERE codigo_seguimiento = ? LIMIT 1");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$resultado = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Consulta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container">
        <h2>Debug - Datos de Adjudicación</h2>
        
        <?php if ($resultado): ?>
        <div class="card">
            <div class="card-body">
                <h5>Resultados:</h5>
                <table class="table table-bordered">
                    <tr><th>ID</th><td><?= $resultado['id'] ?></td></tr>
                    <tr><th>Nombre</th><td><?= $resultado['nombre'] ?></td></tr>
                    <tr><th>DNI</th><td><?= $resultado['dni'] ?></td></tr>
                    <tr><th>Estado</th><td>"<?= $resultado['estado'] ?>"</td></tr>
                    <tr><th>estado (hex)</th><td><?= bin2hex($resultado['estado']) ?></td></tr>
                    <tr><th>pdf_firmado</th><td>"<?= $resultado['pdf_firmado'] ?>"</td></tr>
                    <tr><th>certificado</th><td>"<?= $resultado['certificado'] ?>"</td></tr>
                    <tr><th>completamente_firmado</th><td><?= $resultado['completamente_firmado'] ?></td></tr>
                </table>
                
                <h5>Verificaciones:</h5>
                <ul>
                    <li>in_array('aprobado_total', ['aprobado_total','certificado_generado']): <?php var_dump(in_array('aprobado_total', ['aprobado_total','certificado_generado'])); ?></li>
                    <li>in_array('<?= $resultado['estado'] ?>', ['aprobado_total','certificado_generado']): <?php var_dump(in_array($resultado['estado'], ['aprobado_total','certificado_generado'])); ?></li>
                    <li>!empty(pdf_firmado): <?php var_dump(!empty($resultado['pdf_firmado'])); ?></li>
                </ul>
                
                <?php if (in_array($resultado['estado'], ['aprobado_total','certificado_generado']) && !empty($resultado['pdf_firmado'])): ?>
                    <a href="../<?= htmlspecialchars($resultado['pdf_firmado']) ?>" class="btn btn-success btn-lg" target="_blank">
                        DESCARGAR CERTIFICADO
                    </a>
                <?php else: ?>
                    <div class="alert alert-danger">NO SE CUMPLEN LAS CONDICIONES</div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">No se encontró la solicitud</div>
        <?php endif; ?>
    </div>
</body>
</html>