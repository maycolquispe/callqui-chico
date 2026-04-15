-- Mejorar tabla adjudicaciones para workflow completo
-- Agregar campos necesarios para el sistema de workflow

ALTER TABLE `adjudicaciones` 
ADD COLUMN `fecha_aprobacion_secretario` DATETIME DEFAULT NULL AFTER `obs_secretario`,
ADD COLUMN `fecha_aprobacion_presidente` DATETIME DEFAULT NULL AFTER `obs_presidente`,
ADD COLUMN `fecha_aprobacion_fiscal` DATETIME DEFAULT NULL AFTER `obs_fiscal`,
ADD COLUMN `fecha_aprobacion_tesorero` DATETIME DEFAULT NULL AFTER `obs_tesorero`,
ADD COLUMN `fecha_aprobacion_comite` DATETIME DEFAULT NULL AFTER `obs_comite`,
ADD COLUMN `usuario_aprobacion_secretario` INT(11) DEFAULT NULL,
ADD COLUMN `usuario_aprobacion_presidente` INT(11) DEFAULT NULL,
ADD COLUMN `usuario_aprobacion_fiscal` INT(11) DEFAULT NULL,
ADD COLUMN `usuario_aprobacion_tesorero` INT(11) DEFAULT NULL,
ADD COLUMN `usuario_aprobacion_comite` INT(11) DEFAULT NULL,
ADD COLUMN `fecha_estado` DATETIME DEFAULT NULL AFTER `fecha_aprobacion_comite`,
ADD COLUMN `comentario_final` TEXT DEFAULT NULL AFTER `fecha_estado`;

-- Crear tabla de notificaciones
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `tipo` ENUM('info','success','warning','danger') DEFAULT 'info',
  `leido` TINYINT(1) DEFAULT 0,
  `enlace` VARCHAR(255) DEFAULT NULL,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_leido` (`leido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de auditoría
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tabla` VARCHAR(50) NOT NULL,
  `registro_id` INT(11) NOT NULL,
  `usuario_id` INT(11) NOT NULL,
  `accion` VARCHAR(20) NOT NULL,
  `datos_anteriores` TEXT,
  `datos_nuevos` TEXT,
  `ip` VARCHAR(45) DEFAULT NULL,
  `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tabla_registro` (`tabla`, `registro_id`),
  KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
