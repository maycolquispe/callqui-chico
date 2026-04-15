<?php
/**
 * API de Notificaciones - Callqui Chico
 * Profesional v2.0
 */

require_once '../../bootstrap.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $notificaciones = get_notificaciones(20, false);
        $noLeidas = count_notificaciones_no_leidas();
        
        echo json_encode([
            'success' => true,
            'notificaciones' => $notificaciones,
            'noLeidas' => $noLeidas
        ]);
        break;
        
    case 'no_leidas':
        $noLeidas = count_notificaciones_no_leidas();
        echo json_encode(['success' => true, 'noLeidas' => $noLeidas]);
        break;
        
    case 'marcar_leida':
        $id = intval($_POST['id'] ?? 0);
        $usuarioId = SessionManager::getUserId();
        
        if ($id && $usuarioId) {
            Notificacion::marcarLeida($id, $usuarioId);
            $noLeidas = count_notificaciones_no_leidas();
            echo json_encode(['success' => true, 'noLeidas' => $noLeidas]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        }
        break;
        
    case 'marcar_todas_leidas':
        $usuarioId = SessionManager::getUserId();
        if ($usuarioId) {
            Notificacion::marcarTodasLeidas($usuarioId);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción inválida']);
}
