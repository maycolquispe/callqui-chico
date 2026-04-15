<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($_SESSION['rol'] !== 'presidente') {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

// Obtener estadísticas
$stats = [
    // Lotes
    'total_lotes' => $conn->query("SELECT COUNT(*) as total FROM lotes")->fetch_assoc()['total'] ?? 0,
    'lotes_ocupados' => $conn->query("SELECT COUNT(*) as total FROM lotes WHERE estado = 'OCUPADO'")->fetch_assoc()['total'] ?? 0,
    'lotes_libres' => $conn->query("SELECT COUNT(*) as total FROM lotes WHERE estado = 'LIBRE'")->fetch_assoc()['total'] ?? 0,
    'lotes_excedentes' => $conn->query("SELECT COUNT(*) as total FROM lotes WHERE estado = 'EXCEDENTE'")->fetch_assoc()['total'] ?? 0,
    'area_total' => $conn->query("SELECT SUM(area_m2) as total FROM lotes")->fetch_assoc()['total'] ?? 0,
    
    // Comuneros
    'total_comuneros' => $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'comunero' AND estado = 'activo'")->fetch_assoc()['total'] ?? 0,
    'comuneros_con_lote' => $conn->query("SELECT COUNT(DISTINCT usuario_id) as total FROM lotes WHERE usuario_id IS NOT NULL")->fetch_assoc()['total'] ?? 0,
    
    // Solicitudes
    'sol_pendientes' => $conn->query("SELECT COUNT(*) as total FROM adjudicaciones WHERE estado = 'pendiente'")->fetch_assoc()['total'] ?? 0,
    'sol_en_revision' => $conn->query("SELECT COUNT(*) as total FROM adjudicaciones WHERE estado = 'en_revision'")->fetch_assoc()['total'] ?? 0,
    'sol_aprobadas' => $conn->query("SELECT COUNT(*) as total FROM adjudicaciones WHERE estado = 'aprobado' OR estado = 'aprobado_total'")->fetch_assoc()['total'] ?? 0,
    'sol_rechazadas' => $conn->query("SELECT COUNT(*) as total FROM adjudicaciones WHERE estado = 'rechazado'")->fetch_assoc()['total'] ?? 0,
    
    // Actas
    'total_actas' => $conn->query("SELECT COUNT(*) as total FROM actas")->fetch_assoc()['total'] ?? 0,
    'actas_asistencia' => $conn->query("SELECT COUNT(*) as total FROM actas WHERE asistencia_habilitada = 1")->fetch_assoc()['total'] ?? 0,
    
    // Últimas solicitudes
    'ultimas_solicitudes' => $conn->query("SELECT COUNT(*) as total FROM adjudicaciones WHERE fecha_solicitud >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['total'] ?? 0
];

// Obtener usuarios
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
    <title>Reportes - Callqui Chico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
        }
        .stat-card h4 { color: white; font-size: 2rem; font-weight: 700; margin-bottom: 0.25rem; }
        .stat-card small { color: #94a3b8; }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
        }
        .chart-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
        }
        .chart-card h5 { color: white; font-weight: 600; margin-bottom: 1rem; }
        
        .section-title {
            color: white;
            font-weight: 600;
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        .back-btn:hover { color: white; }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <div class="logo"><i class="bi bi-tree-fill"></i></div>
            <div class="logo-text">
                <h3>Comunidad Callqui Chico</h3>
                <small>Presidente</small>
            </div>
        </div>
        <a href="../../logout.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </nav>

    <div class="main-container">
        
        <a href="presidente.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
        
        <div class="page-header">
            <h2><i class="bi bi-bar-chart-fill me-2"></i>Reportes y Estadísticas</h2>
            <p>Resumen general de la comunidad</p>
        </div>

        <!-- Estadísticas Generales -->
        <h4 class="section-title">Resumen General</h4>
        <div class="stats-grid">
            <div class="stat-card" style="border-left: 4px solid #2563eb;">
                <h4><?= $stats['total_lotes'] ?></h4>
                <small>Total de Lotes</small>
            </div>
            <div class="stat-card" style="border-left: 4px solid #10b981;">
                <h4><?= number_format($stats['area_total'], 0) ?></h4>
                <small>m² Área Total</small>
            </div>
            <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                <h4><?= $stats['total_comuneros'] ?></h4>
                <small>Comuneros Activos</small>
            </div>
            <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                <h4><?= $stats['total_actas'] ?></h4>
                <small>Actas Registradas</small>
            </div>
        </div>

        <!-- Estado de Lotes -->
        <h4 class="section-title">Estado de Lotes</h4>
        <div class="stats-grid">
            <div class="stat-card">
                <h4><?= $stats['lotes_ocupados'] ?></h4>
                <small>Lotes Ocupados</small>
            </div>
            <div class="stat-card">
                <h4><?= $stats['lotes_libres'] ?></h4>
                <small>Lotes Libres</small>
            </div>
            <div class="stat-card">
                <h4><?= $stats['lotes_excedentes'] ?></h4>
                <small>Lotes Excedentes</small>
            </div>
            <div class="stat-card">
                <h4><?= $stats['comuneros_con_lote'] ?></h4>
                <small>Comuneros con Lote</small>
            </div>
        </div>

        <!-- Gráficos -->
        <h4 class="section-title">Gráficos</h4>
        <div class="charts-grid">
            <div class="chart-card">
                <h5>Estado de Lotes</h5>
                <canvas id="lotesChart"></canvas>
            </div>
            <div class="chart-card">
                <h5>Solicitudes de Adjudicación</h5>
                <canvas id="solicitudesChart"></canvas>
            </div>
        </div>

        <!-- Solicitudes Stats -->
        <h4 class="section-title">Solicitudes de Adjudicación</h4>
        <div class="stats-grid">
            <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                <h4><?= $stats['sol_pendientes'] ?></h4>
                <small>Pendientes</small>
            </div>
            <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                <h4><?= $stats['sol_en_revision'] ?></h4>
                <small>En Revisión</small>
            </div>
            <div class="stat-card" style="border-left: 4px solid #10b981;">
                <h4><?= $stats['sol_aprobadas'] ?></h4>
                <small>Aprobadas</small>
            </div>
            <div class="stat-card" style="border-left: 4px solid #ef4444;">
                <h4><?= $stats['sol_rechazadas'] ?></h4>
                <small>Rechazadas</small>
            </div>
        </div>

        <div class="stat-card mt-4">
            <small>Últimos 30 días: <?= $stats['ultimas_solicitudes'] ?> nuevas solicitudes de adjudiciación</small>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de Lotes
        new Chart(document.getElementById('lotesChart'), {
            type: 'doughnut',
            data: {
                labels: ['Ocupados', 'Libres', 'Excedentes'],
                datasets: [{
                    data: [<?= $stats['lotes_ocupados'] ?>, <?= $stats['lotes_libres'] ?>, <?= $stats['lotes_excedentes'] ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#94a3b8' }
                    }
                }
            }
        });

        // Gráfico de Solicitudes
        new Chart(document.getElementById('solicitudesChart'), {
            type: 'bar',
            data: {
                labels: ['Pendientes', 'En Revisión', 'Aprobadas', 'Rechazadas'],
                datasets: [{
                    label: 'Solicitudes',
                    data: [<?= $stats['sol_pendientes'] ?>, <?= $stats['sol_en_revision'] ?>, <?= $stats['sol_aprobadas'] ?>, <?= $stats['sol_rechazadas'] ?>],
                    backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    },
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { display: false }
                    }
                }
            }
        });
    </script>
</body>
</html>