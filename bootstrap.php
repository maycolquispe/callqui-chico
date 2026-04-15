<?php
/**
 * Bootstrap - Initialize all core systems
 * Sistema Callqui Chico - Profesional v2.0
 * 
 * Incluir este archivo al inicio de cualquier página
 */

// Cargar configuración
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/auth.php';

// Cargar utilitarios
require_once __DIR__ . '/includes/funciones.php';

// Inicializar sesión
SessionManager::init();

// Definir constante para rutas
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}
