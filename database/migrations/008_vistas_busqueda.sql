-- Aplicar con scripts/migrate-catalog-views.php para comprobar objetos existentes.
-- DDL hace COMMIT implícito en MariaDB: cada paso se comprueba por separado.
ALTER TABLE palabras_clave_tramite
    ADD COLUMN palabra_normalizada VARCHAR(150) GENERATED ALWAYS AS (LOWER(TRIM(palabra))) STORED,
    ADD INDEX idx_palabras_normalizada (palabra_normalizada, tramite_id);

CREATE ALGORITHM=MERGE SQL SECURITY INVOKER VIEW vista_tramites_publicados AS
SELECT t.id, t.nombre, t.slug, t.descripcion, t.categoria_id, t.dificultad,
       t.tiempo_estimado, t.fuente_id, t.estado_verificacion, t.fecha_verificacion,
       c.nombre AS categoria
FROM tramites t JOIN categorias c ON c.id=t.categoria_id
WHERE t.estado='publicado' AND c.estado=1;

CREATE ALGORITHM=MERGE SQL SECURITY INVOKER VIEW vista_documentos_tramite AS
SELECT td.tramite_id, td.documento_id, td.obligatorio, td.cantidad,
       td.presentacion, td.observaciones, td.fuente_id, td.fecha_verificacion,
       d.nombre, d.descripcion, d.fuente_id AS documento_fuente_id,
       d.fecha_verificacion AS documento_fecha_verificacion
FROM tramite_documentos td JOIN documentos d ON d.id=td.documento_id;
