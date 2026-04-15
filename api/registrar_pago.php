<?php
/**
 * PUENTE - Compatibilidad API Pagos
 * Este archivo redirige al nuevo módulo manteniendo compatibilidad
 * URL: /api/registrar_pago.php (se mantiene igual)
 */
require_once __DIR__ . '/../modules/Pagos/Controller.php';

$controller = new PagoController();
$controller->handleRequest();