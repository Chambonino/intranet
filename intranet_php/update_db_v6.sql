-- =====================================================
-- ACTUALIZACION v6: Nuevas secciones de Nuestra Compañía
-- =====================================================
-- Agrega 4 secciones: Historia, Objetivos, Política Integral, Reconocimientos

INSERT INTO info_compania (seccion, titulo, contenido, orden, activo) VALUES
('historia',           'Nuestra Historia',          'Edita esta sección desde el panel de administración. Cuenta la historia de la empresa, fechas importantes, fundadores y evolución.', 4, 1),
('objetivos',          'Nuestros Objetivos',        'Edita esta sección desde el panel de administración. Define los objetivos estratégicos a corto, mediano y largo plazo.', 5, 1),
('politica_integral',  'Política Integral',         'Edita esta sección desde el panel de administración. Política integral de calidad, medio ambiente y seguridad.', 6, 1),
('reconocimientos',    'Reconocimientos',           'Edita esta sección desde el panel de administración. Lista los premios, certificaciones y reconocimientos obtenidos.', 7, 1);
