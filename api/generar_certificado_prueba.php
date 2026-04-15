<?php
/**
 * API: Generar y Firmar Certificado de Adjudicación
 * Sistema de Prueba - 2 Roles: Secretario + Presidente
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id_solicitud = isset($input['id_solicitud']) ? intval($input['id_solicitud']) : 0;

if (!$id_solicitud) {
    echo json_encode(['success' => false, 'message' => 'ID de solicitud requerido']);
    exit;
}

$conn = getDB();

// Obtener datos de la solicitud
$stmt = $conn->prepare("SELECT * FROM adjudicaciones WHERE id = ?");
$stmt->bind_param("i", $id_solicitud);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$solicitud) {
    echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
    exit;
}

// Verificar que ambas firmas estén completas
$stmt = $conn->prepare("SELECT rol FROM firmas_digitales WHERE id_solicitud = ? AND tipo_documento = 'adjudicacion'");
$stmt->bind_param("i", $id_solicitud);
$stmt->execute();
$result = $stmt->get_result();

$firmas = [];
while ($row = $result->fetch_assoc()) {
    $firmas[] = $row['rol'];
}
$stmt->close();

// Verificar que secretary y presidente hayan firmado
if (!in_array('secretario', $firmas) || !in_array('presidente', $firmas)) {
    echo json_encode(['success' => false, 'message' => 'Aún no se han completado todas las firmas requeridas']);
    exit;
}

// ==================== GENERAR PDF CON TCPDF ====================
require_once __DIR__ . '/../vendor/tcpdf/tcpdf.php';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('Callqui Chico');
$pdf->SetAuthor('Comunidad Campesina Callqui Chico');
$pdf->SetTitle('Certificado de Adjudicación');
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

// Fondo
$fondo = __DIR__ . '/../assets/img/fondo_certificado.png';
if (file_exists($fondo)) {
    $pdf->Image($fondo, 0, 0, 210, 297, 'PNG');
}

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

// Datos del adjudicatario
$pdf->SetXY(60, 72);
$pdf->Cell(0, 5, strtoupper($solicitud['apellidos'] . ', ' . $solicitud['nombre']), 0, 1);

$pdf->SetXY(60, 82);
$pdf->Cell(0, 5, $solicitud['dni'], 0, 1);

$pdf->SetXY(60, 92);
$pdf->Cell(0, 5, strtoupper($solicitud['conyuge_nombre'] ?? ''), 0, 1);

$pdf->SetXY(60, 102);
$pdf->Cell(0, 5, $solicitud['conyuge_dni'] ?? '', 0, 1);

$pdf->SetXY(60, 112);
$pdf->Cell(0, 5, $solicitud['estado_civil'] ?? 'SOLTERO(A)', 0, 1);

// Datos del terreno
$pdf->SetXY(60, 132);
$pdf->Cell(0, 5, $solicitud['sector'] ?? 'CHUÑURANRA', 0, 1);

$pdf->SetXY(60, 142);
$pdf->Cell(0, 5, $solicitud['manzana'] ?? '-', 0, 1);

$pdf->SetXY(60, 152);
$pdf->Cell(0, 5, $solicitud['lote'] ?? '-', 0, 1);

$pdf->SetXY(60, 162);
$pdf->Cell(0, 5, ($solicitud['area_m2'] ?? $solicitud['area'] ?? '0') . ' m²', 0, 1);

$pdf->SetXY(60, 172);
$pdf->Cell(0, 5, ($solicitud['perimetro_ml'] ?? '0') . ' ml', 0, 1);

// Linderos
$pdf->SetXY(60, 192);
$pdf->Cell(0, 5, $solicitud['lindero_frente'] ?? 'Según plano de lotización', 0, 1);

$pdf->SetXY(60, 202);
$pdf->Cell(0, 5, $solicitud['lindero_fondo'] ?? 'Según plano de lotización', 0, 1);

$pdf->SetXY(60, 212);
$pdf->Cell(0, 5, $solicitud['lindero_derecha'] ?? 'Según plano de lotización', 0, 1);

$pdf->SetXY(60, 222);
$pdf->Cell(0, 5, $solicitud['lindero_izquierda'] ?? 'Según plano de lotización', 0, 1);

// Código único
$codigo = $solicitud['codigo_certificado'] ?? 'ADJ-' . date('Y') . '-' . str_pad($id_solicitud, 4, '0', STR_PAD_LEFT);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(140, 30);
$pdf->Cell(0, 5, $codigo, 0, 1);

// QR
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = '/2026';
$qr_data = $protocol . $host . $base_path . '/publico/certificado.php?codigo=' . $codigo;
$qr_url = 'https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl=' . urlencode($qr_data);

$ch = curl_init($qr_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$qr_image = curl_exec($ch);
curl_close($ch);

$qr_dir = __DIR__ . '/../storage/qr';
if (!is_dir($qr_dir)) {
    mkdir($qr_dir, 0777, true);
}
$qr_file = $qr_dir . '/qr_' . $codigo . '.png';
if ($qr_image) {
    file_put_contents($qr_file, $qr_image);
}
if (file_exists($qr_file)) {
    $pdf->Image($qr_file, 90, 245, 30, 30, 'PNG');
}

// Firmas
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(30, 275);
$pdf->Cell(40, 5, 'PRESIDENTE', 0, 1, 'C');
$pdf->SetXY(85, 275);
$pdf->Cell(40, 5, 'SECRETARIO', 0, 1, 'C');
$pdf->SetXY(140, 275);
$pdf->Cell(40, 5, 'COMITÉ DE LOTES', 0, 1, 'C');

// Guardar PDF sin firmar
$documentos_dir = __DIR__ . '/../storage/documentos';
if (!is_dir($documentos_dir)) {
    mkdir($documentos_dir, 0777, true);
}

$nombre_pdf = 'certificado_adjudicacion_' . $id_solicitud . '.pdf';
$pdf_sin_firmar = $documentos_dir . '/' . $nombre_pdf;
$pdf_db = 'storage/documentos/' . $nombre_pdf;

$pdf->Output($pdf_sin_firmar, 'F');

// ==================== FIRMAR PDF CON PYTHON ====================
// Obtener certificado del presidente (último en firmar)
$stmt = $conn->prepare("SELECT u.certificado_digital, u.password_certificado, u.nombres, u.apellidos 
                        FROM firmas_digitales f 
                        JOIN usuarios u ON f.id_usuario = u.id 
                        WHERE f.id_solicitud = ? AND f.rol = 'presidente' 
                        ORDER BY f.fecha_firma DESC LIMIT 1");
$stmt->bind_param("i", $id_solicitud);
$stmt->execute();
$firma_presidente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$firma_presidente || empty($firma_presidente['certificado_digital'])) {
    echo json_encode(['success' => false, 'message' => 'No hay certificado del presidente']);
    exit;
}

$cert_path = __DIR__ . '/../storage/certificados/' . basename($firma_presidente['certificado_digital']);
$cert_password = $firma_presidente['password_certificado'];
$firmante = $firma_presidente['nombres'] . ' ' . $firma_presidente['apellidos'];

if (!file_exists($cert_path)) {
    echo json_encode(['success' => false, 'message' => 'Certificado del presidente no encontrado']);
    exit;
}

// Firmar PDF
$pdf_firmado_dir = __DIR__ . '/../storage/documentos_firmados';
if (!is_dir($pdf_firmado_dir)) {
    mkdir($pdf_firmado_dir, 0777, true);
}

$nombre_firmado = 'certificado_firmado_' . $id_solicitud . '_' . date('YmdHis') . '.pdf';
$pdf_firmado = $pdf_firmado_dir . '/' . $nombre_firmado;
$pdf_firmado_db = 'storage/documentos_firmados/' . $nombre_firmado;

$python_script = __DIR__ . '/../scripts/python/firmar_pdf.py';
$command = sprintf(
    'python "%s" "%s" "%s" "%s" "%s" --firmante "%s" --rol "presidente"',
    $python_script,
    $pdf_sin_firmar,
    $pdf_firmado,
    $cert_path,
    escapeshellarg($cert_password),
    $firmante
);

$output = [];
$return_var = 0;
exec($command, $output, $return_var);

if ($return_var !== 0 || !file_exists($pdf_firmado)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al firmar PDF con Python',
        'debug' => implode("\n", $output)
    ]);
    exit;
}

// ==================== ACTUALIZAR BASE DE DATOS ====================
// Guardar código único si no existe
if (empty($solicitud['codigo_certificado'])) {
    $stmt = $conn->prepare("UPDATE adjudicaciones SET codigo_certificado = ? WHERE id = ?");
    $stmt->bind_param("si", $codigo, $id_solicitud);
    $stmt->execute();
    $stmt->close();
}

// Actualizar campos
$stmt = $conn->prepare("UPDATE adjudicaciones SET 
    certificado = ?,
    pdf_firmado = ?,
    qr_code = ?,
    estado = 'aprobado_total',
    completamente_firmado = 1,
    certificado_generado = 1,
    fecha_generacion_cert = NOW()
    WHERE id = ?");
$qr_db = 'storage/qr/qr_' . $codigo . '.png';
$stmt->bind_param("sssi", $pdf_db, $pdf_firmado_db, $qr_db, $id_solicitud);
$stmt->execute();
$stmt->close();

// Responder
echo json_encode([
    'success' => true,
    'message' => 'Certificado generado y firmado exitosamente',
    'data' => [
        'codigo' => $codigo,
        'pdf_sin_firmar' => $pdf_db,
        'pdf_firmado' => $pdf_firmado_db,
        'qr' => $qr_db
    ]
]);