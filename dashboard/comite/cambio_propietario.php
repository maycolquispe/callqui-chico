<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($_SESSION['rol'] !== 'comite_lotes') {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

// Asegurar que las columnas de copropietario existan
$conn->query("ALTER TABLE lotes ADD COLUMN IF NOT EXISTS copropietario VARCHAR(255) NULL AFTER propietario");
$conn->query("ALTER TABLE transferencias_lote ADD COLUMN IF NOT EXISTS copropietario_anterior VARCHAR(255) NULL AFTER propietario_anterior");
$conn->query("ALTER TABLE transferencias_lote ADD COLUMN IF NOT EXISTS copropietario_nuevo VARCHAR(255) NULL AFTER propietario_nuevo");

$success = '';
$error = '';

// Debug: Log POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST received: " . print_r($_POST, true));
}

// Leer parámetros de solicitudes.php
$solicitud_id = isset($_GET['solicitud_id']) ? intval($_GET['solicitud_id']) : 0;
$preselect_manzana = isset($_GET['manzana']) ? $_GET['manzana'] : '';
$preselect_lote = isset($_GET['lote']) ? $_GET['lote'] : '';
$preselect_nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
$preselect_dni = isset($_GET['dni']) ? $_GET['dni'] : '';

// Procesar cambio de propietario (comunero existente)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambio') {
    $lote_id = intval($_POST['lote_id']);
    $nuevo_propietario_id = intval($_POST['nuevo_propietario_id']);
    $observaciones = $_POST['observaciones'] ?? '';
    $es_pareja = isset($_POST['es_pareja']) && $_POST['es_pareja'] == '1';
    $copropietario_nombre = trim($_POST['copropietario_nombre'] ?? '');
    $copropietario_apellidos = trim($_POST['copropietario_apellidos'] ?? '');
    $copropietario_dni = trim($_POST['copropietario_dni'] ?? '');
    
    if (!$lote_id || !$nuevo_propietario_id) {
        $error = "Debe seleccionar un lote y un nuevo propietario";
    } else {
        // Obtener propietario actual para validar
        $stmtLote = $conn->prepare("SELECT usuario_id, propietario, copropietario FROM lotes WHERE id = ?");
        $stmtLote->bind_param("i", $lote_id);
        $stmtLote->execute();
        $loteActual = $stmtLote->get_result()->fetch_assoc();
        $stmtLote->close();
        
        $propietario_anterior = $loteActual['usuario_id'] ?? null;
        $copropietario_anterior = $loteActual['copropietario'] ?? null;
        
        // Validar que no sea el mismo propietario
        if ($propietario_anterior == $nuevo_propietario_id) {
            $error = "El nuevo propietario no puede ser el mismo que el actual";
        } else {
            // Validar copropietario si es pareja
            if ($es_pareja && (!$copropietario_nombre || !$copropietario_apellidos || !$copropietario_dni)) {
                $error = "Debe completar los datos del copropietario";
            } else {
                // Subir documento si existe
                $documento = null;
                if (isset($_FILES['documento']) && $_FILES['documento']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION));
                    if ($ext === 'pdf') {
                        $documento = time() . '_' . basename($_FILES['documento']['name']);
                        $uploadDir = __DIR__ . '/uploads/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        move_uploaded_file($_FILES['documento']['tmp_name'], $uploadDir . $documento);
                    }
                }
                
                // Nombre completo del copropietario
                $copropietario_nombre_completo = $es_pareja ? $copropietario_nombre . ' ' . $copropietario_apellidos : null;
                
                // Insertar en historial (transferencias) incluyendo copropietarios
                $stmtTrans = $conn->prepare("INSERT INTO transferencias_lote (lote_id, propietario_anterior, propietario_nuevo, copropietario_anterior, copropietario_nuevo, usuario_registro, documento, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtTrans->bind_param("iiississ", $lote_id, $propietario_anterior, $nuevo_propietario_id, $copropietario_anterior, $copropietario_nombre_completo, $usuario_id, $documento, $observaciones);
                
                if ($stmtTrans->execute()) {
                    // Obtener nombre del nuevo propietario
                    $stmtGetName = $conn->prepare("SELECT CONCAT(nombres, ' ', apellidos) as nombre_completo FROM usuarios WHERE id = ?");
                    $stmtGetName->bind_param("i", $nuevo_propietario_id);
                    $stmtGetName->execute();
                    $nuevoNombre = $stmtGetName->get_result()->fetch_assoc()['nombre_completo'];
                    $stmtGetName->close();
                    
                    // Actualizar lote con nuevo propietario, copropietario y estado OCUPADO
                    $stmtUpdate = $conn->prepare("UPDATE lotes SET usuario_id = ?, propietario = ?, copropietario = ?, estado = 'OCUPADO' WHERE id = ?");
                    $stmtUpdate->bind_param("isss", $nuevo_propietario_id, $nuevoNombre, $copropietario_nombre_completo, $lote_id);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                    
                    // Obtener ID de la transferencia recien creada
                    $transferencia_id = $conn->insert_id;
                    
                    // Obtener DNI del nuevo propietario
                    $stmtDni = $conn->prepare("SELECT dni FROM usuarios WHERE id = ?");
                    $stmtDni->bind_param("i", $nuevo_propietario_id);
                    $stmtDni->execute();
                    $dniNuevo = $stmtDni->get_result()->fetch_assoc()['dni'] ?? '';
                    $stmtDni->close();
                    
                    $successMsg = "✅ Cambio de propietario registrado correctamente";
                    if ($es_pareja) {
                        $successMsg .= " (Titular: $nuevoNombre, Copropietario: $copropietario_nombre_completo)";
                    }
                    // Generar URL con datos directos para el PDF
                    $pdfUrl = "generar_certificado_transferencia.php?modo=datos" .
                        "&lote_id=" . $lote_id .
                        "&titular=" . urlencode($nuevoNombre) .
                        "&titular_dni=" . urlencode($dniNuevo) .
                        "&copropietario=" . urlencode($copropietario_nombre_completo);
                    $success = $successMsg . "
                    <a href='$pdfUrl' target='_blank' class='btn btn-sm ms-2' style='background: rgba(245,158,11,0.2); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3);'>
                        <i class='bi bi-printer me-1'></i>Generar Constancia PDF
                    </a>";
                } else {
                    $error = "Error al registrar el cambio: " . $conn->error;
                }
                $stmtTrans->close();
            }
        }
    }
}

// Procesar registro de nuevo comunero desde solicitud pública
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar_nuevo') {
    $lote_id = intval($_POST['lote_id']);
    $solicitud_id = intval($_POST['solicitud_id']);
    $nombre = trim($_POST['nombre']);
    $dni = trim($_POST['dni']);
    $apellidos = trim($_POST['apellidos']);
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $observaciones = $_POST['observaciones'] ?? '';
    $es_pareja = isset($_POST['es_pareja']) && $_POST['es_pareja'] == '1';
    $copropietario_nombre = trim($_POST['copropietario_nombre'] ?? '');
    $copropietario_apellidos = trim($_POST['copropietario_apellidos'] ?? '');
    $copropietario_dni = trim($_POST['copropietario_dni'] ?? '');
    
    if (!$lote_id || !$nombre || !$dni || !$apellidos) {
        $error = "Debe completar los datos obligatorios del nuevo propietario";
    } else {
        // Validar copropietario si es pareja
        if ($es_pareja && (!$copropietario_nombre || !$copropietario_apellidos || !$copropietario_dni)) {
            $error = "Debe completar los datos del copropietario";
        } else {
            // Verificar si el DNI ya existe
            $checkDNI = $conn->prepare("SELECT id FROM usuarios WHERE dni = ?");
            $checkDNI->bind_param("s", $dni);
            $checkDNI->execute();
            $existeDNI = $checkDNI->get_result()->fetch_assoc();
            $checkDNI->close();
            
            if ($existeDNI) {
                $error = "Ya existe un usuario con ese DNI en el sistema";
            } else {
                // Crear nuevo comunero
                $passwordTemp = password_hash($dni, PASSWORD_DEFAULT);
                $stmtNuevo = $conn->prepare("INSERT INTO usuarios (dni, nombres, apellidos, rol, estado, password_hash, fecha_registro, telefono, correo, direccion) VALUES (?, ?, ?, 'comunero', 'activo', ?, NOW(), ?, ?, ?)");
                $stmtNuevo->bind_param("sssssss", $dni, $nombre, $apellidos, $passwordTemp, $telefono, $correo, $direccion);
                
                if ($stmtNuevo->execute()) {
                    $nuevo_usuario_id = $conn->insert_id;
                    $stmtNuevo->close();
                    
                    // Subir documento si existe
                    $documento = null;
                    if (isset($_FILES['documento']) && $_FILES['documento']['error'] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION));
                        if ($ext === 'pdf') {
                            $documento = time() . '_' . basename($_FILES['documento']['name']);
                            $uploadDir = __DIR__ . '/uploads/';
                            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                            move_uploaded_file($_FILES['documento']['tmp_name'], $uploadDir . $documento);
                        }
                    }
                    
                    // Obtener propietario actual del lote
                    $stmtLote = $conn->prepare("SELECT usuario_id, copropietario FROM lotes WHERE id = ?");
                    $stmtLote->bind_param("i", $lote_id);
                    $stmtLote->execute();
                    $loteActual = $stmtLote->get_result()->fetch_assoc();
                    $stmtLote->close();
                    $propietario_anterior = $loteActual['usuario_id'] ?? null;
                    $copropietario_anterior = $loteActual['copropietario'] ?? null;
                    
                    // Validar que no sea el mismo propietario
                    if ($propietario_anterior == $nuevo_usuario_id) {
                        $error = "El nuevo propietario no puede ser el mismo que el actual";
                    } else {
                        // Nombre completo del copropietario
                        $copropietario_nombre_completo = $es_pareja ? $copropietario_nombre . ' ' . $copropietario_apellidos : null;
                        
                        // Registrar transferencia con copropietario
                        $stmtTrans = $conn->prepare("INSERT INTO transferencias_lote (lote_id, propietario_anterior, propietario_nuevo, copropietario_anterior, copropietario_nuevo, usuario_registro, documento, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmtTrans->bind_param("iiississ", $lote_id, $propietario_anterior, $nuevo_usuario_id, $copropietario_anterior, $copropietario_nombre_completo, $usuario_id, $documento, $observaciones);
                        $stmtTrans->execute();
                        $stmtTrans->close();
                        
                        // Actualizar lote con nuevo propietario, copropietario y estado OCUPADO
                        $nombreCompleto = $nombre . ' ' . $apellidos;
                        $stmtUpdateLote = $conn->prepare("UPDATE lotes SET usuario_id = ?, propietario = ?, copropietario = ?, estado = 'OCUPADO' WHERE id = ?");
                        $stmtUpdateLote->bind_param("isss", $nuevo_usuario_id, $nombreCompleto, $copropietario_nombre_completo, $lote_id);
                        $stmtUpdateLote->execute();
                        $stmtUpdateLote->close();
                        
                        // Obtener ID de la transferencia recien creada
                        $transferencia_id = $conn->insert_id;
                        
                        // Actualizar solicitud si existe
                        if ($solicitud_id) {
                            $conn->query("UPDATE adjudicaciones SET usuario_id = $nuevo_usuario_id WHERE id = $solicitud_id");
                        }
                        
                        $successMsg = "✅ Nuevo comunero registrado y cambio de propietario realizado";
                        if ($es_pareja) {
                            $successMsg .= " (Titular: $nombreCompleto, Copropietario: $copropietario_nombre_completo)";
                        }
                        // Generar URL con datos directos para el PDF
                        $pdfUrl = "generar_certificado_transferencia.php?modo=datos" .
                            "&lote_id=" . $lote_id .
                            "&titular=" . urlencode($nombreCompleto) .
                            "&titular_dni=" . urlencode($dni) .
                            "&copropietario=" . urlencode($copropietario_nombre_completo);
                        $success = $successMsg . "
                        <a href='$pdfUrl' target='_blank' class='btn btn-sm ms-2' style='background: rgba(245,158,11,0.2); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3);'>
                            <i class='bi bi-printer me-1'></i>Generar Constancia PDF
                        </a>";
                    }
                } else {
                    $error = "Error al registrar el nuevo comunero: " . $conn->error;
                }
            }
        }
    }
}

// Obtener lista de solicitudes para la pestaña "Desde Solicitud Pública"
$estado_filtro_sol = $_GET['estado_sol'] ?? '';
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

$where_sol = "1=1";
if ($estado_filtro_sol && $estado_filtro_sol !== 'todas') {
    $where_sol .= " AND a.estado = '$estado_filtro_sol'";
}

// Contar total
$count_sql = "SELECT COUNT(*) as total FROM adjudicaciones a WHERE $where_sol";
$total_result = $conn->query($count_sql);
$total_solicitudes = $total_result->fetch_assoc()['total'];
$total_paginas = ceil($total_solicitudes / $por_pagina);

// Obtener solicitudes con paginación
$sql_solicitudes = "SELECT a.*, u.nombres as nombre_usuario, u.apellidos as apellido_usuario 
                   FROM adjudicaciones a
                   LEFT JOIN usuarios u ON a.usuario_id = u.id
                   WHERE $where_sol
                   ORDER BY a.fecha_solicitud DESC
                   LIMIT $offset, $por_pagina";
$result_solicitudes = $conn->query($sql_solicitudes);
$lista_solicitudes = $result_solicitudes ? $result_solicitudes->fetch_all(MYSQLI_ASSOC) : [];

// Obtener lista de comuneros para autocomplete
$comuneros = $conn->query("SELECT id, dni, nombres, apellidos FROM usuarios WHERE rol = 'comunero' AND estado = 'activo' ORDER BY apellidos, nombres")->fetch_all(MYSQLI_ASSOC);

// Obtener datos del usuario
$stmtUser = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Propietario - Callqui Chico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            max-width: 800px;
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
        
        .form-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
        }
        
        .form-control-custom {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            color: white;
        }
        .form-control-custom:focus {
            background: rgba(0,0,0,0.4);
            border-color: #2563eb;
            color: white;
        }
        .form-control-custom::placeholder { color: #64748b; }
        
        .form-label-custom {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .info-box {
            background: rgba(37,99,235,0.15);
            border: 1px solid rgba(37,99,235,0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .info-box h6 { color: #2563eb; font-weight: 600; margin-bottom: 0.5rem; }
        .info-box p { color: white; margin: 0; font-size: 0.9rem; }
        .info-box .empty { color: #f59e0b; font-style: italic; }
        
        .btn-submit {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            width: 100%;
            font-size: 1.1rem;
        }
        
        .autocomplete-wrapper { position: relative; }
        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }
        .autocomplete-results.show { display: block; }
        .autocomplete-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: white;
        }
        .autocomplete-item:hover { background: rgba(37,99,235,0.3); }
        .autocomplete-item small { color: #64748b; display: block; }
        
        /* Table styles for solicitudes list */
        .table-solicitudes {
            width: 100%;
            color: white;
            border-collapse: collapse;
        }
        .table-solicitudes thead {
            background: rgba(0,0,0,0.3);
            color: #94a3b8;
        }
        .table-solicitudes th, .table-solicitudes td {
            padding: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            vertical-align: middle;
            font-size: 0.85rem;
        }
        .table-solicitudes tbody tr:hover {
            background: rgba(255,255,255,0.05);
            cursor: pointer;
        }
        .table-solicitudes .selected {
            background: rgba(37,99,235,0.2);
            border-left: 3px solid #2563eb;
        }
        
        .btn-doc {
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
            background: rgba(37,99,235,0.2);
            color: #2563eb;
            border: 1px solid rgba(37,99,235,0.3);
            border-radius: 4px;
            margin: 1px;
        }
        .btn-doc:hover {
            background: rgba(37,99,235,0.4);
        }
        
        .filtro-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: #94a3b8;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
        }
        .filtro-btn:hover, .filtro-btn.active {
            background: rgba(37,99,235,0.2);
            color: white;
            border-color: #2563eb;
        }
        
        .btn-cancel {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.3);
            color: #94a3b8;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
        }
        .btn-cancel:hover { color: white; background: rgba(255,255,255,0.1); }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        .back-btn:hover { color: white; }
        
        .nav-tabs {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .nav-tabs .nav-link {
            color: #94a3b8;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px 8px 0 0;
        }
        .nav-tabs .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.05);
        }
        .nav-tabs .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border: none;
        }
        
        .tab-pane {
            padding-top: 1rem;
        }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <div class="logo"><i class="bi bi-tree-fill"></i></div>
            <div class="logo-text">
                <h3>Comunidad Callqui Chico</h3>
                <small>Comité de Lotes</small>
            </div>
        </div>
    </nav>

    <div class="main-container">
        
        <a href="comite.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
        
        <div class="page-header">
            <h2><i class="bi bi-person-fill-switch me-2"></i>Cambio de Propietario</h2>
            <p>Seleccione un lote y asigne el nuevo propietario</p>
        </div>

        <!-- Pestañas -->
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-lote-tab" data-bs-toggle="tab" data-bs-target="#tab-lote" type="button">
                    <i class="bi bi-house me-1"></i> Por Lote
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-solicitud-tab" data-bs-toggle="tab" data-bs-target="#tab-solicitud" type="button">
                    <i class="bi bi-file-earmark-text me-1"></i> Desde Solicitud Pública
                </button>
            </li>
        </ul>

        <?php if ($success): ?>
        <div class="alert alert-success mb-4" style="background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.3); color: #10b981;">
            <i class="bi bi-check-circle me-2"></i><?= $success ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger mb-4" style="background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.3); color: #ef4444;">
            <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
        </div>
        <?php endif; ?>

        <div class="tab-content" id="myTabContent">
            <!-- TAB 1: Buscar por Lote -->
            <div class="tab-pane fade show active" id="tab-lote" role="tabpanel">
                <div class="form-card">
                    <form method="POST" enctype="multipart/form-data" id="formCambio">
                        <input type="hidden" name="accion" value="cambio">
                        <input type="hidden" name="lote_id" id="lote_id" value="">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">Manzana *</label>
                                <input type="text" id="buscar_manzana" class="form-control form-control-custom" 
                                       placeholder="Ej: B1, A, C" autocomplete="off">
                                <div class="autocomplete-results" id="resultados_manzana"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Lote *</label>
                                <input type="text" id="buscar_lote" class="form-control form-control-custom" 
                                       placeholder="Ej: 1, 2, 3" autocomplete="off">
                                <div class="autocomplete-results" id="resultados_lote"></div>
                            </div>
                        </div>
                        
                        <div class="info-box" id="info_lote_actual" style="display: none;">
                            <h6><i class="bi bi-house me-1"></i> Propietario Actual</h6>
                            <p id="propietario_actual">Cargando...</p>
                            <a href="#" id="btnVerHistorial" class="btn btn-sm" style="background: rgba(37,99,235,0.2); color: #2563eb; border: 1px solid rgba(37,99,235,0.3); margin-top: 0.5rem;" target="_blank">
                                <i class="bi bi-clock-history me-1"></i> Ver Historial de Transferencias
                            </a>
                        </div>
                        
                        <hr style="border-color: rgba(255,255,255,0.1); margin: 1.5rem 0;">
                        
                        <div class="mb-4">
                            <label class="form-label-custom">Buscar Nuevo Propietario (Comunero existente) *</label>
                            <input type="text" id="buscar_propietario" class="form-control form-control-custom" 
                                   placeholder="Escriba nombre o DNI del comunero..." autocomplete="off"
                                   value="<?= htmlspecialchars($preselect_nombre) ?>">
                            <div class="autocomplete-results" id="resultados_propietario"></div>
                            <input type="hidden" name="nuevo_propietario_id" id="nuevo_propietario_id" value="">
                            
                            <div class="info-box mt-2" id="info_nuevo_propietario" style="display: none;">
                                <h6><i class="bi bi-person-plus me-1"></i> Nuevo Propietario Seleccionado</h6>
                                <p id="nuevo_propietario_info">Cargando...</p>
                            </div>
                        </div>
                        
                        <!-- Opción Copropietario (Pareja) -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="es_pareja" name="es_pareja" value="1">
                                <label class="form-check-label text-white" for="es_pareja">
                                    <i class="bi bi-people me-1"></i>¿El nuevo propietario es una pareja? (Registrar copropietario)
                                </label>
                            </div>
                        </div>
                        
                        <!-- Campos de Copropietario (ocultos por defecto) -->
                        <div id="copropietario_fields" class="mb-4" style="display: none;">
                            <div class="info-box" style="background: rgba(236,72,153,0.15); border-color: rgba(236,72,153,0.3);">
                                <h6><i class="bi bi-person-heart me-1"></i>Datos del Copropietario (Esposo/a)</h6>
                                <div class="row mt-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label-custom">Nombres *</label>
                                        <input type="text" name="copropietario_nombre" id="copropietario_nombre" 
                                               class="form-control form-control-custom" placeholder="Nombres del copropietario">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label-custom">Apellidos *</label>
                                        <input type="text" name="copropietario_apellidos" id="copropietario_apellidos" 
                                               class="form-control form-control-custom" placeholder="Apellidos del copropietario">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label-custom">DNI *</label>
                                        <input type="text" name="copropietario_dni" id="copropietario_dni" 
                                               class="form-control form-control-custom" placeholder="DNI del copropietario">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label-custom">Documento de Respaldo (PDF) - Opcional</label>
                            <input type="file" name="documento" class="form-control form-control-custom" accept=".pdf">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label-custom">Observaciones</label>
                            <textarea name="observaciones" class="form-control form-control-custom" rows="2" 
                                      placeholder="Observaciones adicionales..."></textarea>
                        </div>
                        
                        <div class="d-flex gap-3">
                            <a href="comite.php" class="btn-cancel">Cancelar</a>
                            <button type="submit" class="btn-submit" id="btnRegistrar" disabled>
                                <i class="bi bi-check-lg me-2"></i>Registrar Cambio de Propietario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- TAB 2: Desde Solicitud Pública -->
            <div class="tab-pane fade" id="tab-solicitud" role="tabpanel">
                <div class="form-card">
                    <h5 class="text-white mb-3"><i class="bi bi-file-earmark-text me-2"></i>Solicitudes de Adjudicación</h5>
                    
                    <!-- Filtros -->
                    <div class="mb-3">
                        <a href="cambio_propietario.php?tab=solicitud" class="filtro-btn <?= !$estado_filtro_sol || $estado_filtro_sol === 'todas' ? 'active' : '' ?>">Todas</a>
                        <a href="cambio_propietario.php?estado_sol=pendiente&tab=solicitud" class="filtro-btn <?= $estado_filtro_sol === 'pendiente' ? 'active' : '' ?>">Pendientes</a>
                        <a href="cambio_propietario.php?estado_sol=en_revision&tab=solicitud" class="filtro-btn <?= $estado_filtro_sol === 'en_revision' ? 'active' : '' ?>">En Revisión</a>
                        <a href="cambio_propietario.php?estado_sol=aprobado&tab=solicitud" class="filtro-btn <?= $estado_filtro_sol === 'aprobado' ? 'active' : '' ?>">Aprobadas</a>
                        <a href="cambio_propietario.php?estado_sol=certificado_generado&tab=solicitud" class="filtro-btn <?= $estado_filtro_sol === 'certificado_generado' ? 'active' : '' ?>">Con Certificado</a>
                    </div>
                    
                    <!-- Lista de Solicitudes -->
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table-solicitudes">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Solicitante</th>
                                    <th>DNI</th>
                                    <th>Lote/Mz</th>
                                    <th>Docs</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($lista_solicitudes) > 0): ?>
                                    <?php foreach ($lista_solicitudes as $sol): 
                                        $docCount = 0;
                                        $docTypes = ['archivo_dni', 'archivo_constancia', 'archivo_plano', 'archivo_recibo', 'archivo_memoria', 'archivo_jurada', 'archivo_contrato'];
                                        foreach ($docTypes as $doc) {
                                            if (!empty($sol[$doc])) $docCount++;
                                        }
                                    ?>
                                    <tr onclick="seleccionarSolicitud(<?= htmlspecialchars(json_encode($sol)) ?>)" 
                                        id="sol_row_<?= $sol['id'] ?>">
                                        <td><strong><?= htmlspecialchars($sol['codigo_seguimiento'] ?? $sol['codigo'] ?? '-') ?></strong></td>
                                        <td><?= htmlspecialchars($sol['nombre']) ?></td>
                                        <td><?= htmlspecialchars($sol['dni']) ?></td>
                                        <td><?= htmlspecialchars($sol['lote']) ?>/<?= htmlspecialchars($sol['manzana'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($docCount > 0): ?>
                                                <span class="badge" style="background: rgba(37,99,235,0.3); color: #2563eb;">
                                                    <i class="bi bi-paperclip"></i> <?= $docCount ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $estados = [
                                                'pendiente' => ['label' => 'Pendiente', 'color' => 'rgba(245,158,11,0.3)', 'text' => '#f59e0b'],
                                                'en_revision' => ['label' => 'Revisión', 'color' => 'rgba(59,130,246,0.3)', 'text' => '#3b82f6'],
                                                'aprobado' => ['label' => 'Aprobado', 'color' => 'rgba(16,185,129,0.3)', 'text' => '#10b981'],
                                                'certificado_generado' => ['label' => 'Certificado', 'color' => 'rgba(139,92,246,0.3)', 'text' => '#8b5cf6'],
                                                'rechazado' => ['label' => 'Rechazado', 'color' => 'rgba(239,68,68,0.3)', 'text' => '#ef4444']
                                            ];
                                            $est = $estados[$sol['estado']] ?? ['label' => $sol['estado'], 'color' => 'rgba(100,100,100,0.3)', 'text' => '#94a3b8'];
                                            ?>
                                            <span class="badge" style="background: <?= $est['color'] ?>; color: <?= $est['text'] ?>;">
                                                <?= $est['label'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($docCount > 0): ?>
                                                <button type="button" class="btn-doc" onclick="event.stopPropagation(); verDocs(<?= htmlspecialchars(json_encode($sol)) ?>)">
                                                    <i class="bi bi-eye"></i> Ver Docs
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                            <p class="mt-2">No hay solicitudes</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <small class="text-muted">Mostrando <?= ($offset + 1) ?>-<?= min($offset + $por_pagina, $total_solicitudes) ?> de <?= $total_solicitudes ?></small>
                        <div>
                            <?php if ($pagina > 1): ?>
                                <a href="cambio_propietario.php?estado_sol=<?= $estado_filtro_sol ?>&pagina=<?= $pagina - 1 ?>&tab=solicitud" class="filtro-btn">← Anterior</a>
                            <?php endif; ?>
                            <?php if ($pagina < $total_paginas): ?>
                                <a href="cambio_propietario.php?estado_sol=<?= $estado_filtro_sol ?>&pagina=<?= $pagina + 1 ?>&tab=solicitud" class="filtro-btn">Siguiente →</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Formulario que se llena al seleccionar -->
                    <div id="formulario_solicitud" class="mt-4" style="display: none;">
                        <hr style="border-color: rgba(255,255,255,0.1);">
                        <h5 class="text-white mb-3"><i class="bi bi-person-plus me-2"></i>Nuevo Propietario (Datos del Solicitante)</h5>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="accion" value="registrar_nuevo">
                            <input type="hidden" name="solicitud_id" id="sol_id">
                            <input type="hidden" name="lote_id" id="lote_id_solicitud">
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label-custom">Manzana del Lote *</label>
                                    <input type="text" id="buscar_manzana2" name="manzana" class="form-control form-control-custom" 
                                           placeholder="Ej: B1" autocomplete="off" required>
                                    <div class="autocomplete-results" id="resultados_manzana2"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-custom">Lote *</label>
                                    <input type="text" id="buscar_lote2" name="lote" class="form-control form-control-custom" 
                                           placeholder="Ej: 1" autocomplete="off" required>
                                    <div class="autocomplete-results" id="resultados_lote2"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-custom">Propietario Actual</label>
                                    <div id="propietario_lote2" class="p-2" style="background: rgba(0,0,0,0.2); border-radius: 8px; color: #94a3b8;">
                                        Seleccione lote...
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label-custom">Nombres *</label>
                                    <input type="text" name="nombre" id="sol_nombre" class="form-control form-control-custom" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label-custom">Apellidos *</label>
                                    <input type="text" name="apellidos" id="sol_apellidos" class="form-control form-control-custom" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label-custom">DNI *</label>
                                    <input type="text" name="dni" id="sol_dni" class="form-control form-control-custom" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label-custom">Teléfono</label>
                                    <input type="text" name="telefono" class="form-control form-control-custom" placeholder="987654321">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label-custom">Correo</label>
                                    <input type="email" name="correo" class="form-control form-control-custom" placeholder="correo@example.com">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label-custom">Dirección</label>
                                    <input type="text" name="direccion" class="form-control form-control-custom" placeholder="Dirección">
                                </div>
                            </div>
                            
                            <!-- Opción Copropietario (Pareja) -->
                            <div class="mb-3 mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="es_pareja_sol" name="es_pareja" value="1">
                                    <label class="form-check-label text-white" for="es_pareja_sol">
                                        <i class="bi bi-people me-1"></i>¿El nuevo propietario es una pareja? (Registrar copropietario)
                                    </label>
                                </div>
                            </div>
                            
                            <div id="copropietario_fields_sol" class="mb-3" style="display: none;">
                                <div class="info-box" style="background: rgba(236,72,153,0.15); border-color: rgba(236,72,153,0.3);">
                                    <h6><i class="bi bi-person-heart me-1"></i>Datos del Copropietario (Esposo/a)</h6>
                                    <div class="row mt-2">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label-custom">Nombres *</label>
                                            <input type="text" name="copropietario_nombre" class="form-control form-control-custom" placeholder="Nombres">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label-custom">Apellidos *</label>
                                            <input type="text" name="copropietario_apellidos" class="form-control form-control-custom" placeholder="Apellidos">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label-custom">DNI *</label>
                                            <input type="text" name="copropietario_dni" class="form-control form-control-custom" placeholder="DNI">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label-custom">Documento de Respaldo (PDF) - Opcional</label>
                                <input type="file" name="documento" class="form-control form-control-custom" accept=".pdf">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label-custom">Observaciones</label>
                                <textarea name="observaciones" class="form-control form-control-custom" rows="2" 
                                          placeholder="Observaciones adicionales..."></textarea>
                            </div>
                            
                            <div class="d-flex gap-3">
                                <button type="button" class="btn-cancel" onclick="limpiarFormulario()">Cancelar</button>
                                <button type="submit" class="btn-submit" id="btnRegistrarNuevo">
                                    <i class="bi bi-check-lg me-2"></i>Registrar Cambio de Propietario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal para ver documentos -->
    <div class="modal fade" id="docsModal" tabindex="-1" style="z-index: 9999;">
        <div class="modal-dialog modal-xl">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title text-white">Documentos de la Solicitud</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row" id="docsList"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Datos de comuneros para autocomplete
    const comuneros = <?= json_encode($comuneros) ?>;
    
    // Variables para almacenar resultado de búsqueda
    let loteEncontrado = null;
    
    // Autocomplete Manzana
    const buscarManzana = document.getElementById('buscar_manzana');
    const resultadosManzana = document.getElementById('resultados_manzana');
    
    buscarManzana.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        if (query.length < 1) {
            resultadosManzana.classList.remove('show');
            return;
        }
        
        // Obtener todas las manzanas únicas
        fetch('buscar_lote.php?manzana=' + encodeURIComponent(query))
        .then(r => r.json())
        .then(data => {
            resultadosManzana.innerHTML = '';
            if (data.length > 0) {
                data.forEach(lote => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-item';
                    div.innerHTML = `<strong>${lote.manzana}</strong> - Sector: ${lote.sector}`;
                    div.onclick = () => {
                        buscarManzana.value = lote.manzana;
                        resultadosManzana.classList.remove('show');
                        buscarLote.value = lote.lote;
                        buscarLote.dispatchEvent(new Event('input'));
                    };
                    resultadosManzana.appendChild(div);
                });
                resultadosManzana.classList.add('show');
            } else {
                resultadosManzana.classList.remove('show');
            }
        });
    });
    
    // Autocomplete Lote
    const buscarLote = document.getElementById('buscar_lote');
    const resultadosLote = document.getElementById('resultados_lote');
    
    buscarLote.addEventListener('input', function() {
        const manzana = buscarManzana.value.trim();
        const lote = this.value.trim();
        
        if (manzana.length < 1 || lote.length < 1) {
            resultadosLote.classList.remove('show');
            limpiarInfoLote();
            return;
        }
        
        fetch('buscar_lote.php?manzana=' + encodeURIComponent(manzana) + '&lote=' + encodeURIComponent(lote))
        .then(r => r.json())
        .then(data => {
            if (data) {
                loteEncontrado = data;
                document.getElementById('lote_id').value = data.id;
                document.getElementById('info_lote_actual').style.display = 'block';
                document.getElementById('propietario_actual').innerHTML = data.propietario 
                    ? `<strong>${data.propietario}</strong>` 
                    : '<span class="empty">Sin propietario asignado</span>';
                // Actualizar enlace de historial
                document.getElementById('btnVerHistorial').href = 'historial.php?lote_id=' + data.id;
                verificarCompletado();
            } else {
                limpiarInfoLote();
            }
        });
    });
    
    // Autocomplete Nuevo Propietario
    const buscarPropietario = document.getElementById('buscar_propietario');
    const resultadosPropietario = document.getElementById('resultados_propietario');
    
    buscarPropietario.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        if (query.length < 1) {
            resultadosPropietario.classList.remove('show');
            return;
        }
        
        const filtered = comuneros.filter(c => 
            c.nombres.toLowerCase().includes(query) || 
            c.apellidos.toLowerCase().includes(query) || 
            c.dni.includes(query)
        );
        
        resultadosPropietario.innerHTML = '';
        if (filtered.length > 0) {
            filtered.forEach(c => {
                const div = document.createElement('div');
                div.className = 'autocomplete-item';
                div.innerHTML = `<strong>${c.apellidos}, ${c.nombres}</strong><small>DNI: ${c.dni}</small>`;
                div.onclick = () => {
                    document.getElementById('nuevo_propietario_id').value = c.id;
                    document.getElementById('info_nuevo_propietario').style.display = 'block';
                    document.getElementById('nuevo_propietario_info').innerHTML = `<strong>${c.apellidos}, ${c.nombres}</strong> - DNI: ${c.dni}`;
                    buscarPropietario.value = `${c.apellidos}, ${c.nombres}`;
                    resultadosPropietario.classList.remove('show');
                    verificarCompletado();
                };
                resultadosPropietario.appendChild(div);
            });
            resultadosPropietario.classList.add('show');
        } else {
            resultadosPropietario.classList.remove('show');
        }
    });
    
    function limpiarInfoLote() {
        document.getElementById('info_lote_actual').style.display = 'none';
        document.getElementById('lote_id').value = '';
        document.getElementById('btnVerHistorial').href = '#';
        loteEncontrado = null;
        verificarCompletado();
    }
    
    function verificarCompletado() {
        const loteId = document.getElementById('lote_id').value;
        const nuevoId = document.getElementById('nuevo_propietario_id').value;
        const esPareja = document.getElementById('es_pareja').checked;
        
        let completo = loteId && nuevoId;
        
        if (esPareja) {
            const copropNombre = document.getElementById('copropietario_nombre').value;
            const copropApellidos = document.getElementById('copropietario_apellidos').value;
            const copropDni = document.getElementById('copropietario_dni').value;
            completo = completo && copropNombre && copropApellidos && copropDni;
        }
        
        document.getElementById('btnRegistrar').disabled = !completo;
    }
    
    // Toggle copropietario fields
    document.getElementById('es_pareja').addEventListener('change', function() {
        const fields = document.getElementById('copropietario_fields');
        fields.style.display = this.checked ? 'block' : 'none';
        verificarCompletado();
    });
    
    // Add validation to copropietario fields
    ['copropietario_nombre', 'copropietario_apellidos', 'copropietario_dni'].forEach(id => {
        document.getElementById(id).addEventListener('input', verificarCompletado);
    });
    
    // Pre-fill if coming from solicitud
    <?php if ($preselect_nombre): ?>
    // If name is pre-filled, simulate selection
    window.addEventListener('load', function() {
        // Try to find matching comunero
        const buscarPropietario = document.getElementById('buscar_propietario');
        if (buscarPropietario && comuneros.length > 0) {
            // Check if any comunero matches the pre-filled name
            const match = comuneros.find(c => c.nombres.toLowerCase().includes('<?= strtolower($preselect_nombre) ?>'));
            if (match) {
                document.getElementById('nuevo_propietario_id').value = match.id;
                document.getElementById('info_nuevo_propietario').style.display = 'block';
                document.getElementById('nuevo_propietario_info').innerHTML = `<strong>${match.apellidos}, ${match.nombres}</strong> - DNI: ${match.dni}`;
                buscarPropietario.value = `${match.apellidos}, ${match.nombres}`;
                verificarCompletado();
            }
        }
    });
    <?php endif; ?>
    
    // === TAB 2: Autocomplete para solicitud pública ===
    const buscarManzana2 = document.getElementById('buscar_manzana2');
    const resultadosManzana2 = document.getElementById('resultados_manzana2');
    const buscarLote2 = document.getElementById('buscar_lote2');
    const resultadosLote2 = document.getElementById('resultados_lote2');
    
    buscarManzana2.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        if (query.length < 1) {
            resultadosManzana2.classList.remove('show');
            return;
        }
        
        fetch('buscar_lote.php?manzana=' + encodeURIComponent(query))
        .then(r => r.json())
        .then(data => {
            resultadosManzana2.innerHTML = '';
            if (data.length > 0) {
                data.forEach(lote => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-item';
                    div.innerHTML = `<strong>${lote.manzana}</strong> - Sector: ${lote.sector}`;
                    div.onclick = () => {
                        buscarManzana2.value = lote.manzana;
                        resultadosManzana2.classList.remove('show');
                        buscarLote2.value = lote.lote;
                        buscarLote2.dispatchEvent(new Event('input'));
                    };
                    resultadosManzana2.appendChild(div);
                });
                resultadosManzana2.classList.add('show');
            } else {
                resultadosManzana2.classList.remove('show');
            }
        });
    });
    
    buscarLote2.addEventListener('input', function() {
        const manzana = buscarManzana2.value.trim();
        const lote = this.value.trim();
        
        if (manzana.length < 1 || lote.length < 1) {
            resultadosLote2.classList.remove('show');
            document.getElementById('propietario_lote2').textContent = 'Seleccione lote...';
            document.getElementById('lote_id_solicitud').value = '';
            return;
        }
        
        fetch('buscar_lote.php?manzana=' + encodeURIComponent(manzana) + '&lote=' + encodeURIComponent(lote))
        .then(r => r.json())
        .then(data => {
            if (data) {
                document.getElementById('lote_id_solicitud').value = data.id;
                document.getElementById('propietario_lote2').innerHTML = data.propietario 
                    ? `<strong>${data.propietario}</strong>` 
                    : '<span class="empty">Sin propietario asignado</span>';
            } else {
                document.getElementById('propietario_lote2').textContent = 'Lote no encontrado';
                document.getElementById('lote_id_solicitud').value = '';
            }
        });
    });
    
    // Validar formulario de nuevo comunero antes de enviar
    document.querySelectorAll('#tab-solicitud form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const loteId = document.getElementById('lote_id_solicitud').value;
            const nombre = document.getElementById('sol_nombre').value;
            const dni = document.getElementById('sol_dni').value;
            
            console.log('Submitting - lote_id:', loteId, 'nombre:', nombre, 'dni:', dni);
            
            if (!loteId) {
                e.preventDefault();
                alert('Debe seleccionar un lote válido');
                return;
            }
            if (!nombre || !dni) {
                e.preventDefault();
                alert('Debe completar los datos del nuevo propietario');
                return;
            }
            
            // Show loading
            document.getElementById('btnRegistrarNuevo').innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Registrando...';
        });
    });
    
    // Toggle copropietario en formulario de solicitud
    document.getElementById('es_pareja_sol').addEventListener('change', function() {
        const fields = document.getElementById('copropietario_fields_sol');
        fields.style.display = this.checked ? 'block' : 'none';
    });
    
    // Función para seleccionar una solicitud
    function seleccionarSolicitud(sol) {
        // Quitar selección anterior
        document.querySelectorAll('.table-solicitudes tbody tr').forEach(row => row.classList.remove('selected'));
        
        // Marcar esta fila
        document.getElementById('sol_row_' + sol.id).classList.add('selected');
        
        // Mostrar formulario
        document.getElementById('formulario_solicitud').style.display = 'block';
        
        // Llenar datos
        document.getElementById('sol_id').value = sol.id;
        document.getElementById('sol_nombre').value = sol.nombre || '';
        document.getElementById('sol_apellidos').value = '';
        document.getElementById('sol_dni').value = sol.dni || '';
        
        // Pre-llenar manzana y lote
        document.getElementById('buscar_manzana2').value = sol.manzana || '';
        document.getElementById('buscar_lote2').value = sol.lote || '';
        
        // Buscar automáticamente el lote
        if (sol.manzana && sol.lote) {
            setTimeout(() => {
                buscarLote2.dispatchEvent(new Event('input'));
            }, 500);
        }
        
        // Scroll al formulario
        document.getElementById('formulario_solicitud').scrollIntoView({ behavior: 'smooth' });
    }
    
    // Función para ver documentos
    function verDocs(sol) {
        const docsList = document.getElementById('docsList');
        docsList.innerHTML = '';
        
        const docTypes = {
            'archivo_dni': 'DNI del Solicitante',
            'archivo_constancia': 'Constancia de Adjudicación',
            'archivo_plano': 'Plano de Ubicación',
            'archivo_recibo': 'Recibo de Pago',
            'archivo_memoria': 'Memoria Descriptiva',
            'archivo_jurada': 'Declaración Jurada',
            'archivo_contrato': 'Contrato de Transferencia'
        };
        
        let tieneDocs = false;
        
        for (const [field, label] of Object.entries(docTypes)) {
            if (sol[field]) {
                tieneDocs = true;
                const col = document.createElement('div');
                col.className = 'col-md-4 col-sm-6 mb-3';
                col.innerHTML = `
                    <div class="p-3" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
                        <h6 class="text-white mb-2">${label}</h6>
                        <button class="btn btn-sm w-100" style="background: rgba(37,99,235,0.2); color: #2563eb; border: 1px solid rgba(37,99,235,0.3);"
                                onclick="showPDF('../../publico/uploads/${sol[field]}', '${label}')">
                            <i class="bi bi-eye me-1"></i> Ver PDF
                        </button>
                    </div>
                `;
                docsList.appendChild(col);
            }
        }
        
        if (!tieneDocs) {
            docsList.innerHTML = '<div class="col-12 text-center text-muted py-4">No hay documentos disponibles</div>';
        }
        
        var docsModal = new bootstrap.Modal(document.getElementById('docsModal'));
        docsModal.show();
    }
    
    // Función para limpiar formulario
    function limpiarFormulario() {
        document.getElementById('formulario_solicitud').style.display = 'none';
        document.querySelectorAll('.table-solicitudes tbody tr').forEach(row => row.classList.remove('selected'));
        document.getElementById('sol_id').value = '';
        document.getElementById('sol_nombre').value = '';
        document.getElementById('sol_apellidos').value = '';
        document.getElementById('sol_dni').value = '';
        document.getElementById('buscar_manzana2').value = '';
        document.getElementById('buscar_lote2').value = '';
        document.getElementById('lote_id_solicitud').value = '';
        document.getElementById('propietario_lote2').textContent = 'Seleccione lote...';
    }
    
    // Función para ver PDF (reutilizada de solicitudes.php)
    function showPDF(url, title) {
        document.getElementById('pdfModalTitle').textContent = title;
        document.getElementById('pdfFrame').src = url;
        var pdfModal = new bootstrap.Modal(document.getElementById('pdfModal'));
        pdfModal.show();
    }
    
    // Cerrar autocomplete al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.autocomplete-wrapper') && !e.target.closest('.autocomplete-results')) {
            document.querySelectorAll('.autocomplete-results').forEach(el => el.classList.remove('show'));
        }
    });
    </script>
    
    <!-- Modal para ver PDF -->
    <div class="modal fade" id="pdfModal" tabindex="-1" style="z-index: 10000;">
        <div class="modal-dialog modal-xl">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title text-white" id="pdfModalTitle">Documento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="pdfFrame" src="" style="width: 100%; height: 70vh; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>