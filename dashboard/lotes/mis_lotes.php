<?php 
session_start(); 
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: ../login.php"); 
    exit; 
} 
require_once __DIR__ . '/../config/conexion.php'; 

$usuario_id = $_SESSION['usuario_id']; 
$sql = "SELECT sector, manzana, lote, area_m2, area_excedente_m2, estado FROM lotes WHERE usuario_id = ?"; 
$stmt = $conn->prepare($sql); 
$stmt->bind_param("i", $usuario_id); 
$stmt->execute(); 
$result = $stmt->get_result(); 
$total_lotes = $result->num_rows; 

// Calcular área total
$total_area = 0;
$total_excedente = 0;
$result->data_seek(0);
while($row = $result->fetch_assoc()) {
    $total_area += $row['area_m2'];
    $total_excedente += $row['area_excedente_m2'];
}
$result->data_seek(0);
?> 

<!DOCTYPE html> 
<html lang="es"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Lotes | Comunidad Callqui Chico</title> 
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet"> 
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style> 
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --primary-light: #60a5fa;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark-bg: #0a1928;
            --dark-card: #0f2740;
            --text-light: #f0f5fa;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
            min-height: 100vh;
            color: var(--text-light);
            position: relative;
        }

        /* Efecto de fondo */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 30%, rgba(37,99,235,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 70%, rgba(16,185,129,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Barra de navegación */
        .nav-bar {
            background: rgba(10, 25, 40, 0.95);
            backdrop-filter: blur(12px);
            padding: 1rem 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(37,99,235,0.3);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 800;
            box-shadow: 0 4px 15px rgba(37,99,235,0.3);
        }

        .logo-text h3 {
            color: white;
            font-weight: 700;
            font-size: 1.3rem;
            margin: 0;
            line-height: 1.2;
        }

        .logo-text small {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .nav-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-nav {
            background: rgba(255,255,255,0.05);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .btn-nav:hover {
            background: #2563eb;
            color: white;
            transform: translateY(-2px);
        }

        /* Contenedor principal */
        .main-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        /* Panel principal */
        .panel {
            background: rgba(15, 39, 64, 0.7);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }

        /* Header del panel */
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .header-title h2 {
            color: white;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-title p {
            color: #94a3b8;
            margin: 0;
            font-size: 0.95rem;
        }

        .btn-back {
            background: rgba(255,255,255,0.05);
            color: white;
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .btn-back:hover {
            background: #2563eb;
            color: white;
            transform: translateX(-5px);
        }

        /* Cards de estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(37,99,235,0.2) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #2563eb;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.2rem;
            line-height: 1;
        }

        .stat-detail {
            color: #60a5fa;
            font-size: 0.9rem;
        }

        /* Contenedor de tabla */
        .table-container {
            background: rgba(255,255,255,0.03);
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,0.1);
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
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .date-badge {
            background: rgba(37,99,235,0.2);
            color: #60a5fa;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            border: 1px solid rgba(37,99,235,0.3);
        }

        /* Tabla moderna */
        .table-responsive {
            border-radius: 16px;
            overflow: hidden;
        }

        .table {
            color: white;
            margin: 0;
        }

        .table thead th {
            background: rgba(0,0,0,0.3);
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 1.2rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .table tbody td {
            padding: 1.2rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: rgba(255,255,255,0.03);
        }

        /* Estilos para elementos de la tabla */
        .sector-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            background: rgba(37,99,235,0.15);
            border: 1px solid rgba(37,99,235,0.3);
            border-radius: 50px;
            font-size: 0.9rem;
            color: #60a5fa;
        }

        .lote-highlight {
            font-weight: 600;
            color: #fbbf24;
            background: rgba(251,191,36,0.15);
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            display: inline-block;
            border: 1px solid rgba(251,191,36,0.3);
        }

        .manzana-badge {
            background: rgba(255,255,255,0.1);
            color: #94a3b8;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
        }

        /* Badges de estado */
        .estado-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .estado-ocupado {
            background: rgba(16,185,129,0.15);
            color: #10b981;
            border: 1px solid rgba(16,185,129,0.3);
        }

        .estado-disponible {
            background: rgba(245,158,11,0.15);
            color: #f59e0b;
            border: 1px solid rgba(245,158,11,0.3);
        }

        .estado-proceso {
            background: rgba(59,130,246,0.15);
            color: #3b82f6;
            border: 1px solid rgba(59,130,246,0.3);
        }

        /* Valores numéricos */
        .area-value {
            font-weight: 600;
            color: white;
        }

        .excedente-value {
            color: #fbbf24;
            font-weight: 500;
        }

        /* Mensaje sin datos */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-icon {
            font-size: 5rem;
            color: #2d4a6e;
            margin-bottom: 1.5rem;
        }

        .empty-title {
            color: white;
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: #64748b;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .panel-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .header-title h2 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid rgba(255,255,255,0.1);
                border-radius: 12px;
                padding: 1rem;
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem;
                border: none;
                border-bottom: 1px solid rgba(255,255,255,0.05);
            }

            .table tbody td:last-child {
                border-bottom: none;
            }

            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #94a3b8;
            }
        }
    </style> 
</head> 
<body> 

    <!-- Barra de navegación -->
    <div class="nav-bar">
        <div class="nav-container">
            <div class="logo-area">
                <div class="logo">
                    <i class="bi bi-tree-fill"></i>
                </div>
                <div class="logo-text">
                    <h3>Comunidad Callqui Chico</h3>
                    <small>Sistema de Gestión Comunal</small>
                </div>
            </div>
            <div class="nav-actions">
                <a href="../perfil_ajax.php" class="btn-nav">
                    <i class="bi bi-person-circle"></i>
                    <span class="d-none d-md-inline">Mi Perfil</span>
                </a>
                <a href="../logout.php" class="btn-nav">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-md-inline">Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Contenedor principal -->
    <div class="main-container"> 
        
        <!-- Panel principal -->
        <div class="panel">

            <!-- Header del panel -->
            <div class="panel-header">
                <div class="header-title">
                    <h2>
                        <i class="bi bi-grid-1x2-fill"></i>
                        Mis Lotes y Propiedades
                    </h2>
                    <p>Gestión de terrenos y propiedades en la comunidad</p>
                </div>
                <a href="../dashboard/comunero.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i>
                    <span>Volver al Dashboard</span>
                </a>
            </div>

            <!-- Resumen de estadísticas -->
            <div class="stats-grid mb-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-house-door"></i>
                    </div>
                    <div class="stat-label">Total Lotes</div>
                    <div class="stat-number"><?= $total_lotes ?></div>
                    <div class="stat-detail">Propiedades registradas</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-rulers"></i>
                    </div>
                    <div class="stat-label">Área Total</div>
                    <div class="stat-number"><?= number_format($total_area, 2) ?> m²</div>
                    <div class="stat-detail">Superficie total</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <div class="stat-label">Área Excedente</div>
                    <div class="stat-number"><?= number_format($total_excedente, 2) ?> m²</div>
                    <div class="stat-detail">Terreno adicional</div>
                </div>
            </div>

            <!-- Tabla de propiedades -->
            <div class="table-container">
                <div class="table-header">
                    <h5>
                        <i class="bi bi-table"></i>
                        Detalle de Propiedades
                    </h5>
                    <span class="date-badge">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?= date('d M, Y') ?>
                    </span>
                </div>

                <?php if ($total_lotes > 0): ?> 
                    <div class="table-responsive"> 
                        <table class="table"> 
                            <thead> 
                                <tr> 
                                    <th class="text-center">#</th> 
                                    <th>Sector</th> 
                                    <th>Manzana</th> 
                                    <th>Lote</th> 
                                    <th>Área Total</th> 
                                    <th>Excedente</th> 
                                    <th class="text-center">Estado</th> 
                                </tr> 
                            </thead> 
                            <tbody> 
                                <?php $i=1; while($row = $result->fetch_assoc()): ?> 
                                    <tr> 
                                        <td class="text-center fw-bold text-muted" data-label="#">
                                            <?= $i++ ?>
                                        </td> 
                                        <td data-label="Sector">
                                            <span class="sector-badge">
                                                <?= htmlspecialchars($row['sector']) ?>
                                            </span>
                                        </td> 
                                        <td data-label="Manzana">
                                            <span class="manzana-badge">
                                                <i class="bi bi-grid-3x3 me-1"></i>
                                                <?= htmlspecialchars($row['manzana']) ?>
                                            </span>
                                        </td> 
                                        <td data-label="Lote"> 
                                            <span class="lote-highlight">
                                                <i class="bi bi-pin-map-fill me-1"></i>
                                                <?= htmlspecialchars($row['lote']) ?>
                                            </span> 
                                        </td> 
                                        <td data-label="Área Total" class="area-value">
                                            <?= $row['area_m2'] ? number_format($row['area_m2'], 2).' m²' : '-' ?>
                                        </td> 
                                        <td data-label="Excedente" class="excedente-value">
                                            <?= $row['area_excedente_m2'] ? '+ '.number_format($row['area_excedente_m2'], 2).' m²' : '-' ?>
                                        </td> 
                                        <td class="text-center" data-label="Estado"> 
                                            <?php 
                                            $estado = strtolower($row['estado'] ?? '');
                                            $estado_class = 'estado-disponible';
                                            $estado_icon = 'bi-question-circle';
                                            
                                            if ($estado === 'ocupado') {
                                                $estado_class = 'estado-ocupado';
                                                $estado_icon = 'bi-check-circle-fill';
                                            } elseif ($estado === 'proceso' || $estado === 'en proceso') {
                                                $estado_class = 'estado-proceso';
                                                $estado_icon = 'bi-arrow-repeat';
                                            } elseif ($estado === 'disponible') {
                                                $estado_class = 'estado-disponible';
                                                $estado_icon = 'bi-circle';
                                            }
                                            ?> 
                                            <span class="estado-badge <?= $estado_class ?>">
                                                <i class="bi <?= $estado_icon ?>"></i>
                                                <?= ucfirst(htmlspecialchars($row['estado'])) ?>
                                            </span> 
                                        </td> 
                                    </tr> 
                                <?php endwhile; ?> 
                            </tbody> 
                        </table> 
                    </div> 
                <?php else: ?> 
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-house-x"></i>
                        </div>
                        <h5 class="empty-title">No hay lotes registrados</h5>
                        <p class="empty-text">Actualmente no tienes lotes asignados en el sistema.</p>
                        <a href="../adjudicaciones/adjudicaciones.php" class="btn btn-primary mt-3">
                            <i class="bi bi-plus-circle me-2"></i>
                            Solicitar Adjudicación
                        </a>
                    </div>
                <?php endif; ?> 
            </div>

        </div> <!-- Fin panel -->
    </div> <!-- Fin main-container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body> 
</html>