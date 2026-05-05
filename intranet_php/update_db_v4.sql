-- =====================================================
-- ACTUALIZACION v4: Organigramas dinámicos
-- =====================================================
-- Ejecutar este script en phpMyAdmin sobre la BD de la intranet.

-- Tabla 1: Organigrama jerárquico clásico (vista padre-hijo)
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

-- Tabla 2: Organigramas Drag & Drop (múltiples por departamento, JSON)
CREATE TABLE IF NOT EXISTS organigramas_custom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(180) NOT NULL,
    departamento VARCHAR(120) DEFAULT NULL,
    datos_json LONGTEXT,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
