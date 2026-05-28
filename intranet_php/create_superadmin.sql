-- =====================================================
-- Crear usuario administrador con TODOS los permisos
-- Usuario:    superadmin
-- Contraseña: Admin@2026
-- Rol:        Super Administrador (acceso total)
-- =====================================================
-- IMPORTANTE: ejecutar PRIMERO update_db_v8.sql para
-- tener las columnas permisos y es_super_admin.
-- =====================================================

INSERT INTO administradores (usuario, password, nombre_completo, email, activo, permisos, es_super_admin)
VALUES (
    'superadmin',
    '$2b$10$UH7mLYjtFC4hi.uBQ3gwwePHZEh4kOXONmwUy2JySFI8QtM7cz.ay',
    'Super Administrador',
    'superadmin@empresa.local',
    1,
    NULL,
    1
)
ON DUPLICATE KEY UPDATE
    password        = VALUES(password),
    nombre_completo = VALUES(nombre_completo),
    email           = VALUES(email),
    activo          = 1,
    permisos        = NULL,
    es_super_admin  = 1;

-- Verificación rápida
SELECT id, usuario, nombre_completo, activo, es_super_admin
FROM administradores
WHERE usuario = 'superadmin';
