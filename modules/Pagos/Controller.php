<?php
/**
 * Controller - Pagos
 * Endpoints API del módulo de pagos
 * Sistema Callqui Chico - Modular
 */
header('Content-Type: application/json');
require_once __DIR__ . '/Service.php';

class PagoController {
    
    private $service;
    
    public function __construct() {
        $this->service = new PagoService();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($method === 'POST') {
            return $this->registrar();
        } elseif ($method === 'GET') {
            $action = $_GET['action'] ?? '';
            
            if ($action === 'list') {
                return $this->listar();
            } elseif ($action === 'contar') {
                return $this->contar();
            }
            
            return $this->verificar();
        }
        
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
    
    private function registrar() {
        $resultado = $this->service->registrarPago($_POST);
        echo json_encode($resultado);
    }
    
    private function verificar() {
        $id_solicitud = intval($_GET['id_solicitud'] ?? 0);
        
        if (!$id_solicitud) {
            echo json_encode(['success' => false, 'message' => 'ID de solicitud requerido']);
            return;
        }
        
        $resultado = $this->service->verificarPago($id_solicitud);
        echo json_encode($resultado);
    }
    
    private function listar() {
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        $estado = $_GET['estado'] ?? null;
        
        $pagos = $this->service->obtenerPagos($limit, $offset, $estado);
        
        echo json_encode([
            'success' => true,
            'pagos' => $pagos
        ]);
    }
    
    private function contar() {
        $estado = $_GET['estado'] ?? null;
        $total = $this->service->contarPagos($estado);
        
        echo json_encode([
            'success' => true,
            'total' => $total
        ]);
    }
}

$controller = new PagoController();
$controller->handleRequest();