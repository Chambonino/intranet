-- =====================================================
-- MIGRACIÓN v8: Permisos por usuario y mejoras
-- Ejecutar UNA sola vez en phpMyAdmin (BD intranet_db)
-- =====================================================

-- 1) Columna de permisos (JSON con array de claves de secciones)
ALTER TABLE administradores
    ADD COLUMN IF NOT EXISTS permisos TEXT NULL AFTER email,
    ADD COLUMN IF NOT EXISTS es_super_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER permisos;

-- 2) El usuario id=1 (admin original) se convierte en super-admin (acceso total)
UPDATE administradores SET es_super_admin = 1 WHERE id = 1;

-- 3) Asegurar que departamentos tenga las columnas usadas por la UI
ALTER TABLE departamentos
    ADD COLUMN IF NOT EXISTS orden INT NOT NULL DEFAULT 0 AFTER color,
    ADD COLUMN IF NOT EXISTS fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP AFTER activo;
