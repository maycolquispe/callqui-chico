<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

SessionManager::init();

if (!in_array(SessionManager::get('rol'), ['presidente', 'secretario'])) {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

$conn = getDB();

$sql = "
    SELECT padron, dni, nombres, apellidos, telefono, promocion
    FROM usuarios
    WHERE rol = 'comunero' AND estado = 'activo'
    ORDER BY padron ASC
";
$result = $conn->query($sql);
$comuneros = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

require_once '../../vendor/tcpdf/tcpdf.php';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('Comunidad Callqui Chico');
$pdf->SetAuthor('Sistema de Gestión');
$pdf->SetTitle('Padrón de Comuneros');

$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 40);

$primary = [30, 60, 90];
$dark = [50, 50, 50];
$light = [245, 245, 245];

$pdf->SetFillColor($primary[0], $primary[1], $primary[2]);
$pdf->Rect(0, 0, 210, 35, 'F');

$logoComunidad = 'C:/xampp/htdocs/2026/assets/img/logo_callqui.jpg';
$logoPeru = 'C:/xampp/htdocs/2026/assets/img/logo_peru.jpg';

if (file_exists($logoComunidad)) {
    $pdf->Image($logoComunidad, 18, 6, 22, 22, 'JPG');
}

if (file_exists($logoPeru)) {
    $pdf->Image($logoPeru, 170, 6, 22, 22, 'JPG');
}

$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 14, 'PADRÓN DE COMUNEROS', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 5, 'Comunidad Campesina Callqui Chico', 0, 1, 'C');

$pdf->Ln(8);
$pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Fecha: ' . date('d/m/Y'), 0, 1, 'R');
$pdf->Cell(0, 5, 'Total de comuneros: ' . count($comuneros), 0, 1, 'R');

$pdf->Ln(3);
$pdf->SetFillColor($primary[0], $primary[1], $primary[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 8);

$pdf->Cell(18, 7, 'Padrón', 1, 0, 'C', 1);
$pdf->Cell(28, 7, 'DNI', 1, 0, 'C', 1);
$pdf->Cell(72, 7, 'Apellidos y Nombres', 1, 0, 'C', 1);
$pdf->Cell(28, 7, 'Teléfono', 1, 0, 'C', 1);
$pdf->Cell(29, 7, 'Promoción', 1, 1, 'C', 1);

$pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
$pdf->SetFont('helvetica', '', 7);

$fill = false;
foreach ($comuneros as $c) {
    $pdf->SetFillColor($fill ? $light[0] : 255, $fill ? $light[1] : 255, $fill ? $light[2] : 255);
    $pdf->Cell(18, 6, $c['padron'] ?? '-', 1, 0, 'C', 1);
    $pdf->Cell(28, 6, $c['dni'], 1, 0, 'C', 1);
    $pdf->Cell(72, 6, $c['apellidos'] . ', ' . $c['nombres'], 1, 0, 'L', 1);
    $pdf->Cell(28, 6, $c['telefono'] ?? '-', 1, 0, 'C', 1);
    $pdf->Cell(29, 6, $c['promocion'] ?? '-', 1, 1, 'C', 1);
    $fill = !$fill;
}

$pdf->Ln(12);
$pdf->SetDrawColor($primary[0], $primary[1], $primary[2]);
$pdf->SetLineWidth(0.3);
$pdf->Line(25, $pdf->GetY(), 80, $pdf->GetY());
$pdf->Cell(55, 4, 'Presidente de Comunidad', 0, 0, 'C');
$pdf->Cell(30, 4, '', 0, 0);
$pdf->Line(125, $pdf->GetY(), 185, $pdf->GetY());
$pdf->Cell(60, 4, 'Secretario General', 0, 1, 'C');

$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(140, 140, 140);
$pdf->Cell(0, 3, 'Sistema de Gestión - Comunidad Campesina Callqui Chico', 0, 1, 'C');

$pdf->Output('Padron_Comuneros_' . date('Ymd') . '.pdf', 'I');
exit;