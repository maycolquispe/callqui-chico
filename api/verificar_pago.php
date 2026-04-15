<?php
/**
 * PUENTE - Compatibilidad API Verificar Pago
 * URL: /api/verificar_pago.php?id_solicitud=X (se mantiene igual)
 */
require_once __DIR__ . '/../modules/Pagos/Controller.php';

$controller = new PagoController();
$controller->handleRequest();