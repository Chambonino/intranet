-- =====================================================
-- BASE DE DATOS PARA INTRANET CORPORATIVA
-- Empresa: Automotriz (Inyección, Cromado, Pintura)
-- =====================================================

CREATE DATABASE IF NOT EXISTS intranet_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE intranet_db;

-- =====================================================
-- TABLA DE ADMINISTRADORES
-- =====================================================
CREATE TABLE IF NOT EXISTS administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME NULL
) ENGINE=InnoDB;

-- Usuario admin por defecto (password: admin123)
INSERT INTO administradores (usuario, password, nombre_completo, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@empresa.com');

-- =====================================================
-- TABLA DE DEPARTAMENTOS
-- =====================================================
CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    color VARCHAR(7) DEFAULT '#333333',
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO departamentos (nombre, color) VALUES 
('IT', '#2196F3'),
('RH', '#9C27B0'),
('Compras', '#FF9800'),
('Ventas', '#4CAF50'),
('Logística', '#00BCD4'),
('Proyectos', '#F44336'),
('Pintura', '#E91E63'),
('Calidad', '#3F51B5');

-- =====================================================
-- TABLA DE SLIDER DE NOTICIAS
-- =====================================================
CREATE TABLE IF NOT EXISTS slider_noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255) NOT NULL,
    enlace VARCHAR(255),
    orden INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE EVENTOS DEL CALENDARIO
-- =====================================================
CREATE TABLE IF NOT EXISTS eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_evento DATE NOT NULL,
    hora_inicio TIME,
    hora_fin TIME,
    lugar VARCHAR(200),
    color VARCHAR(7) DEFAULT '#1976D2',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE CUMPLEAÑOS DE EMPLEADOS
-- =====================================================
CREATE TABLE IF NOT EXISTS empleados_cumpleanos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    foto VARCHAR(255),
    departamento_id INT,
    puesto VARCHAR(100),
    fecha_nacimiento DATE NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE APLICACIONES (12 apps)
-- =====================================================
CREATE TABLE IF NOT EXISTS aplicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    icono VARCHAR(50) DEFAULT 'fa-link',
    url VARCHAR(255) NOT NULL,
    color VARCHAR(7) DEFAULT '#333333',
    orden INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO aplicaciones (nombre, icono, url, color, orden) VALUES
('Sistema A', 'fa-desktop', 'http://192.168.1.2/a', '#F44336', 1),
('Sistema B', 'fa-database', 'http://192.168.1.2/b', '#E91E63', 2),
('Sistema C', 'fa-chart-bar', 'http://192.168.1.2/c', '#9C27B0', 3),
('Sistema D', 'fa-users', 'http://192.168.1.2/d', '#673AB7', 4),
('Sistema E', 'fa-file-alt', 'http://192.168.1.2/e', '#3F51B5', 5),
('Sistema F', 'fa-cogs', 'http://192.168.1.2/f', '#2196F3', 6),
('Sistema G', 'fa-truck', 'http://192.168.1.2/g', '#00BCD4', 7),
('Sistema H', 'fa-clipboard', 'http://192.168.1.2/h', '#009688', 8),
('Sistema I', 'fa-calculator', 'http://192.168.1.2/i', '#4CAF50', 9),
('Sistema J', 'fa-calendar', 'http://192.168.1.2/j', '#8BC34A', 10),
('Sistema K', 'fa-envelope', 'http://192.168.1.2/i', '#FF9800', 11),
('Sistema L', 'fa-print', 'http://192.168.1.2/k', '#FF5722', 12);

-- =====================================================
-- TABLA DE ARCHIVOS POR DEPARTAMENTO
-- =====================================================
CREATE TABLE IF NOT EXISTS archivos_departamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    archivo VARCHAR(255) NOT NULL,
    departamento_id INT,
    descargas INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE GALERÍA DE FOTOS
-- =====================================================
CREATE TABLE IF NOT EXISTS galeria_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200),
    descripcion TEXT,
    imagen VARCHAR(255) NOT NULL,
    orden INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE VIDEOS
-- =====================================================
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    archivo_video VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255),
    orden INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE ARTÍCULOS/NOTICIAS
-- =====================================================
CREATE TABLE IF NOT EXISTS articulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    contenido TEXT NOT NULL,
    imagen VARCHAR(255),
    autor VARCHAR(100),
    destacado TINYINT(1) DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    fecha_publicacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE PORTALES DE CLIENTES
-- =====================================================
CREATE TABLE IF NOT EXISTS portales_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    logo VARCHAR(255),
    url VARCHAR(255) NOT NULL,
    descripcion TEXT,
    orden INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE CUENTA REGRESIVA (EVENTOS PRÓXIMOS)
-- =====================================================
CREATE TABLE IF NOT EXISTS cuenta_regresiva (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_evento DATETIME NOT NULL,
    color VARCHAR(7) DEFAULT '#F44336',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE AVISOS
-- =====================================================
CREATE TABLE IF NOT EXISTS avisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    contenido TEXT,
    tipo ENUM('info', 'warning', 'danger', 'success') DEFAULT 'info',
    activo TINYINT(1) DEFAULT 1,
    fecha_inicio DATE,
    fecha_fin DATE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE INFORMACIÓN DE LA COMPAÑÍA
-- =====================================================
CREATE TABLE IF NOT EXISTS info_compania (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seccion VARCHAR(100) NOT NULL,
    titulo VARCHAR(200),
    contenido TEXT,
    imagen VARCHAR(255),
    orden INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO info_compania (seccion, titulo, contenido, orden) VALUES
('mision', 'Nuestra Misión', 'Somos una empresa líder en la industria automotriz, especializada en inyección, cromado y pintura de piezas plásticas automotrices, comprometidos con la excelencia y la innovación.', 1),
('vision', 'Nuestra Visión', 'Ser reconocidos como el socio preferido de los principales fabricantes automotrices a nivel global, destacando por nuestra calidad, tecnología y compromiso con el medio ambiente.', 2),
('valores', 'Nuestros Valores', 'Calidad, Innovación, Trabajo en equipo, Responsabilidad, Compromiso con el cliente, Mejora continua.', 3);
