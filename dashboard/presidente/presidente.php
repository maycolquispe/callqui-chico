<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Verificar rol de presidente
if ($_SESSION['rol'] !== 'presidente') {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

$stmt = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

$fotoPerfil = !empty($usuario['foto']) ? $usuario['foto'] : 'default.png';
$nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Presidente - Callqui Chico</title>
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
            --card-bg: #ffffff;
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
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-info {
            text-align: right;
            color: white;
        }
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
        
        .welcome-section {
            background: linear-gradient(135deg, rgba(37,99,235,0.2), rgba(16,185,129,0.1));
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-text h2 { color: white; font-weight: 700; margin-bottom: 0.5rem; }
        .welcome-text p { color: #94a3b8; margin: 0; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
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
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .welcome-section { flex-direction: column; text-align: center; gap: 1rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <img src="../../assets/img/logo_callqui.png" alt="Logo" style="width: 60px; height: 60px; object-fit: contain;">
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
            <div class="user-avatar" style="padding: 0; background: transparent;">
                <img src="../../assets/img/logo_callqui.png" alt="Logo" style="width: 40px; height: 40px; border-radius: 8px; object-fit: contain;">
            </div>
            <a href="../../logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </nav>

    <div class="main-container">
        
        <div class="welcome-section">
            <div class="welcome-text">
                <h2>Bienvenido, Presidente</h2>
                <p>Gestione la comunidad desde este panel de control</p>
            </div>
            <div class="text-end">
                <small class="text-white-50"><?= date('d/m/Y') ?></small>
            </div>
        </div>

        <?php
        // Estadísticas rápidas
        $stats = [
            'lotes' => $conn->query("SELECT COUNT(*) as total FROM lotes")->fetch_assoc()['total'] ?? 0,
            'comuneros' => $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol='comunero' AND estado='activo'")->fetch_assoc()['total'] ?? 0,
            'solicitudes_pend' => $conn->query("SELECT COUNT(*) as total FROM adjudicaciones WHERE estado='pendiente'")->fetch_assoc()['total'] ?? 0,
            'actas' => $conn->query("SELECT COUNT(*) as total FROM actas")->fetch_assoc()['total'] ?? 0
        ];
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(37,99,235,0.2); color: #2563eb;">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </div>
                <div class="stat-number"><?= $stats['lotes'] ?></div>
                <div class="stat-label">Total de Lotes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16,185,129,0.2); color: #10b981;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-number"><?= $stats['comuneros'] ?></div>
                <div class="stat-label">Comuneros Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245,158,11,0.2); color: #f59e0b;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-number"><?= $stats['solicitudes_pend'] ?></div>
                <div class="stat-label">Solicitudes Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(139,92,246,0.2); color: #8b5cf6;">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <div class="stat-number"><?= $stats['actas'] ?></div>
                <div class="stat-label">Actas Registradas</div>
            </div>
        </div>

        <h3 class="text-white mb-3">Módulos de Gestión</h3>
        
        <div class="modules-grid">
            <a href="lotes.php" class="module-card">
                <div class="module-icon" style="background: rgba(37,99,235,0.2); color: #2563eb;">
                    <i class="bi bi-map-fill"></i>
                </div>
                <h4>Consulta de Lotes</h4>
                <p>Buscar lotes por DNI, nombre o número. Ver propietario, historial y detalles.</p>
            </a>
            
            <a href="padron.php" class="module-card">
                <div class="module-icon" style="background: rgba(16,185,129,0.2); color: #10b981;">
                    <i class="bi bi-person-lines-fill"></i>
                </div>
                <h4>Padrón Completo</h4>
                <p>Listar todos los comuneros con sus lotes asignados. Descargar PDF.</p>
            </a>
            
            <a href="solicitudes.php" class="module-card">
                <div class="module-icon" style="background: rgba(245,158,11,0.2); color: #f59e0b;">
                    <i class="bi bi-file-earmark-check-fill"></i>
                </div>
                <h4>Solicitudes</h4>
                <p>Ver, aprobar o rechazar solicitudes de adjudicaciones.</p>
            </a>
            
            <a href="certificados.php" class="module-card">
                <div class="module-icon" style="background: rgba(201,164,92,0.2); color: #c9a45c;">
                    <i class="bi bi-award-fill"></i>
                </div>
                <h4>Certificados</h4>
                <p>Ver historial de certificados de adjudicaciones generados y emitidos.</p>
            </a>
            
            <a href="actas.php" class="module-card">
                <div class="module-icon" style="background: rgba(139,92,246,0.2); color: #8b5cf6;">
                    <i class="bi bi-file-text-fill"></i>
                </div>
                <h4>Actas</h4>
                <p>Ver actas registradas, descargar PDFs y ver contenido.</p>
            </a>
            
            <a href="solicitudes_generales.php" class="module-card">
                <div class="module-icon" style="background: rgba(168,85,247,0.2); color: #a855f7;">
                    <i class="bi bi-envelope-paper-fill"></i>
                </div>
                <h4>Solicitudes Generales</h4>
                <p>Ver solicitudes de salud, educación y otros trámites.</p>
            </a>
            
            <a href="transferencias.php" class="module-card">
                <div class="module-icon" style="background: rgba(236,72,153,0.2); color: #ec4899;">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <h4>Transferencias</h4>
                <p>Ver historial de cambios de propietario de lotes.</p>
            </a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>