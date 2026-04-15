<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

SessionManager::init();

if (!in_array(SessionManager::get('rol'), ['comite_lotes', 'presidente', 'secretario'])) {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

$conn = getDB();

// Verificar si viene con modo=datos (datos directos) o con id tradicional
$modo = isset($_GET['modo']) && $_GET['modo'] === 'datos';

if ($modo) {
    // Modo datos directos - construir la transferencia desde los parámetros
    $lote_id = intval($_GET['lote_id'] ?? 0);
    $titular = $_GET['titular'] ?? '';
    $titular_dni = $_GET['titular_dni'] ?? '';
    $copropietario = $_GET['copropietario'] ?? '';
    
    if (!$lote_id || !$titular) {
        die("Datos insuficientes para generar el certificado");
    }
    
    // Obtener datos del lote
    $stmtLote = $conn->prepare("SELECT lote, manzana, sector, area_m2, usuario_id as prop_anterior, copropietario as coprop_anterior FROM lotes WHERE id = ?");
    $stmtLote->bind_param("i", $lote_id);
    $stmtLote->execute();
    $lote = $stmtLote->get_result()->fetch_assoc();
    $stmtLote->close();
    
    if (!$lote) {
        die("Lote no encontrado");
    }
    
    // Obtener nombre del propietario anterior
    $propAnteriorNombre = '';
    $propAnteriorDni = '';
    $copropAnterior = '';
    if ($lote['prop_anterior']) {
        $stmtUser = $conn->prepare("SELECT nombres, apellidos, dni, copropietario FROM usuarios WHERE id = ?");
        $stmtUser->bind_param("i", $lote['prop_anterior']);
        $stmtUser->execute();
        $userAnt = $stmtUser->get_result()->fetch_assoc();
        $stmtUser->close();
        if ($userAnt) {
            $propAnteriorNombre = $userAnt['nombres'] . ' ' . $userAnt['apellidos'];
            $propAnteriorDni = $userAnt['dni'] ?? '';
            $copropAnterior = $userAnt['copropietario'] ?? '';
        }
    }
    
    // Construir array de transferencia para usar en el PDF
    $transferencia = [
        'id' => 0,
        'lote' => $lote['lote'],
        'manzana' => $lote['manzana'],
        'sector' => $lote['sector'] ?? '',
        'area_m2' => $lote['area_m2'] ?? '',
        'nombre_anterior' => $propAnteriorNombre,
        'dni_anterior' => $propAnteriorDni,
        'copropietario_anterior' => $copropAnterior,
        'nombre_nuevo' => $titular,
        'dni_nuevo' => $titular_dni,
        'copropietario_nuevo' => $copropietario,
        'nombre_registro' => $_SESSION['nombres'] ?? '',
        'apellido_registro' => $_SESSION['apellidos'] ?? '',
        'fecha_transferencia' => date('Y-m-d H:i:s'),
        'observaciones' => ''
    ];
} else {
    // Modo tradicional - buscar por ID
    $transferencia_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if (!$transferencia_id) {
        die("ID de transferencia no válido");
    }

    // Obtener datos de la transferencia
    $sql = "SELECT t.*, 
                   l.lote, l.manzana, l.sector, l.area_m2,
                   u1.dni as dni_anterior, u1.nombres as nombre_anterior, u1.apellidos as apellido_anterior,
                   u2.dni as dni_nuevo, u2.nombres as nombre_nuevo, u2.apellidos as apellido_nuevo,
                   u3.nombres as nombre_registro, u3.apellidos as apellido_registro
           FROM transferencias_lote t
           LEFT JOIN lotes l ON t.lote_id = l.id
           LEFT JOIN usuarios u1 ON t.propietario_anterior = u1.id
           LEFT JOIN usuarios u2 ON t.propietario_nuevo = u2.id
           LEFT JOIN usuarios u3 ON t.usuario_registro = u3.id
           WHERE t.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $transferencia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transferencia = $result->fetch_assoc();
    $stmt->close();

    if (!$transferencia) {
        die("Transferencia no encontrada");
    }
}

// Generar PDF con TCPDF
require_once '../tcpdf/tcpdf.php';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('Callqui Chico');
$pdf->SetAuthor('Comunidad Campesina Callqui Chico');
$pdf->SetTitle('Certificado de Transferencia');

// Configurar página
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(20);

// Crear página
$pdf->AddPage();

// ==================== ENCABEZADO ====================
$pdf->SetFillColor(250, 248, 240); // Color crema suave
$pdf->Rect(0, 0, 210, 48, 'F');

// Logo callqui - solo JPG (evitar PNG que requiere GD)
$logoCallquiJpg = __DIR__ . '/../../assets/img/logo_callqui.jpg';

if (file_exists($logoCallquiJpg)) {
    $pdf->Image($logoCallquiJpg, 15, 12, 22, 22, 'JPEG');
}

// Título central - completamente centrado
$pdf->SetTextColor(80, 60, 40); // Marrón oscuro

// Línea 1 - Año
$pdf->SetXY(0, 10);
$pdf->SetFont('helvetica', 'BI', 9);
$pdf->Cell(210, 5, 'AÑO DE LA ESPERANZA Y EL FORTALECIMIENTO DE LA DEMOCRACIA', 0, 1, 'C');

// Línea 2 - Nombre comunidad (más destacado)
$pdf->SetXY(0, 16);
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Cell(210, 7, 'COMUNIDAD CAMPESINA CALLQUI CHICO', 0, 1, 'C');

// Línea 3 - Gestión edil
$pdf->SetXY(0, 24);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(210, 5, 'Gestión Edil 2025-2026', 0, 1, 'C');

// Línea 4 - Resolución (más pequeña, italic)
$pdf->SetXY(0, 30);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(100, 90, 80);
$pdf->Cell(210, 4, 'Reconocida mediante Resolución N° 138-2005/GOB.REG.HVCA/GRDE-DRA', 0, 1, 'C');

$pdf->SetXY(0, 34);
$pdf->Cell(210, 4, 'con fecha 07-09-2005', 0, 1, 'C');

// Logo Perú - solo JPG
$logoPeruJpg = __DIR__ . '/../../assets/img/logo_peru.jpg';

if (file_exists($logoPeruJpg)) {
    $pdf->Image($logoPeruJpg, 173, 12, 22, 22, 'JPEG');
}

// Línea dorada separadora inferior
$pdf->SetDrawColor(201, 164, 92); // Dorado
$pdf->SetLineWidth(1);
$pdf->Line(15, 48, 195, 48);
$pdf->SetLineWidth(0.2);

// Reset colors
$pdf->SetTextColor(0, 0, 0);

// ==================== TÍTULO DEL DOCUMENTO ====================
$pdf->SetY(50);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(37, 99, 235);
$pdf->Cell(0, 10, 'CONSTANCIA DE TRANSFERENCIA', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, 'Documento oficial de cambio de propietario de lote', 0, 1, 'C');

$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(8);

// ==================== SECCIÓN 1: DATOS DE LA TRANSFERENCIA ====================
$pdf->SetFillColor(245, 247, 250);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Rect(15, $pdf->GetY(), 180, 30, 'FD');

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(37, 99, 235);
$pdf->Cell(15, 7, '1. INFORMACIÓN DE LA TRANSFERENCIA', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(60, 60, 60);

$pdf->Cell(60, 6, 'N° de Constancia:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'TRANS-' . str_pad($transferencia['id'] ?? date('Ymd'), 6, '0', STR_PAD_LEFT), 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 6, 'Fecha de Registro:', 0, 0);
$pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($transferencia['fecha_transferencia'] ?? date('Y-m-d H:i:s'))), 0, 1);

$pdf->Cell(60, 6, 'Registrado por:', 0, 0);
$nombreRegistro = !empty($transferencia['apellido_registro']) 
    ? $transferencia['apellido_registro'] . ', ' . $transferencia['nombre_registro'] 
    : ($transferencia['nombre_registro'] ?? 'Comité de Lotes');
$pdf->Cell(0, 6, $nombreRegistro, 0, 1);

$pdf->SetY($pdf->GetY() + 5);

// ==================== SECCIÓN 2: DATOS DEL LOTE ====================
$pdf->SetFillColor(240, 255, 244);
$pdf->Rect(15, $pdf->GetY(), 180, 32, 'FD');

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(16, 185, 129);
$pdf->Cell(15, 7, '2. DATOS DEL LOTE', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(60, 60, 60);

$pdf->Cell(50, 6, 'Lote:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, $transferencia['lote'] ?? '-', 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(50, 6, 'Manzana:', 0, 0);
$pdf->Cell(0, 6, $transferencia['manzana'] ?? '-', 0, 1);

$pdf->Cell(50, 6, 'Sector:', 0, 0);
$pdf->Cell(0, 6, $transferencia['sector'] ?? 'Sin sector', 0, 1);

$pdf->Cell(50, 6, 'Área:', 0, 0);
$pdf->Cell(0, 6, ($transferencia['area_m2'] ?? '0') . ' m²', 0, 1);

$pdf->SetY($pdf->GetY() + 5);

// ==================== SECCIÓN 3: PROPIETARIO ANTERIOR ====================
$pdf->SetFillColor(255, 248, 240);
$pdf->Rect(15, $pdf->GetY(), 180, 25, 'FD');

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(180, 100, 50);
$pdf->Cell(15, 7, '3. PROPIETARIO ANTERIOR (Transferente)', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(60, 60, 60);

if ($transferencia['nombre_anterior']) {
    $nombreAnterior = !empty($transferencia['apellido_anterior']) 
        ? $transferencia['apellido_anterior'] . ', ' . $transferencia['nombre_anterior'] 
        : $transferencia['nombre_anterior'];
    
    $pdf->Cell(60, 6, 'Titular:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, $nombreAnterior, 0, 1);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 6, 'DNI:', 0, 0);
    $pdf->Cell(0, 6, $transferencia['dni_anterior'] ?? '-', 0, 1);
    
    if (!empty($transferencia['copropietario_anterior'])) {
        $pdf->Cell(60, 6, 'Copropietario:', 0, 0);
        $pdf->Cell(0, 6, $transferencia['copropietario_anterior'], 0, 1);
    }
} else {
    $pdf->Cell(0, 6, 'Sin propietario anterior (Lote nuevo/adjudicación directa)', 0, 1);
}

$pdf->SetY($pdf->GetY() + 4);

// ==================== SECCIÓN 4: NUEVO PROPIETARIO ====================
$pdf->SetFillColor(235, 245, 255);
$pdf->Rect(15, $pdf->GetY(), 180, 35, 'FD');

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(37, 99, 235);
$pdf->Cell(15, 7, '4. NUEVO PROPIETARIO (Adquiriente)', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(60, 60, 60);

$nombreNuevo = !empty($transferencia['apellido_nuevo']) 
    ? $transferencia['apellido_nuevo'] . ', ' . $transferencia['nombre_nuevo'] 
    : $transferencia['nombre_nuevo'];

$pdf->Cell(60, 6, 'Titular:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, $nombreNuevo, 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 6, 'DNI:', 0, 0);
$pdf->Cell(0, 6, $transferencia['dni_nuevo'] ?? '-', 0, 1);

if (!empty($transferencia['copropietario_nuevo'])) {
    $pdf->Cell(60, 6, 'Copropietario:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, $transferencia['copropietario_nuevo'], 0, 1);
}

$pdf->SetY($pdf->GetY() + 4);

// ==================== OBSERVACIONES ====================
if (!empty($transferencia['observaciones'])) {
    $pdf->SetFillColor(255, 250, 240);
    $pdf->Rect(15, $pdf->GetY(), 180, 20, 'FD');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(150, 100, 0);
    $pdf->Cell(15, 6, 'OBSERVACIONES:', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->MultiCell(170, 5, $transferencia['observaciones'], 0, 'L');
    
    $pdf->SetY($pdf->GetY() + 3);
}

// ==================== PIE DE PÁGINA ====================
$pdf->SetY(-35);

// Línea separadora
$pdf->SetDrawColor(201, 164, 92);
$pdf->SetLineWidth(0.5);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->SetLineWidth(0.2);

$pdf->SetY($pdf->GetY() + 5);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 4, 'Documento generado el ' . date('d/m/Y H:i') . ' - Sistema de Gestión de Comunidad Campesina Callqui Chico', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 3, 'Inscripción de Comunidades Campesinas y Nativas "Comunidad Campesina Callqui Chico" N° Partida 11003875', 0, 1, 'C');

// Output
$pdf->Output('constancia_transferencia_' . date('Ymd') . '.pdf', 'I');
exit;
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'PROPIETARIO ANTERIOR', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

if ($transferencia['nombre_anterior']) {
    // Si viene con apellido y nombre separados, usarlos. Si no, usar nombre completo
    $nombreAnterior = !empty($transferencia['apellido_anterior']) 
        ? $transferencia['apellido_anterior'] . ', ' . $transferencia['nombre_anterior'] 
        : $transferencia['nombre_anterior'];
    
    $pdf->Cell(60, 7, 'Titular:', 0, 0);
    $pdf->Cell(0, 7, $nombreAnterior, 0, 1);
    
    $pdf->Cell(60, 7, 'DNI:', 0, 0);
    $pdf->Cell(0, 7, $transferencia['dni_anterior'] ?? '-', 0, 1);
    
    // Mostrar copropietario anterior si existe
    if (!empty($transferencia['copropietario_anterior'])) {
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 7, 'Copropietario:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, $transferencia['copropietario_anterior'], 0, 1);
    }
} else {
    $pdf->Cell(0, 7, 'Sin propietario anterior (Lote nuevo)', 0, 1);
}

$pdf->Ln(5);

// Nuevo propietario
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'NUEVO PROPIETARIO', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

// Si viene con apellido y nombre separados, usarlos. Si no, usar nombre completo
$nombreNuevo = !empty($transferencia['apellido_nuevo']) 
    ? $transferencia['apellido_nuevo'] . ', ' . $transferencia['nombre_nuevo'] 
    : $transferencia['nombre_nuevo'];

$pdf->Cell(60, 7, 'Titular:', 0, 0);
$pdf->Cell(0, 7, $nombreNuevo, 0, 1);

$pdf->Cell(60, 7, 'DNI:', 0, 0);
$pdf->Cell(0, 7, $transferencia['dni_nuevo'] ?? '-', 0, 1);

// Mostrar copropietario si existe
if (!empty($transferencia['copropietario_nuevo'])) {
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(60, 7, 'Copropietario:', 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, $transferencia['copropietario_nuevo'], 0, 1);
}

$pdf->Ln(5);

// Registrado por
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'REGISTRADO POR', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

$nombreRegistro = !empty($transferencia['apellido_registro']) 
    ? $transferencia['apellido_registro'] . ', ' . $transferencia['nombre_registro'] 
    : $transferencia['nombre_registro'];

$pdf->Cell(60, 7, 'Usuario:', 0, 0);
$pdf->Cell(0, 7, $nombreRegistro, 0, 1);

if ($transferencia['observaciones']) {
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 7, 'OBSERVACIONES', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 7, $transferencia['observaciones'], 0, 'L');
}

// Pie de página
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 4, 'Documento generado el ' . date('d/m/Y H:i'), 0, 1, 'C');
$pdf->Cell(0, 4, 'Sistema de Gestión - Comunidad Campesina Callqui Chico', 0, 1, 'C');

// Información de resolución al final
$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 4, 'Reconocido mediante resolución N° 138-2005/GOB.REG.HVCA/GRDE-DRA, con fecha 07-09-2005', 0, 1, 'C');
$pdf->Cell(0, 4, 'INSCRIPCION DE COMUNIDADES CAMPESINAS Y NATIVAS "COMUNIDAD CAMPESINA CALLQUI CHICO" N° PARTIDA 11003875', 0, 1, 'C');

// Output
$pdf->Output('constancia_transferencia_' . date('Ymd') . '.pdf', 'I');
exit;