<?php
session_start();
require_once "../config/database.php";

$conn = getDB();
$success = "";
$busqueda = null;

function subirArchivo($inputName) {
    if (isset($_FILES[$inputName]) && !empty($_FILES[$inputName]['name'])) {
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES[$inputName]['type'], $allowed_types)) {
            error_log("Tipo no permitido: " . $_FILES[$inputName]['type']);
            return "";
        }
        if ($_FILES[$inputName]['size'] > 5 * 1024 * 1024) {
            error_log("Archivo muy grande: " . $_FILES[$inputName]['size']);
            return "";
        }
        
        $upload_dir = __DIR__ . "/uploads/";
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                error_log("No se pudo crear directorio uploads");
                return "";
            }
        }
        $archivo = time() . "_" . basename($_FILES[$inputName]['name']);
        if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $upload_dir . $archivo)) {
            error_log("Archivo subido: $inputName -> $archivo");
            return $archivo;
        } else {
            error_log("Error al mover archivo: $inputName");
            return "";
        }
    }
    return "";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si es registro o pago
    if (isset($_POST['registrar_pago']) && $_POST['registrar_pago'] == '1') {
        // Procesar pago
        $id_solicitud = intval($_POST['id_solicitud']);
        $numero_propietarios = intval($_POST['numero_propietarios']);
        $monto = 50 + ($numero_propietarios - 1) * 250;
        $medio_pago = $_POST['medio_pago'];
        $numero_operacion = $_POST['numero_operacion'] ?? '';
        
        $comprobante = '';
        if (isset($_FILES['comprobante']) && !empty($_FILES['comprobante']['name'])) {
            $upload_dir = __DIR__ . '/uploads/pagos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $comprobante = time() . '_' . basename($_FILES['comprobante']['name']);
            move_uploaded_file($_FILES['comprobante']['tmp_name'], $upload_dir . $comprobante);
        }
        
        $codigo_pago = 'CCP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("INSERT INTO pagos (codigo_pago, id_solicitud, numero_propietarios, monto, medio_pago, numero_operacion, comprobante, estado, fecha_pago) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())");
        $stmt->bind_param("siissss", $codigo_pago, $id_solicitud, $numero_propietarios, $monto, $medio_pago, $numero_operacion, $comprobante);
        $stmt->execute();
        
        $pago_id = $conn->insert_id;
        $stmt->close();
        
        $conn->query("UPDATE adjudicaciones SET pago_id = $pago_id, estado_pago = 'pendiente' WHERE id = $id_solicitud");
        
        $success = "Pago registrado exitosamente. Código de pago: <strong class='font-mono'>$codigo_pago</strong>. Monto: <strong>S/ $monto</strong>";
    } else {
        // Procesar registro de solicitud
        $año = date('Y');
        $codigo_seguimiento = 'ADJ-' . $año . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $codigo = strtoupper(uniqid("ADJ-"));

        $nombre = $_POST['nombre'] ?? '';
        $dni = $_POST['dni'] ?? '';
        $lote = $_POST['lote'] ?? '';
        $manzana = $_POST['manzana'] ?? '';
        $sector = $_POST['sector'] ?? '';
        $area_m2 = (int)$_POST['area_m2'];
        $estado = "pendiente";
        $expediente = $_POST['expediente'] ?? '';
        $fecha_solicitud = date("Y-m-d H:i:s");
        
        // Linderos
        $lindero_frente = $_POST['lindero_frente'] ?? '';
        $lindero_fondo = $_POST['lindero_fondo'] ?? '';
        $lindero_derecha = $_POST['lindero_derecha'] ?? '';
        $lindero_izquierda = $_POST['lindero_izquierda'] ?? '';
        $metros_frente = $_POST['metros_frente'] ?? '';
        $metros_fondo = $_POST['metros_fondo'] ?? '';
        $metros_derecha = $_POST['metros_derecha'] ?? '';
        $metros_izquierda = $_POST['metros_izquierda'] ?? '';

        $archivo_dni = subirArchivo('archivo_dni');
        $archivo_constancia = subirArchivo('archivo_constancia');
        $archivo_plano = subirArchivo('archivo_plano');
        $archivo_recibo = subirArchivo('archivo_recibo');
        $archivo_memoria = subirArchivo('archivo_memoria');
        $archivo_jurada = subirArchivo('archivo_jurada');
        $archivo_contrato = subirArchivo('archivo_contrato');

        $stmt = $conn->prepare("INSERT INTO adjudicaciones
        (codigo_seguimiento,codigo,nombre,dni,lote,manzana,sector,area_m2,estado,expediente,fecha_solicitud,
        archivo_dni,archivo_constancia,archivo_plano,archivo_recibo,archivo_memoria,archivo_jurada,archivo_contrato,
        lindero_frente,lindero_fondo,lindero_derecha,lindero_izquierda,
        metros_frente,metros_fondo,metros_derecha,metros_izquierda)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $params = [
            $codigo_seguimiento, $codigo, $nombre, $dni, $lote, $manzana, $sector, 
            $area_m2, $estado, $expediente, $fecha_solicitud,
            $archivo_dni, $archivo_constancia, $archivo_plano, $archivo_recibo, 
            $archivo_memoria, $archivo_jurada, $archivo_contrato,
            $lindero_frente, $lindero_fondo, $lindero_derecha, $lindero_izquierda,
            $metros_frente, $metros_fondo, $metros_derecha, $metros_izquierda
        ];
        
        $tipos = str_repeat('s', count($params));
        $tipos[7] = 'i'; // area_m2 es integer
        
        $stmt->bind_param($tipos, ...$params);

        $stmt->execute();
        $id_solicitud = $conn->insert_id;
        $stmt->close();

        // Calcular monto inicial
        $num_propietarios = isset($_POST['numero_propietarios']) ? intval($_POST['numero_propietarios']) : 1;
        $monto_inicial = 50 + ($num_propietarios - 1) * 250;
        
        $success = "Registro exitoso. Código de seguimiento: <strong class='font-mono'>$codigo_seguimiento</strong><br>
        <strong>Monto a pagar: S/ $monto_inicial</strong> (por $num_propietarios propietario(s))<br>
        <a href='#pago-".$id_solicitud."' class='btn btn-sm btn-warning mt-2'>Registrar Pago Ahora</a>";
        $_SESSION['ultimo_id'] = $id_solicitud;
    }
}

if (isset($_GET['buscar_codigo'])) {
    $codigoBuscar = $_GET['buscar_codigo'];
    $stmt = $conn->prepare("SELECT * FROM adjudicaciones WHERE codigo_seguimiento = ? OR codigo = ? LIMIT 1");
    $stmt->bind_param("ss", $codigoBuscar, $codigoBuscar);
    $stmt->execute();
    $result = $stmt->get_result();
    $busqueda = $result->fetch_assoc();
    $stmt->close();
}

function getEstadoLabel($estado) {
    $labels = [
        'pendiente' => 'Pendiente',
        'en_revision' => 'En Revisión',
        'aprobado' => 'Aprobado',
        'aprobado_total' => 'Aprobado Total',
        'certificado_generado' => 'Certificado Generado',
        'rechazado' => 'Rechazado'
    ];
    return $labels[$estado] ?? $estado;
}

function getEstadoClass($estado) {
    $classes = [
        'pendiente' => 'bg-warning',
        'en_revision' => 'bg-info',
        'aprobado' => 'bg-success',
        'aprobado_total' => 'bg-success',
        'certificado_generado' => 'bg-primary',
        'rechazado' => 'bg-danger'
    ];
    return $classes[$estado] ?? 'bg-secondary';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nueva Adjudicación | Comunidad Callqui Chico</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #0f172a;
    --primary-light: #1e293b;
    --accent: #c9a45c;
    --accent-hover: #dbb67b;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --text-light: #f8fafc;
    --text-muted: #94a3b8;
    --border: rgba(255,255,255,0.1);
}

* { box-sizing: border-box; }

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    color: var(--text-light);
    min-height: 100vh;
    line-height: 1.6;
}

body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: url('../img/fondo_callqui.jpg') center/cover no-repeat;
    opacity: 0.04;
    z-index: -1;
    pointer-events: none;
}

/* Header */
.header-section {
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(20px);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 1.5rem 2rem;
}

.logo-text {
    font-size: 1.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--accent), var(--accent-hover));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Cards */
.form-card {
    background: rgba(255,255,255,0.04);
    backdrop-filter: blur(20px);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 2rem;
    margin-top: 1.5rem;
}

.form-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.form-title i { color: var(--accent); }

/* Inputs */
.form-control, .form-select {
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--border);
    color: var(--text-light);
    border-radius: 12px;
    padding: 0.875rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    background: rgba(255,255,255,0.1);
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(201,164,92,0.15);
    color: var(--text-light);
}

.form-control::placeholder { color: var(--text-muted); }

.form-label {
    font-weight: 500;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    color: var(--text-muted);
}

/* File Input */
.file-upload {
    position: relative;
    border: 2px dashed var(--border);
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.file-upload:hover {
    border-color: var(--accent);
    background: rgba(201,164,92,0.05);
}

.file-upload input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.file-upload i {
    font-size: 2rem;
    color: var(--accent);
    margin-bottom: 0.5rem;
}

.file-upload p {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.875rem;
}

.file-upload p.seleccionado {
    color: #10b981;
    font-weight: 600;
}

/* Buttons */
.btn-primary-custom {
    background: linear-gradient(135deg, var(--accent), #a88642);
    border: none;
    color: #0f172a;
    font-weight: 600;
    padding: 1rem 2rem;
    border-radius: 14px;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(201,164,92,0.3);
    background: linear-gradient(135deg, var(--accent-hover), var(--accent));
}

/* Alerts */
.alert-custom {
    background: rgba(16,185,129,0.1);
    border: 1px solid rgba(16,185,129,0.3);
    border-radius: 16px;
    padding: 1.25rem;
    color: var(--success);
}

.alert-custom strong {
    font-family: 'Courier New', monospace;
    background: rgba(255,255,255,0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    display: inline-block;
    margin-top: 0.5rem;
}

/* Navigation Tabs */
.nav-pills-custom .nav-link {
    color: var(--text-muted);
    padding: 0.875rem 1.5rem;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-pills-custom .nav-link:hover {
    color: var(--text-light);
    background: rgba(255,255,255,0.05);
}

.nav-pills-custom .nav-link.active {
    background: linear-gradient(135deg, var(--accent), #a88642);
    color: #0f172a;
}

/* Section titles */
.section-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, rgba(201,164,92,0.2), rgba(201,164,92,0.1));
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent);
    font-size: 1.25rem;
}

/* Divider */
.divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--border), transparent);
    margin: 2rem 0;
}
</style>
</head>

<body>

<div class="container py-5">
    <!-- Header -->
    <div class="header-section d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="logo-text">
                <i class="bi bi-geo-alt-fill me-2"></i>
                Comunidad Campesina Callqui Chico
            </div>
            <small class="text-white-50">Sistema de Adjudicación de Terrenos</small>
        </div>
        <a href="../index.html" class="btn btn-outline-light btn-sm rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Volver al Inicio
        </a>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills-custom justify-content-center gap-2 mt-4" id="navTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="nueva-tab" data-bs-toggle="pill" data-bs-target="#nueva" type="button" role="tab">
                <i class="bi bi-plus-circle me-2"></i>Nueva Solicitud
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="consulta-tab" data-bs-toggle="pill" data-bs-target="#consulta" type="button" role="tab">
                <i class="bi bi-search me-2"></i>Consultar Estado
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="navTabsContent">
        
        <!-- Nueva Solicitud -->
        <div class="tab-pane fade show active" id="nueva" role="tabpanel">
            <div class="form-card">
                <?php if($success): ?>
                <div class="alert-custom mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= $success ?>
                    <?php if(isset($_SESSION['ultimo_id'])):
                    $config_pagos = $conn->query("SELECT * FROM config_pagos WHERE id = 1")->fetch_assoc();
                    $id_sol = $_SESSION['ultimo_id'];
                    $codigo_pago = 'CCP-' . date('Y') . '-' . str_pad($id_sol, 4, '0', STR_PAD_LEFT);
                    ?>
                    <div class="mt-4 p-4 bg-dark bg-opacity-50 rounded border border-warning">
                        <h5 class="text-warning mb-3"><i class="bi bi-bank me-2"></i>Datos para Realizar el Pago</h5>
                        <p class="text-white-50 small mb-3">Realice su pago utilizando una de las siguientes opciones y luego registre su comprobante:</p>
                        
                        <div class="row g-3">
                            <?php if (!empty($config_pagos['numero_yape'])): ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-dark bg-opacity-30 rounded text-center">
                                    <i class="bi bi-phone text-warning" style="font-size: 2rem;"></i>
                                    <h6 class="mt-2 text-white">Yape</h6>
                                    <p class="text-warning fw-bold mb-0" style="font-size: 1.25rem;"><?= htmlspecialchars($config_pagos['numero_yape']) ?></p>
                                    <?php if (!empty($config_pagos['qr_yape'])): ?>
                                    <img src="uploads/qr/<?= htmlspecialchars($config_pagos['qr_yape']) ?>" alt="QR Yape" class="img-fluid mt-2" style="max-width: 120px; border-radius: 8px;">
                                    <p class="text-muted small mt-1">Escanea con tu cámara</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($config_pagos['cuenta_banco'])): ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-dark bg-opacity-30 rounded text-center">
                                    <i class="bi bi-bank text-info" style="font-size: 2rem;"></i>
                                    <h6 class="mt-2 text-white"><?= htmlspecialchars($config_pagos['nombre_banco'] ?? 'Banco') ?></h6>
                                    <p class="text-info fw-bold mb-0" style="font-size: 1.1rem;"><?= htmlspecialchars($config_pagos['cuenta_banco']) ?></p>
                                    <p class="text-muted small mb-0">Titular: <?= htmlspecialchars($config_pagos['titular_cuenta'] ?? '') ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3 p-2 bg-warning bg-opacity-25 rounded text-center">
                            <strong class="text-warning">Código de Pago:</strong>
                            <span class="text-white fw-bold" style="font-size: 1.25rem;"><?= $codigo_pago ?></span>
                            <p class="text-white-50 small mb-0 mt-1">Incluya este código al realizar la transferencia</p>
                        </div>
                        
                        <?php if (!empty($config_pagos['instrucciones'])): ?>
                        <div class="mt-3 p-2 bg-secondary bg-opacity-25 rounded">
                            <small class="text-white-50"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($config_pagos['instrucciones']) ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <hr class="border-secondary">
                        
                        <h6 class="text-success mb-3"><i class="bi bi-upload me-2"></i>Subir Comprobante de Pago</h6>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="registrar_pago" value="1">
                            <input type="hidden" name="id_solicitud" value="<?= $id_sol ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Número de Propietarios</label>
                                    <input type="number" name="numero_propietarios" class="form-control" value="1" min="1" required onchange="actualizarMonto(this.value)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Monto a Pagar</label>
                                    <div class="form-control-plaintext text-warning fw-bold" id="montoMostrado2">S/ 50</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Medio de Pago</label>
                                    <select name="medio_pago" class="form-select" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="yape">Yape</option>
                                        <option value="visa">Visa</option>
                                        <option value="banco">Transferencia Bancaria</option>
                                        <option value="efectivo">Efectivo</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Número de Operación</label>
                                    <input type="text" name="numero_operacion" class="form-control" placeholder="Número de operación">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Comprobante de Pago</label>
                                    <input type="file" name="comprobante" class="form-control" accept="image/*,.pdf">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success mt-3">
                                <i class="bi bi-check-circle me-2"></i>Confirmar Pago
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    <br><small class="text-white-50">Guarde este código para consultar el estado de su trámite</small>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    
                    <!-- Datos del Solicitante -->
                    <div class="form-title">
                        <div class="section-icon"><i class="bi bi-person-fill"></i></div>
                        Datos del Solicitante
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ingrese nombres y apellidos" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">DNI</label>
                            <input type="text" name="dni" class="form-control" placeholder="Número de DNI" maxlength="8" required>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Datos del Terreno -->
                    <div class="form-title">
                        <div class="section-icon"><i class="bi bi-map-fill"></i></div>
                        Datos del Terreno
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Sector</label>
                            <input type="text" name="sector" class="form-control" placeholder="Ej: Chuñuranra" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Manzana</label>
                            <input type="text" name="manzana" class="form-control" placeholder="Número" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Lote</label>
                            <input type="text" name="lote" class="form-control" placeholder="Número" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Área (m²)</label>
                            <input type="number" name="area_m2" class="form-control" placeholder="Metros cuadrados" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de Propietarios</label>
                            <input type="number" name="numero_propietarios" class="form-control" value="1" min="1" required>
                            <small class="text-white-50">Monto: S/ 50 + (propietarios - 1) x S/ 250</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expediente (Opcional)</label>
                            <input type="text" name="expediente" class="form-control" placeholder="Número de expediente administrativo">
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Colindancias -->
                    <div class="form-title">
                        <div class="section-icon"><i class="bi bi-border-width"></i></div>
                        Colindancias del Lote
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Por el Frente</label>
                            <input type="text" name="lindero_frente" class="form-control" placeholder="Colinda con..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Longitud (ml)</label>
                            <input type="number" step="0.01" name="metros_frente" class="form-control" placeholder="Metros lineales" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Por el Fondo</label>
                            <input type="text" name="lindero_fondo" class="form-control" placeholder="Colinda con..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Longitud (ml)</label>
                            <input type="number" step="0.01" name="metros_fondo" class="form-control" placeholder="Metros lineales" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Por el Lado Derecho</label>
                            <input type="text" name="lindero_derecha" class="form-control" placeholder="Colinda con..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Longitud (ml)</label>
                            <input type="number" step="0.01" name="metros_derecha" class="form-control" placeholder="Metros lineales" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Por el Lado Izquierdo</label>
                            <input type="text" name="lindero_izquierda" class="form-control" placeholder="Colinda con..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Longitud (ml)</label>
                            <input type="number" step="0.01" name="metros_izquierda" class="form-control" placeholder="Metros lineales" required>
                        </div>
                        <div class="col-12">
                            <small class="text-white-50"><i class="bi bi-info-circle me-1"></i>Indique qué colinda cada lado del terreno (calle, lote vecinas, etc.) y su longitud en metros lineales</small>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Documentos -->
                    <div class="form-title">
                        <div class="section-icon"><i class="bi bi-folder-fill"></i></div>
                        Documentos Requeridos
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Copia DNI</label>
                            <div class="file-upload">
                                <input type="file" name="archivo_dni" accept=".pdf" onchange="mostrarArchivo(this, 'archivo_dni-label')">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <p id="archivo_dni-label">Arrastra o haz clic para subir</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Constancia de Agua</label>
                            <div class="file-upload">
                                <input type="file" name="archivo_constancia" accept=".pdf" onchange="mostrarArchivo(this, 'archivo_constancia-label')">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <p id="archivo_constancia-label">Arrastra o haz clic para subir</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plano de Ubicación</label>
                            <div class="file-upload">
                                <input type="file" name="archivo_plano" accept=".pdf" onchange="mostrarArchivo(this, 'archivo_plano-label')">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <p id="archivo_plano-label">Arrastra o haz clic para subir</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Recibo de Pago</label>
                            <div class="file-upload">
                                <input type="file" name="archivo_recibo" accept=".pdf" onchange="mostrarArchivo(this, 'archivo_recibo-label')">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <p id="archivo_recibo-label">Arrastra o haz clic para subir</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Memoria Descriptiva</label>
                            <div class="file-upload">
                                <input type="file" name="archivo_memoria" accept=".pdf" onchange="mostrarArchivo(this, 'archivo_memoria-label')">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <p id="archivo_memoria-label">Arrastra o haz clic para subir</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Declaración Jurada</label>
                            <div class="file-upload">
                                <input type="file" name="archivo_jurada" accept=".pdf" onchange="mostrarArchivo(this, 'archivo_jurada-label')">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <p id="archivo_jurada-label">Arrastra o haz clic para subir</p>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <button type="submit" class="btn-primary-custom">
                        <i class="bi bi-send-fill me-2"></i>Enviar Solicitud
                    </button>
                </form>
            </div>
        </div>

        <!-- Consultar Estado -->
        <div class="tab-pane fade" id="consulta" role="tabpanel">
            <div class="form-card">
                <div class="form-title">
                    <div class="section-icon"><i class="bi bi-search"></i></div>
                    Consultar Estado de Solicitud
                </div>

                <form method="GET" class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border); border-right: none; border-radius: 12px 0 0 12px;">
                            <i class="bi bi-hash"></i>
                        </span>
                        <input type="text" name="buscar_codigo" class="form-control" style="border-radius: 0 12px 12px 0;" placeholder="Ingrese su código de seguimiento (ADJ-2026-XXXXXX)" required>
                        <button class="btn btn-primary-custom" style="width: auto;" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <?php if($busqueda): ?>
                <div class="result-box" style="background: rgba(255,255,255,0.05); border-radius: 16px; padding: 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge <?= getEstadoClass($busqueda['estado']) ?>" style="padding: 0.5rem 1rem; border-radius: 8px;">
                            <?= getEstadoLabel($busqueda['estado']) ?>
                        </span>
                        <small class="text-white-50"><?= $busqueda['codigo_seguimiento'] ?></small>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-white-50">Solicitante</small>
                            <p class="mb-0 fw500"><?= htmlspecialchars($busqueda['nombre']) ?></p>
                        </div>
                        <div class="col-md-3">
                            <small class="text-white-50">DNI</small>
                            <p class="mb-0"><?= htmlspecialchars($busqueda['dni']) ?></p>
                        </div>
                        <div class="col-md-3">
                            <small class="text-white-50">Fecha</small>
                            <p class="mb-0"><?= date('d/m/Y', strtotime($busqueda['fecha_solicitud'])) ?></p>
                        </div>
                    </div>
                    
                    <?php 
                    // Mostrar sección de pago si aplica
                    $pago = $conn->query("SELECT * FROM pagos WHERE id_solicitud = " . $busqueda['id'])->fetch_assoc();
                    if (!$pago || $pago['estado'] != 'validado'): 
                        $num_propietarios = 1;
                        $monto = 50 + ($num_propietarios - 1) * 250;
                    ?>
                    <div class="mt-4 p-3 bg-dark bg-opacity-50 rounded">
                        <h6 class="text-warning mb-3"><i class="bi bi-cash me-2"></i>Realizar Pago</h6>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="registrar_pago" value="1">
                            <input type="hidden" name="id_solicitud" value="<?= $busqueda['id'] ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Número de Propietarios</label>
                                    <input type="number" name="numero_propietarios" class="form-control" value="1" min="1" required onchange="actualizarMontoPago(this.value, 'montoPago')">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Monto a Pagar</label>
                                    <div class="form-control-plaintext text-warning fw-bold" id="montoPago">S/ 50</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Medio de Pago</label>
                                    <select name="medio_pago" class="form-select" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="yape">Yape</option>
                                        <option value="visa">Visa</option>
                                        <option value="banco">Transferencia Bancaria</option>
                                        <option value="efectivo">Efectivo</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Número de Operación</label>
                                    <input type="text" name="numero_operacion" class="form-control" placeholder="Número de operación">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Comprobante de Pago</label>
                                    <input type="file" name="comprobante" class="form-control" accept="image/*,.pdf">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success mt-3">
                                <i class="bi bi-check-circle me-2"></i>Confirmar Pago
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="mt-3 p-2 bg-success bg-opacity-25 rounded">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        <strong>Pago realizado y validado</strong> - Código: <?= $pago['codigo_pago'] ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-5 text-white-50">
        <small><i class="bi bi-c-circle"></i> 2026 Comunidad Campesina Callqui Chico - Gestión Edil 2025-2026</small>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function actualizarMonto(propietarios) {
    propietarios = parseInt(propietarios) || 1;
    var monto = 50 + (propietarios - 1) * 250;
    document.getElementById('montoMostrado').textContent = 'S/ ' + monto;
    document.getElementById('montoInput').value = monto;
}
function actualizarMontoPago(propietarios, targetId) {
    propietarios = parseInt(propietarios) || 1;
    var monto = 50 + (propietarios - 1) * 250;
    var el = document.getElementById(targetId);
    if (el) el.textContent = 'S/ ' + monto;
}
function mostrarArchivo(input, labelId) {
    var label = document.getElementById(labelId);
    if (input.files && input.files[0]) {
        label.innerHTML = '<i class="bi bi-check-circle text-success me-2"></i>' + input.files[0].name;
        label.style.color = '#10b981';
        label.style.fontWeight = '500';
    }
}
</script>
</body>
</html>