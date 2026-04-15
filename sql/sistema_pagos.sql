-- Tabla de configuración de pagos
CREATE TABLE IF NOT EXISTS config_pagos (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nombre_banco VARCHAR(100) DEFAULT 'Banco de Crédito del Perú',
    cuenta_banco VARCHAR(50) DEFAULT '',
    titular_cuenta VARCHAR(150) DEFAULT '',
    numero_yape VARCHAR(20) DEFAULT '',
    qr_yape VARCHAR(255) DEFAULT NULL,
    instrucciones TEXT DEFAULT NULL,
    actualizado_por INT(11) DEFAULT NULL,
    fecha_actualizacion DATETIME DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar registro inicial
INSERT INTO config_pagos (id, nombre_banco, cuenta_banco, titular_cuenta, numero_yape) VALUES 
(1, 'Banco de Crédito del Perú', '', '', '');

-- Verificar que la tabla pagos exista
CREATE TABLE IF NOT EXISTS pagos (
    id INT(11) NOT NULL AUTO_INCREMENT,
    codigo_pago VARCHAR(20) NOT NULL,
    id_solicitud INT(11) NOT NULL,
    numero_propietarios INT(11) DEFAULT 1,
    monto DECIMAL(10,2) NOT NULL,
    medio_pago VARCHAR(20) DEFAULT NULL,
    numero_operacion VARCHAR(50) DEFAULT NULL,
    comprobante VARCHAR(255) DEFAULT NULL,
    estado ENUM('pendiente','validado','rechazado') DEFAULT 'pendiente',
    validado_por INT(11) DEFAULT NULL,
    fecha_validacion DATETIME DEFAULT NULL,
    fecha_pago DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY id_solicitud (id_solicitud),
    KEY codigo_pago (codigo_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columnas de pago a adjudicaciones si no existen
ALTER TABLE adjudicaciones ADD COLUMN IF NOT EXISTS pago_id INT(11) DEFAULT NULL;
ALTER TABLE adjudicaciones ADD COLUMN IF NOT EXISTS estado_pago VARCHAR(20) DEFAULT NULL;