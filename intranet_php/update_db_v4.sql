-- =====================================================
-- ACTUALIZACION v4: Organigrama dinámico
-- =====================================================

CREATE TABLE IF NOT EXISTS organigrama_nodos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    puesto VARCHAR(150) NOT NULL,
    departamento VARCHAR(100),
    foto VARCHAR(255),
    parent_id INT DEFAULT NULL,
    orden INT DEFAULT 0,
    color VARCHAR(7) DEFAULT '#1976D2',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES organigrama_nodos(id) ON DELETE SET NULL
) ENGINE=InnoDB;
