-- DIRI: actividad de 12 vistas, 4 por integrante.
-- Ejecucion unica en una base DIRI existente. No usar --force.
-- SQL SECURITY INVOKER: conserva los permisos del usuario que consulta.
SET NAMES utf8mb4;

-- 1. Catálogo público por categoría
-- Bloque asignado: Anthony Chavez Coronado
CREATE SQL SECURITY INVOKER VIEW vw_diri_catalogo_publicado AS
SELECT t.id AS tramite_id, t.nombre AS tramite,
       c.nombre AS categoria, t.estado_verificacion
FROM tramites t
JOIN categorias c ON c.id = t.categoria_id
WHERE t.estado = 'publicado' AND c.estado = 1;

-- 2. Requisitos obligatorios
-- Bloque asignado: Anthony Chavez Coronado
CREATE SQL SECURITY INVOKER VIEW vw_diri_requisitos_obligatorios AS
SELECT r.id AS requisito_id, t.id AS tramite_id,
       t.nombre AS tramite, r.nombre AS requisito,
       r.descripcion, r.estado_verificacion
FROM requisitos r
JOIN tramites t ON t.id = r.tramite_id
JOIN categorias c ON c.id = t.categoria_id
WHERE r.obligatorio = 1
  AND t.estado = 'publicado' AND c.estado = 1;

-- 3. Documentos requeridos
-- Bloque asignado: Anthony Chavez Coronado
CREATE SQL SECURITY INVOKER VIEW vw_diri_documentos_requeridos AS
SELECT t.id AS tramite_id, t.nombre AS tramite,
       d.id AS documento_id, d.nombre AS documento,
       td.obligatorio, td.cantidad, td.presentacion
FROM tramite_documentos td
JOIN documentos d ON d.id = td.documento_id
JOIN tramites t ON t.id = td.tramite_id
JOIN categorias c ON c.id = t.categoria_id
WHERE t.estado = 'publicado' AND c.estado = 1;

-- 4. Alias del buscador
-- Bloque asignado: Anthony Chavez Coronado
CREATE SQL SECURITY INVOKER VIEW vw_diri_alias_busqueda AS
SELECT t.id AS tramite_id, t.nombre AS tramite,
       pc.palabra, pc.palabra_normalizada
FROM palabras_clave_tramite pt
JOIN palabras_clave pc ON pc.palabra = pt.palabra
JOIN tramites t ON t.id = pt.tramite_id
JOIN categorias c ON c.id = t.categoria_id
WHERE t.estado = 'publicado' AND c.estado = 1;

-- 5. Sedes activas
-- Bloque asignado: Sebastian Peñarrieta Peña
CREATE SQL SECURITY INVOKER VIEW vw_diri_sedes_activas AS
SELECT s.id AS sucursal_id, s.nombre AS sucursal,
       i.nombre AS institucion, s.ciudad, s.zona, s.direccion
FROM sucursales s
JOIN instituciones i ON i.id = s.institucion_id
WHERE s.estado = 'activa' AND i.estado = 1;

-- 6. Horarios de las sedes
-- Bloque asignado: Sebastian Peñarrieta Peña
CREATE SQL SECURITY INVOKER VIEW vw_diri_horarios_sedes AS
SELECT h.id AS horario_id, s.id AS sucursal_id,
       s.nombre AS sucursal, i.nombre AS institucion,
       h.dia_semana, h.hora_apertura, h.hora_cierre
FROM horarios h
JOIN sucursales s ON s.id = h.sucursal_id
JOIN instituciones i ON i.id = s.institucion_id
WHERE s.estado = 'activa' AND i.estado = 1;

-- 7. Disponibilidad de trámites por sede
-- Bloque asignado: Sebastian Peñarrieta Peña
CREATE SQL SECURITY INVOKER VIEW vw_diri_tramites_sedes AS
SELECT t.id AS tramite_id, t.nombre AS tramite,
       s.id AS sucursal_id, s.nombre AS sucursal,
       i.nombre AS institucion, s.ciudad, s.zona
FROM tramite_sucursales ts
JOIN tramites t ON t.id = ts.tramite_id
JOIN categorias c ON c.id = t.categoria_id
JOIN sucursales s ON s.id = ts.sucursal_id
JOIN instituciones i ON i.id = s.institucion_id
WHERE ts.disponible = 1 AND t.estado = 'publicado'
  AND c.estado = 1 AND s.estado = 'activa' AND i.estado = 1;

-- 8. Pasos e instituciones
-- Bloque asignado: Sebastian Peñarrieta Peña
CREATE SQL SECURITY INVOKER VIEW vw_diri_pasos_instituciones AS
SELECT p.id AS paso_id, t.id AS tramite_id,
       t.nombre AS tramite, p.numero_paso, p.titulo,
       i.nombre AS institucion
FROM pasos_tramite p
JOIN tramites t ON t.id = p.tramite_id
JOIN categorias c ON c.id = t.categoria_id
LEFT JOIN instituciones i ON i.id = p.institucion_id
WHERE t.estado = 'publicado' AND c.estado = 1;

-- 9. Costos registrados
-- Bloque asignado: Abel Daniel Osco Aquino
CREATE SQL SECURITY INVOKER VIEW vw_diri_costos_tramite AS
SELECT co.id AS costo_id, t.id AS tramite_id,
       t.nombre AS tramite, co.concepto,
       co.monto, co.moneda, co.estado_verificacion
FROM costos co
JOIN tramites t ON t.id = co.tramite_id
JOIN categorias c ON c.id = t.categoria_id
WHERE t.estado = 'publicado' AND c.estado = 1;

-- 10. Resumen documental por trámite
-- Bloque asignado: Abel Daniel Osco Aquino
CREATE SQL SECURITY INVOKER VIEW vw_diri_resumen_documental AS
SELECT t.id AS tramite_id, t.nombre AS tramite,
       COUNT(td.documento_id) AS tipos_documentos,
       SUM(CASE WHEN td.obligatorio = 1 THEN 1 ELSE 0 END)
           AS tipos_obligatorios
FROM tramites t
JOIN categorias c ON c.id = t.categoria_id
LEFT JOIN tramite_documentos td ON td.tramite_id = t.id
WHERE t.estado = 'publicado' AND c.estado = 1
GROUP BY t.id, t.nombre;

-- 11. Cantidad de trámites por categoría
-- Bloque asignado: Abel Daniel Osco Aquino
CREATE SQL SECURITY INVOKER VIEW vw_diri_tramites_por_categoria AS
SELECT c.id AS categoria_id, c.nombre AS categoria,
       COUNT(t.id) AS tramites_publicados
FROM categorias c
LEFT JOIN tramites t ON t.categoria_id = c.id
                   AND t.estado = 'publicado'
WHERE c.estado = 1
GROUP BY c.id, c.nombre;

-- 12. Trámites por verificar
-- Bloque asignado: Abel Daniel Osco Aquino
CREATE SQL SECURITY INVOKER VIEW vw_diri_tramites_por_verificar AS
SELECT t.id AS tramite_id, t.nombre AS tramite,
       t.estado_verificacion, f.nombre AS fuente,
       t.fecha_verificacion
FROM tramites t
JOIN categorias c ON c.id = t.categoria_id
LEFT JOIN fuentes f ON f.id = t.fuente_id
WHERE t.estado = 'publicado' AND c.estado = 1
  AND t.estado_verificacion <> 'verificado';
