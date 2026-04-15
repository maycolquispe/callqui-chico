<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dashboard/tcpdf/tcpdf.php';

$conn = getDB();
$success = "";
$codigo_generado = null;
$solicitud_guardada = null;

// Tipos de solicitudes
$tipos_solicitud = [
    'salud' => 'Por Salud',
    'educacion' => 'Por Educación',
    'trabajo' => 'Por Trabajo',
    'asunto Familiar' => 'Asunto Familiar',
    'constancia' => 'Solicitud de Constancia',
    'certificado' => 'Solicitud de Certificado',
    'otro' => 'Otro'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $año = date('Y');
    $codigo = 'SOL-' . $año . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    
    $nombre = trim($_POST['nombre'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $tipo_solicitud = $_POST['tipo_solicitud'] ?? 'otro';
    $descripcion = trim($_POST['descripcion'] ?? '');
    $dirigido_a = trim($_POST['dirigido_a'] ?? 'Presidente de la Comunidad');
    
    // Subir archivo si existe
    $archivo_subido = '';
    if (isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/solicitudes/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['adjunto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $archivo_subido = $codigo . '_adjunto.' . $ext;
            move_uploaded_file($_FILES['adjunto']['tmp_name'], $upload_dir . $archivo_subido);
        }
    }
    
    // Insertar en la tabla permisos (reutilizando)
    $stmt = $conn->prepare("INSERT INTO permisos 
        (usuario_id, tipo_permiso, fecha_inicio, fecha_fin, motivo, archivo, estado, codigo_unico, nombre_solicitante, dni_solicitante)
        VALUES (0, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, ?, 'pendiente', ?, ?, ?)");
    
    $stmt->bind_param("ssssss", $tipo_solicitud, $descripcion, $archivo_subido, $codigo, $nombre, $dni);
    
    if ($stmt->execute()) {
        $success = "Solicitud registrada exitosamente";
        $solicitud_guardada = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'dni' => $dni,
            'telefono' => $telefono,
            'tipo' => $tipos_solicitud[$tipo_solicitud] ?? $tipo_solicitud,
            'descripcion' => $descripcion,
            'dirigido_a' => $dirigido_a,
            'fecha' => date('d/m/Y')
        ];
        
        // Generar PDF del cargo
        $pdf = new TCPDF('P', 'mm', 'A5', true, 'UTF-8');
        $pdf->SetCreator('Callqui Chico');
        $pdf->SetAuthor('Comunidad Campesina Callqui Chico');
        $pdf->SetTitle('Cargo de Solicitud - ' . $codigo);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        
        // Fondo
        $pdf->SetFillColor(250, 248, 240);
        $pdf->Rect(0, 0, 148, 210, 'F');
        
        // Encabezado
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(10, 43, 60);
        $pdf->Cell(0, 8, 'COMUNIDAD CAMPESINA CALLQUI CHICO', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 4, 'Gestión Edil 2025-2026', 0, 1, 'C');
        
        $pdf->SetDrawColor(201, 164, 92);
        $pdf->Line(15, 28, 133, 28);
        
        // Título del cargo
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(37, 99, 235);
        $pdf->Cell(0, 6, 'CARGO DE PRESENTACIÓN', 0, 1, 'C');
        
        // Código
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(220, 38, 38);
        $pdf->Cell(0, 8, $codigo, 0, 1, 'C');
        
        // Datos del solicitante
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, 'DATOS DEL SOLICITANTE', 0, 1);
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(60, 60, 60);
        
        $pdf->Cell(40, 5, 'Nombre:', 0, 0);
        $pdf->Cell(0, 5, strtoupper($nombre), 0, 1);
        
        $pdf->Cell(40, 5, 'DNI:', 0, 0);
        $pdf->Cell(0, 5, $dni, 0, 1);
        
        if (!empty($telefono)) {
            $pdf->Cell(40, 5, 'Teléfono:', 0, 0);
            $pdf->Cell(0, 5, $telefono, 0, 1);
        }
        
        // Tipo de solicitud
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, 'TIPO DE SOLICITUD', 0, 1);
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->Cell(0, 5, $tipos_solicitud[$tipo_solicitud] ?? $tipo_solicitud, 0, 1);
        
        // Descripción
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, 'DESCRIPCIÓN', 0, 1);
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->MultiCell(0, 5, $descripcion, 0, 'L');
        
        // Dirigido a
        $pdf->Ln(5);
        $pdf->Cell(40, 5, 'Dirigido a:', 0, 0);
        $pdf->Cell(0, 5, $dirigido_a, 0, 1);
        
        // Fecha
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'Fecha de presentación: ' . date('d/m/Y'), 0, 1, 'R');
        
        // Mensaje
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(0, 4, 'Este documento acredita la presentación de su solicitud. Conserve este código para dar seguimiento a su trámite.', 0, 'C');
        
        // Footer
        $pdf->SetY(-20);
        $pdf->SetFont('helvetica', 'I', 7);
        $pdf->Cell(0, 4, 'Sistema de Gestión - Comunidad Campesa Callqui Chico', 0, 1, 'C');
        
        // Guardar PDF
        $pdf_dir = __DIR__ . '/documentos/cargos';
        if (!is_dir($pdf_dir)) mkdir($pdf_dir, 0777, true);
        
        $pdf_path = $pdf_dir . '/cargo_' . $codigo . '.pdf';
        $pdf->Output($pdf_path, 'F');
        
        $solicitud_guardada['pdf_path'] = 'documentos/cargos/cargo_' . $codigo . '.pdf';
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nueva Solicitud | Comunidad Callqui Chico</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #0f172a;
    --accent: #c9a45c;
    --accent-hover: #dbb67b;
    --success: #10b981;
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
}

body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: url('../img/fondo_callqui.jpg') center/cover no-repeat;
    opacity: 0.04;
    z-index: -1;
}

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

.alert-success-custom {
    background: rgba(16,185,129,0.1);
    border: 1px solid rgba(16,185,129,0.3);
    border-radius: 16px;
    padding: 1.5rem;
}

.alert-success-custom strong {
    font-family: 'Courier New', monospace;
    background: rgba(255,255,255,0.1);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    display: inline-block;
}

.btn-download {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    margin-top: 1rem;
}

.btn-download:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(16,185,129,0.3);
    color: white;
}

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

.divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--border), transparent);
    margin: 1.5rem 0;
}

.file-upload {
    position: relative;
    border: 2px dashed var(--border);
    border-radius: 16px;
    padding: 2rem 1.5rem;
    text-align: center;
    background: rgba(255,255,255,0.03);
    transition: all 0.3s ease;
}

.file-upload:hover {
    border-color: var(--accent);
    background: rgba(201,164,92,0.08);
}

.file-upload input[type="file"] {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 10;
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
            <small class="text-white-50">Sistema de Gestión Comunal</small>
        </div>
        <a href="../index.html" class="btn btn-outline-light btn-sm rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Volver al Inicio
        </a>
    </div>

    <div class="form-card">
        
        <?php if($success && $solicitud_guardada): ?>
        <div class="alert-success-custom mb-4 text-center">
            <i class="bi bi-check-circle-fill fs-2 text-success mb-3 d-block"></i>
            <h5 class="text-success">¡Solicitud Registrada!</h5>
            <p class="mb-3">Su código de seguimiento es:</p>
            <strong class="fs-4"><?= $solicitud_guardada['codigo'] ?></strong>
            <p class="text-white-50 small mt-3">Guarde este código para consultar el estado de su trámite</p>
            
            <?php if(!empty($solicitud_guardada['pdf_path'])): ?>
            <a href="../<?= $solicitud_guardada['pdf_path'] ?>" class="btn-download" target="_blank">
                <i class="bi bi-download"></i> Descargar Cargo de Presentación
            </a>
            <?php endif; ?>
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
                <div class="col-md-6">
                    <label class="form-label">Teléfono (Opcional)</label>
                    <input type="text" name="telefono" class="form-control" placeholder="Número de teléfono">
                </div>
            </div>

            <div class="divider"></div>

            <!-- Datos de la Solicitud -->
            <div class="form-title">
                <div class="section-icon"><i class="bi bi-file-text-fill"></i></div>
                Datos de la Solicitud
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Tipo de Solicitud</label>
                    <select name="tipo_solicitud" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <option value="salud">Por Salud</option>
                        <option value="educacion">Por Educación</option>
                        <option value="trabajo">Por Trabajo</option>
                        <option value="asunto Familiar">Asunto Familiar</option>
                        <option value="constancia">Solicitud de Constancia</option>
                        <option value="certificado">Solicitud de Certificado</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Dirigido a</label>
                    <input type="text" name="dirigido_a" class="form-control" value="Presidente de la Comunidad Campesina Callqui Chico">
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción de la Solicitud</label>
                    <textarea name="descripcion" class="form-control" rows="5" placeholder="Describa detalladamente su solicitud..." required></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Adjuntar Documento (Opcional)</label>
                    <div class="file-upload">
                        <input type="file" name="adjunto" id="adjunto" accept=".pdf,.jpg,.jpeg,.png">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <p id="fileName">Arrastra o haz clic para subir (PDF, JPG, PNG)</p>
                    </div>
                    <small class="text-white-50 d-block mt-1" style="font-size: 0.75rem;">Máximo 5MB</small>
                </div>
            </div>

            <div class="divider"></div>

            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-send-fill me-2"></i>Enviar Solicitud
            </button>
        </form>
    </div>

    <!-- Footer -->
    <div class="text-center mt-5 text-white-50">
        <small><i class="bi bi-c-circle"></i> 2026 Comunidad Campesa Callqui Chico - Gestión Edil 2025-2026</small>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('adjunto').addEventListener('change', function(e) {
    var fileName = e.target.files[0] ? e.target.files[0].name : 'Arrastra o haz clic para subir (PDF, JPG, PNG)';
    document.getElementById('fileName').textContent = fileName;
});
</script>
</body>
</html>