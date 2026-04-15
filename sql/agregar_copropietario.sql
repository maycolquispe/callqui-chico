-- Script para agregar soporte de copropietario en lotes y transferencias
-- Fecha: 2026-04-01

-- 1. Agregar columna copropietario a la tabla lotes
ALTER TABLE lotes ADD COLUMN copropietario VARCHAR(255) NULL AFTER propietario;

-- 2. Agregar columnas de copropietario a la tabla transferencias_lote
ALTER TABLE transferencias_lote ADD COLUMN copropietario_anterior VARCHAR(255) NULL AFTER propietario_anterior;
ALTER TABLE transferencias_lote ADD COLUMN copropietario_nuevo VARCHAR(255) NULL AFTER propietario_nuevo;

-- 3. Actualizar registros existentes de transferencias si hay datos
-- (Este paso es para mantener consistencia si ya existen datos)

-- Verificar que las columnas se crearon correctamente
-- SELECT columna_name FROM information_schema.columns 
-- WHERE table_name = 'lotes' AND column_name = 'copropietario';