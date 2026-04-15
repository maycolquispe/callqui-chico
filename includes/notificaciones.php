<?php
/**
 * Sistema de Notificaciones - Callqui Chico
 * Profesional v2.0
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';

class Notificacion {
    
    /**
     * Crear una notificación
     */
    public static function crear($usuarioId, $titulo, $mensaje, $tipo = 'info', $enlace = null, $creadoPor = null) {
        $conn = getDB();
        
        $stmt = $conn->prepare("
            INSERT INTO notificaciones 
            (usuario_id, titulo, mensaje, tipo, enlace, creado_por) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("issssi", $usuarioId, $titulo, $mensaje, $tipo, $enlace, $creadoPor);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        
        return $id;
    }
    
    /**
     * Notificación rápida para usuario actual
     */
    public static function crearParaActual($titulo, $mensaje, $tipo = 'info', $enlace = null) {
        $usuarioId = SessionManager::getUserId();
        if (!$usuarioId) return false;
        
        return self::crear($usuarioId, $titulo, $mensaje, $tipo, $enlace);
    }
    
    /**
     * Notificación a múltiples usuarios
     */
    public static function crearMultiple($usuariosIds, $titulo, $mensaje, $tipo = 'info', $enlace = null, $creadoPor = null) {
        $conn = getDB();
        $creadoPor = $creadoPor ?? SessionManager::getUserId();
        
        $stmt = $conn->prepare("
            INSERT INTO notificaciones 
            (usuario_id, titulo, mensaje, tipo, enlace, creado_por) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($usuariosIds as $usuarioId) {
            $stmt->bind_param("issssi", $usuarioId, $titulo, $mensaje, $tipo, $enlace, $creadoPor);
            $stmt->execute();
        }
        
        $stmt->close();
        return true;
    }
    
    /**
     * Notificar a todos los comuneros
     */
    public static function crearParaTodos($titulo, $mensaje, $tipo = 'info', $creadoPor = null) {
        $conn = getDB();
        
        $result = $conn->query("SELECT id FROM usuarios WHERE estado = 'activo'");
        $usuarios = $result->fetch_all(MYSQLI_ASSOC);
        
        return self::crearMultiple(array_column($usuarios, 'id'), $titulo, $mensaje, $tipo, null, $creadoPor);
    }
    
    /**
     * Obtener notificaciones del usuario
     */
    public static function getPorUsuario($usuarioId, $limite = 10, $noLeidas = false) {
        $conn = getDB();
        
        $sql = "SELECT * FROM notificaciones WHERE usuario_id = ?";
        if ($noLeidas) {
            $sql .= " AND leido = 0";
        }
        $sql .= " ORDER BY fecha_creacion DESC LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $usuarioId, $limite);
        $stmt->execute();
        $result = $stmt->get_result();
        $notificaciones = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $notificaciones;
    }
    
    /**
     * Contar notificaciones no leídas
     */
    public static function contarNoLeidas($usuarioId) {
        $conn = getDB();
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leido = 0");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['total'] ?? 0;
    }
    
    /**
     * Marcar como leída
     */
    public static function marcarLeida($notificacionId, $usuarioId) {
        $conn = getDB();
        
        $stmt = $conn->prepare("UPDATE notificaciones SET leido = 1 WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $notificacionId, $usuarioId);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
    
    /**
     * Marcar todas como leídas
     */
    public static function marcarTodasLeidas($usuarioId) {
        $conn = getDB();
        
        $stmt = $conn->prepare("UPDATE notificaciones SET leido = 1 WHERE usuario_id = ? AND leido = 0");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
    
    /**
     * Eliminar notificación
     */
    public static function eliminar($notificacionId, $usuarioId) {
        $conn = getDB();
        
        $stmt = $conn->prepare("DELETE FROM notificaciones WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $notificacionId, $usuarioId);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
    
    /**
     * Notificaciones para adjudiciones
     */
    public static function notificarAdjudicacion($adjudicacionId, $estado, $nombre) {
        $conn = getDB();
        
        // Obtener usuario de la adjudicacion
        $stmt = $conn->prepare("SELECT dni FROM adjudicaciones WHERE id = ?");
        $stmt->bind_param("i", $adjudicacionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $adj = $result->fetch_assoc();
        $stmt->close();
        
        if (!$adj) return false;
        
        // Buscar usuario por DNI
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE dni = ?");
        $stmt->bind_param("s", $adj['dni']);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();
        
        if (!$usuario) return false;
        
        $titulos = [
            'pendiente' => 'Solicitud en Revisión',
            'en_revision' => 'Solicitud en Revisión',
            'aprobado' => '¡Solicitud Aprobada!',
            'rechazado' => 'Solicitud Rechazada'
        ];
        
        $mensajes = [
            'pendiente' => "Tu solicitud de adopción ha sido recibida y está en espera de revisión.",
            'en_revision' => "Tu solicitud de adopción está siendo revisada por el comité.",
            'aprobado' => "¡Felicidades! Tu solicitud de adopción ha sido aprobada.",
            'rechazado' => "Tu solicitud de adopción ha sido rechazada. Revisa las observaciones."
        ];
        
        $tipos = [
            'pendiente' => 'info',
            'en_revision' => 'warning',
            'aprobado' => 'success',
            'rechazado' => 'danger'
        ];
        
        $titulo = $titulos[$estado] ?? 'Actualización de Solicitud';
        $mensaje = $mensajes[$estado] ?? "Hay una actualización en tu solicitud de adopción.";
        $tipo = $tipos[$estado] ?? 'info';
        
        return self::crear($usuario['id'], $titulo, $mensaje, $tipo, 'dashboard/comunero/adjudicaciones.php');
    }
    
    /**
     * Notificar nueva acta
     */
    public static function notificarActa($actaId, $tituloActa) {
        return self::crearParaTodos(
            'Nueva Acta Publicada',
            "Se ha publicado una nueva acta: '$tituloActa'",
            'info',
            'dashboard/comunero/actas.php'
        );
    }
}

// Funciones helper
function notificar($usuarioId, $titulo, $mensaje, $tipo = 'info', $enlace = null) {
    return Notificacion::crear($usuarioId, $titulo, $mensaje, $tipo, $enlace);
}

function notificar_me($titulo, $mensaje, $tipo = 'info', $enlace = null) {
    return Notificacion::crearParaActual($titulo, $mensaje, $tipo, $enlace);
}

function get_notificaciones($limite = 10, $noLeidas = false) {
    $usuarioId = SessionManager::getUserId();
    if (!$usuarioId) return [];
    return Notificacion::getPorUsuario($usuarioId, $limite, $noLeidas);
}

function count_notificaciones_no_leidas() {
    $usuarioId = SessionManager::getUserId();
    if (!$usuarioId) return 0;
    return Notificacion::contarNoLeidas($usuarioId);
}
