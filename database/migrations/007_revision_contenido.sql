-- Una sola vez sobre instalaciones anteriores. migrate-content.php comprueba el estado.
ALTER TABLE tramites ADD COLUMN revision INT UNSIGNED NOT NULL DEFAULT 1;
INSERT INTO versiones_esquema (version, descripcion) VALUES
('007_revision_contenido', 'Revisión de trámites y protección de cambios de requisitos y documentos');
