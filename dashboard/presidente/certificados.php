<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if (!in_array($_SESSION['rol'], ['presidente', 'secretario'])) {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

// Obtener certificados generados
$sql = "SELECT id, nombre, dni, lote, manzana, sector, area_m2, estado, certificado_generado, completamente_firmado, 
        codigo_certificado, pdf_firmado, certificado, fecha_solicitud, fecha_generacion_cert
        FROM adjudicaciones 
        WHERE completamente_firmado = 1 OR certificado_generado = 1
        ORDER BY fecha_generacion_cert DESC, fecha_solicitud DESC
        LIMIT 50";

$result = $conn->query($sql);
$certificados = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener datos del usuario
$stmtUser = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();
$nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificados - Callqui Chico</title>
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
            max-width: 1200px;
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
        .badge-success { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
        .badge-warning { background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
        
        .code-cell {
            font-family: monospace;
            color: var(--accent);
            font-weight: 600;
            background: rgba(201,164,91,0.1);
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.3s;
        }
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37,99,235,0.4);
            color: white;
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
                <h2><i class="bi bi-award-fill me-2 text-warning"></i>Certificados de Adjudicación</h2>
            </div>
        </div>
        
        <?php
        $total_certificados = count($certificados);
        $este_mes = date('m');
        $este_year = date('Y');
        $certificados_mes = 0;
        foreach ($certificados as $c) {
            if ($c['fecha_generacion_cert'] && date('m', strtotime($c['fecha_generacion_cert'])) == $este_mes && date('Y', strtotime($c['fecha_generacion_cert'])) == $este_year) {
                $certificados_mes++;
            }
        }
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(201,164,92,0.2); color: #c9a45c;">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div class="stat-number"><?= $total_certificados ?></div>
                <div class="stat-label">Total Certificados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16,185,129,0.2); color: #10b981;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-number"><?= $certificados_mes ?></div>
                <div class="stat-label">Este Mes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(37,99,235,0.2); color: #2563eb;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-number"><?= $total_certificados ?></div>
                <div class="stat-label">Adjudicatarios</div>
            </div>
        </div>
        
        <div class="table-card">
            <div class="table-header">
                <h5><i class="bi bi-list-ul"></i> Lista de Certificados</h5>
                <span class="text-white-50"><?= count($certificados) ?> registros</span>
            </div>
            <?php if (count($certificados) > 0): ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Adjudicatario</th>
                            <th>DNI</th>
                            <th>Lote/Manzana</th>
                            <th>Sector</th>
                            <th>Área</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificados as $c): ?>
                        <tr>
                            <td data-label="Código"><span class="code-cell"><?= htmlspecialchars($c['codigo_certificado'] ?? 'N/A') ?></span></td>
                            <td data-label="Adjudicatario"><?= htmlspecialchars($c['nombre']) ?></td>
                            <td data-label="DNI"><?= htmlspecialchars($c['dni']) ?></td>
                            <td data-label="Lote/Manzana"><?= htmlspecialchars($c['lote']) ?> / <?= htmlspecialchars($c['manzana'] ?? '-') ?></td>
                            <td data-label="Sector"><?= htmlspecialchars($c['sector'] ?? '-') ?></td>
                            <td data-label="Área"><?= htmlspecialchars($c['area_m2']) ?> m²</td>
                            <td data-label="Estado">
                                <?php if ($c['completamente_firmado']): ?>
                                <span class="badge-status badge-success">
                                    <i class="bi bi-check-circle"></i>Emitido
                                </span>
                                <?php elseif ($c['certificado_generado']): ?>
                                <span class="badge-status badge-warning">
                                    <i class="bi bi-clock"></i>Generado
                                </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Fecha">
                                <?= $c['fecha_generacion_cert'] ? date('d/m/Y', strtotime($c['fecha_generacion_cert'])) : '-' ?>
                            </td>
                            <td data-label="PDF">
                                <?php 
                                $archivo = $c['pdf_firmado'] ?? $c['certificado'];
                                if (!empty($archivo)):
                                    // Verificar la ruta del archivo
                                    $rutaAbsoluta = 'C:/xampp/htdocs/2026/' . $archivo;
                                    if (file_exists($rutaAbsoluta)) {
                                ?>
                                <a href="/2026/<?= htmlspecialchars($archivo) ?>" class="btn-download" target="_blank">
                                    <i class="bi bi-download"></i> Descargar
                                </a>
                                <?php } else { ?>
                                <span class="text-muted">No disponible</span>
                                <?php } endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-award"></i>
                <h4>No hay certificados generados</h4>
                <p>Los certificados de adjudiciaciones aparecerán aquí cuando sean emitidos.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>