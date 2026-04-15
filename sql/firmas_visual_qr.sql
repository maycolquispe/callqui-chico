-- Tabla para firmas visuales en certificados
CREATE TABLE IF NOT EXISTS config_firmas_visual (
    id INT(11) NOT NULL AUTO_INCREMENT,
    rol VARCHAR(20) NOT NULL UNIQUE,
    firma_imagen VARCHAR(255) DEFAULT NULL,
    qr_code VARCHAR(255) DEFAULT NULL COMMENT 'Logo QR para el certificado',
    actualizado_por INT(11) DEFAULT NULL,
    fecha_actualizacion DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar registros para cada rol
INSERT IGNORE INTO config_firmas_visual (rol) VALUES ('secretario');
INSERT IGNORE INTO config_firmas_visual (rol) VALUES ('tesorero');
INSERT IGNORE INTO config_firmas_visual (rol) VALUES ('presidente');

-- Tabla para logo QR del certificado
CREATE TABLE IF NOT EXISTS config_certificado (
    id INT(11) NOT NULL AUTO_INCREMENT,
    logo_principal VARCHAR(255) DEFAULT NULL COMMENT 'Logo principal para certificado',
    qr_logo VARCHAR(255) DEFAULT NULL COMMENT 'Imagen de QR para mostrar',
    nombre_comunidad VARCHAR(200) DEFAULT 'Comunidad Campesina Callqui Chico',
    actualizada_por INT(11) DEFAULT NULL,
    fecha_actualizacion DATETIME DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO config_certificado (id, nombre_comunidad) VALUES (1, 'Comunidad Campesina Callqui Chico');