<?php
/**
 * API: Obtener Estado de Firmas Digitales
 * Comunidad Campesina Callqui Chico
 * 
 * Retorna el estado de firmas para una solicitud
 */

ini_set('session.cookie_path', '/');
ini_set('session.cookie_samesite', 'Lax');
session_start();

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET' && $method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener parámetros
$id_solicitud = 0;
$tipo_documento = 'adjudicacion';

if ($method === 'GET') {
    $id_solicitud = isset($_GET['id_solicitud']) ? intval($_GET['id_solicitud']) : 0;
    $tipo_documento = isset($_GET['tipo_documento']) ? $_GET['tipo_documento'] : 'adjudicacion';
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_solicitud = isset($input['id_solicitud']) ? intval($input['id_solicitud']) : 0;
    $tipo_documento = isset($input['tipo_documento']) ? $input['tipo_documento'] : 'adjudicacion';
}

if (!$id_solicitud) {
    echo json_encode(['success' => false, 'message' => 'ID de solicitud requerido']);
    exit;
}

$conn = getDB();

// Obtener información de la solicitud
$stmt = $conn->prepare("SELECT * FROM adjudicaciones WHERE id = ?");
$stmt->bind_param("i", $id_solicitud);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$solicitud) {
    echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
    exit;
}

// Orden de firmas: Tesorero -> Comité -> Secretario -> Presidente
$orden_firma = [
    'adjudicacion' => ['tesorero', 'comite_lotes', 'secretario', 'presidente'],
    'certificado_transferencia' => ['tesorero', 'comite_lotes', 'secretario', 'presidente'],
    'solicitud_general' => ['tesorero', 'comite_lotes', 'secretario', 'presidente']
];

$orden_actual = isset($orden_firma[$tipo_documento]) ? $orden_firma[$tipo_documento] : $orden_firma['adjudicacion'];

// Roles válidos para firma - incluye tesorero y comite_lotes
$roles_validos = ['secretario', 'tesorero', 'presidente', 'comite_lotes'];

// Obtener firmas realizadas
$stmt = $conn->prepare("
    SELECT f.rol, f.fecha_firma, f.archivo_pdf_firmado, u.nombres, u.apellidos 
    FROM firmas_digitales f
    JOIN usuarios u ON f.id_usuario = u.id
    WHERE f.id_solicitud = ?
    ORDER BY f.fecha_firma ASC
");
$stmt->bind_param("i", $id_solicitud);
$stmt->execute();
$result = $stmt->get_result();

$firmas_realizadas = [];
while ($row = $result->fetch_assoc()) {
    $firmas_realizadas[$row['rol']] = [
        'nombre' => $row['nombres'] . ' ' . $row['apellidos'],
        'fecha' => $row['fecha_firma'],
        'archivo' => $row['archivo_pdf_firmado']
    ];
}
$stmt->close();

// Obtener certificados de usuarios (secretario, tesorero, presidente, comite_lotes)
$usuarios_firmantes = [];
$stmt = $conn->prepare("
    SELECT id, nombres, apellidos, rol, certificado_digital 
    FROM usuarios 
    WHERE rol IN ('secretario', 'tesorero', 'presidente', 'comite_lotes') 
    AND certificado_digital IS NOT NULL 
    AND certificado_digital != ''
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $usuarios_firmantes[$row['rol']] = [
        'id' => $row['id'],
        'nombre' => $row['nombres'] . ' ' . $row['apellidos'],
        'tiene_certificado' => true
    ];
}
$stmt->close();

// Determinar estado por cada rol
$estados_firma = [];
foreach ($orden_actual as $rol) {
    $ha_firmado = isset($firmas_realizadas[$rol]);
    $puede_firmar = isset($usuarios_firmantes[$rol]);
    
    // Determinar si es el turno de este rol
    $posicion = array_search($rol, $orden_actual);
    $es_su_turno = true;
    
    // Verificar que todos los roles anteriores hayan firmado
    for ($i = 0; $i < $posicion; $i++) {
        $rol_anterior = $orden_actual[$i];
        if (!isset($firmas_realizadas[$rol_anterior])) {
            $es_su_turno = false;
            break;
        }
    }
    
    $estados_firma[$rol] = [
        'firmado' => $ha_firmado,
        'puede_firmar' => $puede_firmar,
        'es_su_turno' => $ha_firmado ? false : $es_su_turno,
        'info' => $ha_firmado ? $firmas_realizadas[$rol] : null
    ];
}

// Determinar si el documento está completamente firmado
$todas_firmas_completas = true;
foreach ($orden_actual as $rol) {
    if (!isset($firmas_realizadas[$rol])) {
        $todas_firmas_completas = false;
        break;
    }
}

// Obtener usuario actual y su estado
$usuario_actual = [
    'id' => isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null,
    'rol' => isset($_SESSION['rol']) ? $_SESSION['rol'] : null,
    'puede_firmar' => false,
    'es_su_turno' => false
];

if ($usuario_actual['id'] && $usuario_actual['rol']) {
    $rol_actual = $usuario_actual['rol'];
    if (isset($estados_firma[$rol_actual])) {
        $usuario_actual['puede_firmar'] = $estados_firma[$rol_actual]['puede_firmar'];
        $usuario_actual['es_su_turno'] = $estados_firma[$rol_actual]['es_su_turno'];
    }
}

// Respuesta
echo json_encode([
    'success' => true,
    'data' => [
        'id_solicitud' => $id_solicitud,
        'tipo_documento' => $tipo_documento,
        'estado_solicitud' => $solicitud['estado'],
        'orden_firma' => $orden_actual,
        'firmas' => $estados_firma,
        'firmas_realizadas' => $firmas_realizadas,
        'todas_firmas_completas' => $todas_firmas_completas,
        'pdf_original' => $solicitud['archivo_pdf'] ?? $solicitud['certificado'] ?? null,
        'pdf_firmado' => $solicitud['pdf_firmado'] ?? null,
        'usuario_actual' => $usuario_actual
    ]
]);
