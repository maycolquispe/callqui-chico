<?php
/**
 * Validar Login - Sistema Callqui Chico
 * Versión segura con bloqueo y auditoría
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit;
}

// Validar CSRF
require_once __DIR__ . '/csrf.php';
$csrf_token = $_POST['csrf_token'] ?? '';
if (!CSRF::validate($csrf_token)) {
    header("Location: ../login.php?error=csrf");
    exit;
}

$dni = trim($_POST['dni'] ?? '');
$password = $_POST['password'] ?? '';

// Validar entrada básica
if (empty($dni) || strlen($dni) !== 8 || !ctype_digit($dni)) {
    header("Location: ../login.php?error=1");
    exit;
}

// Obtener IP del cliente
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

$client_ip = getClientIP();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

$conn = getDB();

// Verificar si el usuario existe
$stmt = $conn->prepare("SELECT id, dni, password_hash, estado, intentos_fallidos, bloqueado_hasta FROM usuarios WHERE dni = ? LIMIT 1");
$stmt->bind_param("s", $dni);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Usuario no existe - registrar intento fallido
    registrarIntento($conn, null, $dni, $client_ip, $user_agent, 'fallido', 'Usuario no encontrado');
    header("Location: ../login.php?error=1");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Verificar si la cuenta está bloqueada
if (!empty($user['bloqueado_hasta']) && strtotime($user['bloqueado_hasta']) > time()) {
    $tiempo_bloqueo = ceil((strtotime($user['bloqueado_hasta']) - time()) / 60);
    registrarIntento($conn, $user['id'], $dni, $client_ip, $user_agent, 'bloqueado', 'Cuenta bloqueada');
    header("Location: ../login.php?error=bloqueado&tiempo=$tiempo_bloqueo");
    exit;
}

// Verificar estado del usuario
if ($user['estado'] !== 'activo') {
    registrarIntento($conn, $user['id'], $dni, $client_ip, $user_agent, 'fallido', 'Cuenta inactiva');
    header("Location: ../login.php?error=1");
    exit;
}

// Verificar contraseña
$login_ok = false;

if (!empty($user['password_hash'])) {
    $login_ok = password_verify($password, $user['password_hash']);
} else {
    // Legacy: usar DNI como contraseña
    $login_ok = ($password === $dni);
}

if (!$login_ok) {
    // Incrementar intentos fallidos
    $intentos_nuevos = $user['intentos_fallidos'] + 1;
    
    if ($intentos_nuevos >= 5) {
        // Bloquear cuenta por 15 minutos
        $bloqueado_hasta = date('Y-m-d H:i:s', time() + 900);
        $stmt = $conn->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?");
        $stmt->bind_param("isi", $intentos_nuevos, $bloqueado_hasta, $user['id']);
        $stmt->execute();
        $stmt->close();
        
        registrarIntento($conn, $user['id'], $dni, $client_ip, $user_agent, 'bloqueado', 'Intentos fallidos: ' . $intentos_nuevos);
        header("Location: ../login.php?error=bloqueado&tiempo=15");
    } else {
        // Solo incrementar intentos
        $stmt = $conn->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?");
        $stmt->bind_param("ii", $intentos_nuevos, $user['id']);
        $stmt->execute();
        $stmt->close();
        
        registrarIntento($conn, $user['id'], $dni, $client_ip, $user_agent, 'fallido', 'Contraseña incorrecta');
        header("Location: ../login.php?error=1");
    }
    exit;
}

// Login exitoso - resetear contadores y actualizar último login
$stmt = $conn->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_login = NOW() WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$stmt->close();

// Registrar login exitoso
registrarIntento($conn, $user['id'], $dni, $client_ip, $user_agent, 'exitoso', 'Login exitoso');

// Obtener datos completos del usuario
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$user_full = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Establecer sesión
$_SESSION['usuario_id'] = $user_full['id'];
$_SESSION['dni'] = $user_full['dni'];
$_SESSION['nombre'] = $user_full['nombres'];
$_SESSION['apellidos'] = $user_full['apellidos'] ?? '';
$_SESSION['rol'] = $user_full['rol'];
$_SESSION['foto'] = $user_full['foto'] ?? '';
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();

// Regenerar ID de sesión para prevenir session fixation
session_regenerate_id(true);

// Redireccionar según rol
$rol = $user_full['rol'];
$redirect = '../dashboard/comunero/comunero.php';

switch ($rol) {
    case 'secretario':
        $redirect = '../dashboard/secretario/secretario.php';
        break;
    case 'presidente':
        $redirect = '../dashboard/presidente/presidente.php';
        break;
    case 'comite_lotes':
        $redirect = '../dashboard/comite/comite.php';
        break;
    case 'tesorero':
        $redirect = '../dashboard/tesorero/tesorero.php';
        break;
    case 'fiscal':
        $redirect = '../dashboard/secretario/secretario.php';
        break;
    default:
        $redirect = '../dashboard/comunero/comunero.php';
}

header("Location: " . $redirect);
exit;

/**
 * Función para registrar intentos de login
 */
function registrarIntento($conn, $usuario_id, $dni, $ip, $user_agent, $resultado, $motivo) {
    $stmt = $conn->prepare("INSERT INTO login_auditoria (usuario_id, dni, ip_address, user_agent, resultado, motivo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $usuario_id, $dni, $ip, $user_agent, $resultado, $motivo);
    $stmt->execute();
    $stmt->close();
}
