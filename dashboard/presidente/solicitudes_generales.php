<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if (!in_array($_SESSION['rol'], ['presidente', 'secretario', 'comite_lotes'])) {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

// Procesar aprobación/rechazo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $id = intval($_POST['id_solicitud']);
    $accion = $_POST['accion'];
    $observacion = $_POST['observacion'] ?? '';
    
    if ($accion === 'aprobar') {
        $stmt = $conn->prepare("UPDATE permisos SET estado = 'Aprobado', observacion_secretario = ? WHERE id = ?");
        $stmt->bind_param("si", $observacion, $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($accion === 'rechazar') {
        $stmt = $conn->prepare("UPDATE permisos SET estado = 'Rechazado', observacion_secretario = ? WHERE id = ?");
        $stmt->bind_param("si", $observacion, $id);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: solicitudes_generales.php");
    exit;
}

// Obtener solicitudes generales de permisos
$sql = "SELECT p.*, u.nombres, u.apellidos, u.dni as dni_usuario
        FROM permisos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.codigo_unico IS NOT NULL AND p.codigo_unico != ''
        ORDER BY p.fecha_registro DESC
        LIMIT 100";

$result = $conn->query($sql);
$solicitudes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener datos del usuario
$stmtUser = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();
$nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];

function getEstadoLabel($estado) {
    $labels = [
        'Pendiente' => 'Pendiente',
        'Aprobado' => 'Aprobado',
        'Rechazado' => 'Rechazado'
    ];
    return $labels[$estado] ?? $estado;
}

function getEstadoClass($estado) {
    $classes = [
        'Pendiente' => 'warning',
        'Aprobado' => 'success',
        'Rechazado' => 'danger'
    ];
    return $classes[$estado] ?? 'secondary';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes Generales - Callqui Chico</title>
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
            --accent: #c9a45c;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: visible;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 30%, rgba(37,99,235,0.08) 0%, transparent 50%),
                        radial-gradient(circle at 80% 70%, rgba(201,164,92,0.08) 0%, transparent 50%);
            z-index: -1;
        }
        
        .navbar-modern {
            background: rgba(10, 25, 40, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(201, 164, 92, 0.3);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .logo-area { display: flex; align-items: center; gap: 1rem; }
        .logo-text h3 { color: white; font-size: 1.1rem; margin: 0; font-weight: 700; }
        .logo-text small { color: #94a3b8; font-size: 0.75rem; }
        
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-info { text-align: right; }
        .user-info div { color: white; font-weight: 500; }
        .user-info small { color: #94a3b8; }
        .user-avatar {
            width: 45px; height: 45px;
            background: linear-gradient(135deg, var(--accent), #a88642);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #0a1928; font-weight: 600; font-size: 0.9rem;
        }
        
        .main-container { 
            padding: 2rem; 
            max-width: 1400px; 
            margin: 0 auto; 
            position: relative;
            z-index: 1;
        }
        
        .page-header {
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            margin-bottom: 2rem;
        }
        .page-header h2 { 
            color: white; 
            font-size: 1.8rem; 
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
        }
        .back-btn:hover { color: white; }
        
        .filter-bar {
            display: flex; 
            gap: 0.75rem; 
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .filter-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #94a3b8;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .filter-btn:hover, .filter-btn.active {
            background: rgba(201,164,92,0.2);
            border-color: rgba(201,164,92,0.5);
            color: var(--accent);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(15, 39, 64, 0.6);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        .stat-icon.total { background: rgba(37,99,235,0.2); color: #2563eb; }
        .stat-icon.pending { background: rgba(245,158,11,0.2); color: #f59e0b; }
        .stat-icon.approved { background: rgba(16,185,129,0.2); color: #10b981; }
        .stat-icon.rejected { background: rgba(239,68,68,0.2); color: #ef4444; }
        .stat-number { color: white; font-size: 2rem; font-weight: 700; }
        .stat-label { color: #94a3b8; font-size: 0.85rem; margin-top: 0.25rem; }
        
        .table-card {
            background: rgba(15, 39, 64, 0.6);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .table-header h5 {
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table-custom thead th {
            background: rgba(0,0,0,0.3);
            color: #94a3b8;
            border: none;
            padding: 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-custom tbody tr {
            transition: all 0.2s;
        }
        .table-custom tbody td {
            background: transparent;
            border: none;
            padding: 1rem;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .table-custom tbody tr:hover td {
            background: rgba(255,255,255,0.03);
        }
        
        .code-cell {
            font-family: monospace;
            color: var(--accent);
            font-weight: 600;
            background: rgba(201,164,91,0.1);
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
        }
        
        .badge-status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .badge-warning { background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
        .badge-success { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
        .badge-danger { background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
        
        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn-view {
            background: rgba(37,99,235,0.15);
            color: #2563eb;
            border: 1px solid rgba(37,99,235,0.3);
        }
        .btn-view:hover { background: #2563eb; color: white; }
        
        .type-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            background: rgba(168,85,247,0.15);
            color: #a78bfa;
            border: 1px solid rgba(168,85,247,0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.3; }
        .empty-state h4 { color: white; margin-bottom: 0.5rem; }
        
        @media (max-width: 768px) {
            .page-header { flex-direction: column; gap: 1rem; text-align: center; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .table-custom thead { display: none; }
            .table-custom tbody tr { display: block; margin-bottom: 1rem; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 1rem; }
            .table-custom tbody td { display: flex; justify-content: space-between; padding: 0.5rem 0; border: none; }
            .table-custom tbody td::before { content: attr(data-label); font-weight: 600; color: #94a3b8; }
        }

        /* Modal Styles */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.6) !important;
        }
        
        .modal {
            z-index: 1055 !important;
        }
        
        .modal-dialog {
            z-index: 1060 !important;
            margin: 1.75rem auto;
        }
        
        .modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show .modal-dialog {
            transform: scale(1);
            opacity: 1;
        }
        
        .modal-backdrop.show {
            opacity: 0.5;
        }
        
        .modal-content-custom {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(201, 164, 92, 0.3);
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            color: #1e293b;
            position: relative;
            z-index: 1065;
            opacity: 1;
            transform: scale(1);
        }
        
        .modal-header-custom {
            background: linear-gradient(135deg, #0a2b3c 0%, #1e4a6a 100%);
            border-radius: 16px 16px 0 0;
            padding: 1.25rem 1.5rem;
            border-bottom: 2px solid #c9a45c;
        }
        
        .modal-header-custom .modal-title {
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-body-custom {
            padding: 1.5rem;
            background: white;
        }
        
        .detail-label {
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            color: #1e293b;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .modal-footer-custom {
            background: #f8fafc;
            border-radius: 0 0 16px 16px;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn-aprobar {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-aprobar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
            color: white;
        }
        
        .btn-rechazar {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-rechazar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
            color: white;
        }
        
        .btn-cerrar {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-cerrar:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <img src="../../assets/img/logo_callqui.png" alt="Logo" style="width: 45px; height: 45px; object-fit: contain; border-radius: 8px;">
            <div class="logo-text">
                <h3>Comunidad Callqui Chico</h3>
                <small>Panel del Presidente</small>
            </div>
        </div>
        <div class="user-menu">
            <div class="user-info">
                <div><?= htmlspecialchars($nombreCompleto) ?></div>
                <small>Presidente</small>
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
        
        <div class="page-header">
            <div>
                <a href="presidente.php" class="btn btn-outline-light btn-sm mb-2">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
                <h2><i class="bi bi-envelope-paper-fill me-2 text-purple"></i>Solicitudes Generales</h2>
            </div>
        </div>
        
        <?php
        $total = count($solicitudes);
        $pendientes = count(array_filter($solicitudes, function($s) { return $s['estado'] == 'Pendiente'; }));
        $aprobadas = count(array_filter($solicitudes, function($s) { return $s['estado'] == 'Aprobado'; }));
        $rechazadas = count(array_filter($solicitudes, function($s) { return $s['estado'] == 'Rechazado'; }));
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total"><i class="bi bi-file-earmark-text"></i></div>
                <div class="stat-number"><?= $total ?></div>
                <div class="stat-label">Total Solicitudes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-number text-warning"><?= $pendientes ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon approved"><i class="bi bi-check-circle"></i></div>
                <div class="stat-number text-success"><?= $aprobadas ?></div>
                <div class="stat-label">Aprobadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon rejected"><i class="bi bi-x-circle"></i></div>
                <div class="stat-number text-danger"><?= $rechazadas ?></div>
                <div class="stat-label">Rechazadas</div>
            </div>
        </div>
        
        <div class="table-card">
            <div class="table-header">
                <h5><i class="bi bi-list-ul"></i> Lista de Solicitudes</h5>
                <span class="text-white-50"><?= count($solicitudes) ?> registros</span>
            </div>
            <?php if (count($solicitudes) > 0): ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Solicitante</th>
                            <th>DNI</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $s): ?>
                        <tr>
                            <td data-label="Código"><span class="code-cell"><?= htmlspecialchars($s['codigo_unico'] ?? '-') ?></span></td>
                            <td data-label="Tipo"><span class="type-badge"><?= htmlspecialchars($s['tipo_permiso'] ?? 'General') ?></span></td>
                            <td data-label="Solicitante"><?= htmlspecialchars($s['nombre_solicitante'] ?? $s['nombres'] . ' ' . $s['apellidos']) ?></td>
                            <td data-label="DNI"><?= htmlspecialchars($s['dni_solicitante'] ?? $s['dni_usuario'] ?? '-') ?></td>
                            <td data-label="Fecha"><?= date('d/m/Y', strtotime($s['fecha_registro'])) ?></td>
                            <td data-label="Estado">
                                <?php 
                                $badgeClass = match($s['estado']) {
                                    'Pendiente' => 'warning',
                                    'Aprobado' => 'success',
                                    'Rechazado' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge-status badge-<?= $badgeClass ?>">
                                    <i class="bi bi-<?= $s['estado'] == 'Pendiente' ? 'hourglass-split' : ($s['estado'] == 'Aprobado' ? 'check-circle' : 'x-circle') ?>"></i>
                                    <?= getEstadoLabel($s['estado']) ?>
                                </span>
                            </td>
                            <td data-label="Acciones">
                                <button class="btn-action btn-view" data-bs-toggle="modal" data-bs-target="#verModal<?= $s['id'] ?>">
                                    <i class="bi bi-eye"></i> Ver
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Modal Ver Detalle -->
                        <div class="modal fade" id="verModal<?= $s['id'] ?>" tabindex="-1" data-bs-backdrop="false" style="background: transparent;">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content modal-content-custom">
                                    <div class="modal-header modal-header-custom">
                                        <h5 class="modal-title">
                                            <i class="bi bi-file-earmark-text"></i>
                                            Detalle de Solicitud
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 1;"></button>
                                    </div>
                                    <div class="modal-body modal-body-custom">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="detail-label">Código</div>
                                                <div class="detail-value code-cell"><?= htmlspecialchars($s['codigo_unico'] ?? '-') ?></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="detail-label">Tipo de Solicitud</div>
                                                <div class="detail-value"><span class="type-badge"><?= htmlspecialchars($s['tipo_permiso'] ?? 'General') ?></span></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="detail-label">Solicitante</div>
                                                <div class="detail-value"><?= htmlspecialchars($s['nombre_solicitante'] ?? '-') ?></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="detail-label">DNI</div>
                                                <div class="detail-value"><?= htmlspecialchars($s['dni_solicitante'] ?? '-') ?></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="detail-label">Teléfono</div>
                                                <div class="detail-value"><?= htmlspecialchars($s['telefono'] ?? '-') ?></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="detail-label">Fecha de Solicitud</div>
                                                <div class="detail-value"><?= date('d/m/Y H:i', strtotime($s['fecha_registro'])) ?></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="detail-label">Estado</div>
                                                <div class="detail-value">
                                                    <span class="badge-status badge-<?= $badgeClass ?>">
                                                        <?= getEstadoLabel($s['estado']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <div class="detail-label">Descripción / Motivo</div>
                                                <div class="detail-value" style="background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                                                    <?= nl2br(htmlspecialchars($s['motivo'] ?? 'Sin descripción')) ?>
                                                </div>
                                            </div>
                                            <?php if (!empty($s['archivo'])): ?>
                                            <div class="col-12 mb-3">
                                                <div class="detail-label">Documento Adjunto</div>
                                                <?php 
                                                $nombreArchivo = $s['archivo'];
                                                
                                                // Rutas a verificar
                                                $rutas = [
                                                    'C:/xampp/htdocs/2026/publico/uploads/solicitudes/' . $nombreArchivo,
                                                    'C:/xampp/htdocs/2026/publico/uploads/' . $nombreArchivo,
                                                    'C:/xampp/htdocs/2026/dashboard/comunero/uploads/' . $nombreArchivo
                                                ];
                                                
                                                $encontrado = false;
                                                $rutaWeb = '';
                                                
                                                foreach ($rutas as $ruta) {
                                                    if (file_exists($ruta)) {
                                                        if (strpos($ruta, 'solicitudes') !== false) {
                                                            $rutaWeb = '/2026/publico/uploads/solicitudes/' . $nombreArchivo;
                                                        } elseif (strpos($ruta, 'comunero') !== false) {
                                                            $rutaWeb = '/2026/dashboard/comunero/uploads/' . $nombreArchivo;
                                                        } else {
                                                            $rutaWeb = '/2026/publico/uploads/' . $nombreArchivo;
                                                        }
                                                        $encontrado = true;
                                                        break;
                                                    }
                                                }
                                                
                                                if ($encontrado) {
                                                    ?>
                                                    <a href="<?= $rutaWeb ?>" class="btn btn-sm mt-2" style="background: #2563eb; color: white;" target="_blank">
                                                        <i class="bi bi-file-earmark-pdf me-1"></i> Ver Documento
                                                    </a>
                                                    <?php
                                                } else {
                                                    // Mostrar la ruta que se buscó para depuración
                                                    ?>
                                                    <span class="text-danger">
                                                        <i class="bi bi-exclamation-triangle me-1"></i> 
                                                        No encontrado: <?= htmlspecialchars($nombreArchivo) ?>
                                                    </span>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($s['estado'] == 'Pendiente'): ?>
                                        <div style="border-top: 1px solid #e2e8f0; margin-top: 1rem; padding-top: 1rem;">
                                            <div class="detail-label mb-3">Acciones</div>
                                            <form method="POST" class="d-flex gap-2 justify-content-end">
                                                <input type="hidden" name="id_solicitud" value="<?= $s['id'] ?>">
                                                <input type="hidden" name="observacion" value="Aprobado por el Presidente">
                                                <button type="submit" name="accion" value="rechazar" class="btn-rechazar">
                                                    <i class="bi bi-x-circle"></i> Rechazar
                                                </button>
                                                <button type="submit" name="accion" value="aprobar" class="btn-aprobar">
                                                    <i class="bi bi-check-circle"></i> Aprobar
                                                </button>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer modal-footer-custom">
                                        <button type="button" class="btn-cerrar" data-bs-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-envelope"></i>
                <h4>No hay solicitudes generales</h4>
                <p>Las solicitudes de salud, educación y otros trámites aparecerán aquí.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>