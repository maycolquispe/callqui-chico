<?php
/**
 * CSRF Protection
 * Sistema Callqui Chico - Profesional
 */

class CSRF {
    
    private static $tokenName = 'csrf_token';
    private static $sessionKey = 'csrf_tokens';
    
    public static function init() {
        SessionManager::init();
        
        if (!SessionManager::has(self::$sessionKey)) {
            SessionManager::set(self::$sessionKey, []);
        }
    }
    
    public static function generate() {
        self::init();
        
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        
        $tokens = SessionManager::get(self::$sessionKey, []);
        $tokens[$tokenHash] = [
            'created' => time(),
            'used' => false
        ];
        
        // Limpiar tokens viejos (más de 1 hora)
        $now = time();
        foreach ($tokens as $hash => $data) {
            if ($data['created'] < $now - 3600) {
                unset($tokens[$hash]);
            }
        }
        
        SessionManager::set(self::$sessionKey, $tokens);
        
        return $token;
    }
    
    public static function getToken() {
        self::init();
        return self::generate();
    }
    
    public static function validate($token) {
        self::init();
        
        if (empty($token)) {
            return false;
        }
        
        $tokenHash = hash('sha256', $token);
        $tokens = SessionManager::get(self::$sessionKey, []);
        
        if (isset($tokens[$tokenHash]) && !$tokens[$tokenHash]['used']) {
            $tokens[$tokenHash]['used'] = true;
            SessionManager::set(self::$sessionKey, $tokens);
            return true;
        }
        
        return false;
    }
    
    public static function getField() {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    public static function getMeta() {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}

// Funciones helper
function csrf_generar() {
    return CSRF::getToken();
}

function csrf_validar($token) {
    return CSRF::validate($token);
}

function csrf_field() {
    return CSRF::getField();
}

function csrf_meta() {
    return CSRF::getMeta();
}
