-- Tabla de transferencias de lotes
CREATE TABLE IF NOT EXISTS `transferencias_lote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lote_id` int(11) NOT NULL,
  `propietario_anterior` int(11) DEFAULT NULL,
  `propietario_nuevo` int(11) NOT NULL,
  `usuario_registro` int(11) NOT NULL,
  `fecha_transferencia` datetime NOT NULL DEFAULT current_timestamp(),
  `documento` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lote` (`lote_id`),
  KEY `idx_fecha` (`fecha_transferencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;