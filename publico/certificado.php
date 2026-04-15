<?php
/**
 * Visualización de Certificado de Adjudicación - Público
 * Acceso vía código QR o enlace directo
 * 
 * URL: publico/certificado.php?codigo=ADJ-2026-0001
 */

require_once "../config/database.php";

$conn = getDB();

// Obtener código del certificado
$codigo = isset($_GET['codigo']) ? sanitizar($_GET['codigo']) : '';

// Validar código
if (empty($codigo)) {
    die("Código de certificado no válido");
}

// Consulta segura - buscar por código de certificado
$stmt = $conn->prepare("SELECT a.*, u.nombres as nombres_titular, u.apellidos as apellidos_titular 
                        FROM adjudicaciones a
                        LEFT JOIN usuarios u ON a.usuario_id = u.id
                        WHERE a.codigo_certificado = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();
$cert = $result->fetch_assoc();
$stmt->close();

if (!$cert) {
    die("Certificado no encontrado");
}

// Verificar que el certificado esté generado
if (empty($cert['pdf_firmado']) && empty($cert['certificado'])) {
    die("El certificado aún no ha sido generado");
}

function sanitizar($valor) {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Adjudicación - <?= $codigo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #2563eb;
            --dark-bg: #0a1928;
            --accent: #c9a45c;
        }
        body {
            background: linear-gradient(145deg, var(--dark-bg) 0%, #0d2336 100%);
            min-height: 100vh;
            color: white;
            font-family: 'Segoe UI', sans-serif;
        }
        .cert-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .cert-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 2rem;
        }
        .cert-header {
            text-align: center;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--accent);
            margin-bottom: 1.5rem;
        }
        .cert-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }
        .cert-code {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            display: inline-block;
        }
        .cert-section {
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .cert-section h5 {
            color: var(--accent);
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .cert-field {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .cert-field:last-child {
            border-bottom: none;
        }
        .cert-label {
            color: #94a3b8;
        }
        .cert-value {
            font-weight: 600;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            display: inline-block;
        }
        .status-success {
            background: rgba(16,185,129,0.2);
            color: #10b981;
        }
        .btn-download {
            background: linear-gradient(135deg, var(--accent), #a88642);
            color: var(--dark-bg);
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-download:hover {
            transform: translateY(-2px);
            color: var(--dark-bg);
        }
        .qr-section {
            text-align: center;
            margin-top: 1rem;
        }
        .qr-section img {
            max-width: 150px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="cert-container">
    <div class="cert-card">
        <div class="cert-header">
            <div class="cert-title">
                <i class="bi bi-award-fill me-2"></i>
                CERTIFICADO DE ADJUDICACIÓN
            </div>
            <div class="cert-code"><?= $codigo ?></div>
            <span class="status-badge status-success mt-2">
                <i class="bi bi-check-circle-fill me-1"></i>
                CERTIFICADO VÁLIDO
            </span>
        </div>
        
        <div class="cert-section">
            <h5><i class="bi bi-person-fill me-2"></i>I. Datos del Adjudicatario</h5>
            <div class="cert-field">
                <span class="cert-label">Titular:</span>
                <span class="cert-value"><?= sanitizar(($cert['apellidos_titular'] ?? '') . ', ' . ($cert['nombres_titular'] ?? $cert['nombre'])) ?></span>
            </div>
            <div class="cert-field">
                <span class="cert-label">DNI:</span>
                <span class="cert-value"><?= sanitizar($cert['dni']) ?></span>
            </div>
            <?php if (!empty($cert['conyuge_nombre'])): ?>
            <div class="cert-field">
                <span class="cert-label">Cónyuge:</span>
                <span class="cert-value"><?= sanitizar($cert['conyuge_nombre']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($cert['estado_civil'])): ?>
            <div class="cert-field">
                <span class="cert-label">Estado Civil:</span>
                <span class="cert-value"><?= sanitizar($cert['estado_civil']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="cert-section">
            <h5><i class="bi bi-map-fill me-2"></i>II. Datos del Terreno</h5>
            <div class="cert-field">
                <span class="cert-label">Comunidad:</span>
                <span class="cert-value">Comunidad Campesina Callqui Chico</span>
            </div>
            <div class="cert-field">
                <span class="cert-label">Sector:</span>
                <span class="cert-value"><?= sanitizar($cert['sector'] ?? 'CHUÑURANRA') ?></span>
            </div>
            <div class="cert-field">
                <span class="cert-label">Manzana:</span>
                <span class="cert-value"><?= sanitizar($cert['manzana'] ?? '-') ?></span>
            </div>
            <div class="cert-field">
                <span class="cert-label">Lote:</span>
                <span class="cert-value"><?= sanitizar($cert['lote'] ?? '-') ?></span>
            </div>
            <div class="cert-field">
                <span class="cert-label">Área Total:</span>
                <span class="cert-value"><?= sanitizar($cert['area_m2'] ?? $cert['area'] ?? '0') ?> m²</span>
            </div>
            <?php if (!empty($cert['perimetro_ml'])): ?>
            <div class="cert-field">
                <span class="cert-label">Perímetro:</span>
                <span class="cert-value"><?= sanitizar($cert['perimetro_ml']) ?> ml</span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="cert-section">
            <h5><i class="bi bi-border-style me-2"></i>III. Linderos</h5>
            <div class="cert-field">
                <span class="cert-label">Frente:</span>
                <span class="cert-value"><?= sanitizar($cert['lindero_frente'] ?? 'Según plano de lotización') ?></span>
            </div>
            <div class="cert-field">
                <span class="cert-label">Fondo:</span>
                <span class="cert-value"><?= sanitizar($cert['lindero_fondo'] ?? 'Según plano de lotización') ?></span>
            </div>
            <div class="cert-field">
                <span class="cert-label">Derecha:</span>
                <span class="cert-value"><?= sanitizar($cert['lindero_derecha'] ?? 'Según plano de lotización') ?></span>
            </div>
            <div class="cert-field">
                <span class="cert-label">Izquierda:</span>
                <span class="cert-value"><?= sanitizar($cert['lindero_izquierda'] ?? 'Según plano de lotización') ?></span>
            </div>
        </div>
        
        <?php if (!empty($cert['resolucion_numero'])): ?>
        <div class="cert-section">
            <h5><i class="bi bi-file-earmark-text-fill me-2"></i>IV. Resolución</h5>
            <div class="cert-field">
                <span class="cert-label">Número:</span>
                <span class="cert-value"><?= sanitizar($cert['resolucion_numero']) ?></span>
            </div>
            <?php if (!empty($cert['resolucion_fecha'])): ?>
            <div class="cert-field">
                <span class="cert-label">Fecha:</span>
                <span class="cert-value"><?= date('d/m/Y', strtotime($cert['resolucion_fecha'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($cert['pdf_firmado'])): ?>
        <div class="text-center mt-4">
            <a href="../<?= sanitizar($cert['pdf_firmado']) ?>" class="btn-download" target="_blank">
                <i class="bi bi-download me-2"></i>Descargar PDF Firmado
            </a>
        </div>
        <?php elseif (!empty($cert['certificado'])): ?>
        <div class="text-center mt-4">
            <a href="<?= sanitizar($cert['certificado']) ?>" class="btn-download" target="_blank">
                <i class="bi bi-download me-2"></i>Descargar Certificado
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($cert['qr_code'])): ?>
        <div class="qr-section mt-3">
            <small class="text-muted">Escanee para verificar</small>
            <br>
            <img src="../<?= sanitizar($cert['qr_code']) ?>" alt="QR Code">
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4" style="color: #64748b; font-size: 0.85rem;">
            <p>Documento generado mediante Sistema de Gestión - Comunidad Campesina Callqui Chico</p>
            <p>Reconocida mediante Resolución N° 138-2005/GOB.REG.HVCA/GRDE-DRA (07-09-2005)</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>