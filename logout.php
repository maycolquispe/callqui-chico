<?php
/**
 * Logout - Cerrar Sesión
 * Sistema Callqui Chico - Profesional
 */

require_once 'config/auth.php';

Auth::logout();

header("Location: login.php?msg=sesion_cerrada");
exit;
