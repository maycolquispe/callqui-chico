<?php
/**
 * Controller - Certificados
 * Endpoints API del módulo de certificados
 * Sistema Callqui Chico - Modular
 */
header('Content-Type: application/json');
require_once __DIR__ . '/Service.php';

class CertificadoController {
    
    private $service;
    
    public function __construct() {
        $this->service = new CertificadoService();
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'generar':
                return $this->generar();
            case 'firmar':
                return $this->firmar();
            case 'generar_y_firmar':
                return $this->generarYFirmar();
            case 'verificar':
                return $this->verificar();
            case 'mis_certificados':
                return $this->misCertificados();
            default:
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
    }
    
    private function generar() {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID de adjudicación requerido']);
            return;
        }
        
        $resultado = $this->service->generarCertificadoPDF($id);
        echo json_encode($resultado);
    }
    
    private function firmar() {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID de adjudicación requerido']);
            return;
        }
        
        $usuario_id = intval($_GET['usuario_id'] ?? $_SESSION['usuario_id'] ?? null);
        
        $resultado = $this->service->firmarCertificado($id, $usuario_id);
        echo json_encode($resultado);
    }
    
    private function generarYFirmar() {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID de adjudicación requerido']);
            return;
        }
        
        $resultado = $this->service->generarYFirmar($id);
        echo json_encode($resultado);
    }
    
    private function verificar() {
        $codigo = $_GET['codigo'] ?? '';
        
        if (!$codigo) {
            echo json_encode(['success' => false, 'message' => 'Código de certificado requerido']);
            return;
        }
        
        $certificado = $this->service->obtenerPorCodigo($codigo);
        
        if (!$certificado) {
            echo json_encode(['success' => false, 'message' => 'Certificado no encontrado']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'certificado' => [
                'codigo' => $certificado['codigo_certificado'],
                'nombre' => $certificado['nombre'],
                'dni' => $certificado['dni'],
                'lote' => $certificado['lote'],
                'manzana' => $certificado['manzana'],
                'sector' => $certificado['sector'],
                'estado' => $certificado['estado']
            ]
        ]);
    }
    
    private function misCertificados() {
        $usuario_id = intval($_GET['usuario_id'] ?? $_SESSION['usuario_id'] ?? 0);
        
        if (!$usuario_id) {
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
            return;
        }
        
        $certificados = $this->service->obtenerMisCertificados($usuario_id);
        
        echo json_encode([
            'success' => true,
            'certificados' => $certificados
        ]);
    }
}

$controller = new CertificadoController();
$controller->handleRequest();