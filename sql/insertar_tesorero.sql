-- Insertar usuario tesorero si no existe
INSERT IGNORE INTO usuarios (dni, nombres, apellidos, rol, estado, password_hash, correo) 
VALUES ('00000001', 'Tesorero', 'Callqui', 'tesorero', 'activo', '$2y$10$abcdefghijklmnopqrstuv', 'tesorero@callqui.com');