-- Usuario Tesorero
INSERT INTO usuarios (dni, nombres, apellidos, rol, estado, password_hash, correo) 
VALUES ('00000001', 'Tesorero', 'Callqui', 'tesorero', 'activo', '$2y$10$abcdefghijklmnopqrstuv', 'tesorero@callqui.com');

-- O si prefieres crear con password específico (password: tesorero123):
-- INSERT INTO usuarios (dni, nombres, apellidos, rol, estado, password_hash, correo) 
-- VALUES ('00000001', 'Tesorero', 'Callqui', 'tesorero', 'activo', '$2y$10$KIXxPZ5XvZ9xPQfXqKfY9EqF0vN5kR8J3P7L9mQ2tE4U6W8X0Y2Z', 'tesorero@callqui.com');