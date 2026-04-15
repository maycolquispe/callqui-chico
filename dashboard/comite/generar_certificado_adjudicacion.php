<?php
/**
 * Generar Certificado de Adjudicación
 * Comunidad Campesina Callqui Chico
 * 
 * Usa el modelo profesional de funciones/generar_certificado.php
 */

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../funciones/generar_certificado.php';

SessionManager::init();
$conn = getDB();

$adjudicacion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$adjudicacion_id) {
    die("ID de solicitud no válido");
}

// Verificar que la solicitud existe
$stmt = $conn->prepare("SELECT id, nombre FROM adjudicaciones WHERE id = ?");
$stmt->bind_param("i", $adjudicacion_id);
$stmt->execute();
$adj = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$adj) {
    die("Adjudicación no encontrada");
}

// Generar el certificado usando la función central
$resultado = generarCertificadoPDF($adjudicacion_id);

if (!$resultado['success']) {
    die("Error al generar certificado: " . $resultado['message']);
}

// Output del PDF
$pdf_path = $resultado['pdf_path'];
if (file_exists($pdf_path)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="certificado_adjudicacion_' . $adjudicacion_id . '.pdf"');
    readfile($pdf_path);
} else {
    die("PDF no encontrado: " . $pdf_path);
}
