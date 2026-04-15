-- Tabla de configuración de pagos
CREATE TABLE IF NOT EXISTS config_pagos (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nombre_banco VARCHAR(100) DEFAULT 'Banco de Crédito del Perú',
    cuenta_banco VARCHAR(50) DEFAULT '',
    titular_cuenta VARCHAR(150) DEFAULT '',
    numero_yape VARCHAR(20) DEFAULT '',
    qr_yape VARCHAR(255) DEFAULT NULL COMMENT 'Ruta del archivo QR de Yape',
    instrucciones TEXT DEFAULT NULL,
    actualizado_por INT(11) DEFAULT NULL,
    fecha_actualizacion DATETIME DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar registro inicial vacío
INSERT INTO config_pagos (id, nombre_banco, cuenta_banco, titular_cuenta, numero_yape) VALUES 
(1, 'Banco de Crédito del Perú', '', '', '');