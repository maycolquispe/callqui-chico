<?php
/**
 * Session Management - Secure Sessions
 * Sistema Callqui Chico - Profesional
 */

// Configuración de seguridad de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

// Nombre de sesión personalizado para mayor seguridad
ini_set('session.name', 'CALLQUI_SESSION');

class SessionManager {
    
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerar ID de sesión periódicamente para prevenir session fixation
        if (!isset($_SESSION['_init_time'])) {
            session_regenerate_id(true);
            $_SESSION['_init_time'] = time();
            $_SESSION['_ip'] = self::getClientIP();
        } else {
            // Verificar cambio de IP (opcional - puede ser muy restrictivo)
            $current_ip = self::getClientIP();
            if ($_SESSION['_ip'] !== $current_ip) {
                // IP cambió - podría ser un ataque, regenerar y alertar
                session_regenerate_id(true);
                $_SESSION['_ip'] = $current_ip;
            }
            
            // Regenerar cada 30 minutos
            if (time() - $_SESSION['_init_time'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['_init_time'] = time();
            }
        }
    }
    
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    public static function set($key, $value) {
        self::init();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        self::init();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has($key) {
        self::init();
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key) {
        self::init();
        unset($_SESSION[$key]);
    }
    
    public static function regenerate() {
        self::init();
        session_regenerate_id(true);
    }
    
    public static function destroy() {
        self::init();
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    // Guardar datos del usuario logueado
    public static function login($userData) {
        self::init();
        self::regenerate();
        
        $_SESSION['usuario_id'] = $userData['id'];
        $_SESSION['dni'] = $userData['dni'];
        $_SESSION['nombre'] = $userData['nombres'];
        $_SESSION['apellidos'] = $userData['apellidos'] ?? '';
        $_SESSION['rol'] = $userData['rol'];
        $_SESSION['foto'] = $userData['foto'] ?? '';
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public static function isLoggedIn() {
        self::init();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public static function getUserId() {
        return self::get('usuario_id');
    }
    
    public static function getUserName() {
        return self::get('nombre');
    }
    
    public static function getRol() {
        return self::get('rol');
    }
    
    public static function requireLogin($redirectTo = '../login.php') {
        if (!self::isLoggedIn()) {
            header("Location: $redirectTo");
            exit;
        }
    }
    
    public static function requireRole($roles, $redirectTo = '../login.php') {
        self::requireLogin($redirectTo);
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (!in_array(self::getRol(), $roles)) {
            header("Location: $redirectTo");
            exit;
        }
    }
}

// Funciones helper para compatibilidad
function sesion_iniciar() {
    SessionManager::init();
}

function sesion_get($key, $default = null) {
    return SessionManager::get($key, $default);
}

function sesion_set($key, $value) {
    SessionManager::set($key, $value);
}

function sesion_tiene($key) {
    return SessionManager::has($key);
}

function sesion_destruir() {
    SessionManager::destroy();
}
