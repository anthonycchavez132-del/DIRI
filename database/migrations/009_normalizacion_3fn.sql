-- DIRI: eliminación de dependencias parciales y catálogo compartido de alias.
ALTER TABLE integraciones_api DROP COLUMN tipo;
CREATE TABLE palabras_clave (
    palabra VARCHAR(150) NOT NULL PRIMARY KEY,
    palabra_normalizada VARCHAR(150) GENERATED ALWAYS AS (LOWER(TRIM(palabra))) STORED,
    KEY idx_palabras_normalizada (palabra_normalizada, palabra)
) ENGINE=InnoDB;
INSERT INTO palabras_clave(palabra) SELECT DISTINCT palabra FROM palabras_clave_tramite;
ALTER TABLE palabras_clave_tramite DROP INDEX idx_palabras_normalizada, DROP COLUMN palabra_normalizada,
    ADD CONSTRAINT fk_alias_palabra FOREIGN KEY(palabra) REFERENCES palabras_clave(palabra);
ALTER TABLE paso_documentos
    DROP FOREIGN KEY fk_pd_paso, DROP FOREIGN KEY fk_pd_documento,
    DROP INDEX idx_pd_paso, DROP INDEX idx_pd_documento, DROP COLUMN tramite_id,
    ADD KEY idx_pd_documento (documento_id),
    ADD CONSTRAINT nf_fk_pd_paso FOREIGN KEY (paso_id) REFERENCES pasos_tramite(id),
    ADD CONSTRAINT nf_fk_pd_documento FOREIGN KEY (documento_id) REFERENCES documentos(id);
ALTER TABLE paso_dependencias
    DROP FOREIGN KEY fk_dependencia_paso, DROP FOREIGN KEY fk_dependencia_previo,
    DROP INDEX idx_dependencia_paso, DROP INDEX idx_dependencia_previo, DROP COLUMN tramite_id,
    ADD KEY idx_dependencia_previo (paso_previo_id),
    ADD CONSTRAINT nf_fk_dependencia_paso FOREIGN KEY (paso_id) REFERENCES pasos_tramite(id),
    ADD CONSTRAINT nf_fk_dependencia_previo FOREIGN KEY (paso_previo_id) REFERENCES pasos_tramite(id);
ALTER TABLE paso_costos
    DROP FOREIGN KEY fk_pc_paso, DROP FOREIGN KEY fk_pc_costo,
    DROP INDEX idx_pc_paso, DROP INDEX idx_pc_costo, DROP COLUMN tramite_id,
    ADD KEY idx_pc_costo (costo_id),
    ADD CONSTRAINT nf_fk_pc_paso FOREIGN KEY (paso_id) REFERENCES pasos_tramite(id),
    ADD CONSTRAINT nf_fk_pc_costo FOREIGN KEY (costo_id) REFERENCES costos(id);
ALTER TABLE progreso_documentos
    DROP FOREIGN KEY fk_progdoc_progreso, DROP FOREIGN KEY fk_progdoc_documento,
    DROP INDEX idx_progdoc_progreso, DROP INDEX idx_progdoc_documento, DROP COLUMN tramite_id,
    ADD KEY idx_progdoc_documento (documento_id),
    ADD CONSTRAINT nf_fk_progdoc_progreso FOREIGN KEY (progreso_tramite_id) REFERENCES progreso_tramites(id) ON DELETE CASCADE,
    ADD CONSTRAINT nf_fk_progdoc_documento FOREIGN KEY (documento_id) REFERENCES documentos(id);
ALTER TABLE progreso_pasos
    DROP FOREIGN KEY fk_progpaso_progreso, DROP FOREIGN KEY fk_progpaso_paso,
    DROP INDEX idx_progpaso_progreso, DROP INDEX idx_progpaso_paso, DROP COLUMN tramite_id,
    ADD KEY idx_progpaso_paso (paso_id),
    ADD CONSTRAINT nf_fk_progpaso_progreso FOREIGN KEY (progreso_tramite_id) REFERENCES progreso_tramites(id) ON DELETE CASCADE,
    ADD CONSTRAINT nf_fk_progpaso_paso FOREIGN KEY (paso_id) REFERENCES pasos_tramite(id);

DELIMITER $$
CREATE TRIGGER nf_paso_documentos_insert BEFORE INSERT ON paso_documentos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM tramite_documentos WHERE documento_id=NEW.documento_id AND tramite_id=parent_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$$
CREATE TRIGGER nf_paso_documentos_update BEFORE UPDATE ON paso_documentos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM tramite_documentos WHERE documento_id=NEW.documento_id AND tramite_id=parent_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$$
CREATE TRIGGER nf_paso_dependencias_insert BEFORE INSERT ON paso_dependencias FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM pasos_tramite WHERE id=NEW.paso_previo_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$$
CREATE TRIGGER nf_paso_dependencias_update BEFORE UPDATE ON paso_dependencias FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM pasos_tramite WHERE id=NEW.paso_previo_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$$
CREATE TRIGGER nf_paso_costos_insert BEFORE INSERT ON paso_costos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM costos WHERE id=NEW.costo_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$$
CREATE TRIGGER nf_paso_costos_update BEFORE UPDATE ON paso_costos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM costos WHERE id=NEW.costo_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$$
CREATE TRIGGER nf_progreso_documentos_insert BEFORE INSERT ON progreso_documentos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM progreso_tramites WHERE id=NEW.progreso_tramite_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM tramite_documentos WHERE documento_id=NEW.documento_id AND tramite_id=parent_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$$
CREATE TRIGGER nf_progreso_documentos_update BEFORE UPDATE ON progreso_documentos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM progreso_tramites WHERE id=NEW.progreso_tramite_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM tramite_documentos WHERE documento_id=NEW.documento_id AND tramite_id=parent_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$$
CREATE TRIGGER nf_progreso_pasos_insert BEFORE INSERT ON progreso_pasos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM progreso_tramites WHERE id=NEW.progreso_tramite_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$$
CREATE TRIGGER nf_progreso_pasos_update BEFORE UPDATE ON progreso_pasos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM progreso_tramites WHERE id=NEW.progreso_tramite_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$$
CREATE TRIGGER nf_pasos_tramite_parent BEFORE UPDATE ON pasos_tramite FOR EACH ROW
BEGIN
    IF NEW.tramite_id<>OLD.tramite_id AND (EXISTS(SELECT 1 FROM paso_documentos WHERE paso_id=OLD.id) OR EXISTS(SELECT 1 FROM paso_costos WHERE paso_id=OLD.id) OR EXISTS(SELECT 1 FROM paso_dependencias WHERE paso_id=OLD.id OR paso_previo_id=OLD.id) OR EXISTS(SELECT 1 FROM progreso_pasos WHERE paso_id=OLD.id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se cambia el tramite mientras existan relaciones';
    END IF;
END$$
CREATE TRIGGER nf_costos_parent BEFORE UPDATE ON costos FOR EACH ROW
BEGIN
    IF NEW.tramite_id<>OLD.tramite_id AND (EXISTS(SELECT 1 FROM paso_costos WHERE costo_id=OLD.id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se cambia el tramite mientras existan relaciones';
    END IF;
END$$
CREATE TRIGGER nf_progreso_tramites_parent BEFORE UPDATE ON progreso_tramites FOR EACH ROW
BEGIN
    IF NEW.tramite_id<>OLD.tramite_id AND (EXISTS(SELECT 1 FROM progreso_documentos WHERE progreso_tramite_id=OLD.id) OR EXISTS(SELECT 1 FROM progreso_pasos WHERE progreso_tramite_id=OLD.id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se cambia el tramite mientras existan relaciones';
    END IF;
END$$
CREATE TRIGGER nf_tramite_documentos_delete BEFORE DELETE ON tramite_documentos FOR EACH ROW
BEGIN
    IF (EXISTS(SELECT 1 FROM paso_documentos pd JOIN pasos_tramite p ON p.id=pd.paso_id WHERE p.tramite_id=OLD.tramite_id AND pd.documento_id=OLD.documento_id) OR EXISTS(SELECT 1 FROM progreso_documentos pd JOIN progreso_tramites p ON p.id=pd.progreso_tramite_id WHERE p.tramite_id=OLD.tramite_id AND pd.documento_id=OLD.documento_id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El documento esta asociado a pasos o seguimientos';
    END IF;
END$$
CREATE TRIGGER nf_tramite_documentos_update BEFORE UPDATE ON tramite_documentos FOR EACH ROW
BEGIN
    IF (NEW.tramite_id<>OLD.tramite_id OR NEW.documento_id<>OLD.documento_id) AND (EXISTS(SELECT 1 FROM paso_documentos pd JOIN pasos_tramite p ON p.id=pd.paso_id WHERE p.tramite_id=OLD.tramite_id AND pd.documento_id=OLD.documento_id) OR EXISTS(SELECT 1 FROM progreso_documentos pd JOIN progreso_tramites p ON p.id=pd.progreso_tramite_id WHERE p.tramite_id=OLD.tramite_id AND pd.documento_id=OLD.documento_id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El documento esta asociado a pasos o seguimientos';
    END IF;
END$$
CREATE TRIGGER nf_alias_insert BEFORE INSERT ON palabras_clave_tramite FOR EACH ROW
BEGIN
    INSERT INTO palabras_clave(palabra) VALUES(NEW.palabra) ON DUPLICATE KEY UPDATE palabra=palabra;
END$$
CREATE TRIGGER nf_alias_update BEFORE UPDATE ON palabras_clave_tramite FOR EACH ROW
BEGIN
    INSERT INTO palabras_clave(palabra) VALUES(NEW.palabra) ON DUPLICATE KEY UPDATE palabra=palabra;
END$$
DELIMITER ;
