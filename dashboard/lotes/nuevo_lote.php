<?php
require_once "../../config/conexion.php";
session_start();

$usuario_id_sesion = $_SESSION['usuario_id'] ?? null; 
if (!$usuario_id_sesion) {
    header("Location: ../login.php");
    exit;
}

$mensaje = "";
$comunero = null;

/* 1. OBTENER LOTE PARA EDITAR */
$lote_editar = null;
if(isset($_GET['editar_lote'])){
    $id_editar = $_GET['editar_lote'];
    $result = $conn->query("SELECT * FROM lotes WHERE id='$id_editar'");
    if($result->num_rows > 0){
        $lote_editar = $result->fetch_assoc();
    }
}

/* 3. PROCESAR FORMULARIO (GUARDAR O ACTUALIZAR) */
if(isset($_POST['accion_lote'])){
    $u_id = $_POST['usuario_id'];
    $lote = $_POST['lote'];
    $manzana = $_POST['manzana'];
    $sector = $_POST['sector'];
    $area_m2 = $_POST['area_m2'];
    $area_excedente_m2 = $_POST['area_excedente_m2'];
    $observacion = $_POST['observacion'];
    $propietario = $_POST['propietario'];

    if($_POST['accion_lote'] == "actualizar"){
        $id_lote = $_POST['id_lote'];
        $conn->query("UPDATE lotes SET lote='$lote', manzana='$manzana', sector='$sector', area_m2='$area_m2', area_excedente_m2='$area_excedente_m2', observacion='$observacion' WHERE id='$id_lote'");
        $mensaje = "Lote actualizado correctamente";
        $_GET['u_ref'] = $u_id; // Mantener comunero visible
    } else {
        // VERIFICAR DUPLICADOS
        $verificar = $conn->query("SELECT id FROM lotes WHERE lote='$lote' AND manzana='$manzana' AND sector='$sector'");
        if($verificar->num_rows > 0){
            $mensaje = "⚠ Error: El lote $lote de la Mz $manzana ya está registrado.";
        } else {
            $conn->query("INSERT INTO lotes (usuario_id, propietario, lote, sector, manzana, area_m2, area_excedente_m2, estado, observacion, created_at) 
                          VALUES ('$u_id', '$propietario', '$lote', '$sector', '$manzana', '$area_m2', '$area_excedente_m2', 'OCUPADO', '$observacion', NOW())");
            $mensaje = "Lote registrado correctamente";
        }
    }
}

/* 4. BUSCAR O RECARGAR COMUNERO */
if(isset($_POST['buscar']) || isset($_POST['usuario_id']) || isset($_GET['editar_lote'])){
    $id_para_buscar = $_POST['usuario_id'] ?? ($_GET['u_ref'] ?? null);
    
    if(isset($_POST['buscar'])){
        $buscar = $_POST['buscar_texto'];
        $sql = "SELECT * FROM usuarios WHERE dni='$buscar' OR CONCAT(nombres,' ',apellidos) LIKE '%$buscar%' LIMIT 1";
    } else {
        $sql = "SELECT * FROM usuarios WHERE id='$id_para_buscar'";
    }
    
    $res_c = $conn->query($sql);
    $comunero = $res_c->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Lotes | Comunidad Callqui Chico</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0a2b3c;
            --primary-dark: #06212e;
            --primary-light: #1e4a6a;
            --accent: #c9a45b;
            --accent-light: #dbb67b;
            --accent-dark: #a88642;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark-bg: #0a1928;
            --dark-card: #0f2740;
            --text-light: #f0f5fa;
            --text-muted: #94a3b8;
            --text-dark: #1e293b;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            color: var(--text-dark);
            position: relative;
            overflow-x: hidden;
        }

        /* Barra de navegación */
        .nav-bar {
            background: white;
            box-shadow: var(--shadow-md);
            padding: 1rem 0;
            border-bottom: 3px solid var(--accent);
            margin-bottom: 2rem;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
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
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
        }

        .logo-text h3 {
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
            color: var(--primary);
        }

        .logo-text small {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        .btn-nav {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-nav:hover {
            background: var(--accent);
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Contenedor principal */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem 2rem;
        }

        /* Header de página */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.3rem;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        /* Buscador */
        .search-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
        }

        .search-input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(201,164,91,0.2);
            outline: none;
        }

        .btn-search {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0 2rem;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-search:hover {
            background: var(--accent);
            color: var(--primary);
        }

        /* Alertas */
        .alert-custom {
            background: white;
            border-left: 4px solid;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
        }

        .alert-success {
            border-color: var(--success);
            color: var(--success);
        }

        .alert-warning {
            border-color: var(--warning);
            color: var(--warning);
        }

        /* Ficha del comunero */
        .ficha-card {
            background: white;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .ficha-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ficha-header h5 {
            color: white;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .btn-print {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            background: var(--accent);
            color: var(--primary);
        }

        .ficha-body {
            padding: 2rem;
        }

        /* Stats del comunero */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-mini {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.2rem;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .stat-mini .stat-label {
            color: var(--text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            margin-bottom: 0.3rem;
        }

        .stat-mini .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-mini .stat-sub {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        /* Tabla de lotes - CORREGIDO: texto oscuro sobre fondo blanco */
        .table-container {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .section-title i {
            color: var(--accent);
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: #e9ecef;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            padding: 1rem;
            border-bottom: 2px solid var(--accent);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
            color: var(--text-dark); /* Texto oscuro para visibilidad */
            background: white;
        }

        .table tbody tr:hover td {
            background: #f8fafc;
        }

        .lote-numero {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.2rem;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            background: white;
            color: var(--text-muted);
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .btn-edit:hover {
            background: var(--warning);
            color: white;
            border-color: var(--warning);
        }

        .btn-delete:hover {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
        }

        /* Formulario */
        .form-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid #e2e8f0;
        }

        .form-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .form-title i {
            color: var(--accent);
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.7rem 1rem;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(201,164,91,0.2);
            outline: none;
        }

        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }

        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: var(--accent);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #f1f5f9;
            color: var(--text-muted);
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            color: var(--text-dark);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            .ficha-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                background: white;
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.8rem;
                border-bottom: 1px solid #e2e8f0;
            }

            .table tbody td:last-child {
                border-bottom: none;
            }

            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--text-muted);
            }
        }

        /* Estilos de Impresión */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                background: white !important;
                font-family: 'Arial', sans-serif !important;
                font-size: 11pt;
                line-height: 1.4;
                margin: 0;
                padding: 0;
            }

            .nav-bar, .search-card, .form-section, .btn-action, .btn-print,
            .ficha-header, .page-header, .alert-custom, .section-title {
                display: none !important;
            }

            .ficha-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .ficha-body {
                padding: 0 !important;
            }

            .stats-row {
                display: none !important;
            }

            /* CONSTANCIA OFICIAL - Header */
            .constancia-header {
                display: block !important;
                text-align: center;
                padding: 15px 0;
                border-bottom: 3px solid #c9a45c;
                margin-bottom: 20px;
            }

            .constancia-logo {
                width: 60px;
                height: 60px;
                object-fit: contain;
                margin-bottom: 10px;
            }

            .constancia-title {
                font-size: 16pt;
                font-weight: 700;
                color: #0a2b3c;
                margin: 5px 0 0;
                text-transform: uppercase;
                letter-spacing: 2px;
            }

            .constancia-subtitle {
                font-size: 10pt;
                color: #666;
                margin: 3px 0 0;
            }

            .constancia-doc-title {
                font-size: 14pt;
                font-weight: 700;
                color: #0a2b3c;
                text-align: center;
                padding: 10px;
                background: #f5f5f5;
                border: 1px solid #0a2b3c;
                margin-bottom: 20px;
            }

            /* Datos del comunero */
            .comunero-info {
                display: flex !important;
                justify-content: space-between;
                margin-bottom: 15px;
                padding: 10px;
                border: 1px solid #ddd;
                background: #fafafa;
            }

            .comunero-info .info-item {
                text-align: center;
            }

            .comunero-info .info-label {
                font-size: 8pt;
                color: #666;
                text-transform: uppercase;
            }

            .comunero-info .info-value {
                font-size: 11pt;
                font-weight: 700;
                color: #0a2b3c;
            }

            /* Tabla formal */
            .table-container {
                border: 2px solid #0a2b3c !important;
                margin-bottom: 20px;
            }

            .table {
                width: 100%;
                border-collapse: collapse;
                font-size: 10pt;
            }

            .table thead th {
                background: #0a2b3c !important;
                color: white !important;
                padding: 8px 5px;
                text-align: center;
                font-weight: 600;
                font-size: 9pt;
                text-transform: uppercase;
            }

            .table tbody td {
                padding: 6px 5px;
                border: 1px solid #ddd;
                text-align: center;
            }

            .table tbody tr:nth-child(even) {
                background: #f9f9f9;
            }

            /* Footer */
            .constancia-footer {
                display: flex !important;
                justify-content: space-between;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #333;
            }

            .firma-box {
                width: 30%;
                text-align: center;
            }

            .firma-line {
                border-top: 1px solid #333;
                padding-top: 5px;
                margin-top: 40px;
                font-weight: 600;
                font-size: 10pt;
            }

            .firma-selo {
                font-size: 8pt;
                color: #888;
                margin-top: 5px;
            }

            .fecha-box {
                text-align: center;
                font-size: 10pt;
            }

            /* Ocultar columna acciones */
            .col-acciones, td:nth-child(7), th:nth-child(7) {
                display: none !important;
            }
        }

        /* Constancia - no visible en pantalla */
        .constancia-header, .constancia-doc-title, .comunero-info, 
        .constancia-footer {
            display: none;
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
                    <h3>Callqui Chico</h3>
                    <small>Catastro de Lotes</small>
                </div>
            </div>
            <a href="../secretario/secretario.php" class="btn-nav">
                <i class="bi bi-grid"></i>
                <span>VOLVER</span>
            </a>
        </div>
    </div>

    <div class="main-container">

        <!-- Header de página -->
        <div class="page-header">
            <h1>Gestión de Lotes</h1>
            <p>Administración de terrenos y propiedades comunales</p>
        </div>

        <!-- Buscador -->
        <div class="search-card">
            <form method="POST" class="search-form">
                <input type="text" name="buscar_texto" class="search-input" 
                       placeholder="Buscar por DNI, nombres o apellidos del comunero...">
                <button class="btn-search" name="buscar">
                    <i class="bi bi-search"></i>
                    <span>Buscar</span>
                </button>
            </form>
        </div>

        <!-- Mensaje de alerta -->
        <?php if($mensaje): ?>
            <div class="alert-custom alert-<?= strpos($mensaje, 'Error') !== false ? 'warning' : 'success' ?>">
                <i class="bi bi-<?= strpos($mensaje, 'Error') !== false ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                <span><?= $mensaje ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Ficha del comunero -->
        <?php if($comunero): ?>
            <div class="ficha-card">
                
                <div class="ficha-header">
                    <h5>
                        <i class="bi bi-person-badge"></i>
                        <?= htmlspecialchars($comunero['nombres']." ".$comunero['apellidos']) ?>
                    </h5>
                    <button onclick="window.print()" class="btn-print">
                        <i class="bi bi-printer"></i>
                        <span>Imprimir</span>
                    </button>
                </div>

                <!-- Print Header (visible only when printing) -->
                <?php
                    $id_c = $comunero['id'];
                    $lotes_res = $conn->query("SELECT * FROM lotes WHERE usuario_id='$id_c'");
                    $total = $lotes_res->num_rows;
                    $m2 = 0;
                    while($l = $lotes_res->fetch_assoc()) { 
                        $m2 += $l['area_m2']; 
                    }
                    $lotes_res->data_seek(0);
                ?>

                <div class="constancia-header">
                    <img src="../../assets/img/logo_callqui.png" alt="Logo" class="constancia-logo">
                    <h1 class="constancia-title">Comunidad Campesina Callqui Chico</h1>
                    <p class="constancia-subtitle">Distrito de Platería - Provincia de Chucuito - Región Puno</p>
                </div>

                <div class="constancia-doc-title">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    CONSTANCIA DE PROPIEDAD
                </div>

                <!-- Datos del comunero para impresión -->
                <div class="comunero-info">
                    <div class="info-item">
                        <div class="info-label">Titular</div>
                        <div class="info-value"><?= htmlspecialchars($comunero['apellidos'].', '.$comunero['nombres']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">DNI</div>
                        <div class="info-value"><?= $comunero['dni'] ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Padrón</div>
                        <div class="info-value"><?= $comunero['padron'] ?? 'S/N' ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total Lotes</div>
                        <div class="info-value"><?= $total ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Área Total</div>
                        <div class="info-value"><?= number_format($m2, 2) ?> m²</div>
                    </div>
                </div>

<div class="ficha-body">

                    <!-- Estadísticas -->
                    <div class="stats-row">
                        <div class="stat-mini">
                            <div class="stat-label">DNI</div>
                            <div class="stat-value"><?= $comunero['dni'] ?></div>
                            <div class="stat-sub">Documento</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-label">Rol</div>
                            <div class="stat-value"><?= ucfirst($comunero['rol']) ?></div>
                            <div class="stat-sub">Cargo</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-label">Lotes</div>
                            <div class="stat-value"><?= $total ?></div>
                            <div class="stat-sub"><?= number_format($m2, 2) ?> m²</div>
                        </div>
                    </div>

                    <!-- Tabla de lotes - AHORA CON TEXTO VISIBLE -->
                    <div class="table-container">
                        <div class="section-title">
                            <i class="bi bi-grid-3x3"></i>
                            <span>Lotes Registrados</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Lote</th>
                                        <th>Manzana</th>
                                        <th>Sector</th>
                                        <th>Área (m²)</th>
                                        <th>Excedente</th>
                                        <th>Observación</th>
                                        <th class="text-end col-acciones">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($total > 0): $i = 1; ?>
                                        <?php while($row = $lotes_res->fetch_assoc()): ?>
                                        <tr>
                                            <td data-label="#"><?= $i++ ?></td>
                                            <td data-label="Lote">
                                                <span class="lote-numero"><?= htmlspecialchars($row['lote']) ?></span>
                                            </td>
                                            <td data-label="Manzana"><?= htmlspecialchars($row['manzana']) ?></td>
                                            <td data-label="Sector"><?= htmlspecialchars($row['sector']) ?></td>
                                            <td data-label="Área"><?= number_format($row['area_m2'], 2) ?></td>
                                            <td data-label="Excedente">
                                                <?php if($row['area_excedente_m2'] > 0): ?>
                                                    <span class="text-success">+ <?= number_format($row['area_excedente_m2'], 2) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Observación">
                                                <small><?= htmlspecialchars($row['observacion'] ?: '-') ?></small>
                                            </td>
                                            <td class="text-end col-acciones" data-label="Acciones">
                                                <a href="?editar_lote=<?= $row['id'] ?>&u_ref=<?= $id_c ?>" 
                                                   class="btn-action btn-edit" 
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="bi bi-inbox" style="font-size: 2rem; color: #94a3b8;"></i>
                                                <p class="text-muted mt-2">No hay lotes registrados</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Print Footer - Constancia formal -->
                    <div class="constancia-footer">
                        <div class="firma-box">
                            <div class="firma-selo">SECRETARIO GENERAL</div>
                            <div class="firma-line">Firma y Sellos</div>
                        </div>
                        <div class="fecha-box">
                            <p><strong>Fecha de Emisión:</strong></p>
                            <p><?= date('d') . ' de ' . ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][date('n')-1] . ' de ' . date('Y') ?></p>
                        </div>
                        <div class="firma-box">
                            <div class="firma-selo">PRESIDENTE DE COMUNIDAD</div>
                            <div class="firma-line">Firma y Sellos</div>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <div class="form-section">
                        <div class="form-title">
                            <i class="bi bi-<?= $lote_editar ? 'pencil' : 'plus-circle' ?>"></i>
                            <span><?= $lote_editar ? 'Editar Lote' : 'Agregar Nuevo Lote' ?></span>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="usuario_id" value="<?= $comunero['id'] ?>">
                            <input type="hidden" name="propietario" value="<?= htmlspecialchars($comunero['nombres'].' '.$comunero['apellidos']) ?>">
                            <?php if($lote_editar): ?>
                                <input type="hidden" name="id_lote" value="<?= $lote_editar['id'] ?>">
                                <input type="hidden" name="accion_lote" value="actualizar">
                            <?php else: ?>
                                <input type="hidden" name="accion_lote" value="guardar">
                            <?php endif; ?>

                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Lote</label>
                                    <input type="text" name="lote" class="form-control" 
                                           value="<?= $lote_editar['lote'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Manzana</label>
                                    <input type="text" name="manzana" class="form-control" 
                                           value="<?= $lote_editar['manzana'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Sector</label>
                                    <input type="text" name="sector" class="form-control" 
                                           value="<?= $lote_editar['sector'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Área m²</label>
                                    <input type="number" step="0.01" name="area_m2" class="form-control" 
                                           value="<?= $lote_editar['area_m2'] ?? '' ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Excedente m²</label>
                                    <input type="number" step="0.01" name="area_excedente_m2" class="form-control" 
                                           value="<?= $lote_editar['area_excedente_m2'] ?? '' ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Observación</label>
                                    <textarea name="observacion" class="form-control"><?= $lote_editar['observacion'] ?? '' ?></textarea>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn-save">
                                            <i class="bi bi-<?= $lote_editar ? 'check' : 'save' ?>"></i>
                                            <?= $lote_editar ? 'Actualizar' : 'Registrar' ?>
                                        </button>
                                        
                                        <?php if($lote_editar): ?>
                                            <a href="?u_ref=<?= $comunero['id'] ?>" class="btn-cancel">
                                                <i class="bi bi-x"></i>
                                                Cancelar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Auto-dismiss alert después de 5 segundos
        setTimeout(() => {
            document.querySelectorAll('.alert-custom').forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>

</body>
</html>
