<?php
session_start();
require_once("../../config/conexion.php");

/* ===============================
   CONTEXTO DE USUARIO
   ================================ */
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if (!$usuario_id) {
    header("Location: ../login.php");
    exit;
}

/* ===============================
   GUARDAR SOLICITUD
   ================================ */
$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo   = $_POST['tipo_permiso'];
    $inicio = $_POST['fecha_inicio'];
    $fin    = $_POST['fecha_fin'];
    $motivo = $_POST['motivo'];

    $archivo = null;

    if (!empty($_FILES['archivo']['name'])) {
        if (!is_dir("uploads")) {
            mkdir("uploads", 0777, true);
        }
        $archivo = time() . "_" . basename($_FILES['archivo']['name']);
        move_uploaded_file($_FILES['archivo']['tmp_name'], "uploads/$archivo");
    }

    // Generar código único de seguimiento: PERM-YYYY-XXXXXX
    $año = date('Y');
    $codigo = 'PERM-' . $año . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

    $sql = "INSERT INTO permisos 
(usuario_id, tipo_permiso, fecha_inicio, fecha_fin, motivo, archivo, estado, codigo_unico)
VALUES (?, ?, ?, ?, ?, ?, 'Pendiente', ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $usuario_id, $tipo, $inicio, $fin, $motivo, $archivo, $codigo);

    if ($stmt->execute()) {
        $mensaje = ["ok", "Solicitud registrada correctamente. Código de seguimiento: <strong>$codigo</strong>"];
    } else {
        $mensaje = ["error", "Error al registrar la solicitud"];
    }
}

/* ===============================
   RESUMEN DE ESTADOS
   ================================ */
$resumen = [
    'Pendiente'  => 0,
    'Aprobado'   => 0,
    'Rechazado'  => 0
];

$r = $conn->query("SELECT estado, COUNT(*) total 
                    FROM permisos 
                    WHERE usuario_id=$usuario_id 
                    GROUP BY estado");

while ($row = $r->fetch_assoc()) {
    $estado = $row['estado'];
    if (isset($resumen[$estado])) {
        $resumen[$estado] = $row['total'];
    }
}

/* ===============================
   HISTORIAL
================================ */
$historial = $conn->query("
    SELECT * FROM permisos 
    WHERE usuario_id=$usuario_id 
    ORDER BY fecha_registro DESC
");
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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Solicitud de Permiso - Callqui Chico</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #2563eb;
        --primary-dark: #1e40af;
        --secondary: #10b981;
        --danger: #ef4444;
        --warning: #f59e0b;
        --dark-bg: #0a1928;
        --card-bg: #ffffff;
        --text-dark: #1e293b;
        --text-light: #64748b;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #0a1628 0%, #0d1f3c 50%, #091524 100%);
        min-height: 100vh;
        position: relative;
    }

    /* Fondo con patrón profesional */
    body::before {
        content: "";
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            radial-gradient(circle at 20% 20%, rgba(37, 99, 235, 0.08) 0%, transparent 40%),
            radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.06) 0%, transparent 40%),
            url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c9a45c' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        pointer-events: none;
        z-index: 0;
    }

    /* Header de navegación */
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

    .logo-img {
        width: 45px;
        height: 45px;
        object-fit: contain;
        border-radius: 8px;
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
    }

    /* Panel principal - AZUL PROFESIONAL */
    .panel {
        background: linear-gradient(145deg, rgba(15, 32, 60, 0.95), rgba(10, 25, 45, 0.98));
        border-radius: 24px;
        padding: 2rem;
        border: 1px solid rgba(37, 99, 235, 0.2);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
    }

    .panel * {
        color: white !important;
    }

    /* Header del panel */
    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(37, 99, 235, 0.15);
    }

    .header-title h2 {
        color: white;
        font-weight: 700;
        font-size: 1.75rem;
        margin-bottom: 0.3rem;
    }

    .header-title p {
        color: #94a3b8;
        margin: 0;
        font-size: 0.9rem;
    }

    .btn-back {
        background: rgba(255, 255, 255, 0.08);
        color: white;
        padding: 0.7rem 1.5rem;
        border-radius: 10px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
    }

    .btn-back:hover {
        background: #7c3aed;
        color: white;
        transform: translateX(-5px);
    }

    /* Alertas modernas */
    .alert-modern {
        padding: 1rem 1.5rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        border: none;
        backdrop-filter: blur(10px);
    }

    .alert-success {
        background: rgba(16,185,129,0.2);
        border: 1px solid rgba(16,185,129,0.3);
        color: #10b981;
    }

    .alert-danger {
        background: rgba(239,68,68,0.2);
        border: 1px solid rgba(239,68,68,0.3);
        color: #ef4444;
    }

    /* Cards de estadísticas - WHITE */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: linear-gradient(145deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
        border-radius: 16px;
        padding: 1.25rem;
        border: 1px solid rgba(37, 99, 235, 0.15);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        border-color: rgba(37, 99, 235, 0.4);
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stat-card.pendiente .stat-icon {
        background: rgba(245, 158, 11, 0.2);
        color: #fbbf24;
    }

    .stat-card.aprobado .stat-icon {
        background: rgba(16, 185, 129, 0.2);
        color: #34d399;
    }

    .stat-card.rechazado .stat-icon {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 800;
        color: white;
        margin-bottom: 0.2rem;
        line-height: 1;
    }

    .stat-label {
        color: #94a3b8;
        font-size: 0.85rem;
        font-weight: 500;
    }

    /* Grid principal - CENTRADO */
    .main-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        max-width: 800px;
        margin: 0 auto;
    }

    /* Cuando hay historial, que se adapte */
    #tab-historial .row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    #tab-historial .historial-card {
        max-width: 100%;
    }

    /* Cards - AZUL OSCURO */
    .card-moderno {
        background: linear-gradient(145deg, #0a1928 0%, #0f2942 100%);
        border-radius: 20px;
        border: 1px solid rgba(37, 99, 235, 0.3);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .card-moderno:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        border-color: rgba(37, 99, 235, 0.5);
    }

    .card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(37, 99, 235, 0.2);
        background: rgba(0, 0, 0, 0.2);
    }

    .card-header h4 {
        color: white;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .card-header h4 i {
        color: #2563eb;
    }

    .card-body {
        padding: 1.5rem;
    }

    .card-body * {
        color: white !important;
    }

    /* Formulario */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        color: #94a3b8;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: block;
        font-size: 0.9rem;
    }

    .form-label i {
        color: #2563eb;
        margin-right: 0.4rem;
    }

    .form-control, .form-select {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(37, 99, 235, 0.3);
        border-radius: 10px;
        padding: 0.75rem 1rem;
        color: white;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        outline: none;
        background: rgba(0, 0, 0, 0.4);
    }

    .form-control::placeholder {
        color: #64748b;
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    /* Input file personalizado */
    .file-input {
        position: relative;
    }

    .file-label {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem;
        background: rgba(0, 0, 0, 0.2);
        border: 2px dashed rgba(37, 99, 235, 0.3);
        border-radius: 12px;
        color: #94a3b8;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .file-label:hover {
        border-color: #2563eb;
        background: rgba(37, 99, 235, 0.1);
        color: white;
    }

    .file-label i {
        font-size: 1.5rem;
        color: #2563eb;
    }

    .file-input input {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        cursor: pointer;
    }

    /* Botones */
    .btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    }

    .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
    }

    /* Tarjetas de historial - AZUL OSCURO PROFESIONAL */
    .historial-card {
        background: linear-gradient(145deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
        border-radius: 14px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(37, 99, 235, 0.2);
        transition: all 0.3s ease;
    }

    .historial-card:hover {
        border-color: rgba(37, 99, 235, 0.5);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }

    .historial-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(37, 99, 235, 0.15);
    }

    .historial-codigo {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        color: #c9a45c !important;
        font-weight: 700;
        background: rgba(201, 164, 91, 0.15);
        padding: 5px 12px;
        border-radius: 6px;
        border: 1px solid rgba(201, 164, 91, 0.3);
    }

    .historial-fecha {
        font-size: 0.8rem;
        color: #94a3b8 !important;
        text-align: right;
    }

    .historial-tipo {
        font-size: 1.1rem;
        font-weight: 700;
        color: white !important;
        margin-bottom: 0.5rem;
    }

    .historial-fechas {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: #94a3b8 !important;
        margin-bottom: 0.75rem;
    }

    .historial-fechas i {
        color: #2563eb !important;
    }

    /* Indicador adjunto */
    .adjunto-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        color: #34d399 !important;
        background: rgba(52, 211, 153, 0.15);
        padding: 4px 10px;
        border-radius: 20px;
        border: 1px solid rgba(52, 211, 153, 0.3);
    }

    /* Observaciones - AZUL OSCURO */
    .obs-box {
        padding: 14px 16px;
        border-radius: 10px;
        font-size: 0.9rem;
        border-left: 4px solid;
        margin-top: 1rem;
        background: rgba(0,0,0,0.2);
    }

    .obs-box.aprobado {
        background: rgba(16, 185, 129, 0.1);
        border-color: #10b981;
    }

    .obs-box.rechazado {
        background: rgba(239, 68, 68, 0.1);
        border-color: #ef4444;
    }

    .obs-box.pendiente {
        background: rgba(245, 158, 11, 0.1);
        border-color: #f59e0b;
    }

    .obs-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        font-weight: 600;
    }

    /* Boton descarga */
    .btn-descargar {
        background: #2563eb;
        color: white !important;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-descargar:hover {
        background: #6d28d9;
        transform: translateY(-1px);
    }

    /* Badges - LIMPIO */
    .badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .badge-pendiente {
        background: rgba(245, 158, 11, 0.2);
        color: #fbbf24 !important;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .badge-aprobado {
        background: rgba(16, 185, 129, 0.2);
        color: #34d399 !important;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .badge-rechazado {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171 !important;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    /* Tabla */
    .table-responsive {
        margin: 0;
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
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding: 1rem;
    }

    .table tbody td {
        padding: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: rgba(255,255,255,0.03);
    }

    /* Fechas en la tabla */
    .fecha-info {
        display: flex;
        flex-direction: column;
    }

    .fecha-small {
        font-size: 0.8rem;
        color: #94a3b8;
    }

    /* Pestañas/Tabs - CENTRADOS */
    .tabs-container {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .panel-header .header-title {
        flex: 1;
        min-width: 200px;
        text-align: center;
    }

    .panel-header .tabs-container {
        flex: 2;
        min-width: 300px;
    }

    .tab-btn {
        background: rgba(255, 255, 255, 0.08);
        color: white;
        padding: 0.7rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .tab-btn:hover {
        background: #7c3aed;
        color: white;
        transform: translateX(-3px);
    }

    .tab-btn.active {
        background: #7c3aed;
        color: white;
        border-color: #7c3aed;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive */
    @media (max-width: 992px) {
        .main-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
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
    }
</style>
</head>

<body>

<!-- Barra de navegación -->
<div class="nav-bar">
    <div class="nav-container">
        <div class="logo-area">
            <div class="">
                 <img src="../../assets/img/logo_callqui.png" alt="Logo Callqui Chico" style="width:60px; height:60px; object-fit:contain;">
            </div>
            <div class="logo-text">
                <h3>Comunidad Callqui Chico</h3>
                <small>Sistema de Gestión Comunal</small>
            </div>
        </div>
        <div class="nav-actions">
            <a href="../perfil_ajax.php" class="nav-btn" style="display:flex;align-items:center;gap:8px;">
    <img src="../../perfil/uploads/<?php echo $fotoPerfil; ?>" 
         style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
    <div class="user-details">
               
                <span style="font-weight:700; letter-spacing:2px; color:#4ade80; text-transform:uppercase; font-size:14px;">
    PERFIL
</span>
            </div>
</a>

            <a href="../../index.html" class="btn-nav">
                <i class="bi bi-box-arrow-right"></i>
                <span class="d-none d-md-inline">Salir</span>
                
            </a>
            
        </div>
    </div>
</div>

<div class="main-container">

    <div class="panel">

        <!-- Header del panel -->
        <div class="panel-header">
            <div class="header-title">
                <h2><i class="bi bi-file-earmark-text me-2"></i>Solicitud de Permisos</h2>
                <p>Registra tus solicitudes y da seguimiento a su estado</p>
            </div>
            
            <!-- Pestañas horizontales arriba -->
            <div class="tabs-container">
                <button type="button" class="tab-btn active" onclick="switchTab('nueva')">
                    <i class="bi bi-plus-circle"></i> Nueva Solicitud
                </button>
                <button type="button" class="tab-btn" onclick="switchTab('historial')">
                    <i class="bi bi-clock-history"></i> Historial
                </button>
            </div>
            
            <a href="comunero.php" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                <span>VOLVER</span>
            </a>
        </div>

        <!-- Mensaje de alerta -->
        <?php if ($mensaje): ?>
        <div class="alert-modern alert-<?= $mensaje[0]=='ok'?'success':'danger' ?>">
            <i class="bi bi-<?= $mensaje[0]=='ok'?'check-circle':'exclamation-triangle' ?> fs-4"></i>
            <span><?= $mensaje[1] ?></span>
        </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245,158,11,0.2); color: #f59e0b;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-number"><?= $resumen['Pendiente'] ?></div>
                <div class="stat-label">Solicitudes Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16,185,129,0.2); color: #10b981;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-number"><?= $resumen['Aprobado'] ?></div>
                <div class="stat-label">Solicitudes Aprobadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(239,68,68,0.2); color: #ef4444;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-number"><?= $resumen['Rechazado'] ?></div>
                <div class="stat-label">Solicitudes Rechazadas</div>
            </div>
        </div>

        <!-- Grid principal -->
        <div class="main-grid">

            <!-- Formulario Nueva Solicitud -->
            <div id="tab-nueva" class="tab-content active">
            <div class="card-moderno">
                <div class="card-header">
                    <h4><i class="bi bi-pencil-square"></i> Nueva Solicitud</h4>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-tag"></i>Tipo de permiso
                            </label>
                            <select name="tipo_permiso" class="form-select" required>
                                <option value="" selected disabled>Seleccione una opción</option>
                                <option value="Salud">🩺 Salud</option>
                                <option value="Personal">👤 Personal</option>
                                <option value="Vacaciones">🏖️ Vacaciones</option>
                                <option value="Familiar">👪 Familiar</option>
                                <option value="Estudio">📚 Estudio</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-calendar"></i>Fecha inicio
                                    </label>
                                    <input type="date" name="fecha_inicio" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-calendar-check"></i>Fecha fin
                                    </label>
                                    <input type="date" name="fecha_fin" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-chat-text"></i>Motivo / Justificación
                            </label>
                            <textarea name="motivo" class="form-control" placeholder="Describa el motivo de su solicitud..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-file-earmark"></i>Documento de sustento
                            </label>
                            <div class="file-input">
                                <label class="file-label">
                                    <i class="bi bi-cloud-upload"></i>
                                    <span>Haga clic para subir un archivo (opcional)</span>
                                </label>
                                <input type="file" name="archivo" accept=".pdf,.doc,.docx,.jpg,.png">
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="bi bi-info-circle"></i> Formatos permitidos: PDF, DOC, JPG, PNG
                            </small>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-eraser"></i>
                                Limpiar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i>
                                Enviar Solicitud
                            </button>
                        </div>

                    </form>
                </div>
            </div>
            </div><!-- Fin tab nueva -->

            <!-- Tab Historial -->
            <div id="tab-historial" class="tab-content">
            <div class="card-moderno">
                <div class="card-header">
                    <h4><i class="bi bi-clock-history"></i> Historial de Solicitudes</h4>
                </div>
                <div class="card-body p-0">
                    <div class="row">
                                <?php if ($historial->num_rows > 0): ?>
                                    <?php while ($h = $historial->fetch_assoc()): ?>
                                    <?php 
                                        $obs = $h['observacion_secretario'] ?? '';
                                        $estado = $h['estado'];
                                        $obsClass = '';
                                        if($obs) {
                                            $obsClass = ($estado == 'Aprobado') ? 'aprobado' : (($estado == 'Rechazado') ? 'rechazado' : 'pendiente');
                                        }
                                        
                                        $badgeClass = match($estado) {
                                            'Pendiente' => 'badge-pendiente',
                                            'Aprobado' => 'badge-aprobado',
                                            'Rechazado' => 'badge-rechazado',
                                            default => 'badge-pendiente'
                                        };
                                        $icon = match($estado) {
                                            'Pendiente' => 'hourglass',
                                            'Aprobado' => 'check-circle',
                                            'Rechazado' => 'x-circle',
                                            default => 'hourglass'
                                        };
                                    ?>
                                    <div class="col-12">
                                        <div class="historial-card">
                                            <div class="historial-header">
                                                <div class="historial-codigo"><?= htmlspecialchars($h['codigo_unico'] ?? 'N/A') ?></div>
                                                <div class="historial-fecha">
                                                    <?= date("d/m/Y", strtotime($h['fecha_registro'])) ?>
                                                    <br><small><?= date("H:i", strtotime($h['fecha_registro'])) ?> hrs</small>
                                                </div>
                                            </div>
                                            
                                            <div class="historial-tipo"><?= htmlspecialchars($h['tipo_permiso']) ?></div>
                                            
                                            <div class="historial-fechas">
                                                <i class="bi bi-calendar-range"></i>
                                                <?= date("d/m/Y", strtotime($h['fecha_inicio'])) ?> 
                                                <i class="bi bi-arrow-right"></i> 
                                                <?= date("d/m/Y", strtotime($h['fecha_fin'])) ?>
                                            </div>
                                            
                                            <?php if(!empty($h['archivo'])): ?>
                                                <div class="mt-2" style="color: #2563eb; font-size: 0.85rem;">
                                                    <i class="bi bi-paperclip"></i> Adjunto disponible
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <span class="badge <?= $badgeClass ?>">
                                                    <i class="bi bi-<?= $icon ?>"></i> <?= $estado ?>
                                                </span>
                                                
                                                <a href="generar_solicitud_pdf.php?id=<?= $h['id'] ?>" 
                                                   class="btn-descargar" 
                                                   target="_blank">
                                                    <i class="bi bi-download"></i> Descargar PDF
                                                </a>
                                            </div>
                                            
                                            <?php if(!empty($obs)): ?>
                                                <div class="obs-box <?= $obsClass ?>">
                                                    <div class="obs-label">Observación del Secretary</div>
                                                    <?= htmlspecialchars($obs) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="col-12 text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No hay solicitudes registradas
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
            </div><!-- Fin tab historial -->

        </div>

    </div>

</div>

<!-- Script para tabs -->
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById('tab-' + tab).classList.add('active');
    document.querySelector('[onclick="switchTab(\'' + tab + '\')"]').classList.add('active');
}
</script>

<!-- Script para previsualizar nombre del archivo -->
<script>
document.querySelector('.file-input input')?.addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    if (fileName) {
        const label = document.querySelector('.file-label span');
        label.textContent = fileName;
        document.querySelector('.file-label').style.borderColor = '#10b981';
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>