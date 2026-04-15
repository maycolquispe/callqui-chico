<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../../includes/verificar_sesion.php";
$conn = getDB();

$success = "";
$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    header("Location: ../login.php");
    exit;
}

$nombres = $_SESSION['nombres'] ?? 'Usuario';

/* OBTENER ACTAS DISPONIBLES */
$actas = $conn->query("SELECT id, titulo, fecha FROM actas WHERE asistencia_habilitada = 1 ORDER BY fecha DESC");

/* RESUMEN DE ASISTENCIAS */
$resumenSQL = "SELECT 
    SUM(CASE WHEN estado = 'asistio' THEN 1 ELSE 0 END) as asistio,
    SUM(CASE WHEN estado = 'falto' THEN 1 ELSE 0 END) as falto,
    SUM(CASE WHEN estado = 'justificado' THEN 1 ELSE 0 END) as justificado
    FROM asistencias WHERE usuario_id = ?";

$stmt = $conn->prepare($resumenSQL);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_assoc();

$asistio = $resumen['asistio'] ?? 0;
$faltas = $resumen['falto'] ?? 0;
$just = $resumen['justificado'] ?? 0;

/* HISTORIAL COMPLETO */
$histSQL = "SELECT a.titulo, a.fecha, asistencias.estado 
            FROM asistencias 
            INNER JOIN actas a ON a.id = asistencias.acta_id 
            WHERE asistencias.usuario_id = ? 
            ORDER BY a.fecha DESC";

$stmt = $conn->prepare($histSQL);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$historial = $stmt->get_result();

$usuario_id = $_SESSION['usuario_id'] ?? 0;

$stmtUser = $conn->prepare("SELECT foto, nombres FROM usuarios WHERE id=?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();

$fotoPerfil = !empty($userData['foto']) ? $userData['foto'] : 'default.png';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Asistencia | Comunidad Callqui Chico</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-glow: rgba(16, 185, 129, 0.4);
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #0a0f1a;
            --dark-soft: #0f1724;
            --dark-card: #1a2332;
            --text-primary: #f1f5f9;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--dark);
            color: var(--text-primary);
            min-height: 100vh;
            background-image: 
                radial-gradient(ellipse at 20% 20%, rgba(16, 185, 129, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(59, 130, 246, 0.06) 0%, transparent 50%),
                linear-gradient(180deg, #0a0f1a 0%, #0d1420 100%);
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--dark-soft) 0%, var(--dark) 100%);
            border-right: 1px solid var(--border);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .brand-logo {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px var(--primary-glow);
        }

        .brand-logo i { font-size: 1.6rem; color: white; }

        .brand-text h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            letter-spacing: -0.3px;
        }

        .brand-text span {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            flex: 1;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            padding: 0.9rem 1rem;
            border-radius: 12px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .nav-link i { font-size: 1.2rem; }

        .nav-link:hover {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 16px var(--primary-glow);
        }

        .user-mini {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem;
            background: var(--dark-card);
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-avatar i { font-size: 1.1rem; }

        .user-details h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
        }

        .user-details span {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            width: calc(100% - 280px);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            letter-spacing: -0.5px;
        }

        .page-title p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-top: 0.3rem;
        }

        .datetime-badge {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.7rem 1.2rem;
            background: var(--dark-card);
            border-radius: 50px;
            border: 1px solid var(--border);
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .datetime-badge i { color: var(--primary); }

        /* Alert Messages */
        .alert-box {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-box i { font-size: 1.3rem; }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--primary);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: var(--warning);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        /* Stats Cards */
        .stat-card {
            background: var(--dark-card);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .stat-card:hover::before { opacity: 1; }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.green {
            background: rgba(16, 185, 129, 0.15);
            color: var(--primary);
        }

        .stat-icon.red {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }

        .stat-icon.yellow {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
        }

        .stat-icon.blue {
            background: rgba(59, 130, 246, 0.15);
            color: var(--info);
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: white;
            line-height: 1;
        }

        .stat-trend {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .stat-trend.positive { color: var(--primary); }
        .stat-trend.negative { color: var(--danger); }

        /* Check-in Card */
        .checkin-card {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            border-radius: 20px;
            padding: 1.75rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .checkin-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .checkin-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .checkin-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            position: relative;
            z-index: 1;
        }

        .form-select-dark {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.9rem 1rem;
            border-radius: 12px;
            width: 100%;
            margin-bottom: 1rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .form-select-dark option {
            background: var(--dark-card);
            color: white;
        }

        .btn-checkin {
            background: white;
            color: var(--primary-dark);
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .btn-checkin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .btn-checkin:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 1.25rem;
        }

        .content-card {
            background: var(--dark-card);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .card-header i {
            font-size: 1.4rem;
            color: var(--primary);
        }

        .card-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: white;
            margin: 0;
        }

        /* Table */
        .table-container {
            max-height: 320px;
            overflow-y: auto;
        }

        .table-container::-webkit-scrollbar {
            width: 6px;
        }

        .table-container::-webkit-scrollbar-track {
            background: var(--dark);
            border-radius: 3px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 3px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            text-align: left;
            padding: 0.8rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            position: sticky;
            top: 0;
            background: var(--dark-card);
        }

        .history-table td {
            padding: 0.9rem 0.8rem;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--border);
        }

        .history-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge-estado {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-asistio {
            background: rgba(16, 185, 129, 0.15);
            color: var(--primary);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .badge-falto {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .badge-justificado {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.25);
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 0.8rem;
            opacity: 0.5;
        }

        .chart-container {
            height: 260px;
            position: relative;
        }

        /* Responsive */
        @media (max-width: 1400px) {
            .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 1rem; }
            .brand-text, .nav-link span, .user-details { display: none; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
            .nav-link { justify-content: center; padding: 0.9rem; }
            .brand { justify-content: center; }
            .content-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .top-bar { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .main-content { padding: 1.25rem; }
        }
    </style>
</head>
<body>

<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
    <div class="">
        <img src="../../assets/img/logo_callqui.png" alt="Logo Callqui Chico" style="width:60px; height:60px; object-fit:contain;">
    </div>
    <div class="brand-text">
        <h3>Callqui Chico</h3>
        <span>Comunidad Campesina</span>
    </div>
</div>

        <nav class="nav-menu">
            <a href="#" class="nav-link active">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-link">
                <i class="bi bi-calendar-check"></i>
                <span>Mi Asistencia</span>
            </a>
            <a href="comunero.php" class="nav-link">
                <i class="bi bi-box-arrow-left"></i>
                <span>VOLVER</span>
            </a>
        </nav>

        <div class="user-mini">
            <a href="../perfil_ajax.php" class="nav-btn" style="display:flex;align-items:center;gap:8px;">
    <img src="../../perfil/uploads/<?php echo $fotoPerfil; ?>" 
         style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
    
</a>
            <div class="user-details">
                <h4><?php echo htmlspecialchars($nombres); ?></h4>
                <span>Comunero</span>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1><i class="bi bi-grid-1x2 me-2" style="color: var(--primary);"></i>Panel de Asistencia</h1>
                <p>Gestiona tu participación en las reuniones comunales</p>
            </div>
            <div class="datetime-badge">
                <i class="bi bi-calendar3"></i>
                <span id="currentDate"></span>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Check-in Card -->
            <div class="checkin-card">
                <div class="checkin-icon">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div class="checkin-title">Marcar Asistencia</div>
                <p style="position:relative;z-index:1;font-size:0.85rem;opacity:0.9;margin-bottom:1rem;">Debes estar dentro del área y horario permitidos</p>
                <form id="formAsistencia">
                    <select id="acta_id" class="form-select-dark" required>
                        <option value="" disabled selected>Seleccionar reunión</option>
                        <?php while ($acta = $actas->fetch_assoc()): ?>
                            <option value="<?php echo $acta['id']; ?>">
                                <?php echo htmlspecialchars($acta['titulo'] . ' - ' . date('d/m/Y', strtotime($acta['fecha']))); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <input type="hidden" id="lat">
                    <input type="hidden" id="lng">
                    <button type="button" id="btnMarcar" class="btn-checkin" onclick="marcarAsistencia()">
                        <i class="bi bi-geo-alt"></i>
                        OBTENER GPS Y REGISTRAR
                    </button>
                </form>
                <div id="mensajeAsistencia" style="margin-top:1rem;position:relative;z-index:1;"></div>
            </div>

            <!-- Stat: Asistencias -->
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-label">Asistencias</div>
                <div class="stat-value"><?php echo $asistio; ?></div>
                <div class="stat-trend positive">
                    <i class="bi bi-arrow-up"></i> Total registrado
                </div>
            </div>

            <!-- Stat: Faltas -->
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-label">Faltas</div>
                <div class="stat-value"><?php echo $faltas; ?></div>
                <div class="stat-trend negative">
                    <i class="bi bi-arrow-down"></i> Sin justificar
                </div>
            </div>

            <!-- Stat: Justificados -->
            <div class="stat-card">
                <div class="stat-icon yellow">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stat-label">Justificados</div>
                <div class="stat-value"><?php echo $just; ?></div>
                <div class="stat-trend positive" style="color: var(--warning);">
                    <i class="bi bi-check"></i> Con justificación
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Chart -->
            <div class="content-card">
                <div class="card-header">
                    <i class="bi bi-pie-chart-fill"></i>
                    <h3>Resumen Estadístico</h3>
                </div>
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
                <div style="display: flex; justify-content: center; gap: 1.5rem; margin-top: 1rem; font-size: 0.85rem;">
                    <span style="color: var(--primary);">● Asistencias</span>
                    <span style="color: var(--danger);">● Faltas</span>
                    <span style="color: var(--warning);">● Justificados</span>
                </div>
            </div>

            <!-- Historial -->
            <div class="content-card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i>
                    <h3>Historial de Asistencia</h3>
                </div>
                <div class="table-container">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Reunión</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($historial->num_rows > 0): ?>
                                <?php while ($row = $historial->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars(substr($row['titulo'], 0, 28)) . (strlen($row['titulo']) > 28 ? '...' : ''); ?></td>
                                    <td>
                                        <span class="badge-estado badge-<?php echo $row['estado']; ?>">
                                            <i class="bi bi-<?php 
                                                echo $row['estado'] == 'asistio' ? 'check-circle' : 
                                                    ($row['estado'] == 'falto' ? 'x-circle' : 'clock-history'); 
                                            ?>"></i>
                                            <?php echo strtoupper($row['estado']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <p>No hay registros de asistencias</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Current Date
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('currentDate').textContent = new Date().toLocaleDateString('es-ES', options);

    function marcarAsistencia() {
        const acta_id = document.getElementById('acta_id').value;
        const mensajeDiv = document.getElementById('mensajeAsistencia');
        const btn = document.getElementById('btnMarcar');

        if (!acta_id) {
            mensajeDiv.innerHTML = '<div class="alert-box alert-warning"><i class="bi bi-exclamation-triangle-fill"></i><span>Selecciona una reunión</span></div>';
            return;
        }

        if (!navigator.geolocation) {
            mensajeDiv.innerHTML = '<div class="alert-box alert-error"><i class="bi bi-x-circle-fill"></i><span>GPS no soportado en tu navegador</span></div>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="bi biHourglassSplit"></i> Obteniendo ubicación...';
        mensajeDiv.innerHTML = '<div class="alert-box" style="background:rgba(59,130,246,0.15);border-color:rgba(59,130,246,0.3);color:var(--info);"><i class="bi bi-geo-alt-fill"></i><span>Obteniendo coordenadas GPS...</span></div>';

        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('lat').value = position.coords.latitude;
                document.getElementById('lng').value = position.coords.longitude;

                mensajeDiv.innerHTML = '<div class="alert-box" style="background:rgba(16,185,129,0.15);border-color:rgba(16,185,129,0.3);color:var(--primary);"><i class="bi bi-check-circle-fill"></i><span>Ubicación obtenida. Verificando...</span></div>';

                const formData = new FormData();
                formData.append('acta_id', acta_id);
                formData.append('lat', position.coords.latitude);
                formData.append('lng', position.coords.longitude);

                fetch('procesar_asistencia.php', {
    method: 'POST',
    body: formData
})
.then(res => res.text()) // 👈 primero texto
.then(text => {
    try {
        const data = JSON.parse(text); // 👈 luego convertir
        return data;
    } catch (e) {
        console.error("Respuesta inválida:", text);
        throw new Error("El servidor no devolvió JSON válido");
    }
})
.then(data => {
    console.log(data);
})
.catch(err => {
    console.error(err);
});
            },
            function(error) {
                let msg = 'No se pudo obtener ubicación';
                if (error.code === 1) msg = 'Permiso de ubicación denegado';
                if (error.code === 2) msg = 'Ubicación no disponible';
                if (error.code === 3) msg = 'Tiempo de espera agotado';

                mensajeDiv.innerHTML = '<div class="alert-box alert-error"><i class="bi bi-geo-alt"></i><span>' + msg + '</span></div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-geo-alt"></i> OBTENER GPS Y REGISTRAR';
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    // Chart.js
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Asistencias', 'Faltas', 'Justificados'],
            datasets: [{
                data: [<?php echo $asistio; ?>, <?php echo $faltas; ?>, <?php echo $just; ?>],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                borderColor: '#1a2332',
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let pct = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${label}: ${value} (${pct}%)`;
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });

    
</script>

</body>
</html>