-- DIRI: instalación nueva con estructura y catálogo.
-- No incluye usuarios ni auditorías personales de la instalación original.
CREATE DATABASE IF NOT EXISTS diri2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE diri2;
SET @usuario_id = NULL;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditorias` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `tabla` varchar(64) NOT NULL,
  `registro_id` varchar(100) NOT NULL,
  `accion` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `valores_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valores_anteriores`)),
  `valores_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valores_nuevos`)),
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_auditorias_usuario` (`usuario_id`),
  KEY `idx_auditorias_registro` (`tabla`,`registro_id`,`fecha`),
  KEY `idx_auditorias_fecha` (`fecha`),
  CONSTRAINT `fk_auditorias_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=479 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_documentos_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_documentos_insert AFTER INSERT ON documentos FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'documentos', CONCAT_WS(':' , NEW.id), 'INSERT', NULL, JSON_OBJECT('id', NEW.id, 'nombre', NEW.nombre, 'descripcion', NEW.descripcion, 'estado', NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_documentos_update AFTER UPDATE ON documentos FOR EACH ROW
BEGIN
IF NOT ((OLD.id <=> NEW.id) AND (OLD.nombre <=> NEW.nombre) AND (OLD.descripcion <=> NEW.descripcion) AND (OLD.estado <=> NEW.estado)) THEN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'documentos', CONCAT_WS(':' , NEW.id), 'UPDATE', JSON_OBJECT('id', OLD.id, 'nombre', OLD.nombre, 'descripcion', OLD.descripcion, 'estado', OLD.estado), JSON_OBJECT('id', NEW.id, 'nombre', NEW.nombre, 'descripcion', NEW.descripcion, 'estado', NEW.estado));
END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_documentos_delete AFTER DELETE ON documentos FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'documentos', CONCAT_WS(':' , OLD.id), 'DELETE', JSON_OBJECT('id', OLD.id, 'nombre', OLD.nombre, 'descripcion', OLD.descripcion, 'estado', OLD.estado), NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `establecimientos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `direccion` varchar(500) NOT NULL,
  `zona` varchar(100) NOT NULL,
  `latitud` decimal(10,7) NOT NULL,
  `longitud` decimal(10,7) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `fuente_url` varchar(2048) DEFAULT NULL,
  `fecha_verificacion` date DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_establecimientos_latitud` CHECK (`latitud` between -90 and 90),
  CONSTRAINT `ck_establecimientos_longitud` CHECK (`longitud` between -180 and 180)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_establecimientos_insert AFTER INSERT ON establecimientos FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'establecimientos', CONCAT_WS(':' , NEW.id), 'INSERT', NULL, JSON_OBJECT('id', NEW.id, 'nombre', NEW.nombre, 'direccion', NEW.direccion, 'zona', NEW.zona, 'latitud', NEW.latitud, 'longitud', NEW.longitud, 'telefono', NEW.telefono, 'fuente_url', NEW.fuente_url, 'fecha_verificacion', NEW.fecha_verificacion, 'estado', NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_establecimientos_update AFTER UPDATE ON establecimientos FOR EACH ROW
BEGIN
IF NOT ((OLD.id <=> NEW.id) AND (OLD.nombre <=> NEW.nombre) AND (OLD.direccion <=> NEW.direccion) AND (OLD.zona <=> NEW.zona) AND (OLD.latitud <=> NEW.latitud) AND (OLD.longitud <=> NEW.longitud) AND (OLD.telefono <=> NEW.telefono) AND (OLD.fuente_url <=> NEW.fuente_url) AND (OLD.fecha_verificacion <=> NEW.fecha_verificacion) AND (OLD.estado <=> NEW.estado)) THEN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'establecimientos', CONCAT_WS(':' , NEW.id), 'UPDATE', JSON_OBJECT('id', OLD.id, 'nombre', OLD.nombre, 'direccion', OLD.direccion, 'zona', OLD.zona, 'latitud', OLD.latitud, 'longitud', OLD.longitud, 'telefono', OLD.telefono, 'fuente_url', OLD.fuente_url, 'fecha_verificacion', OLD.fecha_verificacion, 'estado', OLD.estado), JSON_OBJECT('id', NEW.id, 'nombre', NEW.nombre, 'direccion', NEW.direccion, 'zona', NEW.zona, 'latitud', NEW.latitud, 'longitud', NEW.longitud, 'telefono', NEW.telefono, 'fuente_url', NEW.fuente_url, 'fecha_verificacion', NEW.fecha_verificacion, 'estado', NEW.estado));
END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_establecimientos_delete AFTER DELETE ON establecimientos FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'establecimientos', CONCAT_WS(':' , OLD.id), 'DELETE', JSON_OBJECT('id', OLD.id, 'nombre', OLD.nombre, 'direccion', OLD.direccion, 'zona', OLD.zona, 'latitud', OLD.latitud, 'longitud', OLD.longitud, 'telefono', OLD.telefono, 'fuente_url', OLD.fuente_url, 'fecha_verificacion', OLD.fecha_verificacion, 'estado', OLD.estado), NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_dependencias` (
  `gestion_id` int(10) unsigned NOT NULL,
  `gestion_previa_id` int(10) unsigned NOT NULL,
  `dias_espera` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`gestion_id`,`gestion_previa_id`),
  KEY `fk_gestion_dependencias_previa` (`gestion_previa_id`),
  CONSTRAINT `fk_gestion_dependencias_gestion` FOREIGN KEY (`gestion_id`) REFERENCES `gestiones` (`id`),
  CONSTRAINT `fk_gestion_dependencias_previa` FOREIGN KEY (`gestion_previa_id`) REFERENCES `gestiones` (`id`),
  CONSTRAINT `ck_gestion_dependencias_distintas` CHECK (`gestion_id` <> `gestion_previa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_gestion_dependencias_insert AFTER INSERT ON gestion_dependencias FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id,tabla,registro_id,accion,valores_anteriores,valores_nuevos)
VALUES (NULLIF(@usuario_id,0),'gestion_dependencias',CONCAT_WS(':',NEW.gestion_id,NEW.gestion_previa_id),'INSERT',NULL,JSON_OBJECT('gestion_id', NEW.gestion_id, 'gestion_previa_id', NEW.gestion_previa_id, 'dias_espera', NEW.dias_espera));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_gestion_dependencias_update AFTER UPDATE ON gestion_dependencias FOR EACH ROW
BEGIN
IF NOT ((OLD.gestion_id <=> NEW.gestion_id) AND (OLD.gestion_previa_id <=> NEW.gestion_previa_id) AND (OLD.dias_espera <=> NEW.dias_espera)) THEN
INSERT INTO auditorias (usuario_id,tabla,registro_id,accion,valores_anteriores,valores_nuevos)
VALUES (NULLIF(@usuario_id,0),'gestion_dependencias',CONCAT_WS(':',NEW.gestion_id,NEW.gestion_previa_id),'UPDATE',JSON_OBJECT('gestion_id', OLD.gestion_id, 'gestion_previa_id', OLD.gestion_previa_id, 'dias_espera', OLD.dias_espera),JSON_OBJECT('gestion_id', NEW.gestion_id, 'gestion_previa_id', NEW.gestion_previa_id, 'dias_espera', NEW.dias_espera));
END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_gestion_dependencias_delete AFTER DELETE ON gestion_dependencias FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id,tabla,registro_id,accion,valores_anteriores,valores_nuevos)
VALUES (NULLIF(@usuario_id,0),'gestion_dependencias',CONCAT_WS(':',OLD.gestion_id,OLD.gestion_previa_id),'DELETE',JSON_OBJECT('gestion_id', OLD.gestion_id, 'gestion_previa_id', OLD.gestion_previa_id, 'dias_espera', OLD.dias_espera),NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestion_establecimientos` (
  `gestion_id` int(10) unsigned NOT NULL,
  `establecimiento_id` int(10) unsigned NOT NULL,
  `duracion_atencion_minutos` smallint(5) unsigned DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`gestion_id`,`establecimiento_id`),
  KEY `fk_gestion_establecimientos_establecimiento` (`establecimiento_id`),
  CONSTRAINT `fk_gestion_establecimientos_establecimiento` FOREIGN KEY (`establecimiento_id`) REFERENCES `establecimientos` (`id`),
  CONSTRAINT `fk_gestion_establecimientos_gestion` FOREIGN KEY (`gestion_id`) REFERENCES `gestiones` (`id`),
  CONSTRAINT `ck_gestion_establecimientos_duracion` CHECK (`duracion_atencion_minutos` is null or `duracion_atencion_minutos` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_gestion_establecimientos_insert AFTER INSERT ON gestion_establecimientos FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'gestion_establecimientos', CONCAT_WS(':' , NEW.gestion_id, NEW.establecimiento_id), 'INSERT', NULL, JSON_OBJECT('gestion_id', NEW.gestion_id, 'establecimiento_id', NEW.establecimiento_id, 'duracion_atencion_minutos', NEW.duracion_atencion_minutos, 'estado', NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_gestion_establecimientos_update AFTER UPDATE ON gestion_establecimientos FOR EACH ROW
BEGIN
IF NOT ((OLD.gestion_id <=> NEW.gestion_id) AND (OLD.establecimiento_id <=> NEW.establecimiento_id) AND (OLD.duracion_atencion_minutos <=> NEW.duracion_atencion_minutos) AND (OLD.estado <=> NEW.estado)) THEN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'gestion_establecimientos', CONCAT_WS(':' , NEW.gestion_id, NEW.establecimiento_id), 'UPDATE', JSON_OBJECT('gestion_id', OLD.gestion_id, 'establecimiento_id', OLD.establecimiento_id, 'duracion_atencion_minutos', OLD.duracion_atencion_minutos, 'estado', OLD.estado), JSON_OBJECT('gestion_id', NEW.gestion_id, 'establecimiento_id', NEW.establecimiento_id, 'duracion_atencion_minutos', NEW.duracion_atencion_minutos, 'estado', NEW.estado));
END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_gestion_establecimientos_delete AFTER DELETE ON gestion_establecimientos FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'gestion_establecimientos', CONCAT_WS(':' , OLD.gestion_id, OLD.establecimiento_id), 'DELETE', JSON_OBJECT('gestion_id', OLD.gestion_id, 'establecimiento_id', OLD.establecimiento_id, 'duracion_atencion_minutos', OLD.duracion_atencion_minutos, 'estado', OLD.estado), NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestiones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tramite_id` int(10) unsigned NOT NULL,
  `documento_resultado_id` int(10) unsigned DEFAULT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text NOT NULL,
  `orden` smallint(5) unsigned NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gestiones_orden` (`tramite_id`,`orden`),
  KEY `fk_gestiones_documento` (`documento_resultado_id`),
  CONSTRAINT `fk_gestiones_documento` FOREIGN KEY (`documento_resultado_id`) REFERENCES `documentos` (`id`),
  CONSTRAINT `fk_gestiones_tramite` FOREIGN KEY (`tramite_id`) REFERENCES `tramites` (`id`),
  CONSTRAINT `ck_gestiones_orden` CHECK (`orden` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_gestiones_insert AFTER INSERT ON gestiones FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'gestiones', CONCAT_WS(':' , NEW.id), 'INSERT', NULL, JSON_OBJECT('id', NEW.id, 'tramite_id', NEW.tramite_id, 'documento_resultado_id', NEW.documento_resultado_id, 'nombre', NEW.nombre, 'descripcion', NEW.descripcion, 'orden', NEW.orden, 'estado', NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_gestiones_update AFTER UPDATE ON gestiones FOR EACH ROW
BEGIN
IF NOT ((OLD.id <=> NEW.id) AND (OLD.tramite_id <=> NEW.tramite_id) AND (OLD.documento_resultado_id <=> NEW.documento_resultado_id) AND (OLD.nombre <=> NEW.nombre) AND (OLD.descripcion <=> NEW.descripcion) AND (OLD.orden <=> NEW.orden) AND (OLD.estado <=> NEW.estado)) THEN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'gestiones', CONCAT_WS(':' , NEW.id), 'UPDATE', JSON_OBJECT('id', OLD.id, 'tramite_id', OLD.tramite_id, 'documento_resultado_id', OLD.documento_resultado_id, 'nombre', OLD.nombre, 'descripcion', OLD.descripcion, 'orden', OLD.orden, 'estado', OLD.estado), JSON_OBJECT('id', NEW.id, 'tramite_id', NEW.tramite_id, 'documento_resultado_id', NEW.documento_resultado_id, 'nombre', NEW.nombre, 'descripcion', NEW.descripcion, 'orden', NEW.orden, 'estado', NEW.estado));
END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_gestiones_delete AFTER DELETE ON gestiones FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'gestiones', CONCAT_WS(':' , OLD.id), 'DELETE', JSON_OBJECT('id', OLD.id, 'tramite_id', OLD.tramite_id, 'documento_resultado_id', OLD.documento_resultado_id, 'nombre', OLD.nombre, 'descripcion', OLD.descripcion, 'orden', OLD.orden, 'estado', OLD.estado), NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `horarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `establecimiento_id` int(10) unsigned NOT NULL,
  `dia_semana` tinyint(3) unsigned NOT NULL,
  `hora_apertura` time NOT NULL,
  `hora_cierre` time NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_horarios_apertura` (`establecimiento_id`,`dia_semana`,`hora_apertura`),
  CONSTRAINT `fk_horarios_establecimiento` FOREIGN KEY (`establecimiento_id`) REFERENCES `establecimientos` (`id`),
  CONSTRAINT `ck_horarios_dia` CHECK (`dia_semana` between 1 and 7),
  CONSTRAINT `ck_horarios_intervalo` CHECK (`hora_apertura` >= '00:00:00' and `hora_cierre` < '24:00:00' and `hora_cierre` > `hora_apertura`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_horarios_insert AFTER INSERT ON horarios FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id,tabla,registro_id,accion,valores_anteriores,valores_nuevos)
VALUES (NULLIF(@usuario_id,0),'horarios',NEW.id,'INSERT',NULL,JSON_OBJECT('id', NEW.id, 'establecimiento_id', NEW.establecimiento_id, 'dia_semana', NEW.dia_semana, 'hora_apertura', NEW.hora_apertura, 'hora_cierre', NEW.hora_cierre, 'estado', NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_horarios_update AFTER UPDATE ON horarios FOR EACH ROW
BEGIN
IF NOT ((OLD.id <=> NEW.id) AND (OLD.establecimiento_id <=> NEW.establecimiento_id) AND (OLD.dia_semana <=> NEW.dia_semana) AND (OLD.hora_apertura <=> NEW.hora_apertura) AND (OLD.hora_cierre <=> NEW.hora_cierre) AND (OLD.estado <=> NEW.estado)) THEN
INSERT INTO auditorias (usuario_id,tabla,registro_id,accion,valores_anteriores,valores_nuevos)
VALUES (NULLIF(@usuario_id,0),'horarios',NEW.id,'UPDATE',JSON_OBJECT('id', OLD.id, 'establecimiento_id', OLD.establecimiento_id, 'dia_semana', OLD.dia_semana, 'hora_apertura', OLD.hora_apertura, 'hora_cierre', OLD.hora_cierre, 'estado', OLD.estado),JSON_OBJECT('id', NEW.id, 'establecimiento_id', NEW.establecimiento_id, 'dia_semana', NEW.dia_semana, 'hora_apertura', NEW.hora_apertura, 'hora_cierre', NEW.hora_cierre, 'estado', NEW.estado));
END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_horarios_delete AFTER DELETE ON horarios FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id,tabla,registro_id,accion,valores_anteriores,valores_nuevos)
VALUES (NULLIF(@usuario_id,0),'horarios',OLD.id,'DELETE',JSON_OBJECT('id', OLD.id, 'establecimiento_id', OLD.establecimiento_id, 'dia_semana', OLD.dia_semana, 'hora_apertura', OLD.hora_apertura, 'hora_cierre', OLD.hora_cierre, 'estado', OLD.estado),NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tramite_documentos` (
  `tramite_id` int(10) unsigned NOT NULL,
  `documento_id` int(10) unsigned NOT NULL,
  `obligatorio` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `vigencia_dias` smallint(5) unsigned DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`tramite_id`,`documento_id`),
  KEY `fk_tramite_documentos_documento` (`documento_id`),
  CONSTRAINT `fk_tramite_documentos_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`),
  CONSTRAINT `fk_tramite_documentos_tramite` FOREIGN KEY (`tramite_id`) REFERENCES `tramites` (`id`),
  CONSTRAINT `ck_tramite_documentos_obligatorio` CHECK (`obligatorio` in (0,1)),
  CONSTRAINT `ck_tramite_documentos_vigencia` CHECK (`vigencia_dias` is null or `vigencia_dias` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_tramite_documentos_insert AFTER INSERT ON tramite_documentos FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'tramite_documentos', CONCAT_WS(':' , NEW.tramite_id, NEW.documento_id), 'INSERT', NULL, JSON_OBJECT('tramite_id', NEW.tramite_id, 'documento_id', NEW.documento_id, 'obligatorio', NEW.obligatorio, 'vigencia_dias', NEW.vigencia_dias, 'observaciones', NEW.observaciones));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_tramite_documentos_update AFTER UPDATE ON tramite_documentos FOR EACH ROW
BEGIN
IF NOT ((OLD.tramite_id <=> NEW.tramite_id) AND (OLD.documento_id <=> NEW.documento_id) AND (OLD.obligatorio <=> NEW.obligatorio) AND (OLD.vigencia_dias <=> NEW.vigencia_dias) AND (OLD.observaciones <=> NEW.observaciones)) THEN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'tramite_documentos', CONCAT_WS(':' , NEW.tramite_id, NEW.documento_id), 'UPDATE', JSON_OBJECT('tramite_id', OLD.tramite_id, 'documento_id', OLD.documento_id, 'obligatorio', OLD.obligatorio, 'vigencia_dias', OLD.vigencia_dias, 'observaciones', OLD.observaciones), JSON_OBJECT('tramite_id', NEW.tramite_id, 'documento_id', NEW.documento_id, 'obligatorio', NEW.obligatorio, 'vigencia_dias', NEW.vigencia_dias, 'observaciones', NEW.observaciones));
END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_tramite_documentos_delete AFTER DELETE ON tramite_documentos FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'tramite_documentos', CONCAT_WS(':' , OLD.tramite_id, OLD.documento_id), 'DELETE', JSON_OBJECT('tramite_id', OLD.tramite_id, 'documento_id', OLD.documento_id, 'obligatorio', OLD.obligatorio, 'vigencia_dias', OLD.vigencia_dias, 'observaciones', OLD.observaciones), NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tramites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text NOT NULL,
  `fuente_url` varchar(2048) DEFAULT NULL,
  `fecha_verificacion` date DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_tramites_insert AFTER INSERT ON tramites FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'tramites', CONCAT_WS(':' , NEW.id), 'INSERT', NULL, JSON_OBJECT('id', NEW.id, 'nombre', NEW.nombre, 'descripcion', NEW.descripcion, 'fuente_url', NEW.fuente_url, 'fecha_verificacion', NEW.fecha_verificacion, 'estado', NEW.estado, 'fecha_registro', NEW.fecha_registro));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_tramites_update AFTER UPDATE ON tramites FOR EACH ROW
BEGIN
IF NOT ((OLD.id <=> NEW.id) AND (OLD.nombre <=> NEW.nombre) AND (OLD.descripcion <=> NEW.descripcion) AND (OLD.fuente_url <=> NEW.fuente_url) AND (OLD.fecha_verificacion <=> NEW.fecha_verificacion) AND (OLD.estado <=> NEW.estado) AND (OLD.fecha_registro <=> NEW.fecha_registro)) THEN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'tramites', CONCAT_WS(':' , NEW.id), 'UPDATE', JSON_OBJECT('id', OLD.id, 'nombre', OLD.nombre, 'descripcion', OLD.descripcion, 'fuente_url', OLD.fuente_url, 'fecha_verificacion', OLD.fecha_verificacion, 'estado', OLD.estado, 'fecha_registro', OLD.fecha_registro), JSON_OBJECT('id', NEW.id, 'nombre', NEW.nombre, 'descripcion', NEW.descripcion, 'fuente_url', NEW.fuente_url, 'fecha_verificacion', NEW.fecha_verificacion, 'estado', NEW.estado, 'fecha_registro', NEW.fecha_registro));
END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_tramites_delete AFTER DELETE ON tramites FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'tramites', CONCAT_WS(':' , OLD.id), 'DELETE', JSON_OBJECT('id', OLD.id, 'nombre', OLD.nombre, 'descripcion', OLD.descripcion, 'fuente_url', OLD.fuente_url, 'fecha_verificacion', OLD.fecha_verificacion, 'estado', OLD.estado, 'fecha_registro', OLD.fecha_registro), NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `correo` varchar(254) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('administrador','usuario') NOT NULL DEFAULT 'usuario',
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_usuarios_insert AFTER INSERT ON usuarios FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'usuarios', NEW.id, 'INSERT', NULL, JSON_OBJECT('id', NEW.id, 'nombre', NEW.nombre, 'apellido_paterno', NEW.apellido_paterno, 'apellido_materno', NEW.apellido_materno, 'correo', NEW.correo, 'rol', NEW.rol, 'estado', NEW.estado, 'fecha_registro', NEW.fecha_registro));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_usuarios_update AFTER UPDATE ON usuarios FOR EACH ROW
BEGIN
IF NOT ((OLD.id <=> NEW.id) AND (OLD.nombre <=> NEW.nombre) AND (OLD.apellido_paterno <=> NEW.apellido_paterno) AND (OLD.apellido_materno <=> NEW.apellido_materno) AND (OLD.correo <=> NEW.correo) AND (OLD.rol <=> NEW.rol) AND (OLD.estado <=> NEW.estado) AND (OLD.fecha_registro <=> NEW.fecha_registro) AND (OLD.contrasena <=> NEW.contrasena)) THEN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'usuarios', NEW.id, 'UPDATE', JSON_OBJECT('id', OLD.id, 'nombre', OLD.nombre, 'apellido_paterno', OLD.apellido_paterno, 'apellido_materno', OLD.apellido_materno, 'correo', OLD.correo, 'rol', OLD.rol, 'estado', OLD.estado, 'fecha_registro', OLD.fecha_registro), JSON_OBJECT('id', NEW.id, 'nombre', NEW.nombre, 'apellido_paterno', NEW.apellido_paterno, 'apellido_materno', NEW.apellido_materno, 'correo', NEW.correo, 'rol', NEW.rol, 'estado', NEW.estado, 'fecha_registro', NEW.fecha_registro));
END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER auditar_usuarios_delete AFTER DELETE ON usuarios FOR EACH ROW
BEGIN
INSERT INTO auditorias (usuario_id, tabla, registro_id, accion, valores_anteriores, valores_nuevos)
VALUES (NULLIF(@usuario_id, 0), 'usuarios', OLD.id, 'DELETE', JSON_OBJECT('id', OLD.id, 'nombre', OLD.nombre, 'apellido_paterno', OLD.apellido_paterno, 'apellido_materno', OLD.apellido_materno, 'correo', OLD.correo, 'rol', OLD.rol, 'estado', OLD.estado, 'fecha_registro', OLD.fecha_registro), NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;



/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

INSERT INTO `tramites` (`id`, `nombre`, `descripcion`, `fuente_url`, `fecha_verificacion`, `estado`, `fecha_registro`) VALUES (8,'Primera licencia de conducir particular P - mayores de edad','Primera licencia particular P para mayores de edad. Marca solo los documentos vigentes que ya tienes: DIRI incorpora las evaluaciones, los pagos y la obtención de antecedentes faltantes antes de la presentación final en SEGIP. Si falta la cédula, el catálogo incluye renovación o reposición de un registro existente. El registro de identidad por primera vez requiere una preparación adicional. Los lugares sin horario publicado se muestran con atención por confirmar.','https://www.segip.gob.bo/licencia-para-conducir/','2026-11-17','activo','2026-11-18 01:21:36'),(9,'Renovación de cédula de identidad','Para renovar una cédula boliviana ya registrada en SEGIP, sin cambios de filiación ni estado civil. Incluye el pago pendiente y la atención final. Si necesitas actualizar datos, consulta primero los documentos adicionales en SEGIP.','https://www.segip.gob.bo/cedula-de-identidad/','2026-11-17','activo','2026-11-18 02:14:42'),(10,'Reposición de cédula de identidad','Para reponer por pérdida o deterioro una cédula boliviana ya registrada en SEGIP. Incluye el pago pendiente y la atención final; no corresponde a la primera inscripción de identidad.','https://www.segip.gob.bo/cedula-de-identidad/','2026-11-17','activo','2026-11-18 02:14:42'),(11,'Renovación de licencia de conducir particular P','Para renovar una licencia P registrada, con vencimiento de hasta cinco años. Incluye evaluación médica, antecedente de Tránsito y pago cuando faltan. Si no renovaste en cinco años y un día, SEGIP exige además habilidades de conducción: este caso debe revisarse antes de usar el recorrido simplificado.','https://www.segip.gob.bo/licencia-para-conducir/','2026-11-17','activo','2026-11-18 02:14:42'),(12,'Duplicado de licencia de conducir particular P','Duplicado por robo, extravío o destrucción de licencia particular P de una persona adulta. Incluye cédula vigente, formulario gratuito de denuncia y el pago específico del duplicado.','https://www.segip.gob.bo/licencia-para-conducir/','2026-11-17','activo','2026-11-18 02:14:42'),(13,'Certificado único de antecedentes policiales 3 en 1','Certificación que reúne FELCC, FELCN y Tránsito para licencia nueva o uso administrativo o laboral. Solicitud personal de una persona adulta boliviana.','https://antecedentes.policia.bo/infcert.php','2026-11-17','activo','2026-11-18 02:14:42'),(14,'Certificado de antecedentes FELCC','Certificado individual FELCC para trámite administrativo o laboral. Solicitud personal de una persona adulta boliviana.','https://antecedentes.policia.bo/infcert.php','2026-11-17','activo','2026-11-18 02:14:42'),(15,'Certificado de antecedentes penales REJAP','Certificado de antecedentes penales para solicitud personal y uso nacional, emitido por el Órgano Judicial. Se verifica identidad y se realiza el pago en plataforma. No cubre solicitudes de terceros ni casos con orden judicial.','https://lapaz.organojudicial.gob.bo/Documentos/tramite%20d20230113111413.pdf','2026-11-17','activo','2026-11-18 02:14:42'),(16,'Certificado de no violencia CENVI','Certificado de no violencia para solicitud personal de una persona adulta, emitido por el Órgano Judicial. Incluye verificación de identidad y pago en plataforma. No cubre solicitudes de terceros ni casos judiciales especiales.','https://lapaz.organojudicial.gob.bo/Documentos/tramite%20d20230113111413.pdf','2026-11-17','activo','2026-11-18 02:14:42'),(17,'Certificación de estado civil','Solicitud personal del titular adulto con registro en el padrón electoral biométrico. Incluye verificación registral, pago del arancel y entrega en SERECI. La inscripción biométrica inicial, poderes y rectificación de partidas requieren otra preparación y no están incluidos en este caso.','https://www.oep.org.bo/wp-content/uploads/2024/01/Cert_Estado_Civil.pdf','2026-11-17','activo','2026-11-18 02:14:42');

INSERT INTO `documentos` (`id`, `nombre`, `descripcion`, `estado`) VALUES (9,'Cédula de identidad vigente','Puede ser física o digital. Debe estar vigente antes del recorrido.','activo'),(10,'Certificado de evaluación médica','Emitido por un centro acreditado. SEGIP indica acudir a su oficina desde el día siguiente a la evaluación. Si ya lo tienes, indica su fecha de emisión.','activo'),(11,'Certificado de habilidades de conducción','Certificado original emitido por una autoescuela autorizada. Confirma la cita y la fecha de emisión con el centro.','activo'),(12,'Certificado de antecedentes de Tránsito','Prepara este certificado antes del recorrido. SEGIP permite gestionarlo ante la Policía o mediante CUDAP: https://estadodigital.mingobierno.gob.bo/cudap/home','activo'),(13,'Certificado de antecedentes FELCC','Prepara este certificado antes del recorrido. Consulta el canal CUDAP indicado por SEGIP: https://estadodigital.mingobierno.gob.bo/cudap/home','activo'),(14,'Certificado de antecedentes FELCN','Prepara este certificado antes del recorrido. Consulta el canal CUDAP indicado por SEGIP: https://estadodigital.mingobierno.gob.bo/cudap/home','activo'),(15,'Comprobante de pago de licencia particular P','SEGIP publica Bs 225 para la categoría P. Prepara el comprobante mediante Banco Unión o su canal digital antes del recorrido: https://bancounion.com.bo/BancaDigital/PagosQRSegip','activo'),(16,'Comprobante de pago de antecedentes policiales','Pago correspondiente a los antecedentes solicitados. Si ya tienes todos los antecedentes requeridos, este comprobante auxiliar no se necesita para planificar su obtención.','activo'),(17,'Comprobante de pago de renovación de cédula','Pago auxiliar para renovación o reposición de una cédula ya registrada en SEGIP. No se solicita si la cédula ya está vigente y disponible.','activo'),(18,'Formulario de denuncia por pérdida de licencia','Formulario gratuito para duplicado por robo, extravío o destrucción, otorgado en oficinas SEGIP.','activo'),(19,'Comprobante de pago de duplicado de licencia P','Pago específico para duplicado de licencia particular P; su concepto es diferente al pago de primera emisión o renovación.','activo'),(20,'Comprobante de pago de antecedentes FELCC','Pago por el certificado individual FELCC para uso administrativo o laboral.','activo');

INSERT INTO `establecimientos` (`id`, `nombre`, `direccion`, `zona`, `latitud`, `longitud`, `telefono`, `fuente_url`, `fecha_verificacion`, `estado`) VALUES (7,'Dar Salud 20 de Octubre','Av. 20 de Octubre 1628, edificio Mollinedo, planta baja, San Pedro','San Pedro',-16.5040795,-68.1340667,NULL,'https://darsalud.com.bo/sucursales/','2026-11-17','activo'),(8,'Dar Salud Calacoto','Av. Montenegro 1564, entre calles 22 y 23 de Calacoto','Calacoto',-16.5401828,-68.0765830,NULL,'https://darsalud.com.bo/sucursales/','2026-11-17','activo'),(9,'Automóvil Club Boliviano - Escuela Calacoto','Calle 12 de Calacoto, esquina General Inofuentes 7823','Calacoto',-16.5394375,-68.0869375,'75274172','https://www.acbbolivia.com.bo/en/acbescuela','2026-11-17','activo'),(10,'SEGIP - Segelic La Paz San Pedro','AV. 20 DE OCTUBRE, EDIFICIO MOLLINEDO N°1628 ZONA SAN PEDRO','San Pedro',-16.5041051,-68.1341678,'800101102','https://www.segip.gob.bo/oficina/','2026-11-17','activo'),(11,'SEGIP - Zona Sur Megacenter','ZONA IRPAVI AV. RAFAEL PABÓN 656 MEGACENTER PLANTA BAJA','Irpavi',-16.5320166,-68.0871546,'800101102','https://www.segip.gob.bo/oficina/','2026-11-17','activo'),(12,'Servicios Técnicos Auxiliares - Tránsito Centro','Av. Mariscal Santa Cruz esquina calle Cochabamba, interior de Tránsito','Centro',-16.4976489,-68.1360790,NULL,'https://antecedentes.policia.bo/infcert.php','2026-11-17','activo'),(13,'Servicios Técnicos Auxiliares - Recaudaciones Murillo','Calle Murillo, entre Almirante Grau y Colombia','Centro',-16.5008825,-68.1349270,NULL,'https://antecedentes.policia.bo/infcert.php','2026-11-17','activo'),(14,'Servicios Técnicos Auxiliares - Comando Policial Zona Sur','Av. Final Roma, interior del Comando Policial Zona Sur','Calacoto',-16.5423095,-68.0933250,NULL,'https://antecedentes.policia.bo/infcert.php','2026-11-17','activo'),(15,'Banco Unión - Agencia Montes','Av. Montes 725. Dirección publicada en la memoria oficial 2024; confirma la atención vigente con Banco Unión.','Centro',-16.4932123,-68.1370668,'800102872','https://www.openstreetmap.org/node/4361888373','2026-11-17','activo'),(16,'Banco Unión - Agencia San Miguel','Calle José Aguirre Achá 8484, calle 21, esquina José María Zalles. Dirección publicada en la memoria oficial 2024; confirma la atención vigente.','San Miguel',-16.5450147,-68.0770409,'800102872','https://www.openstreetmap.org/way/446159330','2026-11-17','activo'),(17,'SEGIP - La Paz Centro','Instalaciones del Teleférico Celeste, pasaje Núñez del Prado','Centro',-16.5004318,-68.1327011,'800101102','https://www.segip.gob.bo/oficina/','2026-11-17','activo'),(18,'SERECI - Oficina Departamental La Paz','Av. 16 de Julio 23, frente a plaza Venezuela, lado Toyosa. Directorio oficial 2025; confirma la atención vigente.','Centro',-16.5004722,-68.1335000,'72040130','https://web.oep.org.bo/wp-content/uploads/2025/03/07-03-2025-LA-PAZ.pdf','2026-11-17','activo'),(19,'SERECI - Los Pinos','Calle 25 Arturo Fortún Sanjinés 129, frente al hospital Los Pinos. Directorio oficial 2025; confirma la atención vigente.','Los Pinos',-16.5423611,-68.0712222,'79101689','https://web.oep.org.bo/wp-content/uploads/2025/03/07-03-2025-LA-PAZ.pdf','2026-11-17','activo'),(20,'TDJ La Paz - Plataforma Anexo B','Calle Potosí esquina Yanacocha, edificio Anexo B del Tribunal Departamental de Justicia. Solicita la ventanilla REJAP/CENVI.','Centro',-16.4958985,-68.1353978,'22648500','https://lapaz.organojudicial.gob.bo/Documentos/tramite%20d20230113111413.pdf','2026-11-17','activo');

INSERT INTO `gestiones` (`id`, `tramite_id`, `documento_resultado_id`, `nombre`, `descripcion`, `orden`, `estado`) VALUES (10,8,10,'Obtener el certificado de evaluación médica','Acude a un centro acreditado para la evaluación. El registro del certificado en SEGIP se efectúa al cierre del día; la presentación final se programa desde el día siguiente.',1,'activo'),(11,8,11,'Coordinar y aprobar la evaluación de conducción','Contacta la autoescuela autorizada para confirmar capacitación, cita de evaluación y entrega del certificado de habilidades. La fecha efectiva depende de la programación del centro.',2,'activo'),(12,8,NULL,'Presentar la solicitud de licencia en SEGIP','Presenta tu cédula vigente, certificados y comprobante de pago. La emisión de la licencia depende de la revisión y aceptación del SEGIP.',3,'activo'),(13,8,17,'Pagar la renovación o reposición de cédula','Solo si necesitas renovar o reponer tu cédula ya registrada. Consulta el concepto y costo vigente en SEGIP y conserva el comprobante a tu nombre. También puedes pagar mediante los canales digitales autorizados de Banco Unión. Si nunca tuviste cédula, el registro inicial requiere certificado de nacimiento y una preparación distinta; consulta SEGIP antes de seguir este recorrido.',4,'activo'),(14,8,9,'Renovar o reponer la cédula de identidad','Presenta el comprobante de pago y solicita renovación o reposición de una cédula ya registrada en SEGIP. Este paso debe completarse antes de las evaluaciones y antecedentes que requieren identificación vigente. No cubre el registro de identidad por primera vez. Fuente: https://www.segip.gob.bo/cedula-de-identidad/',5,'activo'),(15,8,16,'Pagar los antecedentes policiales que faltan','Indica que necesitas antecedentes para primera licencia y qué certificados ya tienes. La fuente oficial contempla un certificado único 3 en 1 (FELCC, FELCN y Tránsito). Confirma el concepto, importe y cuenta vigentes antes de pagar; conserva el comprobante. El pago del 3 en 1 cubre el conjunto: no hagas tres pagos por el mismo certificado. Si utilizas CUDAP, puedes completar el pago en su plataforma oficial y marcar los antecedentes disponibles al recibirlos. Fuente: https://antecedentes.policia.bo/infcert.php',6,'activo'),(16,8,12,'Obtener el antecedente Tránsito','Presenta tu cédula vigente y el comprobante correspondiente en Servicios Técnicos Auxiliares. Para licencia nueva, solicita el certificado único 3 en 1 que reúne FELCC, FELCN y Tránsito; una misma emisión puede cubrir los tres requisitos. Las actividades realizadas en la misma oficina se muestran como una sola parada. Alternativa digital: https://estadodigital.mingobierno.gob.bo/cudap/ . Fuente: https://antecedentes.policia.bo/infcert.php',7,'activo'),(17,8,13,'Obtener el antecedente FELCC','Presenta tu cédula vigente y el comprobante correspondiente en Servicios Técnicos Auxiliares. Para licencia nueva, solicita el certificado único 3 en 1 que reúne FELCC, FELCN y Tránsito; una misma emisión puede cubrir los tres requisitos. Las actividades realizadas en la misma oficina se muestran como una sola parada. Alternativa digital: https://estadodigital.mingobierno.gob.bo/cudap/ . Fuente: https://antecedentes.policia.bo/infcert.php',8,'activo'),(18,8,14,'Obtener el antecedente FELCN','Presenta tu cédula vigente y el comprobante correspondiente en Servicios Técnicos Auxiliares. Para licencia nueva, solicita el certificado único 3 en 1 que reúne FELCC, FELCN y Tránsito; una misma emisión puede cubrir los tres requisitos. Las actividades realizadas en la misma oficina se muestran como una sola parada. Alternativa digital: https://estadodigital.mingobierno.gob.bo/cudap/ . Fuente: https://antecedentes.policia.bo/infcert.php',9,'activo'),(19,8,15,'Pagar la emisión de la licencia particular P','Solicita el pago para primera licencia particular P, confirma el importe vigente en SEGIP y conserva el comprobante a tu nombre para la presentación final. El portal de SEGIP informa Bs 225 al verificar el catálogo. Alternativa autorizada sin visita al banco: https://www.bancounion.com.bo/BancaDigital/PagosQRSegip . Completa el pago allí y después marca el comprobante como disponible.',10,'activo'),(20,9,17,'Pagar el servicio de cédula','Confirma en SEGIP el concepto de renovación o reposición y el costo vigente. Conserva el comprobante a tu nombre. Puedes completar el pago digital autorizado antes de salir.',1,'activo'),(21,9,NULL,'Renovación de cédula de identidad en SEGIP','Acude personalmente con tu comprobante. SEGIP publica que para renovar no es necesario presentar otro documento. Los cambios de estado civil o datos personales requieren documentación adicional. Fuente: https://www.segip.gob.bo/cedula-de-identidad/',2,'activo'),(22,10,17,'Pagar el servicio de cédula','Confirma en SEGIP el concepto de renovación o reposición y el costo vigente. Conserva el comprobante a tu nombre. Puedes completar el pago digital autorizado antes de salir.',1,'activo'),(23,10,NULL,'Reposición de cédula de identidad en SEGIP','Acude personalmente con tu comprobante. SEGIP publica que para renovar no es necesario presentar otro documento. Los cambios de estado civil o datos personales requieren documentación adicional. Fuente: https://www.segip.gob.bo/cedula-de-identidad/',2,'activo'),(24,11,17,'Pagar la renovación o reposición de cédula','Solo si necesitas renovar o reponer tu cédula ya registrada. Consulta el concepto y costo vigente en SEGIP y conserva el comprobante a tu nombre. También puedes pagar mediante los canales digitales autorizados de Banco Unión. Si nunca tuviste cédula, el registro inicial requiere certificado de nacimiento y una preparación distinta; consulta SEGIP antes de seguir este recorrido.',1,'activo'),(25,11,9,'Renovar o reponer la cédula de identidad','Presenta el comprobante de pago y solicita renovación o reposición de una cédula ya registrada en SEGIP. Este paso debe completarse antes de las evaluaciones y antecedentes que requieren identificación vigente. No cubre el registro de identidad por primera vez. Fuente: https://www.segip.gob.bo/cedula-de-identidad/',2,'activo'),(26,11,10,'Obtener el certificado de evaluación médica','Acude a un centro acreditado para la evaluación. El registro del certificado en SEGIP se efectúa al cierre del día; la presentación final se programa desde el día siguiente.',3,'activo'),(27,11,16,'Pagar el antecedente de Tránsito','Solicita el pago para antecedente de Tránsito por renovación de licencia. Confirma el concepto y monto vigentes en la Policía; no pagues el 3 en 1 si solo necesitas Tránsito. Si ya tienes el antecedente vigente, esta visita auxiliar se omite.',4,'activo'),(28,11,12,'Obtener el antecedente de Tránsito para renovación','Presenta tu cédula vigente y el comprobante. La Policía también publica la presentación de la licencia original o del reporte de SEGELIC cuando no se dispone de ella; confirma este caso antes de salir. Fuente: https://antecedentes.policia.bo/infcert.php',5,'activo'),(29,11,15,'Pagar la renovación de licencia P','Solicita el concepto de renovación de licencia P en una entidad autorizada o por el canal QR de Banco Unión. Confirma importe y conserva el comprobante.',6,'activo'),(30,11,NULL,'Presentar la renovación en SEGIP','Presenta los requisitos de renovación. El certificado médico se registra al final del día; la atención final se programa desde el día siguiente. Confirma cualquier evaluación adicional según tu caso.',7,'activo'),(31,12,17,'Pagar la renovación o reposición de cédula','Solo si necesitas renovar o reponer tu cédula ya registrada. Consulta el concepto y costo vigente en SEGIP y conserva el comprobante a tu nombre. También puedes pagar mediante los canales digitales autorizados de Banco Unión. Si nunca tuviste cédula, el registro inicial requiere certificado de nacimiento y una preparación distinta; consulta SEGIP antes de seguir este recorrido.',1,'activo'),(32,12,9,'Renovar o reponer la cédula de identidad','Presenta el comprobante de pago y solicita renovación o reposición de una cédula ya registrada en SEGIP. Este paso debe completarse antes de las evaluaciones y antecedentes que requieren identificación vigente. No cubre el registro de identidad por primera vez. Fuente: https://www.segip.gob.bo/cedula-de-identidad/',2,'activo'),(33,12,18,'Obtener el formulario de denuncia de licencia','Solicita y completa el formulario gratuito por robo, extravío o destrucción en SEGIP; no se registra una visita a la Policía para este formulario.',3,'activo'),(34,12,19,'Pagar el duplicado de licencia P','Solicita el concepto de duplicado de licencia P. El portal SEGIP publica Bs 160 para esta categoría al consultar la fuente; confirma el costo vigente antes de pagar. Conserva el comprobante.',4,'activo'),(35,12,NULL,'Solicitar el duplicado en SEGIP','Presenta tu cédula vigente, formulario gratuito de denuncia y comprobante por duplicado. La fuente no incluye certificado médico ni autoescuela para este caso.',5,'activo'),(36,13,17,'Pagar la renovación o reposición de cédula','Solo si necesitas renovar o reponer tu cédula ya registrada. Consulta el concepto y costo vigente en SEGIP y conserva el comprobante a tu nombre. También puedes pagar mediante los canales digitales autorizados de Banco Unión. Si nunca tuviste cédula, el registro inicial requiere certificado de nacimiento y una preparación distinta; consulta SEGIP antes de seguir este recorrido.',1,'activo'),(37,13,9,'Renovar o reponer la cédula de identidad','Presenta el comprobante de pago y solicita renovación o reposición de una cédula ya registrada en SEGIP. Este paso debe completarse antes de las evaluaciones y antecedentes que requieren identificación vigente. No cubre el registro de identidad por primera vez. Fuente: https://www.segip.gob.bo/cedula-de-identidad/',2,'activo'),(38,13,16,'Pagar la certificación policial','Solicita el certificado único 3 en 1; confirma concepto y monto vigentes antes de pagar. Conserva el comprobante. Puede obtenerse por CUDAP con pago en línea. Fuente: https://antecedentes.policia.bo/infcert.php',3,'activo'),(39,13,NULL,'Solicitar Certificado único de antecedentes policiales 3 en 1','Acude a Servicios Técnicos Auxiliares con cédula vigente y comprobante correspondiente. La solicitud es personal. Alternativa digital oficial: https://estadodigital.mingobierno.gob.bo/cudap/ . Confirma la recepción, entrega y horario de la oficina elegida.',4,'activo'),(40,14,17,'Pagar la renovación o reposición de cédula','Solo si necesitas renovar o reponer tu cédula ya registrada. Consulta el concepto y costo vigente en SEGIP y conserva el comprobante a tu nombre. También puedes pagar mediante los canales digitales autorizados de Banco Unión. Si nunca tuviste cédula, el registro inicial requiere certificado de nacimiento y una preparación distinta; consulta SEGIP antes de seguir este recorrido.',1,'activo'),(41,14,9,'Renovar o reponer la cédula de identidad','Presenta el comprobante de pago y solicita renovación o reposición de una cédula ya registrada en SEGIP. Este paso debe completarse antes de las evaluaciones y antecedentes que requieren identificación vigente. No cubre el registro de identidad por primera vez. Fuente: https://www.segip.gob.bo/cedula-de-identidad/',2,'activo'),(42,14,20,'Pagar la certificación policial','Solicita el pago específico del certificado individual FELCC. Confirma importe y cuenta en la fuente oficial de la Policía antes de pagar. Fuente: https://antecedentes.policia.bo/infcert.php',3,'activo'),(43,14,NULL,'Solicitar Certificado de antecedentes FELCC','Acude a Servicios Técnicos Auxiliares con cédula vigente y comprobante correspondiente. La solicitud es personal. Alternativa digital oficial: https://estadodigital.mingobierno.gob.bo/cudap/ . Confirma la recepción, entrega y horario de la oficina elegida.',4,'activo'),(44,15,17,'Pagar la renovación o reposición de cédula','Solo si necesitas renovar o reponer tu cédula ya registrada. Consulta el concepto y costo vigente en SEGIP y conserva el comprobante a tu nombre. También puedes pagar mediante los canales digitales autorizados de Banco Unión. Si nunca tuviste cédula, el registro inicial requiere certificado de nacimiento y una preparación distinta; consulta SEGIP antes de seguir este recorrido.',1,'activo'),(45,15,9,'Renovar o reponer la cédula de identidad','Presenta el comprobante de pago y solicita renovación o reposición de una cédula ya registrada en SEGIP. Este paso debe completarse antes de las evaluaciones y antecedentes que requieren identificación vigente. No cubre el registro de identidad por primera vez. Fuente: https://www.segip.gob.bo/cedula-de-identidad/',2,'activo'),(46,15,NULL,'Solicitar Certificado de antecedentes penales REJAP','La ficha del TDJ La Paz publicada en 2023 indica cédula original y dos fotocopias, y memorándum, convocatoria o carta de designación cuando corresponda. El instructivo nacional de 2024 regula la solicitud personal y la copia de identidad; confirma la documentación e importe vigentes en plataforma antes de acudir. Solicita la ventanilla correspondiente, adquiere el valorado o paga según te indiquen y recibe el certificado. Las duraciones de atención no están registradas; la entrega queda sujeta a la revisión de tu caso. Fuente: https://lapaz.organojudicial.gob.bo/Documentos/tramite%20d20230113111413.pdf',3,'activo'),(47,16,17,'Pagar la renovación o reposición de cédula','Solo si necesitas renovar o reponer tu cédula ya registrada. Consulta el concepto y costo vigente en SEGIP y conserva el comprobante a tu nombre. También puedes pagar mediante los canales digitales autorizados de Banco Unión. Si nunca tuviste cédula, el registro inicial requiere certificado de nacimiento y una preparación distinta; consulta SEGIP antes de seguir este recorrido.',1,'activo'),(48,16,9,'Renovar o reponer la cédula de identidad','Presenta el comprobante de pago y solicita renovación o reposición de una cédula ya registrada en SEGIP. Este paso debe completarse antes de las evaluaciones y antecedentes que requieren identificación vigente. No cubre el registro de identidad por primera vez. Fuente: https://www.segip.gob.bo/cedula-de-identidad/',2,'activo'),(49,16,NULL,'Solicitar Certificado de no violencia CENVI','La ficha del TDJ La Paz publicada en 2023 indica cédula original y dos fotocopias. Consulta la documentación e importe vigentes en plataforma; no uses tarifas de otra ciudad como referencia para el pago. Solicita la ventanilla correspondiente, adquiere el valorado o paga según te indiquen y recibe el certificado. Las duraciones de atención no están registradas; la entrega queda sujeta a la revisión de tu caso. Fuente: https://lapaz.organojudicial.gob.bo/Documentos/tramite%20d20230113111413.pdf',3,'activo'),(50,17,17,'Pagar la renovación o reposición de cédula','Solo si necesitas renovar o reponer tu cédula ya registrada. Consulta el concepto y costo vigente en SEGIP y conserva el comprobante a tu nombre. También puedes pagar mediante los canales digitales autorizados de Banco Unión. Si nunca tuviste cédula, el registro inicial requiere certificado de nacimiento y una preparación distinta; consulta SEGIP antes de seguir este recorrido.',1,'activo'),(51,17,9,'Renovar o reponer la cédula de identidad','Presenta el comprobante de pago y solicita renovación o reposición de una cédula ya registrada en SEGIP. Este paso debe completarse antes de las evaluaciones y antecedentes que requieren identificación vigente. No cubre el registro de identidad por primera vez. Fuente: https://www.segip.gob.bo/cedula-de-identidad/',2,'activo'),(52,17,NULL,'Solicitar la certificación de estado civil','Acude personalmente a SERECI, solicita verificación de tu identidad y del registro biométrico, consulta el arancel vigente y recibe la certificación. Esta ruta cubre a titulares ya registrados; si no estás registrado o hay errores de partida, consulta primero el procedimiento correspondiente. Fuente: https://www.oep.org.bo/wp-content/uploads/2024/01/Cert_Estado_Civil.pdf',3,'activo');

INSERT INTO `tramite_documentos` (`tramite_id`, `documento_id`, `obligatorio`, `vigencia_dias`, `observaciones`) VALUES (8,9,1,NULL,'Si no tienes cédula vigente, se incluye renovación o reposición de un registro existente. Si nunca obtuviste cédula, primero consulta SEGIP por los requisitos de registro inicial; este catálogo no cubre ese caso.'),(8,10,1,NULL,'Emitido por un centro acreditado. SEGIP indica acudir a su oficina desde el día siguiente a la evaluación. Si ya lo tienes, indica su fecha de emisión.'),(8,11,1,NULL,'Certificado original emitido por una autoescuela autorizada. Confirma la cita y la fecha de emisión con el centro.'),(8,12,1,NULL,'Si te falta este antecedente, DIRI incluye el pago y la visita a Servicios Técnicos Auxiliares. El certificado único 3 en 1 para licencia nueva reúne FELCC, FELCN y Tránsito. Si ya tienes ese certificado vigente, marca las tres casillas. Fuente: https://antecedentes.policia.bo/infcert.php'),(8,13,1,NULL,'Si te falta este antecedente, DIRI incluye el pago y la visita a Servicios Técnicos Auxiliares. El certificado único 3 en 1 para licencia nueva reúne FELCC, FELCN y Tránsito. Si ya tienes ese certificado vigente, marca las tres casillas. Fuente: https://antecedentes.policia.bo/infcert.php'),(8,14,1,NULL,'Si te falta este antecedente, DIRI incluye el pago y la visita a Servicios Técnicos Auxiliares. El certificado único 3 en 1 para licencia nueva reúne FELCC, FELCN y Tránsito. Si ya tienes ese certificado vigente, marca las tres casillas. Fuente: https://antecedentes.policia.bo/infcert.php'),(8,15,1,NULL,'Si no tienes comprobante, se incluye una agencia bancaria antes de la presentación final. También puedes pagar por el canal QR oficial de Banco Unión y marcarlo después como disponible.'),(9,17,1,NULL,'Marca el comprobante si ya pagaste el concepto correspondiente. Si falta, DIRI incluye el pago.'),(10,17,1,NULL,'Marca el comprobante si ya pagaste el concepto correspondiente. Si falta, DIRI incluye el pago.'),(11,9,1,NULL,'Marca tu cédula física o digital vigente si ya la tienes. Si falta, se incluye renovación o reposición de un registro existente. La primera inscripción de identidad requiere preparación adicional y no está cubierta por este caso.'),(11,10,1,NULL,'Si ya está disponible y vigente, marca la casilla para omitir su obtención.'),(11,12,1,NULL,'Si ya está disponible y vigente, marca la casilla para omitir su obtención.'),(11,15,1,NULL,'Si ya está disponible y vigente, marca la casilla para omitir su obtención.'),(12,9,1,NULL,'Marca tu cédula física o digital vigente si ya la tienes. Si falta, se incluye renovación o reposición de un registro existente. La primera inscripción de identidad requiere preparación adicional y no está cubierta por este caso.'),(12,18,1,NULL,'SEGIP entrega gratuitamente este formulario. Si ya lo tienes completado para esta solicitud, márcalo.'),(12,19,1,NULL,'Debe corresponder al concepto duplicado de licencia particular P.'),(13,9,1,NULL,'Marca tu cédula física o digital vigente si ya la tienes. Si falta, se incluye renovación o reposición de un registro existente. La primera inscripción de identidad requiere preparación adicional y no está cubierta por este caso.'),(13,16,1,NULL,'Marca el comprobante solo si corresponde al certificado que vas a solicitar.'),(14,9,1,NULL,'Marca tu cédula física o digital vigente si ya la tienes. Si falta, se incluye renovación o reposición de un registro existente. La primera inscripción de identidad requiere preparación adicional y no está cubierta por este caso.'),(14,20,1,NULL,'Marca el comprobante solo si corresponde al certificado que vas a solicitar.'),(15,9,1,NULL,'Marca tu cédula física o digital vigente si ya la tienes. Si falta, se incluye renovación o reposición de un registro existente. La primera inscripción de identidad requiere preparación adicional y no está cubierta por este caso. La ficha del TDJ La Paz publicada en 2023 indica cédula original y dos fotocopias, y memorándum, convocatoria o carta de designación cuando corresponda. El instructivo nacional de 2024 regula la solicitud personal y la copia de identidad; confirma la documentación e importe vigentes en plataforma antes de acudir.'),(16,9,1,NULL,'Marca tu cédula física o digital vigente si ya la tienes. Si falta, se incluye renovación o reposición de un registro existente. La primera inscripción de identidad requiere preparación adicional y no está cubierta por este caso. La ficha del TDJ La Paz publicada en 2023 indica cédula original y dos fotocopias. Consulta la documentación e importe vigentes en plataforma; no uses tarifas de otra ciudad como referencia para el pago.'),(17,9,1,NULL,'Marca tu cédula física o digital vigente si ya la tienes. Si falta, se incluye renovación o reposición de un registro existente. La primera inscripción de identidad requiere preparación adicional y no está cubierta por este caso. Para este caso necesitas además contar previamente con registro en el padrón electoral biométrico. La ficha OEP solicita fotocopia del documento de identidad del titular; confirma el formato vigente.');

INSERT INTO `gestion_dependencias` (`gestion_id`, `gestion_previa_id`, `dias_espera`) VALUES (10,14,0),(11,10,0),(11,14,0),(12,10,1),(12,11,0),(12,14,0),(12,16,0),(12,17,0),(12,18,0),(12,19,0),(14,13,0),(15,14,0),(16,14,0),(16,15,0),(17,14,0),(17,15,0),(18,14,0),(18,15,0),(21,20,0),(23,22,0),(25,24,0),(26,25,0),(27,25,0),(28,25,0),(28,27,0),(30,25,0),(30,26,1),(30,28,0),(30,29,0),(32,31,0),(33,32,0),(35,32,0),(35,33,0),(35,34,0),(37,36,0),(38,37,0),(39,37,0),(39,38,0),(41,40,0),(42,41,0),(43,41,0),(43,42,0),(45,44,0),(46,45,0),(48,47,0),(49,48,0),(51,50,0),(52,51,0);

INSERT INTO `gestion_establecimientos` (`gestion_id`, `establecimiento_id`, `duracion_atencion_minutos`, `estado`) VALUES (10,7,NULL,'activo'),(10,8,NULL,'activo'),(11,9,NULL,'activo'),(12,10,NULL,'activo'),(12,11,NULL,'activo'),(13,15,NULL,'activo'),(13,16,NULL,'activo'),(14,11,NULL,'activo'),(14,17,NULL,'activo'),(15,15,NULL,'activo'),(15,16,NULL,'activo'),(16,12,NULL,'activo'),(16,13,NULL,'activo'),(16,14,NULL,'activo'),(17,12,NULL,'activo'),(17,13,NULL,'activo'),(17,14,NULL,'activo'),(18,12,NULL,'activo'),(18,13,NULL,'activo'),(18,14,NULL,'activo'),(19,15,NULL,'activo'),(19,16,NULL,'activo'),(20,15,NULL,'activo'),(20,16,NULL,'activo'),(21,11,NULL,'activo'),(21,17,NULL,'activo'),(22,15,NULL,'activo'),(22,16,NULL,'activo'),(23,11,NULL,'activo'),(23,17,NULL,'activo'),(24,15,NULL,'activo'),(24,16,NULL,'activo'),(25,11,NULL,'activo'),(25,17,NULL,'activo'),(26,7,NULL,'activo'),(26,8,NULL,'activo'),(27,15,NULL,'activo'),(27,16,NULL,'activo'),(28,12,NULL,'activo'),(28,13,NULL,'activo'),(28,14,NULL,'activo'),(29,15,NULL,'activo'),(29,16,NULL,'activo'),(30,10,NULL,'activo'),(30,11,NULL,'activo'),(31,15,NULL,'activo'),(31,16,NULL,'activo'),(32,11,NULL,'activo'),(32,17,NULL,'activo'),(33,10,NULL,'activo'),(33,11,NULL,'activo'),(34,15,NULL,'activo'),(34,16,NULL,'activo'),(35,10,NULL,'activo'),(35,11,NULL,'activo'),(36,15,NULL,'activo'),(36,16,NULL,'activo'),(37,11,NULL,'activo'),(37,17,NULL,'activo'),(38,15,NULL,'activo'),(38,16,NULL,'activo'),(39,12,NULL,'activo'),(39,13,NULL,'activo'),(39,14,NULL,'activo'),(40,15,NULL,'activo'),(40,16,NULL,'activo'),(41,11,NULL,'activo'),(41,17,NULL,'activo'),(42,15,NULL,'activo'),(42,16,NULL,'activo'),(43,12,NULL,'activo'),(43,13,NULL,'activo'),(43,14,NULL,'activo'),(44,15,NULL,'activo'),(44,16,NULL,'activo'),(45,11,NULL,'activo'),(45,17,NULL,'activo'),(46,20,NULL,'activo'),(47,15,NULL,'activo'),(47,16,NULL,'activo'),(48,11,NULL,'activo'),(48,17,NULL,'activo'),(49,20,NULL,'activo'),(50,15,NULL,'activo'),(50,16,NULL,'activo'),(51,11,NULL,'activo'),(51,17,NULL,'activo'),(52,18,NULL,'activo'),(52,19,NULL,'activo');

INSERT INTO `horarios` (`id`, `establecimiento_id`, `dia_semana`, `hora_apertura`, `hora_cierre`, `estado`) VALUES (21,7,1,'07:30:00','18:30:00','activo'),(22,7,2,'07:30:00','18:30:00','activo'),(23,7,3,'07:30:00','18:30:00','activo'),(24,7,4,'07:30:00','18:30:00','activo'),(25,7,5,'07:30:00','18:30:00','activo'),(26,7,6,'08:30:00','12:30:00','activo'),(27,8,1,'08:00:00','17:00:00','activo'),(28,8,2,'08:00:00','17:00:00','activo'),(29,8,3,'08:00:00','17:00:00','activo'),(30,8,4,'08:00:00','17:00:00','activo'),(31,8,5,'08:00:00','17:00:00','activo'),(32,8,6,'08:30:00','12:30:00','activo'),(33,9,1,'08:30:00','12:30:00','activo'),(34,9,2,'08:30:00','12:30:00','activo'),(35,9,3,'08:30:00','12:30:00','activo'),(36,9,4,'08:30:00','12:30:00','activo'),(37,9,5,'08:30:00','12:30:00','activo'),(38,9,1,'14:30:00','18:30:00','activo'),(39,9,2,'14:30:00','18:30:00','activo'),(40,9,3,'14:30:00','18:30:00','activo'),(41,9,4,'14:30:00','18:30:00','activo'),(42,9,5,'14:30:00','18:30:00','activo'),(43,9,6,'08:30:00','12:30:00','activo'),(44,10,1,'07:00:00','15:00:00','activo'),(45,10,2,'07:00:00','15:00:00','activo'),(46,10,3,'07:00:00','15:00:00','activo'),(47,10,4,'07:00:00','15:00:00','activo'),(48,10,5,'07:00:00','15:00:00','activo'),(49,11,1,'07:00:00','15:00:00','activo'),(50,11,2,'07:00:00','15:00:00','activo'),(51,11,3,'07:00:00','15:00:00','activo'),(52,11,4,'07:00:00','15:00:00','activo'),(53,11,5,'07:00:00','15:00:00','activo'),(54,17,1,'07:00:00','15:00:00','activo'),(55,17,2,'07:00:00','15:00:00','activo'),(56,17,3,'07:00:00','15:00:00','activo'),(57,17,4,'07:00:00','15:00:00','activo'),(58,17,5,'07:00:00','15:00:00','activo'),(59,18,1,'08:30:00','16:30:00','activo'),(60,18,2,'08:30:00','16:30:00','activo'),(61,18,3,'08:30:00','16:30:00','activo'),(62,18,4,'08:30:00','16:30:00','activo'),(63,18,5,'08:30:00','16:30:00','activo'),(64,19,1,'08:30:00','16:30:00','activo'),(65,19,2,'08:30:00','16:30:00','activo'),(66,19,3,'08:30:00','16:30:00','activo'),(67,19,4,'08:30:00','16:30:00','activo'),(68,19,5,'08:30:00','16:30:00','activo'),(69,20,1,'08:30:00','16:30:00','activo'),(70,20,2,'08:30:00','16:30:00','activo'),(71,20,3,'08:30:00','16:30:00','activo'),(72,20,4,'08:30:00','16:30:00','activo'),(73,20,5,'08:30:00','16:30:00','activo');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
