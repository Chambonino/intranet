-- =====================================================
-- ACTUALIZACION v7: KPIs con URL externa al file server
-- =====================================================
-- Permite al admin ingresar una RUTA/URL en otro servidor en lugar de subir el archivo.
-- Ejemplos válidos:
--   http://172.16.16.3/kpis/2026/Calidad_Mayo.pdf       (recomendado, requiere file server con HTTP)
--   \\172.16.16.3\kpis\2026\Calidad_Mayo.xlsx          (UNC path, sólo dentro de Windows)
--   file://172.16.16.3/kpis/2026/Calidad_Mayo.pdf      (file:// algunos navegadores)

ALTER TABLE kpis_departamento
    ADD COLUMN IF NOT EXISTS url_externa VARCHAR(500) DEFAULT NULL AFTER archivo;

-- Si tu MySQL no soporta IF NOT EXISTS en ALTER COLUMN, usa este otro:
-- ALTER TABLE kpis_departamento ADD COLUMN url_externa VARCHAR(500) DEFAULT NULL AFTER archivo;
