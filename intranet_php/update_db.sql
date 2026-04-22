-- =====================================================
-- ACTUALIZACION: Agregar campo archivo a eventos
-- y departamento_id para colores en calendario
-- =====================================================
ALTER TABLE eventos ADD COLUMN archivo VARCHAR(255) DEFAULT NULL AFTER lugar;
ALTER TABLE eventos ADD COLUMN departamento_id INT DEFAULT NULL AFTER color;
ALTER TABLE eventos ADD FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL;
