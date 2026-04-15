<?php
/**
 * API de Verificación de Certificados - Sistema Callqui Chico
 * 
 * Endpoint público para verificar la autenticidad de un certificado
 * Uso: /publico/verificar.php?codigo=ADJ-2026-0001
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funciones.php';

$codigo = $_GET['codigo'] ?? $_POST['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode([
        'success' => false,
        'message' => 'Código de certificado requerido',
        'example' => '/publico/verificar.php?codigo=ADJ-2026-0001'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = getDB();

// Buscar certificado por código
$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.codigo_certificado,
        a.hash_sha256,
        a.timestamp_generacion,
        a.hash_sha256_firmado,
        a.timestamp_firma,
        a.completamente_firmado,
        a.estado,
        u.nombres as nombres_titular,
        u.apellidos as apellidos_titular,
        u.dni as dni_titular,
        a.sector,
        a.manzana,
        a.lote,
        a.area_m2
    FROM adjudicaciones a
    LEFT JOIN usuarios u ON a.usuario_id = u.id
    WHERE a.codigo_certificado = ?
    LIMIT 1
");

$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Certificado no encontrado',
        'codigo' => $codigo
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$certificado = $result->fetch_assoc();
$stmt->close();

// Construir respuesta
$response = [
    'success' => true,
    'certificado' => [
        'codigo' => $certificado['codigo_certificado'],
        'estado' => $certificado['estado'],
        'firmado_digitalmente' => (bool)$certificado['completamente_firmado'],
        'titular' => [
            'nombre' => trim(($certificado['apellidos_titular'] ?? '') . ' ' . ($certificado['nombres_titular'] ?? '')),
            'dni' => $certificado['dni_titular']
        ],
        'lote' => [
            'sector' => $certificado['sector'],
            'manzana' => $certificado['manzana'],
            'lote' => $certificado['lote'],
            'area_m2' => $certificado['area_m2']
        ],
        'generacion' => [
            'timestamp' => $certificado['timestamp_generacion'],
            'hash_sha256' => $certificado['hash_sha256']
        ]
    ],
    'verificacion' => [
        'integridad' => null,
        'firma_digital' => null,
        'timestamp' => date('Y-m-d H:i:s')
    ]
];

// Verificar integridad del archivo actual
if (!empty($certificado['hash_sha256'])) {
    $pdf_path = __DIR__ . '/../' . str_replace('storage/', 'storage/', $certificado['codigo_certificado']);
    
    // Buscar archivo PDF
    $pdf_files = glob(__DIR__ . '/../storage/documentos/certificado_adjudicacion_' . $certificado['id'] . '_*.pdf');
    
    if (!empty($pdf_files)) {
        $hash_actual = hash_file('sha256', end($pdf_files));
        $response['verificacion']['integridad'] = [
            'verificado' => ($hash_actual === $certificado['hash_sha256']),
            'hash_almacenado' => $certificado['hash_sha256'],
            'hash_actual' => $hash_actual,
            'archivo' => basename(end($pdf_files))
        ];
    }
}

// Agregar info de firma digital
if (!empty($certificado['hash_sha256_firmado'])) {
    $response['certificado']['firma'] = [
        'timestamp' => $certificado['timestamp_firma'],
        'hash_sha256' => $certificado['hash_sha256_firmado']
    ];
    $response['verificacion']['firma_digital'] = [
        'verificado' => true,
        'timestamp' => $certificado['timestamp_firma']
    ];
}

// URL de verificación
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = '/2026';
$response['verificacion']['url'] = $protocol . '://' . $host . $base_path . '/publico/verificar.php?codigo=' . $codigo;

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
