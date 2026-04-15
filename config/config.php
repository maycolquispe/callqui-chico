<?php
/**
 * Legacy Config - Deprecated
 * 
 * Este archivo se mantiene por compatibilidad.
 * Por favor usar config/database.php y config/session.php en su lugar.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';

SessionManager::init();

$conn = getDB();
