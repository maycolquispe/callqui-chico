-- ============================================================
-- FIRMA DIGITAL PARA COMUNIDAD CAMPESINA CALLQUI CHICO
-- ============================================================

-- 1. Agregar columnas a tabla usuarios para certificados digitales
ALTER TABLE usuarios ADD COLUMN certificado_digital VARCHAR(255) DEFAULT NULL COMMENT 'Ruta del archivo .p12/.pfx';
ALTER TABLE usuarios ADD COLUMN password_certificado VARCHAR(255) DEFAULT NULL COMMENT 'Password del certificado';

-- 2. Crear tabla de firmas digitales
CREATE TABLE IF NOT EXISTS firmas_digitales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud INT NOT NULL,
    tipo_documento ENUM('adjudicacion', 'certificado_transferencia', 'solicitud_general') NOT NULL,
    id_usuario INT NOT NULL,
    rol ENUM('secretario', 'fiscal', 'tesorero', 'presidente') NOT NULL,
    archivo_pdf_firmado VARCHAR(255) NOT NULL,
    fecha_firma TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    signature_data TEXT,
    INDEX idx_solicitud (id_solicitud),
    INDEX idx_usuario (id_usuario),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Actualizar tabla adjudicaciones con campos de firma
ALTER TABLE adjudicaciones ADD COLUMN pdf_firmado VARCHAR(255) DEFAULT NULL COMMENT 'Ruta del PDF con todas las firmas';
ALTER TABLE adjudicaciones ADD COLUMN completamente_firmado TINYINT(1) DEFAULT 0 COMMENT '1 cuando todas las firmas requeridas están aplicadas';

-- 4. Tabla para configuración de roles de firma (opcional - para personalización)
CREATE TABLE IF NOT EXISTS config_firmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento ENUM('adjudicacion', 'certificado_transferencia', 'solicitud_general') NOT NULL,
    orden_firma JSON NOT NULL COMMENT 'Orden de firmas: ["secretario", "fiscal", "tesorero", "presidente"]',
    requiere_tesorero TINYINT(1) DEFAULT 1 COMMENT 'Si requiere firma de tesorero (solo adjudicacion)',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuración por defecto
INSERT INTO config_firmas (tipo_documento, orden_firma, requiere_tesorero) VALUES
('adjudicacion', '["secretario", "fiscal", "tesorero", "presidente"]', 1),
('certificado_transferencia', '["secretario", "fiscal", "presidente"]', 0),
('solicitud_general', '["secretario", "fiscal", "presidente"]', 0);
