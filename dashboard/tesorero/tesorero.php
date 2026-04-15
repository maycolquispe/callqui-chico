<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if (!in_array($_SESSION['rol'], ['tesorero', 'administrador'])) {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

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
    <title>Dashboard Tesorero - Callqui Chico</title>
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
        }
        
        .navbar-modern {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-area { display: flex; align-items: center; gap: 1rem; }
        .logo {
            width: 45px; height: 45px;
            background: linear-gradient(135deg, #c9a45c, #a88642);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #0a1928;
            font-size: 1.3rem;
        }
        .logo-text h3 { color: white; font-size: 1.1rem; margin: 0; }
        .logo-text small { color: #94a3b8; font-size: 0.75rem; }
        
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-info { text-align: right; }
        .user-info div { color: white; font-weight: 500; }
        .user-info small { color: #94a3b8; }
        .user-avatar {
            width: 45px; height: 45px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 600;
        }
        
        .main-container { padding: 2rem; max-width: 1400px; margin: 0 auto; }
        
        .welcome-section {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(255,255,255,0.05);
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .welcome-text h2 { color: white; font-size: 1.8rem; }
        .welcome-text p { color: #94a3b8; margin: 0; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(201,164,92,0.5);
        }
        .stat-icon {
            width: 60px; height: 60px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        .stat-number { color: white; font-size: 2rem; font-weight: 700; }
        .stat-label { color: #94a3b8; font-size: 0.9rem; }
        
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .module-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 1.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
        }
        .module-card:hover {
            transform: translateY(-5px);
            border-color: rgba(201, 164, 92, 0.5);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .module-icon {
            width: 60px; height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        .module-card h4 { color: white; font-weight: 600; margin-bottom: 0.5rem; }
        .module-card p { color: #94a3b8; font-size: 0.85rem; margin: 0; }
        
        @media (max-width: 768px) {
            .welcome-section { flex-direction: column; text-align: center; gap: 1rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <div class="logo"><i class="bi bi-tree-fill"></i></div>
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
        
        <div class="welcome-section">
            <div class="welcome-text">
                <h2>Bienvenido, Tesorero</h2>
                <p>Gestione los pagos de adjudicaciones desde este panel</p>
            </div>
            <div class="text-end">
                <small class="text-white-50"><?= date('d/m/Y') ?></small>
            </div>
        </div>

        <?php
        // Estadísticas
        $stats = [
            'pendientes' => $conn->query("SELECT COUNT(*) as total FROM pagos WHERE estado='pendiente'")->fetch_assoc()['total'] ?? 0,
            'validados' => $conn->query("SELECT COUNT(*) as total FROM pagos WHERE estado='validado'")->fetch_assoc()['total'] ?? 0,
            'rechazados' => $conn->query("SELECT COUNT(*) as total FROM pagos WHERE estado='rechazado'")->fetch_assoc()['total'] ?? 0,
            'total_monto' => $conn->query("SELECT COALESCE(SUM(monto),0) as total FROM pagos WHERE estado='validado'")->fetch_assoc()['total'] ?? 0
        ];
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245,158,11,0.2); color: #f59e0b;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-number"><?= $stats['pendientes'] ?></div>
                <div class="stat-label">Pagos Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16,185,129,0.2); color: #10b981;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-number"><?= $stats['validados'] ?></div>
                <div class="stat-label">Pagos Validados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(239,68,68,0.2); color: #ef4444;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-number"><?= $stats['rechazados'] ?></div>
                <div class="stat-label">Pagos Rechazados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(201,164,92,0.2); color: #c9a45c;">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-number">S/ <?= number_format($stats['total_monto'], 0) ?></div>
                <div class="stat-label">Total Recaudado</div>
            </div>
        </div>
        
        <h3 class="text-white mb-3">Módulos de Gestión</h3>
        
        <div class="modules-grid">
            <a href="solicitudes.php" class="module-card">
                <div class="module-icon" style="background: rgba(245,158,11,0.2); color: #f59e0b;">
                    <i class="bi bi-file-earmark-check-fill"></i>
                </div>
                <h4>Solicitudes</h4>
                <p>Ver, aprobar o rechazar solicitudes de adjudicaciones.</p>
            </a>
            
            <a href="pagos.php" class="module-card">
                <div class="module-icon" style="background: rgba(16,185,129,0.2); color: #10b981;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <h4>Validar Pagos</h4>
                <p>Revisar y validar pagos de adjudicaciones pendientes.</p>
            </a>
            
            <a href="firmas.php" class="module-card">
                <div class="module-icon" style="background: rgba(139,92,246,0.2); color: #8b5cf6;">
                    <i class="bi bi-pen-fill"></i>
                </div>
                <h4>Firmas Digitales</h4>
                <p>Firmar digitalmente certificados de adjudicaciones.</p>
            </a>
            
            <a href="historial_pagos.php" class="module-card">
                <div class="module-icon" style="background: rgba(37,99,235,0.2); color: #2563eb;">
                    <i class="bi bi-receipt"></i>
                </div>
                <h4>Historial de Pagos</h4>
                <p>Ver todos los pagos realizados y su estado.</p>
            </a>
            
            <a href="reportes.php" class="module-card">
                <div class="module-icon" style="background: rgba(201,164,92,0.2); color: #c9a45c;">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h4>Reportes</h4>
                <p>Estadísticas y reportes de recaudación.</p>
            </a>
            
            <a href="config_pagos.php" class="module-card">
                <div class="module-icon" style="background: rgba(59,130,246,0.2); color: #3b82f6;">
                    <i class="bi bi-bank"></i>
                </div>
                <h4>Configurar Pagos</h4>
                <p>Configurar datos de cuenta bancaria y Yape.</p>
            </a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>