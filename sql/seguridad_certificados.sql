-- ============================================================
-- SEGURIDAD CERTIFICADOS - Hash SHA-256 y Timestamp
-- Sistema Callqui Chico
-- ============================================================

-- 1. Agregar campos de hash y timestamp a adjudicaciones
ALTER TABLE adjudicaciones ADD COLUMN hash_sha256 VARCHAR(64) DEFAULT NULL COMMENT 'Hash SHA-256 del PDF generado';
ALTER TABLE adjudicaciones ADD COLUMN timestamp_generacion DATETIME DEFAULT NULL COMMENT 'Timestamp de generación del certificado';

-- 2. Crear tabla de auditoría de certificados
CREATE TABLE IF NOT EXISTS certificados_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificado_id INT NOT NULL COMMENT 'ID de la adjudicacion',
    codigo_certificado VARCHAR(30) NOT NULL,
    accion VARCHAR(50) NOT NULL COMMENT 'generado, firmado, descargado, verificado',
    usuario_id INT DEFAULT NULL COMMENT 'Usuario que realizó la acción',
    ip_address VARCHAR(45),
    hash_anterior VARCHAR(64) DEFAULT NULL,
    hash_nuevo VARCHAR(64) DEFAULT NULL,
    resultado ENUM('exitoso', 'fallido') DEFAULT 'exitoso',
    detalles TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_certificado (certificado_id),
    INDEX idx_codigo (codigo_certificado),
    INDEX idx_fecha (fecha),
    INDEX idx_accion (accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Agregar campos de firma digital
ALTER TABLE adjudicaciones ADD COLUMN hash_sha256_firmado VARCHAR(64) DEFAULT NULL COMMENT 'Hash SHA-256 del PDF firmado';
ALTER TABLE adjudicaciones ADD COLUMN timestamp_firma DATETIME DEFAULT NULL COMMENT 'Timestamp de firma digital';

-- Verificar
SELECT 'Seguridad de certificados implementada' AS resultado;
