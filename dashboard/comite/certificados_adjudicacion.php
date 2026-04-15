<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($_SESSION['rol'] !== 'comite_lotes') {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

$nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];

// Estados para mostrar
$estados_mostrar = ['en_firma_comite', 'aprobado'];

// Obtener lista de adjudicaciones pendientes de firma del comité
$sql = "SELECT a.*, u.nombres as nombre_usuario, u.apellidos as apellido_usuario, u.dni as dni_usuario
        FROM adjudicaciones a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.estado IN ('en_firma_comite', 'aprobado')
        AND (a.aprobado_comite IS NULL OR a.aprobado_comite = 0)
        ORDER BY a.fecha_solicitud DESC";
$result = $conn->query($sql);
$adjudicaciones = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener solicitudes que ya firmó el comité
$sql_firmados = "SELECT a.*, u.nombres as nombre_usuario, u.apellidos as apellido_usuario
        FROM adjudicaciones a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.aprobado_comite = 1
        ORDER BY a.fecha_solicitud DESC
        LIMIT 20";
$result_firmados = $conn->query($sql_firmados);
$adjudicaciones_firmadas = $result_firmados ? $result_firmados->fetch_all(MYSQLI_ASSOC) : [];

// Obtener info de solicitud si se seleccionó una
$solicitud_seleccionada = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT a.*, u.nombres as nombre_usuario, u.apellidos as apellido_usuario, u.dni as dni_usuario
            FROM adjudicaciones a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $solicitud_seleccionada = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificados de Adjudicación - Comité de Lotes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark-bg: #0a1928;
            --gold: #c9a45c;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
            min-height: 100vh;
            position: relative;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 20% 30%, rgba(37,99,235,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 70%, rgba(16,185,129,0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        .navbar-modern {
            background: rgba(10, 25, 40, 0.95);
            backdrop-filter: blur(12px);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(201, 164, 92, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-area { display: flex; align-items: center; gap: 1rem; }
        .logo {
            width: 45px; height: 45px;
            background: linear-gradient(135deg, #c9a45c, #a88642);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #06212e;
            font-size: 1.3rem;
            font-weight: 800;
        }
        .logo-text h3 { color: white; font-weight: 700; font-size: 1.1rem; margin: 0; }
        .logo-text small { color: #dbb67b; font-size: 0.75rem; }
        
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-info { text-align: right; color: white; }
        .user-info small { color: #94a3b8; }
        .user-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #c9a45c, #a88642);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .page-header {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .page-header h2 { color: white; font-weight: 700; margin-bottom: 0.5rem; }
        .page-header p { color: #94a3b8; margin: 0; }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        .back-btn:hover { color: white; }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .adjudicacion-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        .adjudicacion-card:hover {
            transform: translateY(-5px);
            border-color: rgba(201, 164, 92, 0.5);
        }
        .adjudicacion-card h5 { color: white; font-weight: 600; margin-bottom: 1rem; }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #94a3b8; font-size: 0.85rem; }
        .info-value { color: white; font-weight: 500; font-size: 0.9rem; }
        
        .badge-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-pendiente {
            background: rgba(245,158,11,0.2);
            color: #f59e0b;
        }
        .badge-firmado {
            background: rgba(16,185,129,0.2);
            color: #10b981;
        }
        
        .btn-firmar {
            background: linear-gradient(135deg, #c9a45c, #a88642);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            color: #06212e;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        .btn-firmar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(201, 164, 92, 0.4);
        }
        .btn-firmar:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-ver {
            background: rgba(37,99,235,0.2);
            border: 1px solid rgba(37,99,235,0.3);
            color: #2563eb;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-ver:hover {
            background: rgba(37,99,235,0.3);
        }
        
        /* Modal styles */
        .modal-content-custom {
            background: #0a1928;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
        }
        .modal-header-custom {
            background: rgba(201,164,92,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px 20px 0 0;
        }
        
        .review-section {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .review-section h6 {
            color: #c9a45c;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-custom {
            background: rgba(0,0,0,0.3);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .checkbox-custom:hover {
            border-color: rgba(201, 164, 92, 0.5);
        }
        .checkbox-custom.checked {
            background: rgba(201,164,92,0.2);
            border-color: #c9a45c;
        }
        
        .nav-pills-custom .nav-link {
            color: #94a3b8;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px 8px 0 0;
        }
        .nav-pills-custom .nav-link.active {
            background: rgba(201,164,92,0.2);
            color: #c9a45c;
        }
        
        .tab-content-custom {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-top: none;
            border-radius: 0 0 16px 16px;
            padding: 1.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        .loading i {
            font-size: 2rem;
            color: #c9a45c;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        
        .alert-custom {
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.3);
            border-radius: 12px;
            color: #10b981;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #ef4444;
        }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <div class="logo"><i class="bi bi-tree-fill"></i></div>
            <div class="logo-text">
                <h3>Comunidad Callqui Chico</h3>
                <small>Comité de Lotes</small>
            </div>
        </div>
        <div class="user-menu">
            <div class="user-info">
                <div><?= htmlspecialchars($nombreCompleto) ?></div>
                <small>Comité de Lotes</small>
            </div>
            <div class="user-avatar">
                <?= substr($usuario['nombres'], 0, 1) . substr($usuario['apellidos'], 0, 1) ?>
            </div>
            <a href="../../logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </nav>

    <div class="main-container">
        
        <a href="comite.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
        
        <div class="page-header">
            <h2><i class="bi bi-award-fill me-2" style="color: #c9a45c;"></i>Certificados de Adjudicación</h2>
            <p>Revise los datos del certificado, verifique que sean correctos y firme digitalmente</p>
        </div>

        <!-- Mensajes -->
        <div id="mensaje_exito" class="alert-custom" style="display: none;">
            <i class="bi bi-check-circle me-2"></i>
            <span id="texto_exito"></span>
        </div>
        <div id="mensaje_error" class="alert-custom alert-error" style="display: none;">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <span id="texto_error"></span>
        </div>
        
        <!-- Tabs -->
        <ul class="nav nav-pills nav-pills-custom mb-3" id="pillsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-pendientes-tab" data-bs-toggle="pill" data-bs-target="#pills-pendientes" type="button">
                    <i class="bi bi-clock me-1"></i> Pendientes de Firma
                    <span class="badge bg-warning text-dark ms-2"><?= count($adjudicaciones) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-firmados-tab" data-bs-toggle="pill" data-bs-target="#pills-firmados" type="button">
                    <i class="bi bi-check-circle me-1"></i> Firmados Recientemente
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="pillsTabContent">
            <!-- Pendientes -->
            <div class="tab-pane fade show active" id="pills-pendientes" role="tabpanel">
                <?php if (count($adjudicaciones) > 0): ?>
                    <div class="cards-grid">
                        <?php foreach ($adjudicaciones as $adj): ?>
                            <div class="adjudicacion-card">
                                <h5>
                                    <i class="bi bi-file-earmark-text me-2"></i>
                                    <?= htmlspecialchars($adj['nombre'] ?? $adj['nombre_usuario'] . ' ' . $adj['apellido_usuario']) ?>
                                </h5>
                                <div class="info-row">
                                    <span class="info-label">DNI</span>
                                    <span class="info-value"><?= htmlspecialchars($adj['dni'] ?? $adj['dni_usuario']) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Lote / Manzana</span>
                                    <span class="info-value"><?= htmlspecialchars($adj['lote']) ?> / <?= htmlspecialchars($adj['manzana'] ?? '-') ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Sector</span>
                                    <span class="info-value"><?= htmlspecialchars($adj['sector'] ?? '-') ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Área</span>
                                    <span class="info-value"><?= htmlspecialchars($adj['area_m2'] ?? $adj['area'] ?? '0') ?> m²</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Estado</span>
                                    <span class="badge-status badge-pendiente">
                                        <i class="bi bi-clock me-1"></i> Pendiente
                                    </span>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <?php 
                                    $archivo_mostrar = !empty($adj['pdf_firmado']) ? $adj['pdf_firmado'] : $adj['certificado'];
                                    if (!empty($archivo_mostrar)): ?>
                                        <a href="../../storage/<?= $archivo_mostrar ?>" target="_blank" class="btn-ver">
                                            <i class="bi bi-eye"></i> Ver Certificado
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn-firmar" onclick="revisarSolicitud(<?= $adj['id'] ?>)">
                                        <i class="bi bi-check2-square me-1"></i> Revisar y Firmar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h5>No hay certificados pendientes</h5>
                        <p>Todos los certificados han sido firmados o no hay solicitudes en espera.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Firmados -->
            <div class="tab-pane fade" id="pills-firmados" role="tabpanel">
                <?php if (count($adjudicaciones_firmadas) > 0): ?>
                    <div class="cards-grid">
                        <?php foreach ($adjudicaciones_firmadas as $adj): ?>
                            <div class="adjudicacion-card">
                                <h5>
                                    <i class="bi bi-file-earmark-text me-2"></i>
                                    <?= htmlspecialchars($adj['nombre'] ?? $adj['nombre_usuario'] . ' ' . $adj['apellido_usuario']) ?>
                                </h5>
                                <div class="info-row">
                                    <span class="info-label">DNI</span>
                                    <span class="info-value"><?= htmlspecialchars($adj['dni'] ?? $adj['dni_usuario']) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Lote / Manzana</span>
                                    <span class="info-value"><?= htmlspecialchars($adj['lote']) ?> / <?= htmlspecialchars($adj['manzana'] ?? '-') ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Sector</span>
                                    <span class="info-value"><?= htmlspecialchars($adj['sector'] ?? '-') ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Estado</span>
                                    <span class="badge-status badge-firmado">
                                        <i class="bi bi-check-circle me-1"></i> Firmado
                                    </span>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <?php 
                                    $archivo_mostrar = !empty($adj['pdf_firmado']) ? $adj['pdf_firmado'] : $adj['certificado'];
                                    // Quitar "storage/" si está al inicio para evitar duplicación
                                    $archivo_mostrar = str_replace('storage/', '', $archivo_mostrar);
                                    if (!empty($archivo_mostrar)): ?>
                                        <a href="../../storage/<?= $archivo_mostrar ?>" target="_blank" class="btn-ver">
                                            <i class="bi bi-eye"></i> Ver Certificado
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h5>No hay certificados firmados</h5>
                        <p>Los certificados que firme aparecerán aquí.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Modal de Revisión -->
    <div class="modal fade" id="revisionModal" tabindex="-1" style="z-index: 9999;">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content modal-content-custom">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title text-white">
                        <i class="bi bi-clipboard-check me-2" style="color: #c9a45c;"></i>
                        Revisar Certificado de Adjudicación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="loading" id="loading_review">
                        <i class="bi bi-arrow-repeat"></i>
                        <p class="mt-2">Cargando datos...</p>
                    </div>
                    
                    <div id="contenido_review" style="display: none;">
                        <!-- Datos del Adjudicatario -->
                        <div class="review-section">
                            <h6><i class="bi bi-person-badge"></i> Datos del Adjudicatario</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">Nombre Completo</span>
                                        <span class="info-value" id="rev_nombre"></span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">DNI</span>
                                        <span class="info-value" id="rev_dni"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Datos del Terreno -->
                        <div class="review-section">
                            <h6><i class="bi bi-map"></i> Datos del Terreno</h6>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">Sector</span>
                                        <span class="info-value" id="rev_sector"></span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">Manzana</span>
                                        <span class="info-value" id="rev_manzana"></span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">Lote</span>
                                        <span class="info-value" id="rev_lote"></span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">Área</span>
                                        <span class="info-value" id="rev_area"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Linderos -->
                        <div class="review-section">
                            <h6><i class="bi bi-border-all"></i> Linderos</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">Frente</span>
                                        <span class="info-value" id="rev_frente"></span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">Fondo</span>
                                        <span class="info-value" id="rev_fondo"></span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">Derecha</span>
                                        <span class="info-value" id="rev_derecha"></span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">Izquierda</span>
                                        <span class="info-value" id="rev_izquierda"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Verificar datos -->
                        <div class="review-section">
                            <h6><i class="bi bi-check2-square"></i> Verificación de Datos</h6>
                            <div class="checkbox-custom p-3" id="checkbox_datos" onclick="toggleCheckbox()">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="datos_verificados">
                                    <label class="form-check-label text-white" for="datos_verificados">
                                        <strong>He verificado que los datos son correctos</strong>
                                        <p class="text-muted mb-0 mt-1 small">Al marcar esta opción, confirmo que los datos del certificado coinciden con los documentos de la solicitud.</p>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botón Firmar -->
                        <button type="button" class="btn-firmar" id="btn_firmar" onclick="firmarCertificado()" disabled>
                            <i class="bi bi-patch-check me-2"></i> APROBAR Y FIRMAR CERTIFICADO
                        </button>
                        
                        <input type="hidden" id="solicitud_id_review">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let modalRevision = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            modalRevision = new bootstrap.Modal(document.getElementById('revisionModal'));
        });
        
        function revisarSolicitud(id) {
            document.getElementById('loading_review').style.display = 'block';
            document.getElementById('contenido_review').style.display = 'none';
            document.getElementById('datos_verificados').checked = false;
            document.getElementById('btn_firmar').disabled = true;
            document.getElementById('checkbox_datos').classList.remove('checked');
            
            modalRevision.show();
            
            // Cargar datos via AJAX
            fetch('obtener_datos_certificado.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('solicitud_id_review').value = id;
                    
                    document.getElementById('rev_nombre').textContent = data.adjudicacion.nombre || (data.adjudicacion.nombre_usuario + ' ' + data.adjudicacion.apellido_usuario);
                    document.getElementById('rev_dni').textContent = data.adjudicacion.dni || data.adjudicacion.dni_usuario;
                    document.getElementById('rev_sector').textContent = data.adjudicacion.sector || '-';
                    document.getElementById('rev_manzana').textContent = data.adjudicacion.manzana || '-';
                    document.getElementById('rev_lote').textContent = data.adjudicacion.lote || '-';
                    document.getElementById('rev_area').textContent = (data.adjudicacion.area_m2 || data.adjudicacion.area || '0') + ' m²';
                    document.getElementById('rev_frente').textContent = data.adjudicacion.lindero_frente || '-';
                    document.getElementById('rev_fondo').textContent = data.adjudicacion.lindero_fondo || '-';
                    document.getElementById('rev_derecha').textContent = data.adjudicacion.lindero_derecha || '-';
                    document.getElementById('rev_izquierda').textContent = data.adjudicacion.lindero_izquierda || '-';
                    
                    document.getElementById('loading_review').style.display = 'none';
                    document.getElementById('contenido_review').style.display = 'block';
                } else {
                    alert('Error al cargar datos: ' + data.message);
                    modalRevision.hide();
                }
            })
            .catch(error => {
                alert('Error de conexión');
                modalRevision.hide();
            });
        }
        
        function toggleCheckbox() {
            const checkbox = document.getElementById('datos_verificados');
            const container = document.getElementById('checkbox_datos');
            const btnFirmar = document.getElementById('btn_firmar');
            
            checkbox.checked = !checkbox.checked;
            container.classList.toggle('checked', checkbox.checked);
            btnFirmar.disabled = !checkbox.checked;
        }
        
        function firmarCertificado() {
            const id = document.getElementById('solicitud_id_review').value;
            const btn = document.getElementById('btn_firmar');
            
            if (!id) {
                alert('ID de solicitud no válido');
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i> Firmando...';
            
            fetch('../../api/firmar_documento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_solicitud: parseInt(id),
                    tipo_documento: 'certificado'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('mensaje_exito').style.display = 'block';
                    document.getElementById('texto_exito').textContent = data.message;
                    modalRevision.hide();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    document.getElementById('mensaje_error').style.display = 'block';
                    document.getElementById('texto_error').textContent = data.message;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-patch-check me-2"></i> APROBAR Y FIRMAR CERTIFICADO';
                }
            })
            .catch(error => {
                document.getElementById('mensaje_error').style.display = 'block';
                document.getElementById('texto_error').textContent = 'Error de conexión';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-patch-check me-2"></i> APROBAR Y FIRMAR CERTIFICADO';
            });
        }
    </script>
</body>
</html>
