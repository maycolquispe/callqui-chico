<?php
/**
 * Service - Pagos
 * Lógica de negocio del módulo de pagos
 * Sistema Callqui Chico - Modular
 */
require_once __DIR__ . '/Repository.php';

class PagoService {
    
    private $repository;
    private $upload_dir;
    
    public function __construct() {
        $this->repository = new PagoRepository();
        $this->upload_dir = __DIR__ . '/../../storage/uploads/pagos';
        
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }
    
    public function registrarPago($datos) {
        $errores = $this->validarDatos($datos);
        if (!empty($errores)) {
            return ['success' => false, 'errors' => $errores];
        }
        
        $numero_propietarios = intval($datos['numero_propietarios'] ?? 1);
        $monto = 50 + ($numero_propietarios - 1) * 250;
        
        $año = date('Y');
        $max_num = $this->repository->obtenerMaxCodigo($año);
        $codigo_pago = 'CCP-' . $año . '-' . str_pad($max_num + 1, 4, '0', STR_PAD_LEFT);
        
        $comprobante = $this->procesarComprobante(
            $_FILES['comprobante'] ?? null, 
            $codigo_pago
        );
        
        $pagoData = [
            'codigo_pago' => $codigo_pago,
            'id_solicitud' => intval($datos['id_solicitud']),
            'numero_propietarios' => $numero_propietarios,
            'monto' => $monto,
            'medio_pago' => $datos['medio_pago'] ?? 'Yape',
            'numero_operacion' => trim($datos['numero_operacion'] ?? ''),
            'comprobante' => $comprobante
        ];
        
        $pago_id = $this->repository->crear($pagoData);
        
        if ($pago_id) {
            $this->repository->actualizarEstadoAdjudicacion(
                $pagoData['id_solicitud'], 
                $pago_id
            );
            
            return [
                'success' => true,
                'message' => 'Pago registrado correctamente',
                'codigo_pago' => $codigo_pago,
                'monto' => $monto,
                'pago_id' => $pago_id
            ];
        }
        
        return ['success' => false, 'message' => 'Error al registrar pago'];
    }
    
    public function verificarPago($id_solicitud) {
        $pago = $this->repository->obtenerPorSolicitud($id_solicitud);
        
        if (!$pago) {
            return [
                'success' => true,
                'tiene_pago' => false,
                'puede_firmar' => false,
                'mensaje' => 'No existe pago registrado. Debe realizar el pago primero.'
            ];
        }
        
        $puede_firmar = ($pago['estado'] === 'validado');
        
        return [
            'success' => true,
            'tiene_pago' => true,
            'pago' => [
                'codigo' => $pago['codigo_pago'],
                'monto' => $pago['monto'],
                'estado' => $pago['estado'],
                'medio' => $pago['medio_pago'],
                'fecha' => $pago['fecha_pago']
            ],
            'puede_firmar' => $puede_firmar,
            'mensaje' => $puede_firmar 
                ? 'Pago validado. Puede proceder con la firma.' 
                : 'El pago aún no ha sido validado por el tesorero.'
        ];
    }
    
    public function obtenerPagos($limit = 50, $offset = 0, $estado = null) {
        return $this->repository->obtenerTodos($limit, $offset, $estado);
    }
    
    public function obtenerPagoPorId($pago_id) {
        return $this->repository->obtenerPorId($pago_id);
    }
    
    public function contarPagos($estado = null) {
        return $this->repository->contar($estado);
    }
    
    public function validarPago($pago_id) {
        $this->repository->cambiarEstado($pago_id, 'validado');
        
        $pago = $this->repository->obtenerPorId($pago_id);
        if ($pago && $pago['id_solicitud']) {
            $conn = getDB();
            $stmt = $conn->prepare("UPDATE adjudicaciones SET estado_pago = 'validado' WHERE id = ?");
            $stmt->bind_param("i", $pago['id_solicitud']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    public function rechazarPago($pago_id, $motivo = '') {
        $this->repository->cambiarEstado($pago_id, 'rechazado');
        
        $pago = $this->repository->obtenerPorId($pago_id);
        if ($pago && $pago['id_solicitud']) {
            $conn = getDB();
            $stmt = $conn->prepare("UPDATE adjudicaciones SET estado_pago = 'rechazado' WHERE id = ?");
            $stmt->bind_param("i", $pago['id_solicitud']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    private function validarDatos($datos) {
        $errores = [];
        
        if (empty($datos['id_solicitud'])) {
            $errores[] = 'ID de solicitud requerido';
        }
        
        $numero_propietarios = intval($datos['numero_propietarios'] ?? 1);
        if ($numero_propietarios < 1 || $numero_propietarios > 4) {
            $errores[] = 'Número de propietarios debe ser entre 1 y 4';
        }
        
        return $errores;
    }
    
    private function procesarComprobante($file, $codigo_pago) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return '';
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (!in_array($ext, $allowed)) {
            return '';
        }
        
        $nombre_archivo = $codigo_pago . '_comprobante.' . $ext;
        $ruta = $this->upload_dir . '/' . $nombre_archivo;
        
        if (move_uploaded_file($file['tmp_name'], $ruta)) {
            return 'storage/uploads/pagos/' . $nombre_archivo;
        }
        
        return '';
    }
}