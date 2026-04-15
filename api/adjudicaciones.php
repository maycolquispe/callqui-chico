<?php
/**
 * API de Adjudicaciones - Callqui Chico
 * Profesional v2.0
 * Workflow completo de aprobación
 */

require_once '../../bootstrap.php';
require_once '../../includes/auditoria.php';
require_once '../../includes/notificaciones.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$usuarioId = SessionManager::getUserId();
$rol = SessionManager::getRol();

if (!$usuarioId) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Validar CSRF para acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
        exit;
    }
}

switch ($action) {
    case 'list':
        $estado = $_GET['estado'] ?? '';
        $conn = getDB();
        
        $sql = "SELECT a.*, u.nombres, u.apellidos, u.telefono 
                FROM adjudicaciones a
                LEFT JOIN usuarios u ON a.dni = u.dni
                WHERE 1=1";
        
        $params = [];
        if ($estado) {
            $sql .= " AND a.estado = ?";
            $params[] = $estado;
        }
        
        $sql .= " ORDER BY a.fecha_solicitud DESC LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $adjudicaciones = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode(['success' => true, 'data' => $adjudicaciones]);
        break;
    
    case 'consulta_publica':
        // Consulta pública por código de seguimiento (sin autenticación)
        $codigo = sanitizar($_GET['codigo'] ?? '');
        
        if (!$codigo) {
            echo json_encode(['success' => false, 'error' => 'Código requerido']);
            break;
        }
        
        $conn = getDB();
        $stmt = $conn->prepare("SELECT id, codigo_seguimiento, nombre, dni, lote, manzana, sector, area_m2, estado, observaciones, certificado FROM adjudicaciones WHERE codigo_seguimiento = ? LIMIT 1");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        $adj = $result->fetch_assoc();
        $stmt->close();
        
        if ($adj) {
            echo json_encode(['success' => true, 'data' => $adj]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No encontrado']);
        }
        break;
        
    case 'get':
        $id = intval($_GET['id'] ?? 0);
        $conn = getDB();
        
        $stmt = $conn->prepare("SELECT * FROM adjudicaciones WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $adj = $result->fetch_assoc();
        $stmt->close();
        
        if ($adj) {
            echo json_encode(['success' => true, 'data' => $adj]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No encontrado']);
        }
        break;
        
    case 'crear':
        // Solo comuneros pueden crear
        if ($rol !== 'comunero') {
            echo json_encode(['success' => false, 'error' => 'Sin permiso']);
            break;
        }
        
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $dni = sanitizar($_POST['dni'] ?? '');
        $lote = sanitizar($_POST['lote'] ?? '');
        $manzana = sanitizar($_POST['manzana'] ?? '');
        $sector = sanitizar($_POST['sector'] ?? '');
        $area_m2 = floatval($_POST['area_m2'] ?? 0);
        
        if (!$nombre || !$dni || !$lote) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            break;
        }
        
        $conn = getDB();
        
        // Generar código único de seguimiento: ADJ-YYYY-XXXXXX
        $año = date('Y');
        $codigo_seguimiento = 'ADJ-' . $año . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        
        // Verificar que no exista
        $check = $conn->prepare("SELECT id FROM adjudicaciones WHERE codigo_seguimiento = ?");
        $check->bind_param("s", $codigo_seguimiento);
        $check->execute();
        while ($check->get_result()->num_rows > 0) {
            $codigo_seguimiento = 'ADJ-' . $año . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $check->execute();
        }
        $check->close();
        
        $codigo = generar_codigo('ADJ');
        $estado = 'pendiente';
        $fecha = date('Y-m-d H:i:s');
        
        // Subir archivos
        $archivos = [];
        foreach (['archivo_dni', 'archivo_constancia', 'archivo_plano'] as $campo) {
            if (!empty($_FILES[$campo]['name'])) {
                $resultado = subir_archivo($_FILES[$campo], 'uploads/adjudicaciones');
                if ($resultado['success']) {
                    $archivos[$campo] = $resultado['archivo'];
                }
            }
        }
        
        $stmt = $conn->prepare("
            INSERT INTO adjudicaciones (codigo_seguimiento, codigo, nombre, dni, lote, manzana, sector, area_m2, estado, fecha_solicitud, archivo_dni, archivo_constancia, archivo_plano)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("sssssisssss", 
            $codigo_seguimiento, $codigo, $nombre, $dni, $lote, $manzana, $sector, $area_m2, $estado, $fecha,
            $archivos['archivo_dni'] ?? null,
            $archivos['archivo_constancia'] ?? null,
            $archivos['archivo_plano'] ?? null
        );
        
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            auditoria_registrar('adjudicaciones', $id, 'INSERT', null, $_POST);
            echo json_encode(['success' => true, 'id' => $id, 'codigo' => $codigo, 'codigo_seguimiento' => $codigo_seguimiento]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear']);
        }
        $stmt->close();
        break;
        
    case 'aprobar':
        // Verificar permisos según rol
        $rolesPermitidos = ['secretario', 'presidente', 'fiscal', 'tesorero'];
        if (!in_array($rol, $rolesPermitidos)) {
            echo json_encode(['success' => false, 'error' => 'Sin permiso']);
            break;
        }
        
        $id = intval($_POST['id'] ?? 0);
        $observacion = sanitizar($_POST['observacion'] ?? '');
        
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            break;
        }
        
        $conn = getDB();
        
        // Obtener datos actuales
        $stmt = $conn->prepare("SELECT * FROM adjudicaciones WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $antes = $result->fetch_assoc();
        $stmt->close();
        
        if (!$antes) {
            echo json_encode(['success' => false, 'error' => 'No encontrado']);
            break;
        }
        
        // Actualizar según rol
        $campoAprobado = 'aprobado_' . $rol;
        $campoFecha = 'fecha_aprobacion_' . $rol;
        $campoObs = 'obs_' . $rol;
        
        $sql = "UPDATE adjudicaciones SET $campoAprobado = 1, $campoFecha = NOW(), $campoObs = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $observacion, $id);
        $stmt->execute();
        $stmt->close();
        
        // Verificar si todos aprobaron
        $adj = $conn->query("SELECT * FROM adjudicaciones WHERE id = $id")->fetch_assoc();
        
        $todosAprobados = $adj['aprobado_secretario'] && 
                          $adj['aprobado_presidente'] && 
                          $adj['aprobado_fiscal'] && 
                          $adj['aprobado_tesorero'];
        
        if ($todosAprobados) {
            $conn->query("UPDATE adjudicaciones SET estado = 'aprobado', fecha_estado = NOW() WHERE id = $id");
            Notificacion::notificarAdjudicacion($id, 'aprobado', $adj['nombre']);
        } else {
            Notificacion::notificarAdjudicacion($id, 'en_revision', $adj['nombre']);
        }
        
        auditoria_registrar('adjudicaciones', $id, 'UPDATE', $antes, $adj);
        
        echo json_encode(['success' => true, 'mensaje' => 'Aprobado correctamente']);
        break;
        
    case 'rechazar':
        $rolesPermitidos = ['secretario', 'presidente', 'fiscal', 'tesorero'];
        if (!in_array($rol, $rolesPermitidos)) {
            echo json_encode(['success' => false, 'error' => 'Sin permiso']);
            break;
        }
        
        $id = intval($_POST['id'] ?? 0);
        $observacion = sanitizar($_POST['observacion'] ?? '');
        
        if (!$id || !$observacion) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            break;
        }
        
        $conn = getDB();
        
        $stmt = $conn->prepare("SELECT * FROM adjudicaciones WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $antes = $result->fetch_assoc();
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE adjudicaciones SET estado = 'rechazado', observaciones = ?, fecha_estado = NOW() WHERE id = ?");
        $stmt->bind_param("si", $observacion, $id);
        $stmt->execute();
        $stmt->close();
        
        auditoria_registrar('adjudicaciones', $id, 'UPDATE', $antes, ['estado' => 'rechazado', 'observaciones' => $observacion]);
        
        Notificacion::notificarAdjudicacion($id, 'rechazado', $antes['nombre']);
        
        echo json_encode(['success' => true, 'mensaje' => 'Rechazado correctamente']);
        break;
        
    case 'stats':
        $conn = getDB();
        $stats = [
            'total' => $conn->query("SELECT COUNT(*) as t FROM adjudicaciones")->fetch_assoc()['t'],
            'pendientes' => $conn->query("SELECT COUNT(*) as t FROM adjudicaciones WHERE estado = 'pendiente'")->fetch_assoc()['t'],
            'en_revision' => $conn->query("SELECT COUNT(*) as t FROM adjudicaciones WHERE estado = 'en_revision'")->fetch_assoc()['t'],
            'aprobadas' => $conn->query("SELECT COUNT(*) as t FROM adjudicaciones WHERE estado = 'aprobado'")->fetch_assoc()['t'],
            'aprobado_total' => $conn->query("SELECT COUNT(*) as t FROM adjudicaciones WHERE estado = 'aprobado_total'")->fetch_assoc()['t'],
            'certificado_generado' => $conn->query("SELECT COUNT(*) as t FROM adjudicaciones WHERE estado = 'certificado_generado'")->fetch_assoc()['t'],
            'rechazadas' => $conn->query("SELECT COUNT(*) as t FROM adjudicaciones WHERE estado = 'rechazado'")->fetch_assoc()['t'],
        ];
        echo json_encode(['success' => true, 'data' => $stats]);
        break;
        
    case 'generar_certificado':
        // Solo secretarios pueden generar certificados
        if (!in_array($rol, ['secretario', 'presidente'])) {
            echo json_encode(['success' => false, 'error' => 'Sin permiso']);
            break;
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            break;
        }
        
        $conn = getDB();
        
        // Verificar que esté aprobado
        $stmt = $conn->prepare("SELECT * FROM adjudicaciones WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $adj = $result->fetch_assoc();
        $stmt->close();
        
        if (!$adj) {
            echo json_encode(['success' => false, 'error' => 'No encontrado']);
            break;
        }
        
        if (!in_array($adj['estado'], ['aprobado', 'aprobado_total'])) {
            echo json_encode(['success' => false, 'error' => 'La solicitud debe estar aprobada para generar certificado']);
            break;
        }
        
        // Subir certificado si existe
        $certificado = null;
        if (!empty($_FILES['certificado']['name'])) {
            $resultado = subir_archivo($_FILES['certificado'], 'uploads/certificados');
            if ($resultado['success']) {
                $certificado = $resultado['archivo'];
            }
        }
        
        if ($certificado) {
            $stmt = $conn->prepare("UPDATE adjudicaciones SET certificado = ?, certificado_generado = 1, fecha_certificado = NOW(), estado = 'certificado_generado' WHERE id = ?");
            $stmt->bind_param("si", $certificado, $id);
            $stmt->execute();
            $stmt->close();
            
            auditoria_registrar('adjudicaciones', $id, 'UPDATE', $adj, ['certificado' => $certificado, 'estado' => 'certificado_generado']);
            
            echo json_encode(['success' => true, 'mensaje' => 'Certificado generado correctamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Debe subir el archivo del certificado']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción inválida']);
}
