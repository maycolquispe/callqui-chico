<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if (!in_array($_SESSION['rol'], ['tesorero', 'administrador'])) {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $id = intval($_POST['id_pago']);
    $accion = $_POST['accion'];
    
    $estado = ($accion === 'validar') ? 'validado' : 'rechazado';
    
    $stmt = $conn->prepare("UPDATE pagos SET estado = ?, validado_por = ?, fecha_validacion = NOW() WHERE id = ?");
    $stmt->bind_param("sii", $estado, $usuario_id, $id);
    $stmt->execute();
    
    $pago = $conn->query("SELECT id_solicitud FROM pagos WHERE id = $id")->fetch_assoc();
    if ($pago && $pago['id_solicitud']) {
        if ($accion === 'validar') {
            // Cambio de estado: pagado -> en_firma_tesorero (listo para firma del tesorero)
            $conn->query("UPDATE adjudicaciones SET estado_pago = 'pagado', estado = 'en_firma_tesorero' WHERE id = " . $pago['id_solicitud']);
        } else {
            $conn->query("UPDATE adjudicaciones SET estado_pago = 'sin_pago' WHERE id = " . $pago['id_solicitud']);
        }
    }
    
    $stmt->close();
    header("Location: pagos.php");
    exit;
}

$stmtUser = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();
$nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];

$sql = "SELECT p.*, a.nombre as solicitante, a.dni, a.lote, a.manzana, a.sector 
        FROM pagos p 
        JOIN adjudicaciones a ON p.id_solicitud = a.id 
        WHERE p.estado = 'pendiente'
        ORDER BY p.fecha_pago DESC";
$pagos = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Pagos - Callqui Chico</title>
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
            --accent-light: #dbb67b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
            min-height: 100vh;
            position: relative;
        }
        body::before {
            content: ""; position: fixed; inset: 0;
            background: radial-gradient(circle at 20% 30%, rgba(37,99,235,0.08) 0%, transparent 50%),
                        radial-gradient(circle at 80% 70%, rgba(201,164,92,0.08) 0%, transparent 50%);
            pointer-events: none; z-index: 0;
        }
        
        .navbar-modern {
            background: rgba(10, 25, 40, 0.95);
            backdrop-filter: blur(12px);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 50;
            border-bottom: 1px solid rgba(201, 164, 92, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-area { display: flex; align-items: center; gap: 1rem; }
        .logo-text h3 { color: white; font-weight: 700; font-size: 1.1rem; margin: 0; }
        .logo-text small { color: var(--accent-light); font-size: 0.75rem; }
        
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-info { text-align: right; }
        .user-info div { color: white; font-weight: 500; }
        .user-info small { color: #94a3b8; }
        .user-avatar {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent), #a88642);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #0a1928; font-weight: 600; font-size: 0.85rem;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .page-header {
            background: rgba(15, 39, 64, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .page-header h2 { 
            color: white; font-weight: 700; 
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
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .back-btn:hover { color: white; transform: translateX(-3px); }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(15, 39, 64, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent);
        }
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        .stat-number { color: white; font-size: 2rem; font-weight: 700; }
        .stat-label { color: #94a3b8; font-size: 0.85rem; margin-top: 0.25rem; }
        
        .table-card {
            background: rgba(15, 39, 64, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 1.5rem;
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
            vertical-align: middle;
        }
        .table-custom tbody tr:hover td {
            background: rgba(255,255,255,0.03);
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
        .badge-pending { background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
        .badge-valid { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
        .badge-reject { background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
        
        .code-cell {
            font-family: monospace;
            color: var(--accent);
            font-weight: 600;
            background: rgba(201,164,91,0.1);
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
        }
        
        .amount-cell {
            color: #10b981;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .btn-validate {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 0.5rem 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-validate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16,185,129,0.4);
        }
        
        .btn-reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 0.5rem 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239,68,68,0.4);
        }
        
        .comprobante-img { 
            max-width: 80px; 
            max-height: 60px; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: all 0.3s;
        }
        .comprobante-img:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }
        .empty-state i { font-size: 4rem; opacity: 0.3; margin-bottom: 1rem; }
        .empty-state h4 { color: white; margin-bottom: 0.5rem; }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .table-custom thead { display: none; }
            .table-custom tbody tr { display: block; margin-bottom: 1rem; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 1rem; }
            .table-custom tbody td { display: flex; justify-content: space-between; padding: 0.5rem 0; border: none; }
            .table-custom tbody td::before { content: attr(data-label); font-weight: 600; color: #94a3b8; }
        }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <img src="../../assets/img/logo_callqui.png" alt="Logo" style="width: 45px; height: 45px; object-fit: contain; border-radius: 8px;">
            <div class="logo-text">
                <h3>Comunidad Callqui Chico</h3>
                <small>Panel del Tesorero</small>
            </div>
        </div>
        <div class="user-menu">
            <div class="user-info">
                <div><?= htmlspecialchars($nombreCompleto) ?></div>
                <small>Tesorero</small>
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
        
        <a href="tesorero.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
        
        <div class="page-header">
            <h2><i class="bi bi-cash-stack me-2"></i>Validar Pagos</h2>
            <p class="text-white-50 mb-0">Revisa y valida los pagos de adjudicaciones</p>
        </div>
        
        <?php
        $total_pagos = count($pagos);
        $monto_total = array_sum(array_column($pagos, 'monto'));
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245,158,11,0.2); color: #f59e0b;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-number"><?= $total_pagos ?></div>
                <div class="stat-label">Pagos Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16,185,129,0.2); color: #10b981;">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-number">S/ <?= number_format($monto_total, 0) ?></div>
                <div class="stat-label">Monto Total</div>
            </div>
        </div>
        
        <div class="table-card">
            <div class="table-header">
                <h5><i class="bi bi-list-ul"></i> Lista de Pagos Pendientes</h5>
                <span class="text-white-50"><?= count($pagos) ?> registros</span>
            </div>
            <?php if (count($pagos) > 0): ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Solicitante</th>
                            <th>DNI</th>
                            <th>Lote/Mz</th>
                            <th>Propietarios</th>
                            <th>Monto</th>
                            <th>Medio</th>
                            <th>N° Operación</th>
                            <th>Comprobante</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $p): ?>
                        <tr>
                            <td data-label="Código"><span class="code-cell"><?= htmlspecialchars($p['codigo_pago']) ?></span></td>
                            <td data-label="Solicitante"><?= htmlspecialchars($p['solicitante']) ?></td>
                            <td data-label="DNI"><?= htmlspecialchars($p['dni']) ?></td>
                            <td data-label="Lote/Mz"><?= htmlspecialchars($p['lote']) ?>/<?= htmlspecialchars($p['manzana'] ?? '-') ?></td>
                            <td data-label="Propietarios"><?= $p['numero_propietarios'] ?></td>
                            <td data-label="Monto"><span class="amount-cell">S/ <?= number_format($p['monto'], 2) ?></span></td>
                            <td data-label="Medio"><?= htmlspecialchars($p['medio_pago']) ?></td>
                            <td data-label="N° Operación"><?= htmlspecialchars($p['numero_operacion'] ?? '-') ?></td>
                            <td data-label="Comprobante">
                                <?php if (!empty($p['comprobante'])): ?>
                                <a href="/2026/publico/uploads/pagos/<?= htmlspecialchars($p['comprobante']) ?>" target="_blank">
                                    <img src="/2026/publico/uploads/pagos/<?= htmlspecialchars($p['comprobante']) ?>" class="comprobante-img" alt="Comprobante">
                                </a>
                                <?php else: ?>
                                <span class="text-muted">Sin imagen</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Fecha"><?= date('d/m/Y H:i', strtotime($p['fecha_pago'])) ?></td>
                            <td data-label="Acciones">
                                <form method="POST" class="action-buttons">
                                    <input type="hidden" name="id_pago" value="<?= $p['id'] ?>">
                                    <button type="submit" name="accion" value="rechazar" class="btn-reject" title="Rechazar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <button type="submit" name="accion" value="validar" class="btn-validate" title="Validar">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-check-circle"></i>
                <h4>No hay pagos pendientes</h4>
                <p>Todos los pagos han sido validados.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>