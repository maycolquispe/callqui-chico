<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($_SESSION['rol'] !== 'tesorero') {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $id = intval($_POST['id']);
    $accion = $_POST['accion'];
    $observacion = $_POST['observacion'] ?? '';
    
    if ($accion === 'aprobar') {
        $stmt = $conn->prepare("UPDATE adjudicaciones SET aprobado_tesorero = 1, obs_tesorero = ?, fecha_aprobacion_tesorero = NOW() WHERE id = ?");
        $stmt->bind_param("si", $observacion, $id);
        $stmt->execute();
        $stmt->close();
        
        $adj = $conn->query("SELECT aprobado_secretario, aprobado_presidente, aprobado_tesorero, estado FROM adjudicaciones WHERE id = $id")->fetch_assoc();
        $todosAprobados = $adj['aprobado_secretario'] && $adj['aprobado_presidente'] && $adj['aprobado_tesorero'];
        
        if ($todosAprobados && $adj['estado'] !== 'aprobado') {
            $conn->query("UPDATE adjudicaciones SET estado = 'aprobado', fecha_estado = NOW() WHERE id = $id");
        }
        
    } elseif ($accion === 'rechazar') {
        $stmt = $conn->prepare("UPDATE adjudicaciones SET estado = 'rechazado', obs_tesorero = ?, fecha_estado = NOW() WHERE id = ?");
        $stmt->bind_param("si", $observacion, $id);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: solicitudes.php?msg=actualizado");
    exit;
}

$estado_filtro = $_GET['estado'] ?? '';
$where = "1=1";
if ($estado_filtro) {
    $where .= " AND a.estado = '$estado_filtro'";
}

$sql = "SELECT a.*, u.nombres, u.apellidos, u.dni as dni_usuario,
        p.codigo_pago, p.monto as monto_pago, p.estado as estado_pago, p.medio_pago, p.validado_por
        FROM adjudicaciones a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        LEFT JOIN pagos p ON a.pago_id = p.id
        WHERE $where
        ORDER BY a.fecha_solicitud DESC 
        LIMIT 100";

$result = $conn->query($sql);
$solicitudes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$stmtUser = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

function getEstadoLabel($estado) {
    $labels = [
        'pendiente' => 'Pendiente',
        'en_revision' => 'En Revisión',
        'aprobado' => 'Aprobado',
        'aprobado_total' => 'Aprobado Total',
        'certificado_generado' => 'Certificado',
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

function getPagoLabel($estado) {
    if ($estado === 'validado') return 'Validado';
    if ($estado === 'pendiente') return 'Pendiente';
    if ($estado === 'rechazado') return 'Rechazado';
    return 'Sin pago';
}

function getPagoClass($estado) {
    if ($estado === 'validado') return 'bg-success';
    if ($estado === 'pendiente') return 'bg-warning';
    if ($estado === 'rechazado') return 'bg-danger';
    return 'bg-secondary';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes - Tesorero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --dark-bg: #0a1928; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
            min-height: 100vh;
        }
        body::before {
            content: ""; position: fixed; inset: 0;
            background: radial-gradient(circle at 20% 30%, rgba(37,99,235,0.1) 0%, transparent 50%);
            pointer-events: none; z-index: 0;
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
        
        .filtros {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .filtro-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: #94a3b8;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        .filtro-btn:hover, .filtro-btn.active {
            background: rgba(37,99,235,0.2);
            color: white;
            border-color: #2563eb;
        }
        
        .table-container {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            overflow: hidden;
        }
        .table-custom {
            width: 100%;
            color: white;
        }
        .table-custom thead {
            background: rgba(0,0,0,0.3);
            color: #94a3b8;
        }
        .table-custom th, .table-custom td {
            padding: 0.8rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            vertical-align: middle;
        }
        .table-custom tbody tr:hover {
            background: rgba(255,255,255,0.05);
        }
        
        .btn-aprobar {
            background: rgba(16,185,129,0.2);
            color: #10b981;
            border: 1px solid rgba(16,185,129,0.3);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        .btn-rechazar {
            background: rgba(239,68,68,0.2);
            color: #ef4444;
            border: 1px solid rgba(239,68,68,0.3);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        .btn-ver {
            background: rgba(37,99,235,0.2);
            color: #2563eb;
            border: 1px solid rgba(37,99,235,0.3);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        
        .aprobacion-status {
            font-size: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .aprobacion-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .check-ok { color: #10b981; }
        .check-no { color: #ef4444; }
        
        .firma-panel {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .firma-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .firma-item:last-child { border-bottom: none; }
        .firma-rol { font-weight: 500; }
        .firma-estado-firmado {
            background: rgba(16,185,129,0.2);
            color: #10b981;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .firma-estado-pendiente {
            background: rgba(255,193,7,0.2);
            color: #ffc107;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .firma-estado-bloqueado {
            background: rgba(107,114,128,0.2);
            color: #6b7280;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        
        .modal-backdrop { opacity: 0.3 !important; pointer-events: none !important; }
        .modal { pointer-events: auto !important; z-index: 1055 !important; }
        .modal-dialog { pointer-events: auto !important; z-index: 1056 !important; }
        .modal-content { pointer-events: auto !important; }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        .back-btn:hover { color: white; }
        
        .pago-box {
            font-size: 0.8rem;
        }
        .pago-monto {
            font-weight: 600;
            color: #10b981;
        }
        
        @media (max-width: 768px) {
            .table-custom { font-size: 0.75rem; }
            .table-custom th, .table-custom td { padding: 0.4rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <div class="logo"><i class="bi bi-tree-fill"></i></div>
            <div class="logo-text">
                <h3>Comunidad Callqui Chico</h3>
                <small>Tesorero</small>
            </div>
        </div>
        <a href="../../logout.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </nav>

    <div class="main-container">
        
        <a href="tesorero.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
        
        <div class="page-header">
            <h2><i class="bi bi-file-earmark-check-fill me-2"></i>Solicitudes de Adjudicación</h2>
            <p>Revise, valide pagos, apruebe o rechace las solicitudes de terrenos comunales</p>
        </div>

        <div class="filtros">
            <a href="solicitudes.php" class="filtro-btn <?= !$estado_filtro ? 'active' : '' ?>">Todas</a>
            <a href="solicitudes.php?estado=pendiente" class="filtro-btn <?= $estado_filtro === 'pendiente' ? 'active' : '' ?>">Pendientes</a>
            <a href="solicitudes.php?estado=en_revision" class="filtro-btn <?= $estado_filtro === 'en_revision' ? 'active' : '' ?>">En Revisión</a>
            <a href="solicitudes.php?estado=aprobado" class="filtro-btn <?= $estado_filtro === 'aprobado' ? 'active' : '' ?>">Aprobadas</a>
            <a href="solicitudes.php?estado=rechazado" class="filtro-btn <?= $estado_filtro === 'rechazado' ? 'active' : '' ?>">Rechazadas</a>
        </div>

        <div class="table-container">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Solicitante</th>
                        <th>Lote/Manzana</th>
                        <th>Área</th>
                        <th>Pago</th>
                        <th>Estado</th>
                        <th>Aprobaciones</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['codigo_seguimiento'] ?? $s['codigo'] ?? '-') ?></strong></td>
                        <td>
                            <?= htmlspecialchars($s['nombre']) ?><br>
                            <small class="text-muted">DNI: <?= htmlspecialchars($s['dni']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($s['lote']) ?>/<?= htmlspecialchars($s['manzana'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['area_m2']) ?> m²</td>
                        <td>
                            <div class="pago-box">
                                <?php if ($s['codigo_pago']): ?>
                                    <span class="badge bg-info"><?= $s['codigo_pago'] ?></span><br>
                                    <span class="pago-monto">S/ <?= number_format($s['monto_pago'], 2) ?></span><br>
                                    <span class="badge <?= getPagoClass($s['estado_pago']) ?>"><?= getPagoLabel($s['estado_pago']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Sin pago</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= getEstadoClass($s['estado']) ?>">
                                <?= getEstadoLabel($s['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="aprobacion-status">
                                <div class="aprobacion-item">
                                    <?= $s['aprobado_secretario'] ? '<i class="bi bi-check-circle-fill check-ok"></i>' : '<i class="bi bi-circle check-no"></i>' ?>
                                    <span>Secretario</span>
                                </div>
                                <div class="aprobacion-item">
                                    <?= $s['aprobado_presidente'] ? '<i class="bi bi-check-circle-fill check-ok"></i>' : '<i class="bi bi-circle check-no"></i>' ?>
                                    <span>Presidente</span>
                                </div>
                                <div class="aprobacion-item">
                                    <?= $s['aprobado_tesorero'] ? '<i class="bi bi-check-circle-fill check-ok"></i>' : '<i class="bi bi-circle check-no"></i>' ?>
                                    <span>Tesorero</span>
                                </div>
                            </div>
                        </td>
                        <td><small><?= date('d/m/Y', strtotime($s['fecha_solicitud'])) ?></small></td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <button class="btn btn-ver" data-bs-toggle="modal" data-bs-target="#verModal<?= $s['id'] ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                
                                <?php if ($s['estado'] !== 'rechazado' && $s['estado'] !== 'aprobado' && $s['estado'] !== 'certificado_generado'): ?>
                                <button class="btn btn-aprobar" data-bs-toggle="modal" data-bs-target="#aprobarModal<?= $s['id'] ?>">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button class="btn btn-rechazar" data-bs-toggle="modal" data-bs-target="#rechazarModal<?= $s['id'] ?>">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Modal Ver Detalle -->
                    <div class="modal fade" id="verModal<?= $s['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content bg-dark text-white">
                                <div class="modal-header">
                                    <h5 class="modal-title">Detalle de Solicitud</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Código:</strong> <?= htmlspecialchars($s['codigo_seguimiento'] ?? '-') ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Estado:</strong> <?= getEstadoLabel($s['estado']) ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Solicitante:</strong> <?= htmlspecialchars($s['nombre']) ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>DNI:</strong> <?= htmlspecialchars($s['dni']) ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Lote:</strong> <?= htmlspecialchars($s['lote']) ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Manzana:</strong> <?= htmlspecialchars($s['manzana'] ?? '-') ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Sector:</strong> <?= htmlspecialchars($s['sector'] ?? '-') ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Área:</strong> <?= htmlspecialchars($s['area_m2']) ?> m²
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Expediente:</strong> <?= htmlspecialchars($s['expediente'] ?? '-') ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($s['fecha_solicitud'])) ?>
                                        </div>
                                        
                                        <?php if ($s['observaciones']): ?>
                                        <div class="col-12 mb-3">
                                            <strong>Observaciones:</strong> <?= htmlspecialchars($s['observaciones']) ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($s['obs_tesorero']): ?>
                                        <div class="col-12 mb-3">
                                            <strong>Observación Tesorero:</strong> <?= htmlspecialchars($s['obs_tesorero']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-4 p-3 bg-secondary bg-opacity-25 rounded">
                                        <h6 class="text-white mb-3"><i class="bi bi-cash me-2"></i>Información de Pago</h6>
                                        <?php if ($s['codigo_pago']): ?>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <strong>Código:</strong> <?= $s['codigo_pago'] ?>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <strong>Monto:</strong> S/ <?= number_format($s['monto_pago'], 2) ?>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <strong>Método:</strong> <?= ucfirst($s['medio_pago'] ?? '-') ?>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <strong>Estado:</strong> <span class="badge <?= getPagoClass($s['estado_pago']) ?>"><?= getPagoLabel($s['estado_pago']) ?></span>
                                            </div>
                                            <?php if ($s['validado_por']): ?>
                                            <div class="col-md-4 mb-2">
                                                <strong>Validado por:</strong> <?= $s['validado_por'] ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <p class="text-muted">No hay información de pago registrada</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h6 class="text-white mb-3"><i class="bi bi-folder me-2"></i>Documentos Adjuntos</h6>
                                        <div class="row">
                                            <?php 
                                            $archivos = [
                                                'archivo_dni' => 'Copia DNI',
                                                'archivo_constancia' => 'Constancia de Agua',
                                                'archivo_plano' => 'Plano de Ubicación',
                                                'archivo_recibo' => 'Recibo de Pago',
                                                'archivo_memoria' => 'Memoria Descriptiva',
                                                'archivo_jurada' => 'Declaración Jurada'
                                            ];
                                            
                                            foreach ($archivos as $campo => $label) {
                                                if (!empty($s[$campo])) {
                                                    $ruta = '../../publico/uploads/' . $s[$campo];
                                                    echo '<div class="col-md-6 mb-2">';
                                                    echo '<a href="' . $ruta . '" class="btn btn-sm btn-outline-light w-100 text-start" target="_blank">';
                                                    echo '<i class="bi bi-file-earmark-pdf me-2 text-danger"></i>' . $label;
                                                    echo '</a>';
                                                    echo '</div>';
                                                }
                                            }
                                            
                                            $tiene_archivos = !empty($s['archivo_dni']) || !empty($s['archivo_constancia']) || !empty($s['archivo_plano']) || !empty($s['archivo_recibo']) || !empty($s['archivo_memoria']) || !empty($s['archivo_jurada']);
                                            
                                            if (!$tiene_archivos) {
                                                echo '<div class="col-12"><p class="text-muted small">No hay documentos adjuntos</p></div>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4" id="firmaPanel<?= $s['id'] ?>">
                                        <h6 class="text-white mb-3"><i class="bi bi-pen me-2"></i>Firmas Digitales</h6>
                                        <div class="firma-panel" id="firmaStatus<?= $s['id'] ?>">
                                            <div class="text-center"><div class="spinner-border spinner-border-sm text-light" role="status"></div></div>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <button class="btn btn-warning" id="btnFirmar<?= $s['id'] ?>" onclick="firmarDocumento(<?= $s['id'] ?>, 'adjudicacion')" style="display: none;">
                                                <i class="bi bi-pen-fill me-2"></i>Firmar Digitalmente
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Aprobar -->
                    <div class="modal fade" id="aprobarModal<?= $s['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-white">
                                <div class="modal-header">
                                    <h5 class="modal-title">Aprobar Solicitud</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="accion" value="aprobar">
                                    <div class="modal-body">
                                        <p>¿Está seguro de aprobar la solicitud de <strong><?= htmlspecialchars($s['nombre']) ?></strong>?</p>
                                        <?php if (!$s['codigo_pago']): ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <strong>Advertencia:</strong> Esta solicitud no tiene pago registrado. ¿Desea aprobar de todas formas?
                                        </div>
                                        <?php endif; ?>
                                        <div class="mb-3">
                                            <label class="form-label">Observación (opcional)</label>
                                            <textarea name="observacion" class="form-control" rows="3" placeholder="Agregue una observación..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-success">Aprobar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Rechazar -->
                    <div class="modal fade" id="rechazarModal<?= $s['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-white">
                                <div class="modal-header">
                                    <h5 class="modal-title">Rechazar Solicitud</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="accion" value="rechazar">
                                    <div class="modal-body">
                                        <p>¿Está seguro de rechazar la solicitud de <strong><?= htmlspecialchars($s['nombre']) ?></strong>?</p>
                                        <div class="mb-3">
                                            <label class="form-label">Motivo del rechazo *</label>
                                            <textarea name="observacion" class="form-control" rows="3" required placeholder="Indique el motivo..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-danger">Rechazar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (count($solicitudes) === 0): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size: 4rem;"></i>
            <p class="mt-3">No hay solicitudes con ese filtro</p>
        </div>
        <?php endif; ?>

    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        cargarEstadosFirma();
    });
    
    function cargarEstadosFirma() {
        const solicitudIds = [<?= !empty($solicitudes) ? implode(',', array_column($solicitudes, 'id')) : '' ?>];
        solicitudIds.forEach(function(id) {
            cargarEstadoFirma(id, 'adjudicacion');
        });
    }
    
    function cargarEstadoFirma(idSolicitud, tipoDocumento) {
        fetch('../../api/estado_firmas.php?id_solicitud=' + idSolicitud + '&tipo_documento=' + tipoDocumento, {
            credentials: 'include'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderFirmaStatus(idSolicitud, data.data);
            } else {
                document.getElementById('firmaStatus' + idSolicitud).innerHTML = 
                    '<div class="text-danger text-center"><i class="bi bi-exclamation-triangle"></i> ' + (data.message || 'Error al cargar') + '</div>';
            }
        })
        .catch(e => {
            console.error('Error:', e);
            document.getElementById('firmaStatus' + idSolicitud).innerHTML = 
                '<div class="text-danger text-center"><i class="bi bi-exclamation-triangle"></i> Error de conexión</div>';
        });
    }
    
    function renderFirmaStatus(idSolicitud, data) {
        const container = document.getElementById('firmaStatus' + idSolicitud);
        const btnFirmar = document.getElementById('btnFirmar' + idSolicitud);
        
        let html = '';
        Object.entries(data.firmas).forEach(([rol, info]) => {
            let estadoClass = '';
            let estadoText = '';
            let icon = '';
            
            if (info.firmado) {
                estadoClass = 'firma-estado-firmado';
                estadoText = 'Firmado';
                icon = '<i class="bi bi-check-circle-fill me-1"></i>';
            } else if (info.es_su_turno && info.puede_firmar) {
                estadoClass = 'firma-estado-pendiente';
                estadoText = 'Pendiente - Tu turno';
                icon = '<i class="bi bi-clock me-1"></i>';
            } else if (!info.puede_firmar) {
                estadoClass = 'firma-estado-bloqueado';
                estadoText = 'Sin certificado';
                icon = '<i class="bi bi-x-circle me-1"></i>';
            } else {
                estadoClass = 'firma-estado-bloqueado';
                estadoText = 'Esperando turno';
                icon = '<i class="bi bi-hourglass-split me-1"></i>';
            }
            
            html += '<div class="firma-item">';
            html += '<span class="firma-rol">' + capitalize(rol) + '</span>';
            html += '<span class="' + estadoClass + '">' + icon + estadoText + '</span>';
            html += '</div>';
        });
        
        container.innerHTML = html || '<div class="text-muted text-center"><i class="bi bi-inbox"></i> Sin firmas registradas</div>';
        
        if (data.usuario_actual && data.usuario_actual.es_su_turno && data.usuario_actual.puede_firmar) {
            btnFirmar.style.display = 'inline-block';
        } else {
            btnFirmar.style.display = 'none';
        }
    }
    
    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    function firmarDocumento(idSolicitud, tipoDocumento) {
        if (!confirm('¿Está seguro de firmar digitalmente este documento?\n\nEsta acción no se puede deshacer.')) {
            return;
        }
        
        const btn = document.getElementById('btnFirmar' + idSolicitud);
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Firmando...';
        
        fetch('../../api/firmar_documento.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'include',
            body: JSON.stringify({
                id_solicitud: idSolicitud,
                tipo_documento: tipoDocumento
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Documento firmado exitosamente!\n\nFirmante: ' + data.data.firmante);
                cargarEstadoFirma(idSolicitud, tipoDocumento);
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(e => {
            console.error('Error:', e);
            alert('Error al procesar la firma digital');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>