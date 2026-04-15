<?php
require_once "../../includes/verificar_sesion.php";

$conn = getDB();
$success = "";
$codigo_generado = "";

$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Función para subir archivos PDF
function subirArchivo($inputName) {
    if (isset($_FILES[$inputName]) && !empty($_FILES[$inputName]['name'])) {
        $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') return null; // solo PDF

        if (!is_dir("uploads")) mkdir("uploads", 0777, true);
        $archivo = time() . "_" . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($_FILES[$inputName]['name']));
        move_uploaded_file($_FILES[$inputName]['tmp_name'], "uploads/$archivo");
        return $archivo;
    }
    return null;
}

// Manejo de envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = $_POST['nombre'] ?? '';
    $dni = $_POST['dni'] ?? '';
    $lote = $_POST['lote'] ?? '';
    $manzana = $_POST['manzana'] ?? '';
    $sector = $_POST['sector'] ?? '';
    $area_m2 = (int)($_POST['area_m2'] ?? 0);
    $estado = "pendiente";
    $expediente = $_POST['expediente'] ?? '';
    $fecha_solicitud = date("Y-m-d H:i:s");

    // Generar código único de seguimiento
    $año = date('Y');
    $codigo_seguimiento = 'ADJ-' . $año . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

    // Subida de archivos
    $archivo_dni        = subirArchivo('archivo_dni') ?? '';
    $archivo_constancia = subirArchivo('archivo_constancia') ?? '';
    $archivo_plano      = subirArchivo('archivo_plano') ?? '';
    $archivo_recibo     = subirArchivo('archivo_recibo') ?? '';
    $archivo_memoria    = subirArchivo('archivo_memoria') ?? '';
    $archivo_jurada     = subirArchivo('archivo_jurada') ?? '';
    $archivo_contrato   = subirArchivo('archivo_contrato') ?? '';

    // Insert en la BD con código de seguimiento y usuario_id
    $stmt = $conn->prepare("
        INSERT INTO adjudicaciones
        (codigo_seguimiento, usuario_id, nombre, dni, lote, manzana, sector, area_m2, estado, expediente, fecha_solicitud,
         archivo_dni, archivo_constancia, archivo_plano, archivo_recibo, archivo_memoria, archivo_jurada, archivo_contrato)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sisssssissssssssss",
        $codigo_seguimiento,
        $usuario_id,
        $nombre,
        $dni,
        $lote,
        $manzana,
        $sector,
        $area_m2,
        $estado,
        $expediente,
        $fecha_solicitud,
        $archivo_dni,
        $archivo_constancia,
        $archivo_plano,
        $archivo_recibo,
        $archivo_memoria,
        $archivo_jurada,
        $archivo_contrato
    );

    $stmt->execute();
    $stmt->close();

    $success = "Solicitud registrada correctamente.";
    $codigo_generado = $codigo_seguimiento;
}

// Obtener solo las adjudicaciones del usuario actual
$stmt = $conn->prepare("SELECT * FROM adjudicaciones WHERE usuario_id = ? ORDER BY fecha_solicitud DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$adjudicaciones = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
<title>Adjudicaciones - Comunidad Callqui Chico</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
        min-height: 100vh;
        position: relative;
    }

    /* Fondo con efecto */
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

    /* Panel principal */
    .panel {
        background: rgba(255,255,255,0.05);
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

    /* Layout de dos columnas */
    .two-columns {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    /* Cards modernas */
    .card-moderno {
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(12px);
        border-radius: 24px;
        border: 1px solid rgba(255,255,255,0.1);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .card-moderno:hover {
        border-color: #2563eb;
        transform: translateY(-5px);
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .card-header h4 {
        color: white;
        font-weight: 600;
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

    /* Formulario */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group.full-width {
        grid-column: span 2;
    }

    .form-label {
        color: white;
        font-weight: 500;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-label i {
        color: #2563eb;
        margin-right: 0.5rem;
    }

    .form-control {
        background: rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 0.8rem 1.2rem;
        color: white;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        background: rgba(0,0,0,0.4);
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
        color: white;
    }

    .form-control::placeholder {
        color: #64748b;
    }

    /* Input file personalizado */
    .file-upload-section {
        margin: 1.5rem 0;
        padding: 1.5rem;
        background: rgba(0,0,0,0.2);
        border-radius: 16px;
    }

    .file-section-title {
        color: #94a3b8;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 1rem;
    }

    .file-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .file-input-item {
        position: relative;
    }

    .file-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        background: rgba(0,0,0,0.3);
        border: 2px dashed rgba(255,255,255,0.2);
        border-radius: 12px;
        color: #94a3b8;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .file-label:hover {
        border-color: #2563eb;
        background: rgba(37,99,235,0.1);
        color: white;
    }

    .file-label i {
        font-size: 1.5rem;
        color: #2563eb;
    }

    .file-label small {
        font-size: 0.7rem;
        opacity: 0.7;
    }

    .file-input-item input {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        cursor: pointer;
    }

    .file-name {
        font-size: 0.8rem;
        margin-top: 0.3rem;
        color: #10b981;
        word-break: break-all;
    }

    /* Botones */
    .btn {
        padding: 0.8rem 1.8rem;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: white;
        box-shadow: 0 4px 15px rgba(37,99,235,0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(37,99,235,0.4);
    }

    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16,185,129,0.4);
    }

    /* Listado de solicitudes */
    .solicitudes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .solicitud-card {
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(12px);
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.1);
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .solicitud-card:hover {
        transform: translateY(-5px);
        border-color: #2563eb;
        box-shadow: 0 10px 30px rgba(37,99,235,0.2);
    }

    .solicitud-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .solicitud-nombre {
        font-weight: 700;
        font-size: 1.2rem;
        color: white;
    }

    .badge-estado {
        padding: 0.4rem 1rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .badge-pendiente {
        background: rgba(245,158,11,0.2);
        color: #f59e0b;
        border: 1px solid rgba(245,158,11,0.3);
    }

    .badge-aprobado {
        background: rgba(16,185,129,0.2);
        color: #10b981;
        border: 1px solid rgba(16,185,129,0.3);
    }

    .badge-rechazado {
        background: rgba(239,68,68,0.2);
        color: #ef4444;
        border: 1px solid rgba(239,68,68,0.3);
    }

    .solicitud-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .info-item {
        display: flex;
        flex-direction: column;
    }

    .info-label {
        font-size: 0.75rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: 1rem;
        font-weight: 600;
        color: white;
    }

    .archivos-section {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    .archivos-titulo {
        font-size: 0.85rem;
        color: #94a3b8;
        margin-bottom: 0.5rem;
    }

    .archivos-links {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .archivo-link {
        background: rgba(37,99,235,0.1);
        color: #2563eb;
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.8rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        border: 1px solid rgba(37,99,235,0.3);
        transition: all 0.3s ease;
    }

    .archivo-link:hover {
        background: #2563eb;
        color: white;
    }

    .solicitud-footer {
        margin-top: 1rem;
        padding-top: 0.5rem;
        font-size: 0.8rem;
        color: #64748b;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .two-columns {
            grid-template-columns: 1fr;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .form-group.full-width {
            grid-column: span 1;
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

        .solicitudes-grid {
            grid-template-columns: 1fr;
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
            <a href="../perfil_ajax.php" class="btn-nav">
              <img src="../../perfil/uploads/<?php echo !empty($fotoPerfil) ? $fotoPerfil : 'default.png'; ?>" 
     style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                <span class="d-none d-md-inline">Mi Perfil</span>
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
                <h2>
                    <i class="bi bi-house-heart-fill"></i>
                    Adjudicaciones de Terrenos
                </h2>
                <p>Registra y da seguimiento a tus solicitudes de adjudicación</p>
            </div>
            <a href="comunero.php" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                <span>VOLVER</span>
            </a>
        </div>

        <!-- Mensaje de alerta -->
        <?php if ($success): ?>
        <div class="alert-modern alert-success">
            <i class="bi bi-check-circle-fill fs-4"></i>
            <div>
                <span><?= htmlspecialchars($success) ?></span>
                <?php if ($codigo_generado): ?>
                <div class="mt-2 p-2 bg-white bg-opacity-25 rounded">
                    <small class="text-white-50">Su código de seguimiento:</small>
                    <div class="fw-bold text-white fs-5"><?= htmlspecialchars($codigo_generado) ?></div>
                    <small class="text-white-50">Guarde este código para consultar el estado de su trámite</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulario de registro -->
        <div class="card-moderno">
            <div class="card-header">
                <h4><i class="bi bi-pencil-square"></i> Registrar Nueva Solicitud</h4>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    
                    <!-- Datos personales -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-person"></i>Nombre completo
                            </label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Juan Pérez" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-card-text"></i>DNI
                            </label>
                            <input type="text" name="dni" class="form-control" placeholder="12345678" required>
                        </div>
                    </div>

                    <!-- Datos del terreno -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-pin-map"></i>Lote
                            </label>
                            <input type="text" name="lote" class="form-control" placeholder="Ej: A-15" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-grid-3x3"></i>Manzana
                            </label>
                            <input type="text" name="manzana" class="form-control" placeholder="Ej: MZ-B" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-geo-alt"></i>Sector
                            </label>
                            <input type="text" name="sector" class="form-control" placeholder="Ej: Sector Norte" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-rulers"></i>Área (m²)
                            </label>
                            <input type="number" name="area_m2" class="form-control" placeholder="Ej: 250" required>
                        </div>
                    </div>

                    <!-- Expediente -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-folder"></i>Número de expediente (opcional)
                        </label>
                        <input type="text" name="expediente" class="form-control" placeholder="Ej: EXP-2025-001">
                    </div>

                    <!-- Archivos -->
                    <div class="file-upload-section">
                        <div class="file-section-title">
                            <i class="bi bi-files"></i> Documentos requeridos (PDF)
                        </div>
                        <div class="file-grid">
                            
                            <div class="file-input-item">
                                <label class="file-label">
                                    <i class="bi bi-person-badge"></i>
                                    <span>DNI</span>
                                    <small>PDF</small>
                                </label>
                                <input type="file" name="archivo_dni" accept=".pdf">
                                <div class="file-name" id="file-name-dni"></div>
                            </div>

                            <div class="file-input-item">
                                <label class="file-label">
                                    <i class="bi bi-file-text"></i>
                                    <span>Constancia</span>
                                    <small>PDF</small>
                                </label>
                                <input type="file" name="archivo_constancia" accept=".pdf">
                                <div class="file-name" id="file-name-constancia"></div>
                            </div>

                            <div class="file-input-item">
                                <label class="file-label">
                                    <i class="bi bi-map"></i>
                                    <span>Plano</span>
                                    <small>PDF</small>
                                </label>
                                <input type="file" name="archivo_plano" accept=".pdf">
                                <div class="file-name" id="file-name-plano"></div>
                            </div>

                            <div class="file-input-item">
                                <label class="file-label">
                                    <i class="bi bi-receipt"></i>
                                    <span>Recibo</span>
                                    <small>PDF</small>
                                </label>
                                <input type="file" name="archivo_recibo" accept=".pdf">
                                <div class="file-name" id="file-name-recibo"></div>
                            </div>

                            <div class="file-input-item">
                                <label class="file-label">
                                    <i class="bi bi-journal-text"></i>
                                    <span>Memoria</span>
                                    <small>PDF</small>
                                </label>
                                <input type="file" name="archivo_memoria" accept=".pdf">
                                <div class="file-name" id="file-name-memoria"></div>
                            </div>

                            <div class="file-input-item">
                                <label class="file-label">
                                    <i class="bi bi-file-earmark-check"></i>
                                    <span>Declaración Jurada</span>
                                    <small>PDF</small>
                                </label>
                                <input type="file" name="archivo_jurada" accept=".pdf">
                                <div class="file-name" id="file-name-jurada"></div>
                            </div>

                            <div class="file-input-item">
                                <label class="file-label">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>Contrato</span>
                                    <small>PDF</small>
                                </label>
                                <input type="file" name="archivo_contrato" accept=".pdf">
                                <div class="file-name" id="file-name-contrato"></div>
                            </div>

                        </div>
                    </div>

                    <!-- Botón de envío -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-send"></i>
                            Registrar Solicitud
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listado de solicitudes -->
        <h3 class="mt-5 mb-4" style="color: white;">
            <i class="bi bi-list-check"></i> Solicitudes Registradas
        </h3>

        <?php if ($adjudicaciones): ?>
            <div class="solicitudes-grid">
                <?php foreach($adjudicaciones as $a): ?>
                <div class="solicitud-card">
                    <div class="solicitud-header">
                        <span class="solicitud-nombre"><?= htmlspecialchars($a['nombre']) ?></span>
                        <div class="d-flex flex-column align-items-end gap-2">
                            <span class="badge-estado badge-<?= $a['estado'] ?>">
                                <?= ucfirst(htmlspecialchars($a['estado'])) ?>
                            </span>
                            <?php if ($a['estado_pago'] && $a['estado_pago'] !== 'sin_pago'): ?>
                            <span class="badge-estado badge-<?= $a['estado_pago'] === 'validado' ? 'success' : ($a['estado_pago'] === 'pagado' ? 'info' : 'warning') ?>">
                                <i class="bi bi-cash me-1"></i>
                                <?= $a['estado_pago'] === 'validado' ? 'Pagado' : ($a['estado_pago'] === 'pagado' ? 'En revisión' : 'Pago pendiente') ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="solicitud-info">
                        <?php if (!empty($a['codigo_seguimiento'])): ?>
                        <div class="info-item" style="background: rgba(37, 99, 235, 0.2);">
                            <span class="info-label">Código de Seguimiento</span>
                            <span class="info-value text-info"><?= htmlspecialchars($a['codigo_seguimiento']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-label">DNI</span>
                            <span class="info-value"><?= htmlspecialchars($a['dni']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Lote/Manzana</span>
                            <span class="info-value"><?= htmlspecialchars($a['lote']) ?>/<?= htmlspecialchars($a['manzana']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Sector</span>
                            <span class="info-value"><?= htmlspecialchars($a['sector']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Área</span>
                            <span class="info-value"><?= htmlspecialchars($a['area_m2']) ?> m²</span>
                        </div>
                    </div>

                    <?php 
                    // Botón de descargar certificado si está completamente firmado
                    $puede_descargar = !empty($a['completamente_firmado']) || $a['estado'] === 'aprobado_total';
                    $archivo_certificado = $a['pdf_firmado'] ?? $a['certificado'] ?? '';
                    
                    if ($puede_descargar && !empty($archivo_certificado)): ?>
                    <div class="solicitud-acciones mt-3">
                        <a href="../<?= htmlspecialchars($archivo_certificado) ?>" class="btn btn-success btn-sm" target="_blank">
                            <i class="bi bi-download me-1"></i> Descargar Certificado
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    $archivos = [];
                    if($a['archivo_dni']) $archivos[] = ['nombre' => 'DNI', 'archivo' => $a['archivo_dni']];
                    if($a['archivo_constancia']) $archivos[] = ['nombre' => 'Constancia', 'archivo' => $a['archivo_constancia']];
                    if($a['archivo_plano']) $archivos[] = ['nombre' => 'Plano', 'archivo' => $a['archivo_plano']];
                    if($a['archivo_recibo']) $archivos[] = ['nombre' => 'Recibo', 'archivo' => $a['archivo_recibo']];
                    if($a['archivo_memoria']) $archivos[] = ['nombre' => 'Memoria', 'archivo' => $a['archivo_memoria']];
                    if($a['archivo_jurada']) $archivos[] = ['nombre' => 'Declaración', 'archivo' => $a['archivo_jurada']];
                    if($a['archivo_contrato']) $archivos[] = ['nombre' => 'Contrato', 'archivo' => $a['archivo_contrato']];
                    ?>

                    <?php if(!empty($archivos)): ?>
                    <div class="archivos-section">
                        <div class="archivos-titulo">
                            <i class="bi bi-paperclip"></i> Documentos adjuntos
                        </div>
                        <div class="archivos-links">
                            <?php foreach($archivos as $arch): ?>
                            <a href="uploads/<?= htmlspecialchars($arch['archivo']) ?>" 
                               target="_blank" 
                               class="archivo-link">
                                <i class="bi bi-file-pdf"></i>
                                <?= $arch['nombre'] ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Botón de pago - solo si es adjudication y no tiene pago validado
                    $necesita_pago = ($a['tipo_solicitud'] === 'adjudicacion' || $a['tipo_solicitud'] === 'adjudicacion');
                    $pago_pendiente = ($a['estado_pago'] === 'pendiente' || $a['estado_pago'] === 'pagado');
                    $pago_validado = ($a['estado_pago'] === 'validado');
                    
                    if ($necesita_pago && !$pago_validado): ?>
                    <div class="solicitud-acciones mt-3">
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#pagoModal<?= $a['id'] ?>">
                            <i class="bi bi-cash me-1"></i>
                            <?= $pago_pendiente ? 'Ver Detalle de Pago' : 'Realizar Pago' ?>
                        </button>
                    </div>
                    
                    <!-- Modal Pago -->
                    <div class="modal fade" id="pagoModal<?= $a['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-white">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="bi bi-cash-stack me-2"></i>Registro de Pago</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="../api/registrar_pago.php" enctype="multipart/form-data" class="pagoForm">
                                    <div class="modal-body">
                                        <input type="hidden" name="id_solicitud" value="<?= $a['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Número de Propietarios</label>
                                            <select name="numero_propietarios" class="form-control propietarios-select" data-id="<?= $a['id'] ?>" required>
                                                <option value="1">1 propietario (S/ 50)</option>
                                                <option value="2">2 propietarios (S/ 300)</option>
                                                <option value="3">3 propietarios (S/ 550)</option>
                                                <option value="4">4 propietarios (S/ 800)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Monto a Pagar</label>
                                            <div class="input-group">
                                                <span class="input-group-text">S/</span>
                                                <input type="text" class="form-control monto-mostrar" id="monto<?= $a['id'] ?>" value="50" readonly>
                                            </div>
                                            <small class="text-muted">Monto calculado automáticamente</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Medio de Pago</label>
                                            <select name="medio_pago" class="form-control" required>
                                                <option value="Yape">Yape</option>
                                                <option value="Visa">Visa</option>
                                                <option value="Banco">Banco</option>
                                                <option value="Efectivo">Efectivo</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Número de Operación</label>
                                            <input type="text" name="numero_operacion" class="form-control" placeholder="Número de operación" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Comprobante (Imagen)</label>
                                            <input type="file" name="comprobante" class="form-control" accept="image/*,.pdf">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-warning">Registrar Pago</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="solicitud-footer">
                        <span>
                            <i class="bi bi-calendar"></i>
                            <?= date("d/m/Y H:i", strtotime($a['fecha_solicitud'])) ?>
                        </span>
                        <?php if(!empty($a['expediente'])): ?>
                        <span>
                            <i class="bi bi-folder"></i> <?= htmlspecialchars($a['expediente']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #4b5563;"></i>
                <p class="text-muted mt-3">No hay solicitudes registradas</p>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- Script para mostrar nombres de archivos -->
<script>
document.querySelectorAll('.file-input-item input').forEach(input => {
    input.addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        const nameDiv = document.getElementById('file-name-' + this.name.replace('archivo_', ''));
        if (nameDiv && fileName) {
            nameDiv.textContent = fileName;
            this.parentElement.querySelector('.file-label').style.borderColor = '#10b981';
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Calcular monto automáticamente
document.querySelectorAll('.propietarios-select').forEach(select => {
    select.addEventListener('change', function() {
        const id = this.dataset.id;
        const propietarios = parseInt(this.value);
        const monto = 50 + (propietarios - 1) * 250;
        document.getElementById('monto' + id).value = monto;
    });
});

// Envío del formulario de pago
document.querySelectorAll('.pagoForm').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('../api/registrar_pago.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Pago registrado: ' + data.codigo_pago + '\nMonto: S/ ' + data.monto);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            alert('Error al procesar pago');
            console.error(err);
        });
    });
});
</script>
</body>
</html>