<?php
/**
 * Authentication Functions
 * Sistema Callqui Chico - Profesional
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';

class Auth {
    
    public static function login($dni, $password = null) {
        $conn = getDB();
        
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE dni = ? AND estado = 'activo' LIMIT 1");
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'error' => 'Usuario no encontrado'];
        }
        
        $user = $result->fetch_assoc();
        
        // Si no tiene contraseña, permitir acceso (para usuarios existentes)
        if (empty($user['password_hash'])) {
            SessionManager::login($user);
            return ['success' => true, 'user' => $user];
        }
        
        // Verificar contraseña
        if ($password !== null && !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Contraseña incorrecta'];
        }
        
        // Login exitoso
        SessionManager::login($user);
        
        // Registrar último acceso
        self::updateLastAccess($user['id']);
        
        return ['success' => true, 'user' => $user];
    }
    
    public static function crearPassword($userId, $password) {
        $conn = getDB();
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $passwordHash, $userId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    public static function cambiarPassword($userId, $passwordActual, $nuevaPassword) {
        $conn = getDB();
        
        // Verificar contraseña actual
        $stmt = $conn->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!password_verify($passwordActual, $user['password_hash'])) {
            return ['success' => false, 'error' => 'La contraseña actual es incorrecta'];
        }
        
        // Cambiar contraseña
        $nuevaHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevaHash, $userId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    }
    
    public static function logout() {
        SessionManager::destroy();
    }
    
    public static function updateLastAccess($userId) {
        // Omitir si la columna no existe
        return true;
    }
    
    public static function getRedirectPath($rol) {
        $paths = [
            'secretario' => '../dashboard/secretario/secretario.php',
            'presidente' => '../dashboard/presidente/presidente.php',
            'comunero' => '../dashboard/comunero/comunero.php',
            'tesorero' => '../dashboard/secretario/secretario.php',
            'fiscal' => '../dashboard/secretario/secretario.php',
            'comite_lotes' => '../dashboard/comite/comite.php'
        ];
        
        return $paths[$rol] ?? '../dashboard/comunero/comunero.php';
    }
    
    public static function getAllRoles() {
        return ['secretario', 'presidente', 'tesorero', 'fiscal', 'comunero', 'comite_lotes'];
    }
    
    public static function isAdmin() {
        $rol = SessionManager::getRol();
        return in_array($rol, ['secretario', 'presidente']);
    }
    
    public static function isComite() {
        $rol = SessionManager::getRol();
        return $rol === 'comite_lotes';
    }
    
    public static function canApproveAdjudicacion() {
        $rol = SessionManager::getRol();
        return in_array($rol, ['secretario', 'presidente', 'comite_lotes']);
    }
}

function auth_login($dni, $password = null) {
    return Auth::login($dni, $password);
}

function auth_logout() {
    Auth::logout();
}

function auth_redirigir($rol) {
    $path = Auth::getRedirectPath($rol);
    header("Location: $path");
    exit;
}
