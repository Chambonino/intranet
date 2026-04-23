-- =====================================================
-- ACTUALIZACION v2: Nuevas funcionalidades
-- =====================================================

-- Aniversarios laborales
ALTER TABLE empleados_cumpleanos ADD COLUMN fecha_ingreso DATE DEFAULT NULL AFTER fecha_nacimiento;

-- KPIs por departamento
CREATE TABLE IF NOT EXISTS kpis_departamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    archivo VARCHAR(255) NOT NULL,
    departamento_id INT,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Galería por departamento
ALTER TABLE galeria_fotos ADD COLUMN departamento_id INT DEFAULT NULL AFTER descripcion;
ALTER TABLE galeria_fotos ADD FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL;

-- Tipo de evento en galería
ALTER TABLE galeria_fotos ADD COLUMN tipo_evento VARCHAR(100) DEFAULT NULL AFTER departamento_id;
ALTER TABLE info_compania ADD COLUMN archivo_pdf VARCHAR(255) DEFAULT NULL AFTER contenido;
