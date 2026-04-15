<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
$conn = getDB();

$acta_id = $_GET['acta_id'] ?? 0;

if (!$acta_id) {
    die("Acta no especificada");
}

$sql_acta = "SELECT * FROM actas WHERE id = ?";
$stmt = $conn->prepare($sql_acta);
$stmt->bind_param("i", $acta_id);
$stmt->execute();
$result_acta = $stmt->get_result();

if ($result_acta->num_rows == 0) {
    die("Acta no encontrada");
}

$acta = $result_acta->fetch_assoc();
$stmt->close();

$sql_asistencias = "
SELECT 
    u.dni,
    u.nombres,
    u.apellidos,
    u.padron,
    a.estado
FROM asistencias a
INNER JOIN usuarios u ON u.id = a.usuario_id
WHERE a.acta_id = ?
ORDER BY u.padron ASC
";

$stmt = $conn->prepare($sql_asistencias);
$stmt->bind_param("i", $acta_id);
$stmt->execute();
$asistencias = $stmt->get_result();

$total = 0;
$asistieron = 0;
$faltaron = 0;

while ($row = $asistencias->fetch_assoc()) {
    $total++;
    if ($row['estado'] == 'asistio') {
        $asistieron++;
    } else {
        $faltaron++;
    }
}

$stmt->close();

if(isset($_GET['pdf'])){
    
    require_once '../../vendor/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Sistema Comunal');
    $pdf->SetAuthor('Comunidad Campesina Callqui Chico');
    $pdf->SetTitle('Reporte de Asistencia - ' . $acta['titulo']);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    $color_primary = [10, 43, 60];
    $color_secondary = [100, 100, 100];
    $color_linea = [180, 180, 180];
    
    $logo_peru = __DIR__ . '/../../assets/img/logo_peru.jpg';
    $logo_callqui = __DIR__ . '/../../assets/img/logo_callqui.jpg';
    
    if (file_exists($logo_peru)) {
        $pdf->Image($logo_peru, 15, 8, 18, 18, 'JPG');
    }
    if (file_exists($logo_callqui)) {
        $pdf->Image($logo_callqui, 177, 8, 18, 18, 'JPG');
    }
    
    $pdf->SetDrawColor($color_linea[0], $color_linea[1], $color_linea[2]);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, 32, 195, 32);
    
    $pdf->SetFont('times', 'B', 16);
    $pdf->SetTextColor($color_primary[0], $color_primary[1], $color_primary[2]);
    $pdf->Cell(0, 8, 'COMUNIDAD CAMPESINA CALLQUI CHICO', 0, 1, 'C');
    
    $pdf->SetFont('times', 'B', 12);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(0, 6, 'REPORTE OFICIAL DE ASISTENCIA', 0, 1, 'C');
    
    $pdf->Ln(5);
    
    $pdf->SetFont('times', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    
    $titulo_limpio = html_entity_decode($acta['titulo'], ENT_QUOTES, 'UTF-8');
    
    $pdf->Cell(30, 6, 'Acta:', 0, 0);
    $pdf->SetFont('times', 'B', 10);
    $pdf->Cell(0, 6, $titulo_limpio, 0, 1);
    
    $pdf->SetFont('times', '', 10);
    $pdf->Cell(30, 6, 'Fecha:', 0, 0);
    $pdf->SetFont('times', 'B', 10);
    $pdf->Cell(0, 6, date('d/m/Y', strtotime($acta['fecha'])), 0, 1);
    
    $pdf->Ln(8);
    
    $pdf->SetFillColor($color_primary[0], $color_primary[1], $color_primary[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('times', 'B', 9);
    
    $pdf->Cell(15, 8, 'PADRÓN', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'DNI', 1, 0, 'C', true);
    $pdf->Cell(65, 8, 'NOMBRE COMPLETO', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'ESTADO', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'FIRMA', 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('times', '', 9);
    
    $stmt = $conn->prepare($sql_asistencias);
    $stmt->bind_param("i", $acta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(15, 7, $row['padron'] ?? '-', 1, 0, 'C');
        $pdf->Cell(25, 7, $row['dni'], 1, 0, 'C');
        $pdf->Cell(65, 7, htmlspecialchars($row['apellidos'] . ' ' . $row['nombres']), 1, 0, 'L');
        
        if ($row['estado'] == 'asistio') {
            $pdf->SetTextColor(22, 101, 52);
            $pdf->Cell(30, 7, 'ASISTIÓ', 1, 0, 'C');
        } else {
            $pdf->SetTextColor(153, 27, 27);
            $pdf->Cell(30, 7, 'FALTÓ', 1, 0, 'C');
        }
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->Cell(45, 7, '', 1, 1);
    }
    $stmt->close();
    
    $pdf->Ln(5);
    
    $pdf->SetDrawColor($color_linea[0], $color_linea[1], $color_linea[2]);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    $pdf->SetFont('times', 'B', 10);
    $pdf->SetTextColor($color_primary[0], $color_primary[1], $color_primary[2]);
    $pdf->Cell(0, 6, 'RESUMEN: Total: ' . $total . ' comuneros  |  Asistieron: ' . $asistieron . '  |  Faltaron: ' . $faltaron, 0, 1, 'C');
    
    $pdf->Ln(8);
    
    $pdf->SetDrawColor($color_linea[0], $color_linea[1], $color_linea[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(15, 280, 195, 280);
    
    $pdf->SetFont('times', 'I', 8);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(0, 4, 'Documento generado por el Sistema de Gestión de la Comunidad Campesina Callqui Chico', 0, 1, 'C');
    $pdf->Cell(0, 4, 'Fecha de generación: ' . date('d/m/Y H:i'), 0, 1, 'C');
    
    $filename = 'Reporte_Asistencia_Acta_' . $acta_id . '.pdf';
    $pdf->Output($filename, 'I');
    
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte Oficial de Asistencia</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #0a2b3c;
    --accent: #c9a45c;
    --bg-page: #f2efe6;
}
body {
    background: var(--bg-page);
    font-family: 'Inter', sans-serif;
    padding: 20px;
}
.documento {
    max-width: 900px;
    margin: auto;
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15);
}
.header {
    text-align: center;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 20px;
    margin-bottom: 25px;
}
.logo-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.logo-img {
    height: 70px;
    object-fit: contain;
}
.titulo {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary);
    margin: 10px 0 5px;
}
.subtitulo {
    font-size: 16px;
    color: #666;
}
.info {
    display: flex;
    justify-content: space-between;
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    font-size: 15px;
}
.info-item strong {
    color: var(--primary);
}
.botones {
    margin: 20px 0;
    display: flex;
    gap: 15px;
}
.btn-descargar {
    background: var(--primary);
    color: white;
    padding: 12px 25px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}
.btn-descargar:hover {
    background: var(--accent);
    color: var(--primary);
}
.btn-regresar {
    background: #e9ecef;
    color: #495057;
    padding: 12px 25px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}
.btn-regresar:hover {
    background: #dee2e6;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th {
    background: var(--primary);
    color: white;
    padding: 12px;
    font-size: 14px;
    text-align: left;
}
td {
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    font-size: 14px;
}
tr:nth-child(even) {
    background: #f8f9fa;
}
tr:hover {
    background: #e9ecef;
}
.estado-presente {
    color: #166534;
    font-weight: 600;
    background: #dcfce7;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
}
.estado-ausente {
    color: #991b1b;
    font-weight: 600;
    background: #fee2e2;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
}
.columna-firma {
    border-left: 2px dashed #ccc;
    width: 80px;
}
.footer {
    margin-top: 30px;
    text-align: center;
    font-size: 13px;
    color: #666;
    border-top: 1px solid #e0e0e0;
    padding-top: 15px;
}
</style>
</head>
<body>

<div class="documento">

<div class="header">
    <div class="logo-container">
        <img src="../../assets/img/logo_peru.jpg" class="logo-img" alt="Logo Perú">
        <img src="../../assets/img/logo_callqui.png" class="logo-img" alt="Logo Callqui">
    </div>
    <div class="titulo">COMUNIDAD CAMPESINA CALLQUI CHICO</div>
    <div class="subtitulo">REPORTE OFICIAL DE ASISTENCIA</div>
</div>

<?php $titulo_limpio = html_entity_decode($acta['titulo'], ENT_QUOTES, 'UTF-8'); ?>
<div class="info">
    <div class="info-item">
        <strong><i class="bi bi-file-text me-2"></i>Acta:</strong> <?= $titulo_limpio ?>
    </div>
    <div class="info-item">
        <strong><i class="bi bi-calendar me-2"></i>Fecha:</strong> <?= date('d/m/Y', strtotime($acta['fecha'])) ?>
    </div>
</div>

<div class="botones">
    <a href="?acta_id=<?= $acta_id ?>&pdf=1" class="btn-descargar">
        <i class="bi bi-file-pdf"></i> DESCARGAR PDF
    </a>
    <a href="asistencias.php" class="btn-regresar">
        <i class="bi bi-arrow-left"></i> REGRESAR
    </a>
</div>

<table>
    <thead>
        <tr>
            <th style="width: 80px;">PADRÓN</th>
            <th style="width: 100px;">DNI</th>
            <th>NOMBRE COMPLETO</th>
            <th style="width: 100px;">ESTADO</th>
            <th class="columna-firma" style="width: 100px;">FIRMA</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $stmt = $conn->prepare($sql_asistencias);
        $stmt->bind_param("i", $acta_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){ 
        ?>
        <tr>
            <td><?= $row['padron'] ?? '-' ?></td>
            <td><?= $row['dni'] ?></td>
            <td><?= htmlspecialchars($row['apellidos'] . ' ' . $row['nombres']) ?></td>
            <td>
                <?php if($row['estado'] == 'asistio'): ?>
                    <span class="estado-presente">ASISTIÓ</span>
                <?php else: ?>
                    <span class="estado-ausente">FALTÓ</span>
                <?php endif; ?>
            </td>
            <td class="columna-firma"></td>
        </tr>
        <?php } ?>
        <?php $stmt->close(); ?>
    </tbody>
</table>

<div class="footer">
    <strong>Resumen:</strong> Total: <?= $total ?> comuneros | Asistieron: <?= $asistieron ?> | Faltaron: <?= $faltaron ?>
    <br>
    Sistema Oficial de Control de Asistencia - Comunidad Campesina Callqui Chico
</div>

</div>

</body>
</html>