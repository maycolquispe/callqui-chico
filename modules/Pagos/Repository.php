<?php
/**
 * Repository - Pagos
 * Acceso a datos de pagos
 * Sistema Callqui Chico - Modular
 */
require_once __DIR__ . '/../../config/database.php';

class PagoRepository {
    
    private $conn;
    
    public function __construct() {
        $this->conn = getDB();
    }
    
    public function obtenerPorSolicitud($id_solicitud) {
        $sql = "SELECT p.id, p.codigo_pago, p.monto, p.estado, p.medio_pago, 
                       p.numero_operacion, p.fecha_pago, p.comprobante,
                       a.nombre, a.dni, a.lote, a.manzana
                FROM pagos p
                JOIN adjudicaciones a ON p.id_solicitud = a.id
                WHERE p.id_solicitud = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_solicitud);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function obtenerPorId($pago_id) {
        $sql = "SELECT * FROM pagos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $pago_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function obtenerMaxCodigo($año) {
        $sql = "SELECT MAX(CAST(SUBSTRING(codigo_pago, 9) AS UNSIGNED)) as max_num 
                FROM pagos WHERE codigo_pago LIKE ?";
        $like = "CCP-$año-%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['max_num'] ?? 0;
    }
    
    public function crear($datos) {
        $sql = "INSERT INTO pagos (codigo_pago, id_solicitud, numero_propietarios, 
                      monto, medio_pago, numero_operacion, comprobante, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "siissss",
            $datos['codigo_pago'],
            $datos['id_solicitud'],
            $datos['numero_propietarios'],
            $datos['monto'],
            $datos['medio_pago'],
            $datos['numero_operacion'],
            $datos['comprobante']
        );
        
        $stmt->execute();
        $id = $this->conn->insert_id;
        $stmt->close();
        
        return $id;
    }
    
    public function actualizarEstadoAdjudicacion($id_solicitud, $pago_id) {
        $sql = "UPDATE adjudicaciones SET estado_pago = 'pendiente', pago_id = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $pago_id, $id_solicitud);
        $stmt->execute();
        $stmt->close();
    }
    
    public function obtenerTodos($limit = 50, $offset = 0, $estado = null) {
        if ($estado) {
            $sql = "SELECT p.*, a.nombre as nombre_adjudicatario, a.lote, a.manzana, a.sector
                    FROM pagos p
                    LEFT JOIN adjudicaciones a ON p.id_solicitud = a.id
                    WHERE p.estado = ?
                    ORDER BY p.id DESC
                    LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sii", $estado, $limit, $offset);
        } else {
            $sql = "SELECT p.*, a.nombre as nombre_adjudicatario, a.lote, a.manzana, a.sector
                    FROM pagos p
                    LEFT JOIN adjudicaciones a ON p.id_solicitud = a.id
                    ORDER BY p.id DESC
                    LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $pagos = [];
        while ($row = $result->fetch_assoc()) {
            $pagos[] = $row;
        }
        
        return $pagos;
    }
    
    public function cambiarEstado($pago_id, $estado) {
        $sql = "UPDATE pagos SET estado = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $estado, $pago_id);
        $stmt->execute();
        $stmt->close();
    }
    
    public function contar($estado = null) {
        if ($estado) {
            $sql = "SELECT COUNT(*) as total FROM pagos WHERE estado = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $estado);
        } else {
            $sql = "SELECT COUNT(*) as total FROM pagos";
            $stmt = $this->conn->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }
}