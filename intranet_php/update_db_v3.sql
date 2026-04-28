-- =====================================================
-- ACTUALIZACION v3: Organigrama y KPIs con imagen
-- =====================================================

-- Tabla de organigrama
CREATE TABLE IF NOT EXISTS organigrama (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL DEFAULT 'Organigrama Corporativo',
    imagen VARCHAR(255) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- KPIs: agregar mes, año e imagen
ALTER TABLE kpis_departamento ADD COLUMN mes INT DEFAULT NULL AFTER archivo;
ALTER TABLE kpis_departamento ADD COLUMN anio INT DEFAULT NULL AFTER mes;
ALTER TABLE kpis_departamento ADD COLUMN imagen VARCHAR(255) DEFAULT NULL AFTER anio;
