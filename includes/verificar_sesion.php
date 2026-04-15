<?php
/**
 * Verificar Sesión - Protection Include
 * Sistema Callqui Chico - Profesional
 * 
 * Incluir este archivo al inicio de cualquier página que requiera autenticación
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/funciones.php';

// Iniciar sesión
SessionManager::init();

// Verificar que el usuario esté logueado
if (!SessionManager::isLoggedIn()) {
    header("Location: ../../login.php?error=sesion_expirada");
    exit;
}

// Opcional: Verificar rol específico
// Usage: verificar_sesion(['secretario', 'presidente']);
function verificar_rol($roles = []) {
    if (empty($roles)) return true;
    
    $rolActual = SessionManager::get('rol');
    
    if (!in_array($rolActual, $roles)) {
        // Redirigir al dashboard según el rol
        $path = Auth::getRedirectPath(SessionManager::get('rol'));
        header("Location: $path?error=sin_permiso");
        exit;
    }
    
    return true;
}

// Verificar tiempo de sesión (opcional - expira después de X horas)
function verificar_tiempo_sesion($horas = 8) {
    $loginTime = SessionManager::get('login_time');
    
    if ($loginTime) {
        $tiempoTranscurrido = time() - $loginTime;
        $tiempoMaximo = $horas * 3600;
        
        if ($tiempoTranscurrido > $tiempoMaximo) {
            SessionManager::destroy();
            header("Location: ../../login.php?error=sesion_expirada");
            exit;
        }
    }
    
    return true;
}

// Función helper para obtener el usuario actual
function usuario_actual() {
    return [
        'id' => SessionManager::getUserId(),
        'nombre' => SessionManager::getUserName(),
        'dni' => SessionManager::get('dni'),
        'rol' => SessionManager::get('rol'),
        'foto' => SessionManager::get('foto')
    ];
}
