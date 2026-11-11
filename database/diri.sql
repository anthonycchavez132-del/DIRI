-- DIRI / Fase 3 / MariaDB 10.4+ o MySQL 8.0.16+.
-- Instalación inicial exclusivamente: no borra ni reemplaza estructuras existentes.
-- Importar deteniendo la ejecución ante el primer error. No usar --force.
-- Las fechas de verificación se dejan vacías hasta revisar las fuentes.
SET NAMES utf8mb4;
SET SESSION time_zone = '+00:00';
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION';

CREATE DATABASE diri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE diri;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE planes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT chk_planes_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Solo hashes de password_hash, nunca texto plano',
    rol_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    estado ENUM('activo','inactivo','bloqueado') NOT NULL DEFAULT 'activo',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_usuarios_rol (rol_id),
    KEY idx_usuarios_plan (plan_id),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id),
    CONSTRAINT fk_usuarios_plan FOREIGN KEY (plan_id) REFERENCES planes(id)
) ENGINE=InnoDB;

CREATE TABLE categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT chk_categorias_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE fuentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    url VARCHAR(2048) NULL,
    tipo ENUM('oficial','referencial','DIRI','usuario') NOT NULL,
    fecha_verificacion DATE NULL,
    observaciones TEXT NULL,
    KEY idx_fuentes_tipo (tipo)
) ENGINE=InnoDB;

CREATE TABLE tramites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    estado ENUM('borrador','publicado','inactivo') NOT NULL DEFAULT 'borrador',
    dificultad ENUM('baja','media','alta') NULL,
    tiempo_estimado VARCHAR(150) NULL COMMENT 'Texto contextual verificado o claramente referencial',
    fuente_id INT UNSIGNED NULL,
    estado_verificacion ENUM('pendiente','referencial','verificado') NOT NULL DEFAULT 'pendiente',
    fecha_verificacion DATE NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    KEY idx_tramites_categoria_estado (categoria_id, estado),
    KEY idx_tramites_estado_nombre (estado, nombre),
    KEY idx_tramites_fuente (fuente_id),
    CONSTRAINT fk_tramites_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    CONSTRAINT fk_tramites_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT chk_tramites_verificacion CHECK (estado_verificacion <> 'verificado' OR (fuente_id IS NOT NULL AND fecha_verificacion IS NOT NULL))
) ENGINE=InnoDB;

CREATE TABLE requisitos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tramite_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    obligatorio TINYINT(1) NOT NULL DEFAULT 1,
    observaciones TEXT NULL,
    fuente_id INT UNSIGNED NULL,
    estado_verificacion ENUM('pendiente','referencial','verificado') NOT NULL DEFAULT 'pendiente',
    fecha_verificacion DATE NULL,
    KEY idx_requisitos_tramite (tramite_id),
    KEY idx_requisitos_fuente (fuente_id),
    CONSTRAINT fk_requisitos_tramite FOREIGN KEY (tramite_id) REFERENCES tramites(id),
    CONSTRAINT fk_requisitos_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT chk_requisitos_obligatorio CHECK (obligatorio IN (0,1)),
    CONSTRAINT chk_requisitos_verificacion CHECK (estado_verificacion <> 'verificado' OR (fuente_id IS NOT NULL AND fecha_verificacion IS NOT NULL))
) ENGINE=InnoDB;

CREATE TABLE documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    fuente_id INT UNSIGNED NULL,
    fecha_verificacion DATE NULL,
    KEY idx_documentos_fuente (fuente_id),
    CONSTRAINT fk_documentos_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id)
) ENGINE=InnoDB;

CREATE TABLE tramite_documentos (
    tramite_id INT UNSIGNED NOT NULL,
    documento_id INT UNSIGNED NOT NULL,
    obligatorio TINYINT(1) NOT NULL DEFAULT 1,
    cantidad SMALLINT UNSIGNED NULL,
    presentacion ENUM('original','copia','original_y_copia','digital') NULL,
    observaciones TEXT NULL,
    fuente_id INT UNSIGNED NULL,
    fecha_verificacion DATE NULL,
    PRIMARY KEY (tramite_id, documento_id),
    KEY idx_td_documento (documento_id),
    KEY idx_td_fuente (fuente_id),
    CONSTRAINT fk_td_tramite FOREIGN KEY (tramite_id) REFERENCES tramites(id),
    CONSTRAINT fk_td_documento FOREIGN KEY (documento_id) REFERENCES documentos(id),
    CONSTRAINT fk_td_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT chk_td_obligatorio CHECK (obligatorio IN (0,1)),
    CONSTRAINT chk_td_cantidad CHECK (cantidad IS NULL OR cantidad > 0)
) ENGINE=InnoDB;

CREATE TABLE etiquetas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE tramite_etiquetas (
    tramite_id INT UNSIGNED NOT NULL,
    etiqueta_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (tramite_id, etiqueta_id),
    KEY idx_te_etiqueta (etiqueta_id),
    CONSTRAINT fk_te_tramite FOREIGN KEY (tramite_id) REFERENCES tramites(id) ON DELETE CASCADE,
    CONSTRAINT fk_te_etiqueta FOREIGN KEY (etiqueta_id) REFERENCES etiquetas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE palabras_clave (
    palabra VARCHAR(150) NOT NULL PRIMARY KEY,
    palabra_normalizada VARCHAR(150) GENERATED ALWAYS AS (LOWER(TRIM(palabra))) STORED,
    KEY idx_palabras_normalizada (palabra_normalizada, palabra)
) ENGINE=InnoDB;

CREATE TABLE palabras_clave_tramite (
    tramite_id INT UNSIGNED NOT NULL,
    palabra VARCHAR(150) NOT NULL,
    PRIMARY KEY (tramite_id, palabra),
    KEY idx_palabras_busqueda (palabra),
    CONSTRAINT fk_alias_palabra FOREIGN KEY(palabra) REFERENCES palabras_clave(palabra),
    CONSTRAINT fk_palabras_tramite FOREIGN KEY (tramite_id) REFERENCES tramites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE instituciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    telefono VARCHAR(50) NULL,
    sitio_web VARCHAR(2048) NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fuente_id INT UNSIGNED NULL,
    fecha_verificacion DATE NULL,
    KEY idx_instituciones_fuente (fuente_id),
    CONSTRAINT fk_instituciones_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT chk_instituciones_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE sucursales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institucion_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    direccion VARCHAR(500) NULL,
    ciudad VARCHAR(100) NULL,
    zona VARCHAR(150) NULL,
    telefono VARCHAR(50) NULL,
    latitud DECIMAL(10,7) NULL,
    longitud DECIMAL(10,7) NULL,
    estado ENUM('activa','inactiva','temporalmente_cerrada') NOT NULL DEFAULT 'inactiva',
    fuente_id INT UNSIGNED NULL,
    fecha_verificacion DATE NULL,
    UNIQUE KEY uq_sucursal_institucion_nombre (institucion_id, nombre),
    KEY idx_sucursales_ciudad_estado (ciudad, estado),
    KEY idx_sucursales_fuente (fuente_id),
    CONSTRAINT fk_sucursales_institucion FOREIGN KEY (institucion_id) REFERENCES instituciones(id),
    CONSTRAINT fk_sucursales_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT chk_sucursales_coordenadas CHECK ((latitud IS NULL AND longitud IS NULL) OR (latitud IS NOT NULL AND longitud IS NOT NULL AND latitud BETWEEN -90 AND 90 AND longitud BETWEEN -180 AND 180))
) ENGINE=InnoDB;

CREATE TABLE horarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sucursal_id INT UNSIGNED NOT NULL,
    dia_semana TINYINT UNSIGNED NOT NULL COMMENT '1=lunes, 7=domingo',
    hora_apertura TIME NOT NULL,
    hora_cierre TIME NOT NULL,
    fuente_id INT UNSIGNED NULL,
    fecha_verificacion DATE NULL,
    UNIQUE KEY uq_horario_turno (sucursal_id, dia_semana, hora_apertura),
    KEY idx_horarios_fuente (fuente_id),
    CONSTRAINT fk_horarios_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
    CONSTRAINT fk_horarios_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT chk_horarios_dia CHECK (dia_semana BETWEEN 1 AND 7),
    CONSTRAINT chk_horarios_horas CHECK (hora_apertura >= '00:00:00' AND hora_cierre <= '23:59:59' AND hora_apertura < hora_cierre)
) ENGINE=InnoDB;

CREATE TABLE excepciones_horario (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sucursal_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    cerrada TINYINT(1) NOT NULL DEFAULT 1,
    hora_apertura TIME NULL,
    hora_cierre TIME NULL,
    motivo VARCHAR(500) NULL,
    fuente_id INT UNSIGNED NULL,
    fecha_verificacion DATE NULL,
    UNIQUE KEY uq_excepcion_sucursal_fecha (sucursal_id, fecha),
    KEY idx_excepciones_fuente (fuente_id),
    CONSTRAINT fk_excepciones_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
    CONSTRAINT fk_excepciones_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT chk_excepciones_horas CHECK ((cerrada = 1 AND hora_apertura IS NULL AND hora_cierre IS NULL) OR (cerrada = 0 AND hora_apertura IS NOT NULL AND hora_cierre IS NOT NULL AND hora_apertura >= '00:00:00' AND hora_cierre <= '23:59:59' AND hora_apertura < hora_cierre))
) ENGINE=InnoDB;

CREATE TABLE tramite_sucursales (
    tramite_id INT UNSIGNED NOT NULL,
    sucursal_id INT UNSIGNED NOT NULL,
    disponible TINYINT(1) NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    fuente_id INT UNSIGNED NULL,
    fecha_verificacion DATE NULL,
    PRIMARY KEY (tramite_id, sucursal_id),
    KEY idx_ts_sucursal (sucursal_id),
    KEY idx_ts_fuente (fuente_id),
    CONSTRAINT fk_ts_tramite FOREIGN KEY (tramite_id) REFERENCES tramites(id),
    CONSTRAINT fk_ts_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
    CONSTRAINT fk_ts_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT chk_ts_disponible CHECK (disponible IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE pasos_tramite (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tramite_id INT UNSIGNED NOT NULL,
    numero_paso SMALLINT UNSIGNED NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    institucion_id INT UNSIGNED NULL,
    tiempo_estimado VARCHAR(150) NULL,
    observaciones TEXT NULL,
    fuente_id INT UNSIGNED NULL,
    fecha_verificacion DATE NULL,
    UNIQUE KEY uq_pasos_numero (tramite_id, numero_paso),
    UNIQUE KEY uq_pasos_tramite_id (tramite_id, id),
    KEY idx_pasos_institucion (institucion_id),
    KEY idx_pasos_fuente (fuente_id),
    CONSTRAINT fk_pasos_tramite FOREIGN KEY (tramite_id) REFERENCES tramites(id),
    CONSTRAINT fk_pasos_institucion FOREIGN KEY (institucion_id) REFERENCES instituciones(id),
    CONSTRAINT fk_pasos_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT chk_pasos_numero CHECK (numero_paso > 0)
) ENGINE=InnoDB;

CREATE TABLE paso_documentos (
    paso_id INT UNSIGNED NOT NULL,
    documento_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (paso_id, documento_id),
    KEY idx_pd_documento (documento_id),
    CONSTRAINT nf_fk_pd_paso FOREIGN KEY (paso_id) REFERENCES pasos_tramite(id),
    CONSTRAINT nf_fk_pd_documento FOREIGN KEY (documento_id) REFERENCES documentos(id)
) ENGINE=InnoDB;

CREATE TABLE paso_dependencias (
    paso_id INT UNSIGNED NOT NULL,
    paso_previo_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (paso_id, paso_previo_id),
    KEY idx_dependencia_previo (paso_previo_id),
    CONSTRAINT nf_fk_dependencia_paso FOREIGN KEY (paso_id) REFERENCES pasos_tramite(id),
    CONSTRAINT nf_fk_dependencia_previo FOREIGN KEY (paso_previo_id) REFERENCES pasos_tramite(id),
    CONSTRAINT chk_paso_no_autodependencia CHECK (paso_id <> paso_previo_id)
) ENGINE=InnoDB;

CREATE TABLE costos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tramite_id INT UNSIGNED NOT NULL,
    institucion_id INT UNSIGNED NULL COMMENT 'Institución responsable del concepto',
    institucion_pago_id INT UNSIGNED NULL COMMENT 'Entidad receptora del pago, si corresponde',
    concepto VARCHAR(200) NOT NULL,
    monto DECIMAL(12,2) NULL COMMENT 'NULL=desconocido, 0=sin costo solo si está respaldado',
    moneda CHAR(3) NOT NULL DEFAULT 'BOB',
    lugar_pago VARCHAR(500) NULL,
    medio_pago VARCHAR(150) NULL,
    observaciones TEXT NULL,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fuente_id INT UNSIGNED NULL,
    estado_verificacion ENUM('pendiente','referencial','verificado') NOT NULL DEFAULT 'pendiente',
    fecha_verificacion DATE NULL,
    UNIQUE KEY uq_costos_tramite_id (tramite_id, id),
    KEY idx_costos_institucion (institucion_id),
    KEY idx_costos_pago (institucion_pago_id),
    KEY idx_costos_fuente (fuente_id),
    CONSTRAINT fk_costos_tramite FOREIGN KEY (tramite_id) REFERENCES tramites(id),
    CONSTRAINT fk_costos_institucion FOREIGN KEY (institucion_id) REFERENCES instituciones(id),
    CONSTRAINT fk_costos_pago FOREIGN KEY (institucion_pago_id) REFERENCES instituciones(id),
    CONSTRAINT fk_costos_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT chk_costos_monto CHECK (monto IS NULL OR monto >= 0),
    CONSTRAINT chk_costos_verificacion CHECK (estado_verificacion <> 'verificado' OR (fuente_id IS NOT NULL AND fecha_verificacion IS NOT NULL AND monto IS NOT NULL))
) ENGINE=InnoDB;

CREATE TABLE paso_costos (
    paso_id INT UNSIGNED NOT NULL,
    costo_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (paso_id, costo_id),
    KEY idx_pc_costo (costo_id),
    CONSTRAINT nf_fk_pc_paso FOREIGN KEY (paso_id) REFERENCES pasos_tramite(id),
    CONSTRAINT nf_fk_pc_costo FOREIGN KEY (costo_id) REFERENCES costos(id)
) ENGINE=InnoDB;

CREATE TABLE tramite_fuentes (
    tramite_id INT UNSIGNED NOT NULL,
    fuente_id INT UNSIGNED NOT NULL,
    fecha_verificacion DATE NULL,
    observaciones TEXT NULL,
    PRIMARY KEY (tramite_id, fuente_id),
    KEY idx_tf_fuente (fuente_id),
    CONSTRAINT fk_tf_tramite FOREIGN KEY (tramite_id) REFERENCES tramites(id),
    CONSTRAINT fk_tf_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id)
) ENGINE=InnoDB;

CREATE TABLE rutas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    ciudad VARCHAR(100) NULL,
    actividad_economica VARCHAR(200) NULL,
    estado ENUM('borrador','publicada','inactiva') NOT NULL DEFAULT 'borrador',
    fuente_id INT UNSIGNED NULL,
    fecha_verificacion DATE NULL,
    KEY idx_rutas_fuente (fuente_id),
    CONSTRAINT fk_rutas_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id)
) ENGINE=InnoDB;

CREATE TABLE ruta_tramites (
    ruta_id INT UNSIGNED NOT NULL,
    tramite_id INT UNSIGNED NOT NULL,
    numero_orden SMALLINT UNSIGNED NOT NULL,
    observaciones TEXT NULL,
    PRIMARY KEY (ruta_id, tramite_id),
    UNIQUE KEY uq_ruta_orden (ruta_id, numero_orden),
    KEY idx_rt_tramite (tramite_id),
    CONSTRAINT fk_rt_ruta FOREIGN KEY (ruta_id) REFERENCES rutas(id),
    CONSTRAINT fk_rt_tramite FOREIGN KEY (tramite_id) REFERENCES tramites(id),
    CONSTRAINT chk_rt_orden CHECK (numero_orden > 0)
) ENGINE=InnoDB;

CREATE TABLE ruta_dependencias (
    ruta_id INT UNSIGNED NOT NULL,
    tramite_id INT UNSIGNED NOT NULL,
    tramite_previo_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (ruta_id, tramite_id, tramite_previo_id),
    KEY idx_rd_previo (ruta_id, tramite_previo_id),
    CONSTRAINT fk_rd_tramite FOREIGN KEY (ruta_id, tramite_id) REFERENCES ruta_tramites(ruta_id, tramite_id),
    CONSTRAINT fk_rd_previo FOREIGN KEY (ruta_id, tramite_previo_id) REFERENCES ruta_tramites(ruta_id, tramite_id),
    CONSTRAINT chk_ruta_no_autodependencia CHECK (tramite_id <> tramite_previo_id)
) ENGINE=InnoDB;

CREATE TABLE favoritos (
    usuario_id INT UNSIGNED NOT NULL,
    tramite_id INT UNSIGNED NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, tramite_id),
    KEY idx_favoritos_tramite (tramite_id),
    CONSTRAINT fk_favoritos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_favoritos_tramite FOREIGN KEY (tramite_id) REFERENCES tramites(id)
) ENGINE=InnoDB;

CREATE TABLE progreso_tramites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tramite_id INT UNSIGNED NOT NULL,
    estado ENUM('en_preparacion','en_curso','completado','pausado') NOT NULL DEFAULT 'en_preparacion',
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_finalizacion DATETIME NULL,
    UNIQUE KEY uq_progreso_usuario_tramite (usuario_id, tramite_id),
    UNIQUE KEY uq_progreso_id_tramite (id, tramite_id),
    KEY idx_progreso_tramite (tramite_id),
    CONSTRAINT fk_progreso_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_progreso_tramite FOREIGN KEY (tramite_id) REFERENCES tramites(id),
    CONSTRAINT chk_progreso_fechas CHECK (fecha_finalizacion IS NULL OR fecha_finalizacion >= fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE progreso_documentos (
    progreso_tramite_id INT UNSIGNED NOT NULL,
    documento_id INT UNSIGNED NOT NULL,
    completado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (progreso_tramite_id, documento_id),
    KEY idx_progdoc_documento (documento_id),
    CONSTRAINT nf_fk_progdoc_progreso FOREIGN KEY (progreso_tramite_id) REFERENCES progreso_tramites(id) ON DELETE CASCADE,
    CONSTRAINT nf_fk_progdoc_documento FOREIGN KEY (documento_id) REFERENCES documentos(id),
    CONSTRAINT chk_progdoc_completado CHECK (completado IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE progreso_pasos (
    progreso_tramite_id INT UNSIGNED NOT NULL,
    paso_id INT UNSIGNED NOT NULL,
    completado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (progreso_tramite_id, paso_id),
    KEY idx_progpaso_paso (paso_id),
    CONSTRAINT nf_fk_progpaso_progreso FOREIGN KEY (progreso_tramite_id) REFERENCES progreso_tramites(id) ON DELETE CASCADE,
    CONSTRAINT nf_fk_progpaso_paso FOREIGN KEY (paso_id) REFERENCES pasos_tramite(id),
    CONSTRAINT chk_progpaso_completado CHECK (completado IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE rutas_guardadas (
    usuario_id INT UNSIGNED NOT NULL,
    ruta_id INT UNSIGNED NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, ruta_id),
    KEY idx_rg_ruta (ruta_id),
    CONSTRAINT fk_rg_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_rg_ruta FOREIGN KEY (ruta_id) REFERENCES rutas(id)
) ENGINE=InnoDB;

CREATE TABLE historial_busquedas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    consulta VARCHAR(500) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_historial_usuario_fecha (usuario_id, fecha),
    CONSTRAINT fk_historial_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reportes_atencion (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    sucursal_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    personas_esperando INT UNSIGNED NULL,
    tiempo_espera INT UNSIGNED NULL COMMENT 'Minutos reportados, NULL si se desconoce',
    nivel_demanda ENUM('baja','media','alta') NOT NULL,
    observaciones VARCHAR(1000) NULL,
    estado_revision ENUM('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente',
    revisado_por INT UNSIGNED NULL,
    fecha_revision DATETIME NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_reportes_sucursal_fecha (sucursal_id, fecha, hora),
    KEY idx_reportes_usuario_fecha (usuario_id, fecha_registro),
    KEY idx_reportes_revision (estado_revision, fecha_registro),
    KEY idx_reportes_revisor (revisado_por),
    UNIQUE KEY uq_reporte_sucursal_id (sucursal_id, id),
    CONSTRAINT fk_reportes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_reportes_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
    CONSTRAINT fk_reportes_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios(id),
    CONSTRAINT chk_reportes_hora CHECK (hora BETWEEN '00:00:00' AND '23:59:59')
) ENGINE=InnoDB;

CREATE TABLE historico_atencion (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sucursal_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    personas_esperando INT UNSIGNED NULL,
    tiempo_espera INT UNSIGNED NULL,
    fuente ENUM('OFICIAL_API','DIRI_USUARIOS','ADMINISTRADOR','ESTIMACION') NOT NULL,
    fuente_id INT UNSIGNED NULL,
    reporte_id INT UNSIGNED NULL,
    confianza ENUM('baja','media','alta') NOT NULL DEFAULT 'baja',
    KEY idx_historico_sucursal_fecha (sucursal_id, fecha, hora),
    KEY idx_historico_fuente (fuente_id),
    UNIQUE KEY uq_historico_reporte (reporte_id),
    KEY idx_historico_sucursal_reporte (sucursal_id, reporte_id),
    CONSTRAINT fk_historico_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
    CONSTRAINT fk_historico_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT fk_historico_reporte FOREIGN KEY (sucursal_id, reporte_id) REFERENCES reportes_atencion(sucursal_id, id),
    CONSTRAINT chk_historico_hora CHECK (hora BETWEEN '00:00:00' AND '23:59:59'),
    CONSTRAINT chk_historico_datos CHECK (personas_esperando IS NOT NULL OR tiempo_espera IS NOT NULL)
) ENGINE=InnoDB;

CREATE TABLE estado_atencion (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sucursal_id INT UNSIGNED NOT NULL UNIQUE,
    personas_esperando INT UNSIGNED NULL,
    ficha_actual INT UNSIGNED NULL,
    ficha_atendida INT UNSIGNED NULL,
    tiempo_estimado INT UNSIGNED NULL COMMENT 'Valor informado por la fuente, en minutos',
    tiempo_minimo INT UNSIGNED NULL COMMENT 'Límite inferior del intervalo DIRI',
    tiempo_maximo INT UNSIGNED NULL COMMENT 'Límite superior del intervalo DIRI',
    nivel_demanda ENUM('desconocida','baja','media','alta') NOT NULL DEFAULT 'desconocida',
    fuente ENUM('OFICIAL_API','DIRI_USUARIOS','ADMINISTRADOR','ESTIMACION') NOT NULL,
    fuente_id INT UNSIGNED NULL,
    confianza ENUM('baja','media','alta') NULL,
    cantidad_observaciones INT UNSIGNED NULL,
    ultima_actualizacion DATETIME NOT NULL,
    valido_hasta DATETIME NOT NULL,
    KEY idx_estado_vigencia (valido_hasta),
    KEY idx_estado_fuente (fuente_id),
    CONSTRAINT fk_estado_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
    CONSTRAINT fk_estado_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    CONSTRAINT chk_estado_intervalo CHECK ((tiempo_minimo IS NULL AND tiempo_maximo IS NULL) OR (tiempo_minimo IS NOT NULL AND tiempo_maximo IS NOT NULL AND tiempo_minimo <= tiempo_maximo)),
    CONSTRAINT chk_estado_vigencia CHECK (valido_hasta >= ultima_actualizacion)
) ENGINE=InnoDB;

CREATE TABLE integraciones_api (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institucion_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    url_base VARCHAR(2048) NOT NULL,
    requiere_token TINYINT(1) NOT NULL DEFAULT 0,
    credencial_referencia VARCHAR(100) NULL COMMENT 'Nombre de configuración privada, nunca el token',
    estado ENUM('inactiva','activa','error') NOT NULL DEFAULT 'inactiva',
    ultima_consulta DATETIME NULL,
    ultima_respuesta VARCHAR(1000) NULL COMMENT 'Resumen sanitizado, nunca credenciales ni datos personales',
    UNIQUE KEY uq_integracion_institucion_nombre (institucion_id, nombre),
    CONSTRAINT fk_integraciones_institucion FOREIGN KEY (institucion_id) REFERENCES instituciones(id),
    CONSTRAINT chk_integraciones_token CHECK (requiere_token IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE integracion_ejecuciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    integracion_id INT UNSIGNED NOT NULL,
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME NULL,
    resultado ENUM('en_curso','exito','error') NOT NULL DEFAULT 'en_curso',
    codigo_http SMALLINT UNSIGNED NULL,
    resumen VARCHAR(1000) NULL COMMENT 'Sin cuerpos privados de respuesta ni tokens',
    KEY idx_ejecuciones_integracion_fecha (integracion_id, fecha_inicio),
    CONSTRAINT fk_ejecuciones_integracion FOREIGN KEY (integracion_id) REFERENCES integraciones_api(id),
    CONSTRAINT chk_ejecuciones_http CHECK (codigo_http IS NULL OR codigo_http BETWEEN 100 AND 599),
    CONSTRAINT chk_ejecuciones_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE auditoria_administrativa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    entidad VARCHAR(100) NOT NULL,
    entidad_id INT UNSIGNED NULL,
    accion VARCHAR(100) NOT NULL,
    resumen VARCHAR(1000) NULL COMMENT 'No almacenar contraseñas, hashes ni tokens',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auditoria_usuario_fecha (usuario_id, fecha),
    KEY idx_auditoria_entidad (entidad, entidad_id),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE versiones_esquema (
    version VARCHAR(50) PRIMARY KEY,
    descripcion VARCHAR(255) NOT NULL,
    fecha_aplicacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DELIMITER $
CREATE TRIGGER nf_paso_documentos_insert BEFORE INSERT ON paso_documentos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM tramite_documentos WHERE documento_id=NEW.documento_id AND tramite_id=parent_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$
CREATE TRIGGER nf_paso_documentos_update BEFORE UPDATE ON paso_documentos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM tramite_documentos WHERE documento_id=NEW.documento_id AND tramite_id=parent_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$
CREATE TRIGGER nf_paso_dependencias_insert BEFORE INSERT ON paso_dependencias FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM pasos_tramite WHERE id=NEW.paso_previo_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$
CREATE TRIGGER nf_paso_dependencias_update BEFORE UPDATE ON paso_dependencias FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM pasos_tramite WHERE id=NEW.paso_previo_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$
CREATE TRIGGER nf_paso_costos_insert BEFORE INSERT ON paso_costos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM costos WHERE id=NEW.costo_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$
CREATE TRIGGER nf_paso_costos_update BEFORE UPDATE ON paso_costos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM costos WHERE id=NEW.costo_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$
CREATE TRIGGER nf_progreso_documentos_insert BEFORE INSERT ON progreso_documentos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM progreso_tramites WHERE id=NEW.progreso_tramite_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM tramite_documentos WHERE documento_id=NEW.documento_id AND tramite_id=parent_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$
CREATE TRIGGER nf_progreso_documentos_update BEFORE UPDATE ON progreso_documentos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM progreso_tramites WHERE id=NEW.progreso_tramite_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM tramite_documentos WHERE documento_id=NEW.documento_id AND tramite_id=parent_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$
CREATE TRIGGER nf_progreso_pasos_insert BEFORE INSERT ON progreso_pasos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM progreso_tramites WHERE id=NEW.progreso_tramite_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$
CREATE TRIGGER nf_progreso_pasos_update BEFORE UPDATE ON progreso_pasos FOR EACH ROW
BEGIN
    DECLARE parent_id INT UNSIGNED DEFAULT NULL;
    DECLARE other_id INT UNSIGNED DEFAULT NULL;
    SELECT tramite_id INTO parent_id FROM progreso_tramites WHERE id=NEW.progreso_tramite_id FOR UPDATE;
    SELECT tramite_id INTO other_id FROM pasos_tramite WHERE id=NEW.paso_id FOR UPDATE;
    IF parent_id IS NULL OR other_id IS NULL OR parent_id<>other_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='La relacion debe pertenecer al mismo tramite';
    END IF;
END$
CREATE TRIGGER nf_pasos_tramite_parent BEFORE UPDATE ON pasos_tramite FOR EACH ROW
BEGIN
    IF NEW.tramite_id<>OLD.tramite_id AND (EXISTS(SELECT 1 FROM paso_documentos WHERE paso_id=OLD.id) OR EXISTS(SELECT 1 FROM paso_costos WHERE paso_id=OLD.id) OR EXISTS(SELECT 1 FROM paso_dependencias WHERE paso_id=OLD.id OR paso_previo_id=OLD.id) OR EXISTS(SELECT 1 FROM progreso_pasos WHERE paso_id=OLD.id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se cambia el tramite mientras existan relaciones';
    END IF;
END$
CREATE TRIGGER nf_costos_parent BEFORE UPDATE ON costos FOR EACH ROW
BEGIN
    IF NEW.tramite_id<>OLD.tramite_id AND (EXISTS(SELECT 1 FROM paso_costos WHERE costo_id=OLD.id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se cambia el tramite mientras existan relaciones';
    END IF;
END$
CREATE TRIGGER nf_progreso_tramites_parent BEFORE UPDATE ON progreso_tramites FOR EACH ROW
BEGIN
    IF NEW.tramite_id<>OLD.tramite_id AND (EXISTS(SELECT 1 FROM progreso_documentos WHERE progreso_tramite_id=OLD.id) OR EXISTS(SELECT 1 FROM progreso_pasos WHERE progreso_tramite_id=OLD.id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se cambia el tramite mientras existan relaciones';
    END IF;
END$
CREATE TRIGGER nf_tramite_documentos_delete BEFORE DELETE ON tramite_documentos FOR EACH ROW
BEGIN
    IF (EXISTS(SELECT 1 FROM paso_documentos pd JOIN pasos_tramite p ON p.id=pd.paso_id WHERE p.tramite_id=OLD.tramite_id AND pd.documento_id=OLD.documento_id) OR EXISTS(SELECT 1 FROM progreso_documentos pd JOIN progreso_tramites p ON p.id=pd.progreso_tramite_id WHERE p.tramite_id=OLD.tramite_id AND pd.documento_id=OLD.documento_id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El documento esta asociado a pasos o seguimientos';
    END IF;
END$
CREATE TRIGGER nf_tramite_documentos_update BEFORE UPDATE ON tramite_documentos FOR EACH ROW
BEGIN
    IF (NEW.tramite_id<>OLD.tramite_id OR NEW.documento_id<>OLD.documento_id) AND (EXISTS(SELECT 1 FROM paso_documentos pd JOIN pasos_tramite p ON p.id=pd.paso_id WHERE p.tramite_id=OLD.tramite_id AND pd.documento_id=OLD.documento_id) OR EXISTS(SELECT 1 FROM progreso_documentos pd JOIN progreso_tramites p ON p.id=pd.progreso_tramite_id WHERE p.tramite_id=OLD.tramite_id AND pd.documento_id=OLD.documento_id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El documento esta asociado a pasos o seguimientos';
    END IF;
END$
CREATE TRIGGER nf_alias_insert BEFORE INSERT ON palabras_clave_tramite FOR EACH ROW
BEGIN
    INSERT INTO palabras_clave(palabra) VALUES(NEW.palabra) ON DUPLICATE KEY UPDATE palabra=palabra;
END$
CREATE TRIGGER nf_alias_update BEFORE UPDATE ON palabras_clave_tramite FOR EACH ROW
BEGIN
    INSERT INTO palabras_clave(palabra) VALUES(NEW.palabra) ON DUPLICATE KEY UPDATE palabra=palabra;
END$
DELIMITER ;

-- Semillas internas. No se crea ningún usuario ni contraseña predeterminada.
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

START TRANSACTION;

INSERT INTO roles (id, nombre, descripcion) VALUES
(1, 'administrador', 'Administración de contenido y usuarios'),
(2, 'usuario', 'Cuenta de usuario de DIRI');

INSERT INTO planes (id, nombre, descripcion) VALUES
(1, 'free', 'Consulta de información básica de trámites'),
(2, 'premium', 'Preparado para organización y recomendaciones avanzadas, sin pagos reales');

INSERT INTO categorias (id, nombre, slug) VALUES
(1, 'Identidad y registro civil', 'identidad-registro-civil'),
(2, 'Conducción y vehículos', 'conduccion-vehiculos'),
(3, 'Negocios y empresas', 'negocios-empresas'),
(4, 'Viajes y migración', 'viajes-migracion'),
(5, 'Certificaciones', 'certificaciones'),
(6, 'Gestiones municipales', 'gestiones-municipales');

INSERT INTO fuentes (id, nombre, tipo, observaciones) VALUES
(1, 'Catálogo preparatorio de DIRI', 'DIRI', 'Títulos de planificación. Pendiente de verificación. No constituye una fuente oficial ni acredita requisitos, costos o procedimientos.');

INSERT INTO tramites (nombre, slug, categoria_id, descripcion, fuente_id) VALUES
('Cédula de identidad', 'cedula-de-identidad', 1, 'Pendiente de verificación', 1),
('Renovación de cédula', 'renovacion-de-cedula', 1, 'Pendiente de verificación', 1),
('Certificado de nacimiento', 'certificado-de-nacimiento', 1, 'Pendiente de verificación', 1),
('Certificado de matrimonio', 'certificado-de-matrimonio', 1, 'Pendiente de verificación', 1),
('Certificado de defunción', 'certificado-de-defuncion', 1, 'Pendiente de verificación', 1),
('Licencia de conducir', 'licencia-de-conducir', 2, 'Pendiente de verificación', 1),
('Renovación de licencia', 'renovacion-de-licencia', 2, 'Pendiente de verificación', 1),
('Pasaporte', 'pasaporte', 4, 'Pendiente de verificación', 1),
('Certificado de antecedentes', 'certificado-de-antecedentes', 5, 'Pendiente de verificación', 1),
('Registro de nacimiento', 'registro-de-nacimiento', 1, 'Pendiente de verificación', 1),
('NIT', 'nit', 3, 'Pendiente de verificación', 1),
('Registro de actividad económica', 'registro-de-actividad-economica', 3, 'Pendiente de verificación', 1),
('Apertura de negocio', 'apertura-de-negocio', 3, 'Pendiente de verificación', 1),
('Licencia de funcionamiento', 'licencia-de-funcionamiento', 6, 'Pendiente de verificación', 1),
('Registro de empresa', 'registro-de-empresa', 3, 'Pendiente de verificación', 1),
('Trámites relacionados con vehículos', 'tramites-relacionados-con-vehiculos', 2, 'Pendiente de verificación', 1),
('Transferencia de vehículo', 'transferencia-de-vehiculo', 2, 'Pendiente de verificación', 1),
('Certificados SERECI', 'certificados-sereci', 1, 'Pendiente de verificación', 1),
('Trámites municipales', 'tramites-municipales', 6, 'Pendiente de verificación', 1),
('Reposición de cédula', 'reposicion-de-cedula', 1, 'Pendiente de verificación', 1);

INSERT INTO versiones_esquema (version, descripcion) VALUES
('003_inicial', 'Estructura inicial de DIRI y catálogo de borradores pendientes de verificación'),
('007_revision_contenido', 'Revisión de trámites y protección de cambios de requisitos y documentos'),
('008_vistas_busqueda', 'Vistas del catálogo e índice de alias normalizados'),
('009_normalizacion_3fn', 'Relaciones sin dependencias parciales y catálogo de palabras clave');

COMMIT;
