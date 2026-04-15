<?php
/**
 * Dashboard de Estadísticas - Callqui Chico
 * Profesional v2.0
 */

require_once '../../includes/verificar_sesion.php';
require_once '../../includes/funciones.php';

$conn = getDB();

// Estadísticas generales
$stats = [];

// Total comuneros
$stats['comuneros'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'")->fetch_assoc()['total'];

// Total lotes
$stats['lotes'] = $conn->query("SELECT COUNT(*) as total FROM lotes")->fetch_assoc()['total'];

// Lotes disponibles
$stats['lotes_disponibles'] = $conn->query("SELECT COUNT(*) as total FROM lotes WHERE estado = 'DISPONIBLE'")->fetch_assoc()['total'];

// Lotes ocupados
$stats['lotes_ocupados'] = $conn->query("SELECT COUNT(*) as total FROM lotes WHERE estado = 'OCUPADO'")->fetch_assoc()['total'];

// Adjudicaciones pendientes
$stats['adj_pendientes'] = $conn->query("SELECT COUNT(*) as total FROM adjudicaciones WHERE estado = 'pendiente'")->fetch_assoc()['total'];

// Adjudicaciones aprobadas
$stats['adj_aprobadas'] = $conn->query("SELECT COUNT(*) as total FROM adjudicaciones WHERE estado = 'aprobado'")->fetch_assoc()['total'];

// Actas este año
$stats['actas'] = $conn->query("SELECT COUNT(*) as total FROM actas WHERE YEAR(fecha) = YEAR(NOW())")->fetch_assoc()['total'];

// Total asistencias este año
$stats['asistencias'] = $conn->query("SELECT COUNT(*) as total FROM asistencias WHERE YEAR(fecha_registro) = YEAR(NOW())")->fetch_assoc()['total'];

// Últimas adjudicaciones
$ultimasAdj = $conn->query("
    SELECT a.*, u.nombres, u.apellidos 
    FROM adjudicaciones a
    LEFT JOIN usuarios u ON a.dni = u.dni
    ORDER BY a.fecha_solicitud DESC 
    LIMIT 5
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
    
    <style>
        body {
            background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stat-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .stat-icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stat-icon.yellow { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .stat-icon.red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .stat-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        
        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #0a2b3c;
            margin: 0;
            line-height: 1;
        }
        .stat-content p {
            color: #64748b;
            margin: 0.25rem 0 0;
            font-size: 0.9rem;
        }
        .table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .table-card .card-header {
            background: #0a2b3c;
            color: white;
            padding: 1rem 1.5rem;
            border: none;
        }
        .table-card .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-speedometer2 me-2 text-accent"></i>Dashboard</h2>
                    <p class="text-muted mb-0">Resumen general del sistema</p>
                </div>
                <div>
                    <span class="text-muted">
                        <i class="bi bi-calendar me-1"></i>
                        <?php echo date('d/m/Y'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['comuneros']); ?></h3>
                    <p>Comuneros Activos</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="bi bi-house-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['lotes']); ?></h3>
                    <p>Total Lotes</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon yellow">
                    <i class="bi bi-building"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['lotes_disponibles']); ?></h3>
                    <p>Lotes Disponibles</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['adj_pendientes']); ?></h3>
                    <p>Adjudicaciones Pendientes</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['adj_aprobadas']); ?></h3>
                    <p>Adjudicaciones Aprobadas</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['actas']); ?></h3>
                    <p>Actas (<?php echo date('Y'); ?>)</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-clock-history me-2"></i>Últimas Adjudicaciones</h5>
                        <a href="adjudicaciones_secretario.php" class="btn btn-sm btn-accent">Ver todas</a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Lote</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimasAdj as $adj): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($adj['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($adj['lote']); ?></td>
                                    <td>
                                        <span class="badge-estado badge-<?php echo $adj['estado']; ?>">
                                            <?php echo $adj['estado']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo format_fecha($adj['fecha_solicitud']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($ultimasAdj)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No hay solicitudes</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-pie-chart me-2"></i>Distribución de Lotes</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-around align-items-center text-center">
                            <div>
                                <div class="display-4 text-success"><?php echo $stats['lotes_ocupados']; ?></div>
                                <p class="text-muted mb-0">Ocupados</p>
                            </div>
                            <div class="vr" style="height: 60px;"></div>
                            <div>
                                <div class="display-4 text-warning"><?php echo $stats['lotes_disponibles']; ?></div>
                                <p class="text-muted mb-0">Disponibles</p>
                            </div>
                            <div class="vr" style="height: 60px;"></div>
                            <div>
                                <div class="display-4 text-primary"><?php echo $stats['lotes']; ?></div>
                                <p class="text-muted mb-0">Total</p>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <?php if ($stats['lotes'] > 0): ?>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Ocupación</small>
                                <small class="text-muted">
                                    <?php echo round(($stats['lotes_ocupados'] / $stats['lotes']) * 100); ?>%
                                </small>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: <?php echo ($stats['lotes_ocupados'] / $stats['lotes']) * 100; ?>%"></div>
                                <div class="progress-bar bg-warning" style="width: <?php echo ($stats['lotes_disponibles'] / $stats['lotes']) * 100; ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>
