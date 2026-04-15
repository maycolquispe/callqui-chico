<?php
/**
 * Service - Firmador Digital
 * Integración con Python para firma digital de PDFs
 * Sistema Callqui Chico - Modular
 */
require_once __DIR__ . '/../../config/database.php';

class FirmadorService {
    
    private $python_script;
    private $documentos_firmados_dir;
    
    public function __construct() {
        $base_path = dirname(__DIR__);
        $this->python_script = $base_path . '/scripts/python/firmar_pdf.py';
        $this->documentos_firmados_dir = $base_path . '/storage/documentos_firmados';
        
        if (!is_dir($this->documentos_firmados_dir)) {
            mkdir($this->documentos_firmados_dir, 0777, true);
        }
    }
    
    public function firmarPDF($pdf_input, $adjudicacion_id, $usuario_id = null) {
        $conn = getDB();
        
        if (!$usuario_id) {
            $usuario_id = $_SESSION['usuario_id'] ?? 1;
        }
        
        $stmt = $conn->prepare("SELECT certificado_digital, password_certificado, nombres, apellidos FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (empty($user['certificado_digital']) || empty($user['password_certificado'])) {
            return ['success' => false, 'message' => 'Usuario no tiene certificado digital configurado'];
        }
        
        $base_path = dirname(__DIR__);
        $cert_path = $base_path . '/storage/' . $user['certificado_digital'];
        
        if (!file_exists($cert_path)) {
            // Intentar también con la ruta original
            $cert_path = $base_path . '/' . $user['certificado_digital'];
        }
        
        if (!file_exists($cert_path)) {
            return ['success' => false, 'message' => 'Certificado digital no encontrado'];
        }
        
        $cert_password = $user['password_certificado'];
        $nombre_firmante = $user['nombres'] . ' ' . $user['apellidos'];
        
        if (!file_exists($pdf_input)) {
            return ['success' => false, 'message' => 'PDF a firmar no encontrado'];
        }
        
        $timestamp = date('YmdHis');
        $pdf_firmado_path = $this->documentos_firmados_dir . '/adjudicacion_' . $adjudicacion_id . '_firmado_' . $timestamp . '.pdf';
        
        if (!file_exists($this->documentos_firmados_dir)) {
            mkdir($this->documentos_firmados_dir, 0777, true);
        }
        
        if (!file_exists($this->python_script)) {
            return ['success' => false, 'message' => 'Script de firma no encontrado: ' . $this->python_script];
        }
        
        $command = sprintf(
            'python "%s" "%s" "%s" "%s" "%s" --firmante "%s" --rol "firma"',
            $this->python_script,
            $pdf_input,
            $pdf_firmado_path,
            $cert_path,
            escapeshellarg($cert_password),
            $nombre_firmante
        );
        
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        if ($return_var !== 0 || empty($output)) {
            return ['success' => false, 'message' => 'Error al ejecutar firma digital', 'debug' => implode("\n", $output)];
        }
        
        $resultado = json_decode(implode("\n", $output), true);
        if (!$resultado || !$resultado['success']) {
            return ['success' => false, 'message' => 'Error en la firma: ' . ($resultado['message'] ?? 'Unknown error')];
        }
        
        if (!file_exists($pdf_firmado_path)) {
            return ['success' => false, 'message' => 'PDF firmado no fue creado'];
        }
        
        $archivo_firmado = 'storage/documentos_firmados/adjudicacion_' . $adjudicacion_id . '_firmado_' . $timestamp . '.pdf';
        
        return [
            'success' => true,
            'pdf_firmado' => $pdf_firmado_path,
            'pdf_firmado_db' => $archivo_firmado,
            'firmante' => $nombre_firmante,
            'fecha' => $resultado['fecha']
        ];
    }
    
    public function verificarCertificado($cert_path, $password) {
        if (!file_exists($cert_path)) {
            return ['success' => false, 'message' => 'Certificado no encontrado'];
        }
        
        return [
            'success' => true,
            'message' => 'Certificado disponible para firma'
        ];
    }
}