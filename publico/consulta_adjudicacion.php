<?php
/**
 * Consulta de Seguimiento - Adjudicaciones Callqui Chico
 * Permite al usuario consultar el estado de su solicitud con código único
 */

require_once '../../config/database.php';
require_once '../../includes/funciones.php';

$conn = getDB();
$resultado = null;
$error = '';

// Procesar consulta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['codigo'])) {
    $codigo = sanitizar($_POST['codigo']);
    
    // Debug
    error_log("Consulta adjudicacion - codigo: " . $codigo);
    
    $stmt = $conn->prepare("SELECT a.*, u.nombres as nombre_usuario, u.apellidos 
                            FROM adjudicaciones a 
                            LEFT JOIN usuarios u ON a.dni = u.dni 
                            WHERE a.codigo_seguimiento = ? 
                            LIMIT 1");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Debug resultado
    if ($resultado) {
        error_log("Resultado: estado=" . $resultado['estado'] . ", pdf_firmado=" . ($resultado['pdf_firmado'] ?? 'NULL'));
    }
    
    if (!$resultado) {
        $error = 'No se encontró ninguna solicitud con ese código de seguimiento';
    }
}

function getEstadoLabel($estado) {
    $labels = [
        'pendiente' => 'Pendiente',
        'en_revision' => 'En Revisión',
        'aprobado' => 'Aprobado',
        'aprobado_total' => 'Aprobado Total',
        'certificado_generado' => 'Certificado Generado',
        'rechazado' => 'Rechazado'
    ];
    return $labels[$estado] ?? $estado;
}

function getEstadoClass($estado) {
    $classes = [
        'pendiente' => 'bg-warning',
        'en_revision' => 'bg-info',
        'aprobado' => 'bg-success',
        'aprobado_total' => 'bg-success',
        'certificado_generado' => 'bg-primary',
        'rechazado' => 'bg-danger'
    ];
    return $classes[$estado] ?? 'bg-secondary';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Seguimiento - Comunidad Callqui Chico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark-bg: #0a1928;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-light: #64748b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
            min-height: 100vh;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 20% 30%, rgba(37,99,235,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 70%, rgba(16,185,129,0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        .header {
            background: rgba(10, 25, 40, 0.95);
            padding: 1.5rem 0;
            border-bottom: 3px solid #c9a45c;
        }
        .logo-area {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #c9a45c, #a88642);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #06212e;
            font-size: 1.5rem;
            font-weight: 800;
        }
        .logo-text h3 {
            color: white;
            font-weight: 700;
            font-size: 1.3rem;
        }
        .logo-text small {
            color: #dbb67b;
            font-size: 0.8rem;
        }
        .main-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }
        .panel {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 2.5rem;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .panel-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .panel-header h2 {
            color: white;
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        .panel-header p {
            color: #94a3b8;
            margin: 0;
        }
        .form-control {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            color: white;
            font-size: 1.1rem;
            text-align: center;
        }
        .form-control:focus {
            background: rgba(0,0,0,0.4);
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
            color: white;
        }
        .form-control::placeholder {
            color: #64748b;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            border: none;
            padding: 1rem 3rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(37,99,235,0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37,99,235,0.4);
        }
        .alert {
            border-radius: 16px;
            padding: 1rem 1.5rem;
        }
        .alert-danger {
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.3);
            color: #ef4444;
        }
        .result-card {
            background: rgba(255,255,255,0.05);
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .codigo-badge {
            background: rgba(37,99,235,0.2);
            color: #2563eb;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
        }
        .estado-badge {
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .info-item {
            background: rgba(0,0,0,0.2);
            padding: 1rem;
            border-radius: 12px;
        }
        .info-label {
            color: #94a3b8;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }
        .btn-descargar {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        .btn-descargar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16,185,129,0.4);
            color: white;
        }
        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
            .result-header { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="logo-area">
                <div class="logo">
                    <i class="bi bi-tree-fill"></i>
                </div>
                <div class="logo-text">
                    <h3>Comunidad Callqui Chico</h3>
                    <small>Sistema de Gestión Comunal</small>
                </div>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="panel">
            <div class="panel-header">
                <h2><i class="bi bi-search me-2"></i>Consulta de Seguimiento</h2>
                <p>Ingrese su código de seguimiento para verificar el estado de su solicitud</p>
            </div>

            <form method="POST" class="mb-4">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <input type="text" name="codigo" class="form-control" 
                               placeholder="Ejemplo: ADJ-2026-123456" 
                               value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>" 
                               required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i>Consultar
                        </button>
                    </div>
                </div>
            </form>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($resultado): ?>
            <div class="result-card">
                <div class="result-header">
                    <span class="codigo-badge">
                        <i class="bi bi-hash me-1"></i>
                        <?= htmlspecialchars($resultado['codigo_seguimiento']) ?>
                    </span>
                    <span class="estado-badge <?= getEstadoClass($resultado['estado']) ?>">
                        <i class="bi bi-<?= $resultado['estado']=='pendiente'?'hourglass':($resultado['estado']=='rechazado'?'x-circle':($resultado['estado']=='certificado_generado'?'award':'check-circle')) ?> me-1"></i>
                        <?= getEstadoLabel($resultado['estado']) ?>
                    </span>
                </div>
                
                <?php if (!empty($resultado['codigo_certificado'])): ?>
                <div class="codigo-certificado mb-3" style="background: rgba(37,99,235,0.1); padding: 0.75rem; border-radius: 8px; text-align: center;">
                    <small class="text-muted">Código de Certificado:</small><br>
                    <strong style="color: #2563eb; font-size: 1.1rem;"><?= htmlspecialchars($resultado['codigo_certificado']) ?></strong>
                </div>
                <?php endif; ?>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Solicitante</div>
                        <div class="info-value"><?= htmlspecialchars($resultado['nombre']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">DNI</div>
                        <div class="info-value"><?= htmlspecialchars($resultado['dni']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Lote</div>
                        <div class="info-value"><?= htmlspecialchars($resultado['lote']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Manzana</div>
                        <div class="info-value"><?= htmlspecialchars($resultado['manzana'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Área</div>
                        <div class="info-value"><?= htmlspecialchars($resultado['area_m2'] ?? $resultado['area']) ?> m²</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Fecha de Solicitud</div>
                        <div class="info-value"><?= date('d/m/Y', strtotime($resultado['fecha_solicitud'])) ?></div>
                    </div>
                </div>

                <?php if (!empty($resultado['observaciones'])): ?>
                <div class="info-item mt-3">
                    <div class="info-label">Observaciones</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($resultado['observaciones'])) ?></div>
                </div>
                <?php endif; ?>

                <?php 
                // Debug forzado
                $debug_info = "estado=" . $resultado['estado'] . ", pdf_firmado=" . ($resultado['pdf_firmado'] ?? 'null') . ", in_array=" . (in_array($resultado['estado'], ['aprobado_total', 'certificado_generado']) ? 'true' : 'false');
                ?>
                <div class="alert alert-warning mt-3" style="font-size: 12px;">
                    DEBUG: <?= $debug_info ?>
                </div>

                <?php 
                // Verificación final
                $estado_ok = in_array($resultado['estado'], ['aprobado_total', 'certificado_generado']);
                $pdf_ok = !empty($resultado['pdf_firmado']);
                
                if ($estado_ok && $pdf_ok): ?>
                <a href="../<?= htmlspecialchars($resultado['pdf_firmado']) ?>" 
                   class="btn-descargar" target="_blank">
                    <i class="bi bi-download me-2"></i>Descargar Certificado Firmado
                </a>
                <?php elseif ($resultado['estado'] === 'certificado_generado' && !empty($resultado['certificado'])): ?>
                <a href="<?= htmlspecialchars($resultado['certificado']) ?>" 
                   class="btn-descargar" target="_blank">
                    <i class="bi bi-download me-2"></i>Descargar Certificado
                </a>
                <?php else: ?>
                <div class="alert alert-danger mt-2">
                    No cumple condiciones: estado_ok=<?= $estado_ok ?>, pdf_ok=<?= $pdf_ok ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>