-- =====================================================
-- ACTUALIZACION v5: Módulo de Encuestas
-- =====================================================
-- Ejecutar este script en phpMyAdmin sobre la BD de la intranet.

CREATE TABLE IF NOT EXISTS encuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    pregunta TEXT NOT NULL,
    permite_multiple TINYINT(1) DEFAULT 0,
    anonima TINYINT(1) DEFAULT 1,
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME DEFAULT NULL,
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS encuestas_opciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    encuesta_id INT NOT NULL,
    texto VARCHAR(255) NOT NULL,
    orden INT DEFAULT 0,
    FOREIGN KEY (encuesta_id) REFERENCES encuestas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS encuestas_respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    encuesta_id INT NOT NULL,
    opcion_id INT NOT NULL,
    ip VARCHAR(45),
    user_agent VARCHAR(255),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (encuesta_id) REFERENCES encuestas(id) ON DELETE CASCADE,
    FOREIGN KEY (opcion_id) REFERENCES encuestas_opciones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_encuestas_activa ON encuestas(activa);
CREATE INDEX idx_resp_encuesta_ip ON encuestas_respuestas(encuesta_id, ip);
