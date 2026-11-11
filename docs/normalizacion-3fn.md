# Normalización de DIRI hasta tercera forma normal

La migración `database/migrations/009_normalizacion_3fn.sql` aplica el diseño al MariaDB local. `database/diri.sql` instala directamente la misma estructura en una base nueva. El esquema actual tiene **40 tablas y 2 vistas SQL**.

Normalizar tablas es organizar entidades, claves y dependencias para evitar anomalías al insertar, actualizar o borrar. Convertir texto a minúsculas para un índice es una operación distinta. Tener claves foráneas o muchas tablas, por sí solo, no demuestra 3FN.

## Qué se corrigió

Antes, `paso_documentos` guardaba `(paso_id, documento_id, tramite_id)`. Su clave era `(paso_id, documento_id)`, pero `paso_id` ya determina el trámite en `pasos_tramite`. Por tanto, `tramite_id` dependía de una parte de la clave: era una dependencia parcial que impedía 2FN y, por ello, 3FN.

Ahora solo se almacena `(paso_id, documento_id)`. Se recupera el trámite con JOIN:

```sql
SELECT pd.paso_id, p.tramite_id, d.nombre
FROM paso_documentos pd
JOIN pasos_tramite p ON p.id=pd.paso_id
JOIN documentos d ON d.id=pd.documento_id;
```

Se eliminó la misma redundancia de `paso_dependencias` y `paso_costos`. En `progreso_documentos` y `progreso_pasos`, se quitó `tramite_id` porque lo determina `progreso_tramite_id` en `progreso_tramites`. Las claves originales y los datos de completado se conservan.

También se separó `palabras_clave` de `palabras_clave_tramite`:

```text
palabras_clave:         palabra (PK), palabra_normalizada (calculada)
palabras_clave_tramite: tramite_id (PK, FK), palabra (PK, FK)
```

Así, el valor calculado depende de la clave completa del catálogo y no de una parte de la clave de la asociación. Dos trámites pueden compartir una palabra. El índice `idx_palabras_normalizada` sigue funcionando, ahora sobre el catálogo. Se conserva cada alias original en la asociación. Dos triggers mantienen el catálogo al insertar o cambiar una asociación, incluso desde SQL directo. Pueden quedar palabras sin asociaciones: no crean resultados de búsqueda y pueden reutilizarse posteriormente.

Se eliminó `integraciones_api.tipo`, que solo admitía el valor constante `oficial`. La clasificación sigue siendo una regla de este módulo; no requiere una copia por integración. No se modificó `fuentes.tipo`, que sí distingue varios tipos reales.

## Cómo justificar las tres formas normales

1. **1FN:** cada columna representa un valor de su dominio; no hay columnas repetidas como `documento1`, `documento2` ni listas de identificadores separadas por comas. Varios documentos o pasos se representan mediante varias filas. Una descripción narrativa se trata como texto, no como una lista de entidades que el sistema deba relacionar.
2. **2FN:** los atributos de cada relación dependen de la clave completa. Por ejemplo, la cantidad de copias depende de `(tramite_id, documento_id)` en `tramite_documentos`; el nombre del documento se almacena en `documentos`. Las dependencias parciales señaladas arriba se eliminaron.
3. **3FN:** los datos propios de otra entidad se obtienen por su clave foránea. `usuarios` almacena `rol_id` y `plan_id`; los nombres y descripciones del rol y plan están en sus catálogos. `tramites` no copia el nombre de categoría; `sucursales` no copia los datos de institución. Los atributos de cada entidad dependen de sus claves candidatas, sin dependencias transitivas entre atributos no clave según las reglas actuales de DIRI.

Una clave artificial `id` no bastaba para justificar 3FN: también se revisaron las claves alternativas y las claves compuestas. Una columna calculada no es información editable independiente; en el catálogo de palabras su determinante, `palabra`, es además la clave primaria.

## Revisión de las 40 tablas

| Tablas | Claves principales | Dependencias y separación |
| --- | --- | --- |
| `roles`, `planes` | `id`; `nombre` único | Datos propios del rol o plan; no datos de usuarios |
| `usuarios` | `id`; `email` único | Perfil por usuario; rol y plan referenciados |
| `categorias` | `id`; `nombre` y `slug` únicos | Datos de categoría separados de los trámites |
| `fuentes` | `id` | Evidencia, tipo y revisión de esa fuente |
| `tramites` | `id`; `slug` único | Datos del trámite; categoría y fuente por FK |
| `requisitos` | `id` | Requisito concreto de un trámite; su evidencia puede diferir de otros requisitos |
| `documentos` | `id`; `nombre` único | Descripción compartida del documento |
| `tramite_documentos` | `(tramite_id, documento_id)` | Cantidad, presentación y obligatoriedad dependen de la pareja |
| `etiquetas` | `id`; `nombre` único | Catálogo de etiquetas |
| `tramite_etiquetas` | `(tramite_id, etiqueta_id)` | Solo asociación |
| `palabras_clave` | `palabra` | Valor de búsqueda calculado e índice dependen de esa clave |
| `palabras_clave_tramite` | `(tramite_id, palabra)` | Solo asociación; ya no almacena el valor calculado |
| `instituciones` | `id`; `nombre` único | Datos de institución |
| `sucursales` | `id`; `(institucion_id, nombre)` único | Datos de la oficina, sin copiar la institución |
| `horarios` | `id`; `(sucursal_id, dia_semana, hora_apertura)` único | Un turno por fila |
| `excepciones_horario` | `id`; `(sucursal_id, fecha)` único | Excepción propia de la oficina y fecha |
| `tramite_sucursales` | `(tramite_id, sucursal_id)` | Disponibilidad y condiciones de esa pareja |
| `pasos_tramite` | `id`; `(tramite_id, numero_paso)` único | Instrucciones del paso; institución y evidencia por FK |
| `paso_documentos` | `(paso_id, documento_id)` | Solo asociación; trámite obtenido del paso |
| `paso_dependencias` | `(paso_id, paso_previo_id)` | Solo asociación; trámite obtenido del paso |
| `costos` | `id` | Un concepto por fila; institución responsable y receptora son papeles distintos |
| `paso_costos` | `(paso_id, costo_id)` | Solo asociación; trámite obtenido del paso |
| `tramite_fuentes` | `(tramite_id, fuente_id)` | Evidencia complementaria y revisión de esa asociación |
| `rutas` | `id`; `slug` único | Una guía que reúne trámites para un objetivo |
| `ruta_tramites` | `(ruta_id, tramite_id)`; `(ruta_id, numero_orden)` único | Orden y observaciones de esa pertenencia |
| `ruta_dependencias` | `(ruta_id, tramite_id, tramite_previo_id)` | Solo asociación; un trámite puede aparecer en diferentes rutas |
| `favoritos` | `(usuario_id, tramite_id)` | Fecha de esa asociación |
| `progreso_tramites` | `id`; `(usuario_id, tramite_id)` único | Seguimiento de un usuario para un trámite |
| `progreso_documentos` | `(progreso_tramite_id, documento_id)` | Completado y fecha dependen de la pareja; trámite obtenido del seguimiento |
| `progreso_pasos` | `(progreso_tramite_id, paso_id)` | Completado y fecha dependen de la pareja; trámite obtenido del seguimiento |
| `rutas_guardadas` | `(usuario_id, ruta_id)` | Fecha de esa asociación |
| `historial_busquedas` | `id` | Un evento de búsqueda; repetir texto en eventos distintos es válido |
| `reportes_atencion` | `id` | Una observación y su moderación; usuario, sucursal y revisor por FK |
| `historico_atencion` | `id`; `reporte_id` único cuando existe | Una observación histórica, con origen y confianza; el enlace al reporte es opcional |
| `estado_atencion` | `id`; `sucursal_id` único | Una lectura o estimación actual por sucursal, con vigencia y procedencia |
| `integraciones_api` | `id`; `(institucion_id, nombre)` único | Configuración de una integración; sin la clasificación constante repetida |
| `integracion_ejecuciones` | `id` | Un evento de ejecución; no duplica la configuración completa |
| `auditoria_administrativa` | `id` | Un evento de auditoría con actor; resumen histórico independiente del contenido actual |
| `versiones_esquema` | `version` | Descripción y fecha de aplicación de esa versión |

Las fechas de verificación de una fuente, un requisito y una asociación son hechos distintos: revisar uno no significa revisar los otros. Las mediciones históricas, las estimaciones actuales y los reportes ciudadanos también tienen identidades y momentos distintos. Que coincida un valor entre ellos no implica una dependencia funcional que obligue a fusionarlos. `estado_atencion` conserva un resultado vigente de cálculo; no es una copia del catálogo de sucursales.

La revisión depende de estas reglas semánticas. Las restricciones, las pruebas y los datos de ejemplo no pueden demostrar automáticamente todas las dependencias funcionales posibles. Si cambian las reglas de negocio, debe revisarse el modelo. Véase la [explicación de 3FN de Cornell](https://cvw.cac.cornell.edu/RelationalDBs/design-create/third_normal_form).

## Integridad después de quitar los datos repetidos

Las FK mantienen la existencia de pasos, documentos, costos y seguimientos. Diecisiete triggers preservan las reglas que las FK compuestas garantizaban antes, o mantienen el catálogo de palabras:

- INSERT y UPDATE de las cinco relaciones comprueban que sus entidades pertenecen al mismo trámite.
- No se permite mover un paso, costo o seguimiento de trámite mientras tenga relaciones asociadas.
- No se permite retirar o cambiar una asociación documental usada en pasos o progreso.
- INSERT y UPDATE de alias aseguran la existencia de la palabra en el catálogo.

Las validaciones PHP siguen activas y los formularios administrativos conservan el contexto del trámite, obtenido mediante una subconsulta en `Resource`. Ese valor ayuda a validar la selección, pero no vuelve a almacenarse en las tres relaciones de pasos.

## Cómo verlo en phpMyAdmin y comprobarlo

```sql
SHOW CREATE TABLE paso_documentos;
SHOW CREATE TABLE progreso_documentos;
SHOW CREATE TABLE palabras_clave;
SHOW CREATE TABLE palabras_clave_tramite;
SHOW TRIGGERS;

EXPLAIN
SELECT palabra FROM palabras_clave
WHERE palabra_normalizada = 'carnet';
```

El ejemplo anterior de EXPLAIN sobre `palabras_clave_tramite.palabra_normalizada` corresponde a la estructura anterior y debe sustituirse por este.

```powershell
& 'C:\xampp\php\php.exe' scripts/migrate-normalization.php --check
& 'C:\xampp\php\php.exe' tests/normalization.php
& 'C:\xampp\php\php.exe' tests/database-schema.php
& 'C:\xampp\php\php.exe' tests/fresh-install.php
```

El comprobador consulta metadatos sin modificar objetos: 0 indica estructura completa, 2 pasos pendientes y 1 error. `--apply` guarda la estructura y todos los registros de las tablas en un respaldo privado en `storage/backups/`, aplica los pasos pendientes y comprueba definiciones. No es un volcado de vistas, triggers o rutinas. El DDL hace COMMIT implícito; no hay rollback global de la migración. Es reanudable después de resolver un error y rechaza definiciones incompatibles de los objetos normalizados. Se debe ejecutar sin escrituras concurrentes y con permisos de DDL y TRIGGER. Los respaldos contienen datos privados y deben permanecer fuera de `public/`.

Se comprobó la instalación desde cero y la actualización de una copia aislada de la estructura anterior con registros en las cinco relaciones. Se verificaron los registros de las 38 tablas de negocio originales contra el respaldo; la tabla restante, `versiones_esquema`, recibe el marcador 009. Se conservaron las veinte fichas publicadas y las 74 asociaciones de alias. Las pruebas funcionales usan filas temporales y las revierten o retiran al terminar.
