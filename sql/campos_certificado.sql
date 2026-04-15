-- Agregar columnas de metros lineales a linderos
ALTER TABLE adjudicaciones ADD COLUMN metros_frente DECIMAL(10,2);
ALTER TABLE adjudicaciones ADD COLUMN metros_fondo DECIMAL(10,2);
ALTER TABLE adjudicaciones ADD COLUMN metros_derecha DECIMAL(10,2);
ALTER TABLE adjudicaciones ADD COLUMN metros_izquierda DECIMAL(10,2);