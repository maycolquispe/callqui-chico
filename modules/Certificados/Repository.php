<?php
/**
 * Repository - Certificados
 * Acceso a datos de certificados
 * Sistema Callqui Chico - Modular
 */
require_once __DIR__ . '/../../config/database.php';

class CertificadoRepository {
    
    private $conn;
    
    public function __construct() {
        $this->conn = getDB();
    }
    
    public function obtenerAdjudicacion($id) {
        $sql = "SELECT a.*, u.nombres as nombres_titular, u.apellidos as apellidos_titular, u.dni as dni_titular
                FROM adjudicaciones a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                WHERE a.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function obtenerAdjudicacionPorCodigo($codigo) {
        $sql = "SELECT a.*, u.nombres as nombres_titular, u.apellidos as apellidos_titular, u.dni as dni_titular
                FROM adjudicaciones a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                WHERE a.codigo_certificado = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function actualizarCodigoCertificado($id, $codigo) {
        $sql = "UPDATE adjudicaciones SET codigo_certificado = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $codigo, $id);
        $stmt->execute();
        $stmt->close();
    }
    
    public function actualizarPDF($id, $pdf_path, $pdf_firmado = null) {
        if ($pdf_firmado) {
            $sql = "UPDATE adjudicaciones SET pdf_firmado = ?, certificado = ?, completamente_firmado = 1, estado = 'aprobado_total' WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssi", $pdf_firmado, $pdf_firmado, $id);
        } else {
            $sql = "UPDATE adjudicaciones SET certificado = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $pdf_path, $id);
        }
        $stmt->execute();
        $stmt->close();
    }
    
    public function obtenerFirmasVisuales() {
        $firmas = [];
        try {
            $res = $this->conn->query("SELECT rol, firma_imagen FROM config_firmas_visual WHERE firma_imagen IS NOT NULL AND firma_imagen != ''");
            while ($row = $res->fetch_assoc()) {
                $firmas[$row['rol']] = $row['firma_imagen'];
            }
        } catch (Exception $e) {
            // Tabla puede no existir
        }
        return $firmas;
    }
    
    public function obtenerCertificadosPorUsuario($usuario_id, $limit = 20) {
        $sql = "SELECT a.*, u.nombres, u.apellidos, u.dni
                FROM adjudicaciones a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                WHERE a.usuario_id = ? AND a.certificado IS NOT NULL AND a.certificado != ''
                ORDER BY a.id DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $usuario_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $certificados = [];
        while ($row = $result->fetch_assoc()) {
            $certificados[] = $row;
        }
        
        return $certificados;
    }
}