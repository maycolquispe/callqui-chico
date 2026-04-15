<?php
/**
 * Sistema de Auditoría - Callqui Chico
 * Profesional v2.0
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';

class Auditoria {
    
    /**
     * Registrar acción en auditoría
     */
    public static function registrar($tabla, $registroId, $accion, $datosAnteriores = null, $datosNuevos = null) {
        $conn = getDB();
        
        $usuarioId = SessionManager::getUserId() ?? null;
        $ip = Util::getIP();
        
        $anteriores = $datosAnteriores ? json_encode($datosAnteriores, JSON_UNESCAPED_UNICODE) : null;
        $nuevos = $datosNuevos ? json_encode($datosNuevos, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt = $conn->prepare("
            INSERT INTO auditoria 
            (tabla, registro_id, usuario_id, accion, datos_anteriores, datos_nuevos, ip) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("siissss", $tabla, $registroId, $usuarioId, $accion, $anteriores, $nuevos, $ip);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
    
    /**
     * Registrar inserción
     */
    public static function insert($tabla, $registroId, $datos) {
        return self::registrar($tabla, $registroId, 'INSERT', null, $datos);
    }
    
    /**
     * Registrar actualización
     */
    public static function update($tabla, $registroId, $datosAnteriores, $datosNuevos) {
        return self::registrar($tabla, $registroId, 'UPDATE', $datosAnteriores, $datosNuevos);
    }
    
    /**
     * Registrar eliminación
     */
    public static function delete($tabla, $registroId, $datos) {
        return self::registrar($tabla, $registroId, 'DELETE', $datos, null);
    }
    
    /**
     * Obtener historial de un registro
     */
    public static function getHistorial($tabla, $registroId) {
        $conn = getDB();
        
        $stmt = $conn->prepare("
            SELECT a.*, u.nombres as usuario_nombre, u.apellidos as usuario_apellidos
            FROM auditoria a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.tabla = ? AND a.registro_id = ?
            ORDER BY a.fecha DESC
        ");
        
        $stmt->bind_param("si", $tabla, $registroId);
        $stmt->execute();
        $result = $stmt->get_result();
        $historial = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $historial;
    }
    
    /**
     * Obtener actividad reciente
     */
    public static function getReciente($limite = 50) {
        $conn = getDB();
        
        $stmt = $conn->prepare("
            SELECT a.*, u.nombres as usuario_nombre, u.apellidos as usuario_apellidos
            FROM auditoria a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            ORDER BY a.fecha DESC
            LIMIT ?
        ");
        
        $stmt->bind_param("i", $limite);
        $stmt->execute();
        $result = $stmt->get_result();
        $actividades = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $actividades;
    }
    
    /**
     * Obtener actividad por usuario
     */
    public static function getPorUsuario($usuarioId, $limite = 20) {
        $conn = getDB();
        
        $stmt = $conn->prepare("
            SELECT a.*, u.nombres as usuario_nombre, u.apellidos as usuario_apellidos
            FROM auditoria a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.usuario_id = ?
            ORDER BY a.fecha DESC
            LIMIT ?
        ");
        
        $stmt->bind_param("ii", $usuarioId, $limite);
        $stmt->execute();
        $result = $stmt->get_result();
        $actividades = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $actividades;
    }
    
    /**
     * Obtener estadísticas de actividad
     */
    public static function getEstadisticas($dias = 30) {
        $conn = getDB();
        
        $sql = "
            SELECT 
                COUNT(*) as total_acciones,
                COUNT(DISTINCT usuario_id) as usuarios_activos,
                COUNT(DISTINCT tabla) as tablas_afectadas,
                DATE(fecha) as fecha
            FROM auditoria 
            WHERE fecha >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(fecha)
            ORDER BY fecha DESC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $dias);
        $stmt->execute();
        $result = $stmt->get_result();
        $estadisticas = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $estadisticas;
    }
}

// Funciones helper
function auditoria_registrar($tabla, $registroId, $accion, $anteriores = null, $nuevos = null) {
    return Auditoria::registrar($tabla, $registroId, $accion, $anteriores, $nuevos);
}

function auditoria_historial($tabla, $registroId) {
    return Auditoria::getHistorial($tabla, $registroId);
}

function auditoria_reciente($limite = 50) {
    return Auditoria::getReciente($limite);
}
