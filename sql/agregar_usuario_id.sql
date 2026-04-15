-- Agregar columna usuario_id a adjudicaciones si no existe
-- Ejecutar una vez

ALTER TABLE adjudicaciones ADD COLUMN IF NOT EXISTS usuario_id INT DEFAULT NULL;

-- Agregar índice para búsquedas rápidas
ALTER TABLE adjudicaciones ADD INDEX idx_usuario (usuario_id);