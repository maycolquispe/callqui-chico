<?php
session_start();
require_once("../../config/conexion.php");

$usuario_id = $_SESSION['usuario_id'] ?? 0;
if (!$usuario_id) {
    die("Acceso denegado");
}

$permiso_id = $_GET['id'] ?? 0;
if (!$permiso_id) {
    die("ID de permiso no válido");
}

// Obtener datos del permiso
$stmt = $conn->prepare("SELECT p.*, u.nombres, u.apellidos, u.dni, u.padron 
                       FROM permisos p 
                       JOIN usuarios u ON p.usuario_id = u.id 
                       WHERE p.id = ? AND p.usuario_id = ?");
$stmt->bind_param("ii", $permiso_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Permiso no encontrado");
}

$p = $result->fetch_assoc();
$stmt->close();

// Cargar TCPDF
require_once '../../vendor/tcpdf/tcpdf.php';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('Comunidad Callqui Chico');
$pdf->SetAuthor('Sistema de Gestión');
$pdf->SetTitle('Solicitud de Permiso - ' . $p['codigo_unico']);

$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// Logo como fondo de marca de agua (logo_blanco_negro.jpg)
$watermarkPath = 'C:/xampp/htdocs/2026/assets/img/logo_blanco_negro.jpg';
if (file_exists($watermarkPath)) {
    $pdf->SetAlpha(0.08);
    $pdf->Image($watermarkPath, 25, 60, 160, 160, 'JPG');
    $pdf->SetAlpha(1);
}

// Colores
$azul = [10, 43, 60];
$dorado = [201, 164, 91];
$verde = [16, 185, 129];
$rojo = [239, 68, 68];
$amarillo = [245, 158, 11];

// ============= HEADER =============
$pdf->SetFillColor($azul[0], $azul[1], $azul[2]);
$pdf->Rect(0, 0, 210, 45, 'F');

// Logo
$logoPath = dirname(__DIR__) . '/../../assets/img/logo_callqui.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 90, 8, 30, 30, 'PNG');
}

// Texto del header
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 15, 'COMUNIDAD CAMPESINA CALLQUI CHICO', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Distrito de Huancavelica - Provincia de Huancavelica - Departamento de Huancavelica', 0, 1, 'C');

$pdf->SetDrawColor($dorado[0], $dorado[1], $dorado[2]);
$pdf->SetLineWidth(2);
$pdf->Line(15, 40, 195, 40);

// ============= TÍTULO DOCUMENTO =============
$pdf->Ln(15);
$pdf->SetTextColor($azul[0], $azul[1], $azul[2]);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 8, 'CONSTANCIA DE SOLICITUD DE PERMISO', 0, 1, 'C');

// ============= CÓDIGO Y ESTADO =============
$pdf->Ln(10);

// Fondo para código

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(90, 8, 'CÓDIGO DE SEGURIDAD', 0, 0, 'C');

$pdf->SetFont('helvetica', 'B', 12);
if($p['estado'] == 'Aprobado') {
    $pdf->SetTextColor($verde[0], $verde[1], $verde[2]);
} elseif($p['estado'] == 'Rechazado') {
    $pdf->SetTextColor($rojo[0], $rojo[1], $rojo[2]);
} else {
    $pdf->SetTextColor($amarillo[0], $amarillo[1], $amarillo[2]);
}
$pdf->Cell(0, 8, strtoupper($p['estado']), 0, 1, 'C');

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(90, 8, $p['codigo_unico'], 0, 0, 'C');

$y_after = $pdf->GetY();

// ============= DATOS DEL SOLICITANTE =============
$pdf->Ln(10);
$pdf->SetFillColor($azul[0], $azul[1], $azul[2]);
$pdf->Rect(15, $pdf->GetY(), 180, 12, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 12, 'DATOS DEL SOLICITANTE', 0, 1, 'L');

$pdf->SetDrawColor($azul[0], $azul[1], $azul[2]);
$pdf->SetLineWidth(0.5);
$pdf->SetAlpha(0.5);
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(15, $pdf->GetY(), 180, 35, 'F');
$pdf->SetAlpha(1);
$pdf->Rect(15, $pdf->GetY(), 180, 35);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, strtoupper($p['apellidos'] . ', ' . $p['nombres']), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(50, 6, 'DNI:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(60, 6, $p['dni'], 0, 0);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(30, 6, 'PADRÓN:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, ($p['padron'] ?? 'N/A'), 0, 1);

// ============= DETALLE DE SOLICITUD =============
$pdf->Ln(8);
$pdf->SetFillColor($azul[0], $azul[1], $azul[2]);
$pdf->Rect(15, $pdf->GetY(), 180, 12, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 12, 'DETALLE DE SOLICITUD', 0, 1, 'L');

$pdf->SetDrawColor($azul[0], $azul[1], $azul[2]);
$pdf->SetLineWidth(0.5);
$pdf->SetAlpha(0.5);
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(15, $pdf->GetY(), 180, 40, 'F');
$pdf->SetAlpha(1);
$pdf->Rect(15, $pdf->GetY(), 180, 40);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 10);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 8, 'Tipo de Permiso:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 8, $p['tipo_permiso'], 0, 1);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 6, 'Fecha Inicio:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 6, date('d/m/Y', strtotime($p['fecha_inicio'])), 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(35, 6, 'Fecha Fin:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, date('d/m/Y', strtotime($p['fecha_fin'])), 0, 1);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 6, 'Fecha Solicitud:', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($p['fecha_registro'])), 0, 1);

// ============= MOTIVO =============
$pdf->Ln(8);
$pdf->SetFillColor($azul[0], $azul[1], $azul[2]);
$pdf->Rect(15, $pdf->GetY(), 180, 12, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 12, 'MOTIVO / JUSTIFICACIÓN', 0, 1, 'L');

$pdf->SetDrawColor($azul[0], $azul[1], $azul[2]);
$pdf->SetLineWidth(0.5);
$pdf->SetAlpha(0.5);
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(15, $pdf->GetY(), 180, 25, 'F');
$pdf->SetAlpha(1);
$pdf->Rect(15, $pdf->GetY(), 180, 25);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 5, $p['motivo'], 0, 'L');

// ============= OBSERVACIÓN (solo si existe) =============
if (!empty($p['observacion_secretario'])) {
    $pdf->Ln(8);
    $pdf->SetFillColor($azul[0], $azul[1], $azul[2]);
    $pdf->Rect(15, $pdf->GetY(), 180, 12, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 12, 'RESPUESTA DEL SECRETARIO', 0, 1, 'L');
    
    $pdf->SetDrawColor($azul[0], $azul[1], $azul[2]);
    $pdf->SetLineWidth(0.5);
    $pdf->SetAlpha(0.5);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(15, $pdf->GetY(), 180, 20, 'F');
    $pdf->SetAlpha(1);
    $pdf->Rect(15, $pdf->GetY(), 180, 20);
    
    $pdf->SetTextColor($azul[0], $azul[1], $azul[2]);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 8, strtoupper($p['estado']), 0, 1, 'C');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 5, $p['observacion_secretario'], 0, 'L');
}

// ============= NOTA DE PIE =============
$pdf->Ln(15);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 4, 'Este documento tiene carácter informativo. Para cualquier consulta acuda a las oficinas de la Comunidad.', 0, 1, 'C');
$pdf->Cell(0, 4, 'Generado el ' . date('d/m/Y') . ' a las ' . date('H:i') . ' hrs - Sistema de Gestión Callqui Chico', 0, 1, 'C');

// Output
$pdf->Output('Solicitud_Permiso_' . $p['codigo_unico'] . '.pdf', 'I');