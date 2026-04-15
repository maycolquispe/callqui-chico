-- ============================================================
-- SEGURIDAD LOGIN - Sistema Callqui Chico
-- Agregar campos de seguridad a tabla usuarios
-- y crear tabla de auditoría de login
-- ============================================================

-- 1. Agregar campos de seguridad a usuarios
ALTER TABLE usuarios ADD COLUMN intentos_fallidos INT DEFAULT 0 COMMENT 'Intentos fallidos de login';
ALTER TABLE usuarios ADD COLUMN bloqueado_hasta DATETIME DEFAULT NULL COMMENT 'Cuenta bloqueada hasta';
ALTER TABLE usuarios ADD COLUMN ultimo_login DATETIME DEFAULT NULL COMMENT 'Último inicio de sesión';

-- 2. Crear tabla de auditoría de login
CREATE TABLE IF NOT EXISTS login_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL COMMENT 'ID del usuario (null si no existe)',
    dni VARCHAR(8) COMMENT 'DNI usado en el intento',
    ip_address VARCHAR(45) COMMENT 'Dirección IP del cliente',
    user_agent VARCHAR(255) COMMENT 'Navegador del cliente',
    resultado ENUM('exitoso', 'fallido', 'bloqueado') NOT NULL COMMENT 'Resultado del intento',
    motivo VARCHAR(255) COMMENT 'Motivo del resultado',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dni (dni),
    INDEX idx_fecha (fecha),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Verificar si se ejecutó correctamente
SELECT 'Campos de seguridad agregados' AS resultado;
