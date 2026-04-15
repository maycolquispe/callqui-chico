-- ============================================================
-- CERTIFICADOS DE ADJUDICACIÓN - COMUNIDAD CAMPESINA CALLQUI CHICO
-- ============================================================

-- 1. Agregar campos para certificados de adjudicación
ALTER TABLE adjudicaciones ADD COLUMN codigo_certificado VARCHAR(30) DEFAULT NULL COMMENT 'Código único del certificado (ADJ-2026-0001)';
ALTER TABLE adjudicaciones ADD COLUMN pdf_firmado VARCHAR(255) DEFAULT NULL COMMENT 'Ruta del PDF firmado';
ALTER TABLE adjudicaciones ADD COLUMN completamente_firmado TINYINT(1) DEFAULT 0 COMMENT '1 cuando todas las firmas requeridas están aplicadas';
ALTER TABLE adjudicaciones ADD COLUMN qr_code VARCHAR(255) DEFAULT NULL COMMENT 'Ruta del código QR';
ALTER TABLE adjudicaciones ADD COLUMN fecha_generacion_cert DATETIME DEFAULT NULL COMMENT 'Fecha de generación del certificado';
ALTER TABLE adjudicaciones ADD COLUMN firmado_por_secretario DATETIME DEFAULT NULL COMMENT 'Fecha de firma del secretario';
ALTER TABLE adjudicaciones ADD COLUMN firmado_por_fiscal DATETIME DEFAULT NULL COMMENT 'Fecha de firma del fiscal';
ALTER TABLE adjudicaciones ADD COLUMN firmado_por_tesorero DATETIME DEFAULT NULL COMMENT 'Fecha de firma del tesorero';
ALTER TABLE adjudicaciones ADD COLUMN firmado_por_presidente DATETIME DEFAULT NULL COMMENT 'Fecha de firma del presidente';

-- 2. Agregar campos de linderos y datos del terreno
ALTER TABLE adjudicaciones ADD COLUMN lindero_frente TEXT DEFAULT NULL COMMENT 'Lindero frente';
ALTER TABLE adjudicaciones ADD COLUMN lindero_fondo TEXT DEFAULT NULL COMMENT 'Lindero fondo';
ALTER TABLE adjudicaciones ADD COLUMN lindero_derecha TEXT DEFAULT NULL COMMENT 'Lindero derecha';
ALTER TABLE adjudicaciones ADD COLUMN lindero_izquierda TEXT DEFAULT NULL COMMENT 'Lindero izquierda';
ALTER TABLE adjudicaciones ADD COLUMN perimetro_ml DECIMAL(10,2) DEFAULT NULL COMMENT 'Perímetro total en metros lineales';
ALTER TABLE adjudicaciones ADD COLUMN conyuge_nombre VARCHAR(255) DEFAULT NULL COMMENT 'Nombre del cónyuge';
ALTER TABLE adjudicaciones ADD COLUMN conyuge_dni VARCHAR(15) DEFAULT NULL COMMENT 'DNI del cónyuge';
ALTER TABLE adjudicaciones ADD COLUMN estado_civil VARCHAR(50) DEFAULT NULL COMMENT 'Estado civil del titular';
ALTER TABLE adjudicaciones ADD COLUMN resolucion_numero VARCHAR(50) DEFAULT NULL COMMENT 'Número de resolución';
ALTER TABLE adjudicaciones ADD COLUMN resolucion_fecha DATE DEFAULT NULL COMMENT 'Fecha de resolución';

-- 3. Tabla de certificados emitidos (historial)
CREATE TABLE IF NOT EXISTS certificados_adjudicacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adjudicacion_id INT NOT NULL,
    codigo_certificado VARCHAR(30) NOT NULL UNIQUE,
    pdf_original VARCHAR(255) NOT NULL,
    pdf_firmado VARCHAR(255) NOT NULL,
    qr_code VARCHAR(255) DEFAULT NULL,
    fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    firmado_por JSON DEFAULT NULL,
    INDEX idx_codigo (codigo_certificado),
    INDEX idx_adjudicacion (adjudicacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Actualizar config_firmas si existe
UPDATE config_firmas SET orden_firma = '["secretario", "fiscal", "tesorero", "presidente"]' 
WHERE tipo_documento = 'adjudicacion';

-- Insertar si no existe
INSERT IGNORE INTO config_firmas (tipo_documento, orden_firma, requiere_tesorero) 
VALUES ('adjudicacion', '["secretario", "fiscal", "tesorero", "presidente"]', 1);