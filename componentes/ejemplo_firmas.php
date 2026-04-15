<?php
/**
 * Ejemplo de uso del componente de Firma Digital Visual
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../componentes/firma_digital.php';

$conn = getDB();

// Obtener firmas desde la BD
$firmas = obtenerFirmasParaCertificado($conn);

if (empty($firmas)) {
    // Firmas de ejemplo si no hay datos en BD
    $firmas = [
        [
            'nombre' => 'Kevin Sedano Huaman',
            'dni' => '19984226',
            'cargo' => 'EL TESORERO',
            'fecha' => date('d/m/Y H:i')
        ],
        [
            'nombre' => 'Comité de Lotes Callqui',
            'dni' => '19999999',
            'cargo' => 'COMITÉ DE LOTES',
            'fecha' => date('d/m/Y H:i')
        ],
        [
            'nombre' => 'Maria Perez Gonzales',
            'dni' => '19984228',
            'cargo' => 'EL SECRETARIO',
            'fecha' => date('d/m/Y H:i')
        ],
        [
            'nombre' => 'Juan Rodriguez Lopez',
            'dni' => '19984229',
            'cargo' => 'EL PRESIDENTE',
            'fecha' => date('d/m/Y H:i')
        ]
    ];
}

// Renderizar
$html = renderizarFirmaHTML($firmas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplo Firmas Digitales Visuales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%); min-height: 100vh; padding: 40px; }
        .demo-container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 900px; margin: 0 auto; }
        h2 { color: #c9a45c; margin-bottom: 10px; }
        .btn-imprimir { background: #c9a45c; color: #06212e; border: none; }
        .btn-imprimir:hover { background: #b8964e; color: #06212e; }
    </style>
</head>
<body>
    <div class="demo-container">
        <h2><i class="bi bi-pen-fill me-2"></i>Firmas Digitales Visuales</h2>
        <p class="text-muted mb-4">Vista previa de cómo aparecerán las firmas en el certificado:</p>
        
        <?= $html ?>
        
        <div class="mt-4 text-center">
            <a href="javascript:window.print()" class="btn btn-imprimir px-4 py-2 rounded">
                <i class="bi bi-printer me-2"></i>Imprimir
            </a>
            <a href="javascript:history.back()" class="btn btn-outline-secondary px-4 py-2 rounded ms-2">
                <i class="bi bi-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>
</body>
</html>
