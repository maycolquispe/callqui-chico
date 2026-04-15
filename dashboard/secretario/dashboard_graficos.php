<?php
/**
 * Dashboard con Gráficos - Callqui Chico
 * Profesional v2.0
 */

require_once '../../includes/verificar_sesion.php';
require_once '../../includes/funciones.php';

$conn = getDB();

// Stats principales
$stats = [
    'comuneros' => $conn->query("SELECT COUNT(*) as t FROM usuarios WHERE estado = 'activo'")->fetch_assoc()['t'],
    'lotes' => $conn->query("SELECT COUNT(*) as t FROM lotes")->fetch_assoc()['t'],
    'lotes_disp' => $conn->query("SELECT COUNT(*) as t FROM lotes WHERE estado = 'DISPONIBLE'")->fetch_assoc()['t'],
    'adj_pendientes' => $conn->query("SELECT COUNT(*) as t FROM adjudicaciones WHERE estado = 'pendiente'")->fetch_assoc()['t'],
    'adj_aprobadas' => $conn->query("SELECT COUNT(*) as t FROM adjudicaciones WHERE estado = 'aprobado'")->fetch_assoc()['t'],
    'adj_rechazadas' => $conn->query("SELECT COUNT(*) as t FROM adjudicaciones WHERE estado = 'rechazado'")->fetch_assoc()['t'],
];

// Datos para gráfico de lotes por sector
$lotesSector = $conn->query("
    SELECT sector, COUNT(*) as total, 
    SUM(CASE WHEN estado = 'DISPONIBLE' THEN 1 ELSE 0 END) as disponibles
    FROM lotes GROUP BY sector
")->fetch_all(MYSQLI_ASSOC);

// Datos para gráfico de adjudicaciones por mes
$adjMes = $conn->query("
    SELECT DATE_FORMAT(fecha_solicitud, '%Y-%m') as mes, COUNT(*) as total
    FROM adjudicaciones
    WHERE fecha_solicitud >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY mes ORDER BY mes
")->fetch_all(MYSQLI_ASSOC);

// Datos para gráfico de actas por tipo
$actasTipo = $conn->query("
    SELECT tipo, COUNT(*) as total FROM actas 
    WHERE YEAR(fecha) = YEAR(NOW()) GROUP BY tipo
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = "Dashboard - Estadísticas";
$mostrarNavbar = true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Callqui Chico</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../css/main.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body { background: #f8fafc; }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0a2b3c;
            line-height: 1;
        }
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1"><i class="bi bi-graph-up me-2 text-accent"></i>Dashboard</h4>
                <p class="text-muted mb-0">Estadísticas en tiempo real</p>
            </div>
            <div>
                <span class="text-muted"><i class="bi bi-clock me-1"></i>Última actualización: <?php echo date('H:i'); ?></span>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['comuneros']); ?></div>
                    <div class="stat-label"><i class="bi bi-people me-1"></i>Comuneros</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['lotes']); ?></div>
                    <div class="stat-label"><i class="bi bi-house me-1"></i>Total Lotes</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number text-success"><?php echo number_format($stats['lotes_disp']); ?></div>
                    <div class="stat-label"><i class="bi bi-building me-1"></i>Disponibles</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-number text-warning"><?php echo number_format($stats['adj_pendientes']); ?></div>
                    <div class="stat-label"><i class="bi bi-clock me-1"></i>Pendientes</div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row 1 -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-pie-chart me-2"></i>Adjudicaciones por Estado</h6>
                    <canvas id="chartAdjudicaciones" height="250"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-bar-chart me-2"></i>Adjudicaciones por Mes</h6>
                    <canvas id="chartAdjMes" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Charts Row 2 -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-bar-chart-steps me-2"></i>Lotes por Sector</h6>
                    <canvas id="chartLotesSector" height="250"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-pie-chart me-2"></i>Actas por Tipo (<?php echo date('Y'); ?>)</h6>
                    <canvas id="chartActas" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
        // Chart defaults
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#64748b';
        
        // Colores
        const colors = {
            primary: '#0a2b3c',
            accent: '#c9a45c',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            info: '#3b82f6'
        };
        
        // Chart: Adjudicaciones por Estado
        new Chart(document.getElementById('chartAdjudicaciones'), {
            type: 'doughnut',
            data: {
                labels: ['Pendientes', 'Aprobadas', 'Rechazadas'],
                datasets: [{
                    data: [
                        <?php echo $stats['adj_pendientes']; ?>,
                        <?php echo $stats['adj_aprobadas']; ?>,
                        <?php echo $stats['adj_rechazadas']; ?>
                    ],
                    backgroundColor: [colors.warning, colors.success, colors.danger],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // Chart: Adjudicaciones por Mes
        new Chart(document.getElementById('chartAdjMes'), {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(fn($r) => "'" . $r['mes'] . "'", $adjMes)); ?>],
                datasets: [{
                    label: 'Adjudicaciones',
                    data: [<?php echo implode(',', array_column($adjMes, 'total')); ?>],
                    backgroundColor: colors.primary,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
        
        // Chart: Lotes por Sector
        new Chart(document.getElementById('chartLotesSector'), {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(fn($r) => "'" . ($r['sector'] ?? 'Sin sector') . "'", $lotesSector)); ?>],
                datasets: [
                    {
                        label: 'Total',
                        data: [<?php echo implode(',', array_column($lotesSector, 'total')); ?>],
                        backgroundColor: colors.primary,
                        borderRadius: 6
                    },
                    {
                        label: 'Disponibles',
                        data: [<?php echo implode(',', array_column($lotesSector, 'disponibles')); ?>],
                        backgroundColor: colors.success,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
        
        // Chart: Actas por Tipo
        new Chart(document.getElementById('chartActas'), {
            type: 'pie',
            data: {
                labels: [<?php echo implode(',', array_map(fn($r) => "'" . ucfirst($r['tipo']) . "'", $actasTipo)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($actasTipo, 'total')); ?>],
                    backgroundColor: [colors.primary, colors.accent, colors.info],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>
