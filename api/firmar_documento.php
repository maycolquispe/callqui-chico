<?php
/**
 * API: Firmar Documento Digitalmente
 * Comunidad Campesina Callqui Chico
 * 
 * Endpoint para firmar digitalmente PDFs usando Python + PyHanko
 */

ini_set('session.cookie_path', '/');
ini_set('session.cookie_samesite', 'Lax');

session_start();

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

$id_solicitud = isset($input['id_solicitud']) ? intval($input['id_solicitud']) : 0;
$tipo_documento = isset($input['tipo_documento']) ? $input['tipo_documento'] : 'adjudicacion';

// Validaciones básicas
if (!$id_solicitud) {
    echo json_encode(['success' => false, 'message' => 'ID de solicitud requerido']);
    exit;
}

// Debug: Ver qué sesión hay
error_log("DEBUG Session: " . print_r($_SESSION, true));
error_log("DEBUG Cookie: " . print_r($_COOKIE, true));

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida', 'debug' => ['session' => $_SESSION, 'cookie' => $_COOKIE['PHPSESSID'] ?? 'none']]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

// Roles válidos para firma - incluye comite_lotes
$roles_firma_validos = ['secretario', 'tesorero', 'presidente', 'comite_lotes'];
if (!in_array($rol, $roles_firma_validos)) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para firmar']);
    exit;
}

$conn = getDB();

// FUERZA LA CONTRASEÑA Y CERTIFICADO PARA PRUEBAS - QUITAR EN PRODUCCIÓN
$cert_password_fijo = '123456';
$cert_path_fijo = 'C:/xampp/htdocs/2026/storage/certificados/cert_1_secretario_20260409231648.p12';

$stmt = $conn->prepare("SELECT id, nombres, apellidos, certificado_digital, password_certificado FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Usa la contraseña y certificado fijos para pruebas
$usuario['password_certificado'] = $cert_password_fijo;
$usuario['certificado_digital'] = $cert_path_fijo;

if (!$usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

// Verificar que tiene certificado digital
if (empty($usuario['certificado_digital']) || empty($usuario['password_certificado'])) {
    echo json_encode(['success' => false, 'message' => 'No tienes certificado digital configurado. Contacta al administrador.']);
    exit;
}

// Try multiple paths to find the certificate
$possible_paths = [
    __DIR__ . '/../storage/certificados/' . basename($usuario['certificado_digital']),
    __DIR__ . '/../certificados/' . basename($usuario['certificado_digital']),
    __DIR__ . '/../storage/' . $usuario['certificado_digital'],
    __DIR__ . '/../' . $usuario['certificado_digital']
];

$certificado_path = '';
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $certificado_path = $path;
        break;
    }
}

if (empty($certificado_path)) {
    echo json_encode(['success' => false, 'message' => 'Certificado digital no encontrado. Intentos: ' . json_encode($possible_paths)]);
    exit;
}

$cert_password = $usuario['password_certificado'];

// Obtener info de la solicitud/adjudicación
$stmt = $conn->prepare("SELECT a.*, u.nombres, u.apellidos FROM adjudicaciones a 
    LEFT JOIN usuarios u ON a.usuario_id = u.id 
    WHERE a.id = ?");
$stmt->bind_param("i", $id_solicitud);
$stmt->execute();
$adjudicacion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$adjudicacion) {
    echo json_encode(['success' => false, 'message' => 'Adjudicación no encontrada']);
    exit;
}

// Nombre del firmante
$nombre_firmante = $usuario['nombres'] . ' ' . $usuario['apellidos'];

// Obtener PDF fuente - buscar en varios campos
$pdf_campos = ['certificado', 'pdf_firmado', 'archivo_pdf'];
$pdf_fuente_path = null;

foreach ($pdf_campos as $campo) {
    if (!empty($adjudicacion[$campo])) {
        $ruta = __DIR__ . '/../storage/' . $adjudicacion[$campo];
        if (file_exists($ruta)) {
            $pdf_fuente_path = $ruta;
            break;
        }
    }
}

// Si no se encontró en BD, buscar en archivos generados
if (!$pdf_fuente_path) {
    $cert_files = glob(__DIR__ . '/../storage/documentos/certificado_adjudicacion_' . $id_solicitud . '_*.pdf');
    if (!empty($cert_files)) {
        // Usar el más reciente
        usort($cert_files, function($a, $b) { return filemtime($b) - filemtime($a); });
        $pdf_fuente_path = $cert_files[0];
    }
}

// También buscar certificado simple
if (!$pdf_fuente_path) {
    $cert_simple = __DIR__ . '/../storage/documentos/certificado_adjudicacion_' . $id_solicitud . '.pdf';
    if (file_exists($cert_simple)) {
        $pdf_fuente_path = $cert_simple;
    }
}

if (!$pdf_fuente_path) {
    echo json_encode(['success' => false, 'message' => 'PDF fuente no encontrado para ID: ' . $id_solicitud]);
    exit;
}

// Ruta del script Python y PDF firmado
$python_script = __DIR__ . '/../scripts/python/firmar_pdf.py';
$timestamp = date('YmdHis');
$pdf_firmado_nombre = 'adjudicacion_' . $id_solicitud . '_firmado_' . $timestamp . '.pdf';
$pdf_firmado_path = __DIR__ . '/../storage/documentos_firmados/' . $pdf_firmado_nombre;

// Asegurar que existe el directorio
$dir = dirname($pdf_firmado_path);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Display roles
$rol_display = [
    'secretario' => 'Secretario',
    'tesorero' => 'Tesorero', 
    'presidente' => 'Presidente'
][$rol] ?? $rol;

// Orden de firmas
$orden_actual = ['tesorero', 'secretario', 'presidente'];

// Firmas previas
$firmas_previas = [];
if ($adjudicacion['aprobado_tesorero']) $firmas_previas[] = 'tesorero';
if ($adjudicacion['aprobado_comite']) $firmas_previas[] = 'comite';
if ($adjudicacion['aprobado_secretario']) $firmas_previas[] = 'secretario';
if ($adjudicacion['aprobado_presidente']) $firmas_previas[] = 'presidente';

// Ejecutar Python para firmar
$command = sprintf(
    'python "%s" "%s" "%s" "%s" "%s" --firmante "%s" --rol "%s"',
    $python_script,
    $pdf_fuente_path,
    $pdf_firmado_path,
    $certificado_path,
    escapeshellarg($cert_password),
    $nombre_firmante,
    $rol
);

$output = [];
$return_var = 0;
exec($command, $output, $return_var);
$python_output = implode("\n", $output);

error_log("PYTHON COMMAND: " . $command);
error_log("PYTHON OUTPUT: " . $python_output);
error_log("PYTHON RETURN: " . $return_var);

// Debug info
$debug_info = [
    'python_script_exists' => file_exists($python_script),
    'certificado_path_used' => $certificado_path,
    'certificado_exists' => file_exists($certificado_path),
    'pdf_fuente_exists' => file_exists($pdf_fuente_path),
    'pdf_fuente_path' => $pdf_fuente_path,
    'password_first_3_chars' => $cert_password ? substr($cert_password, 0, 3) : 'NULL',
    'output' => implode("\n", $output),
    'return' => $return_var,
    'python_output_raw' => substr($python_output, 0, 800)
];
error_log("FIRMAR DOC DEBUG: " . json_encode($debug_info));

// SIEMPRE mostrar output para debug
if ($return_var !== 0 || empty($python_output)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al ejecutar Python',
        'debug' => $debug_info
    ]);
    exit;
}

$resultado = json_decode($python_output, true);
if (!$resultado || !isset($resultado['success'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al parsear respuesta de Python',
        'debug' => ['python_output' => $python_output, 'return' => $return_var]
    ]);
    exit;
}

if (!$resultado['success']) {
    echo json_encode([
        'success' => false, 
        'message' => $resultado['message'] ?? 'Error en Python',
        'debug' => ['python_output' => $python_output]
    ]);
    exit;
}

// Verificar que el PDF firmado se creó
if (!file_exists($pdf_firmado_path)) {
    echo json_encode([
        'success' => false, 
        'message' => 'El PDF firmado no se creó correctamente',
        'debug' => $python_output
    ]);
    exit;
}

// Registrar firma en base de datos
$archivo_pdf_firmado = 'documentos_firmados/' . $pdf_firmado_nombre;
$signature_data = json_encode($resultado);

$stmt = $conn->prepare("
    INSERT INTO firmas_digitales (id_solicitud, tipo_documento, id_usuario, rol, archivo_pdf_firmado, signature_data) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("isisss", $id_solicitud, $tipo_documento, $usuario_id, $rol, $archivo_pdf_firmado, $signature_data);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al registrar firma en base de datos']);
    exit;
}
$stmt->close();

// Actualizar campo de aprobación según rol
// Mapeo especial para comite_lotes -> aprobado_comite
$mapeo_campos = [
    'secretario' => 'aprobado_secretario',
    'tesorero' => 'aprobado_tesorero',
    'presidente' => 'aprobado_presidente',
    'comite_lotes' => 'aprobado_comite'
];
$campo_aprobado = $mapeo_campos[$rol] ?? 'aprobado_' . $rol;

$stmt = $conn->prepare("UPDATE adjudicaciones SET $campo_aprobado = 1 WHERE id = ?");
$stmt->bind_param("i", $id_solicitud);
$stmt->execute();
$stmt->close();

// Actualizar estado según el orden de firmas
$estados_firma = [
    'tesorero' => 'en_firma_tesorero',
    'comite_lotes' => 'en_firma_comite',
    'secretario' => 'en_firma_secretario',
    'presidente' => 'en_firma_presidente'
];

if (isset($estados_firma[$rol])) {
    $nuevo_estado = $estados_firma[$rol];
    $stmt = $conn->prepare("UPDATE adjudicaciones SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $nuevo_estado, $id_solicitud);
    $stmt->execute();
    $stmt->close();
}

// Verificar si todas las firmas requeridas están completas
$firmas_necesarias = ['tesorero', 'comite', 'secretario', 'presidente'];

// Agregar el rol actual si no está en firmas_previas
$todas_firmas = array_merge($firmas_previas, [$rol === 'comite_lotes' ? 'comite' : $rol]);

$faltan_firmas = false;
foreach ($firmas_necesarias as $rol_necesario) {
    if (!in_array($rol_necesario, $todas_firmas)) {
        $faltan_firmas = true;
        break;
    }
}

// Si todas las firmas están completas, actualizar estado y generar certificado final
if (!$faltan_firmas) {
    // Copiar el PDF firmado como certificado final
    $certificado_final = $archivo_pdf_firmado;
    
    $stmt = $conn->prepare("UPDATE adjudicaciones SET 
        completamente_firmado = 1, 
        estado = 'aprobado_total', 
        certificado_generado = 1,
        pdf_firmado = ?,
        certificado = ?
        WHERE id = ?");
    $stmt->bind_param("ssi", $certificado_final, $certificado_final, $id_solicitud);
    $stmt->execute();
    $stmt->close();
}

// Responder éxito
echo json_encode([
    'success' => true,
    'message' => "Documento firmado por {$nombre_firmante} ({$rol_display})",
    'data' => [
        'id_solicitud' => $id_solicitud,
        'tipo_documento' => $tipo_documento,
        'firmante' => $nombre_firmante,
        'rol' => $rol_display,
        'archivo_firmado' => $archivo_pdf_firmado,
        'fecha_firma' => $resultado['fecha'],
        'todas_firmas_completas' => !$faltan_firmas
    ]
]);
