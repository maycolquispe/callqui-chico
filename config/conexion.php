<?php
/**
 * Legacy Connection - Deprecated
 * 
 * Este archivo se mantiene por compatibilidad.
 * Por favor usar config/database.php en su lugar.
 */

require_once __DIR__ . '/database.php';

$conn = getDB();

// Definir variables para compatibilidad con código legacy
$host = "localhost";
$user = "root";
$pass = "";
$db = "callqui_chico";
