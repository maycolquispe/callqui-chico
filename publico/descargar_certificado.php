<?php
/**
 * Descargar Certificado - Comunidad Campesina Callqui Chico
 * Con detección de PDF viejo y regeneración automática
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../funciones/generar_certificado.php';

$file = $_GET['file'] ?? '';
$codigo = $_GET['codigo'] ?? '';

// Si viene código, buscar el archivo
if (!empty($codigo)) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT id, certificado, pdf_firmado FROM adjudicaciones WHERE codigo_seguimiento = ? OR codigo_certificado = ? OR codigo = ?");
    $stmt->bind_param("sss", $codigo, $codigo, $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $adjudicacion_id = $row['id'];
        $archivo_existente = $row['pdf_firmado'] ?? $row['certificado'] ?? '';
        
        // Verificar si existe y si es viejo
        $filepath = dirname(__DIR__) . '/' . $archivo_existente;
        
        // Regenerar siempre si hay ID
        $resultado = generarCertificadoPDF($adjudicacion_id);
        
        if ($resultado['success']) {
            // Actualizar la ruta del certificado en la base de datos
            $stmt2 = $conn->prepare("UPDATE adjudicaciones SET certificado = ?, pdf_firmado = ? WHERE id = ?");
            $stmt2->bind_param("ssi", $resultado['pdf_db'], $resultado['pdf_db'], $adjudicacion_id);
            $stmt2->execute();
            $stmt2->close();
            
            $filepath = dirname(__DIR__) . '/' . $resultado['pdf_db'];
        } else {
            die('Error al regenerar certificado: ' . $resultado['message']);
        }
    } else {
        die('Adjudicación no encontrada para código: ' . $codigo);
    }
    $stmt->close();
} elseif (!empty($file)) {
    // El archivo está en la raíz del proyecto
    $filepath = dirname(__DIR__) . '/' . $file;
    
    // Verificar si es un PDF viejo (sin el nuevo formato)
    if (!file_exists($filepath) || !preg_match('/_\d{14}\.pdf$/', basename($filepath))) {
        // Extraer ID del nombre del archivo
        if (preg_match('/certificado_adjudicacion_(\d+)/', $filepath, $matches)) {
            $adjudicacion_id = intval($matches[1]);
            
            // Regenerar certificado automáticamente
            $resultado = generarCertificadoPDF($adjudicacion_id);
            
            if ($resultado['success']) {
                $conn = getDB();
                $stmt = $conn->prepare("UPDATE adjudicaciones SET certificado = ?, pdf_firmado = ? WHERE id = ?");
                $stmt->bind_param("ssi", $resultado['pdf_db'], $resultado['pdf_db'], $adjudicacion_id);
                $stmt->execute();
                $stmt->close();
                
                $filepath = dirname(__DIR__) . '/' . $resultado['pdf_db'];
            }
        }
    }
} else {
    die('Archivo no especificado');
}

if (!file_exists($filepath)) {
    echo "Archivo no encontrado: " . $filepath;
    exit;
}

$es_descarga = isset($_GET['download']) && $_GET['download'] === '1';

header('Content-Type: application/pdf');
if ($es_descarga) {
    header('Content-Disposition: attachment; filename="Certificado_Callqui_Chico.pdf"');
} else {
    header('Content-Disposition: inline; filename="Certificado_Callqui_Chico.pdf"');
}
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;