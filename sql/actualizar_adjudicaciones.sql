-- Agregar columnas necesarias para seguimiento de adjudicaciones
-- Ejecutar en phpMyAdmin o línea de comandos

-- Agregar columna codigo_seguimiento si no existe
ALTER TABLE adjudicaciones ADD COLUMN IF NOT EXISTS codigo_seguimiento VARCHAR(30) DEFAULT NULL AFTER codigo;

-- Agregar columna certificado si no existe
ALTER TABLE adjudicaciones ADD COLUMN IF NOT EXISTS certificado VARCHAR(255) DEFAULT NULL AFTER codigo_seguimiento;

-- Actualizar estado para incluir los nuevos valores
ALTER TABLE adjudicaciones MODIFY COLUMN estado ENUM('pendiente','en_revision','aprobado','aprobado_total','certificado_generado','rechazado') DEFAULT 'pendiente';

-- Crear índice para búsquedas rápidas por código de seguimiento
ALTER TABLE adjudicaciones ADD INDEX idx_codigo_seguimiento (codigo_seguimiento);