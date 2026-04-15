<?php
/**
 * Descargar Cargo de Solicitud - Comunidad Campesa Callqui Chico
 */
$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    die('Código no especificado');
}

// El archivo está en la carpeta uploads/solicitudes dentro de publico
$filepath = __DIR__ . '/documentos/cargos/cargo_' . $codigo . '.pdf';

if (!file_exists($filepath)) {
    die('Cargo no encontrado: ' . $filepath);
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="cargo_' . $codigo . '.pdf"');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;