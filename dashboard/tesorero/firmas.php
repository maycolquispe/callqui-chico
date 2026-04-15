<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($_SESSION['rol'] !== 'tesorero') {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

$stmtUser = $conn->prepare("SELECT foto, nombres, apellidos, certificado_digital, password_certificado FROM usuarios WHERE id=?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();
$nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];

$hasCertificado = !empty($usuario['certificado_digital']) && !empty($usuario['password_certificado']);

$sql = "SELECT a.*, u.nombres as nombre_usuario, u.apellidos as apellido_usuario,
        (SELECT COUNT(*) FROM firmas_digitales f WHERE f.id_solicitud = a.id AND f.tipo_documento = 'adjudicacion') as firmas_count
        FROM adjudicaciones a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.estado IN ('en_firma_tesorero', 'aprobado', 'pagado', 'aprobado_total', 'certificado_generado')
        ORDER BY a.fecha_solicitud DESC 
        LIMIT 50";

$result = $conn->query($sql);
$solicitudes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

function getEstadoLabel($estado) {
    $labels = [
        'pendiente' => 'Pendiente', 
        'en_revision' => 'En Revisión', 
        'aprobado' => 'Aprobado', 
        'en_firma_tesorero' => 'Firma Tesorero',
        'en_firma_comite' => 'Firma Comité',
        'en_firma_secretario' => 'Firma Secretario',
        'en_firma_presidente' => 'Firma Presidente',
        'aprobado_total' => 'Aprobado Total', 
        'certificado_generado' => 'Certificado', 
        'pagado' => 'Pagado'
    ];
    return $labels[$estado] ?? $estado;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firmas Digitales - Tesorero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --dark-bg: #0a1928; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%); min-height: 100vh; }
        body::before { content: ""; position: fixed; inset: 0; background: radial-gradient(circle at 20% 30%, rgba(37,99,235,0.1) 0%, transparent 50%); pointer-events: none; z-index: 0; }
        
        .navbar-modern { background: rgba(10, 25, 40, 0.95); backdrop-filter: blur(12px); padding: 1rem 2rem; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(201, 164, 92, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .logo-area { display: flex; align-items: center; gap: 1rem; }
        .logo { width: 45px; height: 45px; background: linear-gradient(135deg, #c9a45c, #a88642); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #06212e; font-size: 1.3rem; font-weight: 800; }
        .logo-text h3 { color: white; font-weight: 700; font-size: 1.1rem; margin: 0; }
        .logo-text small { color: #dbb67b; font-size: 0.75rem; }
        
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-info { text-align: right; }
        .user-info div { color: white; font-weight: 500; }
        .user-info small { color: #94a3b8; }
        .user-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        
        .main-container { max-width: 1400px; margin: 2rem auto; padding: 0 1.5rem; position: relative; z-index: 1; }
        
        .page-header { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .page-header h2 { color: white; font-weight: 700; margin-bottom: 0.5rem; }
        .page-header p { color: #94a3b8; margin: 0; }
        
        .alert-cert { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; }
        .alert-cert h6 { color: #f59e0b; margin-bottom: 0.5rem; }
        .alert-cert p { color: #94a3b8; font-size: 0.9rem; margin: 0; }
        
        .table-container { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; overflow: hidden; }
        .table-custom { width: 100%; color: white; }
        .table-custom thead { background: rgba(0,0,0,0.3); color: #94a3b8; }
        .table-custom th, .table-custom td { padding: 0.8rem; border-bottom: 1px solid rgba(255,255,255,0.1); vertical-align: middle; }
        .table-custom tbody tr:hover { background: rgba(255,255,255,0.05); }
        
        .btn-firmar { background: linear-gradient(135deg, #c9a45c, #a88642); color: #06212e; border: none; font-weight: 600; padding: 0.5rem 1rem; border-radius: 8px; }
        .btn-firmar:hover { background: linear-gradient(135deg, #d4b06a, #b8964e); color: #06212e; }
        .btn-firmar:disabled { background: rgba(107,114,128,0.3); color: #6b7280; cursor: not-allowed; }
        
        .firma-status { font-size: 0.75rem; display: flex; align-items: center; gap: 0.5rem; }
        .firma-ok { color: #10b981; }
        .firma-pending { color: #f59e0b; }
        .firma-bloqueado { color: #6b7280; }
        
        .back-btn { display: inline-flex; align-items: center; gap: 0.5rem; color: #94a3b8; text-decoration: none; margin-bottom: 1rem; }
        .back-btn:hover { color: white; }
        
        .modal-backdrop { opacity: 0.3 !important; pointer-events: none !important; }
        .modal { pointer-events: auto !important; z-index: 1055 !important; }
        .modal-dialog { pointer-events: auto !important; z-index: 1056 !important; }
        .modal-content { pointer-events: auto !important; }
        
        .firma-panel { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 1rem; margin-top: 1rem; }
        .firma-item { display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .firma-item:last-child { border-bottom: none; }
        .firma-rol { font-weight: 500; }
        .firma-estado-firmado { background: rgba(16,185,129,0.2); color: #10b981; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; }
        .firma-estado-pendiente { background: rgba(255,193,7,0.2); color: #ffc107; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; }
        .firma-estado-bloqueado { background: rgba(107,114,128,0.2); color: #6b7280; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; }
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
        <div class="user-menu">
            <div class="user-info"><div><?= htmlspecialchars($nombreCompleto) ?></div><small>Tesorero</small></div>
            <div class="user-avatar"><?= substr($usuario['nombres'], 0, 1) . substr($usuario['apellidos'], 0, 1) ?></div>
            <a href="../../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </nav>

    <div class="main-container">
        
        <a href="tesorero.php" class="back-btn"><i class="bi bi-arrow-left"></i> Volver al Dashboard</a>
        
        <div class="page-header">
            <h2><i class="bi bi-pen-fill me-2 text-warning"></i>Firmas Digitales</h2>
            <p>Firme digitalmente certificados de adjudicaciones ya pagados</p>
        </div>
        
        <?php if (!$hasCertificado): ?>
        <div class="alert-cert">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Certificado Digital No Configurado</h6>
            <p>Para firmar documentos digitalmente, necesita tener un certificado digital configurado. Contacte al administrador.</p>
        </div>
        <?php endif; ?>
        
        <?php
        $puede_firmar_ids = [];
        foreach ($solicitudes as $s) {
            $firmas = $conn->query("SELECT rol FROM firmas_digitales WHERE id_solicitud = " . $s['id'] . " AND tipo_documento = 'adjudicacion'")->fetch_all(MYSQLI_ASSOC);
            $firmas_roles = array_column($firmas, 'rol');
            $orden = ['tesorero', 'comite_lotes', 'secretario', 'presidente'];
            $mi_posicion = array_search('tesorero', $orden);
            $anteriores = array_slice($orden, 0, $mi_posicion);
            $puede = !in_array('tesorero', $firmas_roles) && count(array_intersect($anteriores, $firmas_roles)) === count($anteriores);
            if ($puede) $puede_firmar_ids[] = $s['id'];
        }
        ?>
        
        <div class="table-container">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Solicitante</th>
                        <th>Lote/Mz</th>
                        <th>Estado</th>
                        <th>Firmas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes as $s): ?>
                    <?php 
                    $firmas = $conn->query("SELECT rol, fecha_firma FROM firmas_digitales WHERE id_solicitud = " . $s['id'] . " AND tipo_documento = 'adjudicacion'")->fetch_all(MYSQLI_ASSOC);
                    $firmas_roles = array_column($firmas, 'rol');
                    $orden = ['tesorero', 'comite_lotes', 'secretario', 'presidente'];
                    $mi_posicion = array_search('tesorero', $orden);
                    $anteriores = array_slice($orden, 0, $mi_posicion);
                    $es_mi_turno = !in_array('tesorero', $firmas_roles) && count(array_intersect($anteriores, $firmas_roles)) === count($anteriores);
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['codigo_seguimiento'] ?? $s['codigo'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($s['nombre']) ?></td>
                        <td><?= htmlspecialchars($s['lote']) ?>/<?= htmlspecialchars($s['manzana'] ?? '-') ?></td>
                        <td><span class="badge bg-<?= in_array($s['estado'], ['aprobado_total', 'certificado_generado']) ? 'success' : (in_array($s['estado'], ['en_firma_tesorero', 'en_firma_comite', 'en_firma_secretario', 'en_firma_presidente']) ? 'warning' : 'info') ?>"><?= getEstadoLabel($s['estado']) ?></span></td>
                        <td>
                            <div class="firma-status">
                                <?php foreach ($orden as $rol): ?>
                                    <?php if (in_array($rol, $firmas_roles)): ?>
                                        <i class="bi bi-check-circle-fill firma-ok" title="<?= ucfirst($rol) ?> firmó"></i>
                                    <?php elseif ($rol === 'tesorero' && $es_mi_turno): ?>
                                        <i class="bi bi-clock-fill firma-pending" title="Tu turno"></i>
                                    <?php else: ?>
                                        <i class="bi bi-circle firma-bloqueado" title="<?= ucfirst($rol) ?>"></i>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-firmar" <?= !$hasCertificado || !$es_mi_turno ? 'disabled' : '' ?> 
                                    data-bs-toggle="modal" data-bs-target="#firmarModal<?= $s['id'] ?>">
                                <i class="bi bi-pen-fill me-1"></i> Firmar
                            </button>
                        </td>
                    </tr>
                    
                    <div class="modal fade" id="firmarModal<?= $s['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-white">
                                <div class="modal-header">
                                    <h5 class="modal-title">Firmar Documento</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>¿Está seguro de firmar digitalmente el certificado de <strong><?= htmlspecialchars($s['nombre']) ?></strong>?</p>
                                    <p class="text-muted small">Esta acción no se puede deshacer.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-warning" onclick="firmarDocumento(<?= $s['id'] ?>)">
                                        <i class="bi bi-pen-fill me-2"></i>Confirmar Firma
                                    </button>
                                </div>
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
            <p class="mt-3">No hay solicitudes listas para firmar</p>
        </div>
        <?php endif; ?>
        
    </div>
    
    <script>
    function firmarDocumento(idSolicitud) {
        fetch('../../api/firmar_documento.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'include',
            body: JSON.stringify({id_solicitud: idSolicitud, tipo_documento: 'adjudicacion'})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Documento firmado exitosamente!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(e => {
            console.error('Error:', e);
            alert('Error al procesar la firma digital');
        });
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>