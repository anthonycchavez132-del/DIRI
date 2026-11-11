# Vistas SQL e índices utilizados por DIRI

Las vistas se definen en `database/migrations/008_vistas_busqueda.sql`. Desde la migración `009_normalizacion_3fn.sql`, el índice está en el catálogo compartido `palabras_clave`. La instalación nueva `database/diri.sql` incluye la estructura actual: 40 tablas y dos vistas. Las vistas son consultas guardadas y no copian registros.

La [actividad de 12 vistas adicionales](actividad-12-vistas.md) se conserva por separado en `database/views/actividad_12_vistas.sql`. En la base local donde se aplicó hay 14 vistas: las dos anteriores y las 12 de la actividad. Este archivo adicional no sustituye las vistas utilizadas por el backend.

## Usos reales en PHP

| Objeto SQL | Uso en DIRI | Archivo |
| --- | --- | --- |
| `vista_tramites_publicados` | Reúne trámite y categoría mediante JOIN; excluye borradores, inactivos y categorías desactivadas | `app/services/DatabaseSearch.php`, `app/services/CatalogService.php` |
| `vista_documentos_tramite` | Reúne requisitos documentales y descripción del documento mediante JOIN | `app/models/TramiteDocumento.php`, método `forTramite` |
| `idx_palabras_normalizada` | Busca alias exactos normalizados para darles prioridad en los resultados | `app/services/DatabaseSearch.php`, subconsulta `exactas` |

La vista de documentos permite consultar borradores desde la administración. En la ficha pública, `CatalogService::detail` comprueba primero que el trámite esté en la vista de publicados. La vista por sí sola no sustituye los controles de acceso del backend.

## Inspeccionar en phpMyAdmin o en la terminal SQL

Selecciona la base `diri` y ejecuta:

```sql
SHOW FULL TABLES WHERE Table_type = 'VIEW';
SHOW CREATE VIEW vista_tramites_publicados;
SHOW CREATE VIEW vista_documentos_tramite;
SELECT * FROM vista_tramites_publicados ORDER BY nombre LIMIT 10;
SELECT * FROM vista_documentos_tramite ORDER BY tramite_id, nombre LIMIT 10;
```

Una vista guarda la definición del SELECT. Cada consulta lee los datos actuales de las tablas: cambiar el nombre de un documento se refleja inmediatamente, sin mantener otra copia.

## Índice sobre una expresión y EXPLAIN

MariaDB 10.4 permite indexar una columna generada. DIRI utiliza esta definición compatible:

```sql
palabra_normalizada VARCHAR(150)
    GENERATED ALWAYS AS (LOWER(TRIM(palabra))) STORED
```

Sobre esa columna calculada está el índice compuesto `idx_palabras_normalizada (palabra_normalizada, palabra)` en `palabras_clave`. La columna calculada está en `palabras_clave`, cuya clave primaria es `palabra`; depende de esa clave completa. La relación con los trámites solo conserva `(tramite_id, palabra)`. La expresión quita espacios de los extremos y convierte a minúsculas: `'  CARNET  '` genera `'carnet'`. El texto original no cambia y MariaDB recalcula el valor al insertar o editar un alias. Es un índice sobre una expresión implementado mediante una columna generada; los otros índices compuestos o UNIQUE no son por ello índices funcionales.

```sql
SHOW CREATE TABLE palabras_clave;
SHOW INDEX FROM palabras_clave;

EXPLAIN
SELECT palabra
FROM palabras_clave
WHERE palabra_normalizada = 'carnet';
```

Esta consulta muestra la parte indexada del buscador. El backend añade un JOIN con `palabras_clave_tramite` para obtener los trámites y priorizar coincidencias exactas:

```sql
SELECT DISTINCT p.tramite_id
FROM palabras_clave c
JOIN palabras_clave_tramite p ON p.palabra=c.palabra
WHERE c.palabra_normalizada = LOWER(TRIM('carnet'));
```

En la base local comprobada, `key` muestra `idx_palabras_normalizada` y `type` muestra `ref`. `possible_keys` enumera candidatos; `key` indica el elegido; `rows` es una estimación, no un conteo exacto ni una medición de tiempo. El optimizador puede escoger otro plan según los datos.

Para comparar los planes con los mismos datos sin borrar el índice:

```sql
EXPLAIN
SELECT palabra
FROM palabras_clave IGNORE INDEX (idx_palabras_normalizada)
WHERE palabra_normalizada = 'carnet';
```

El índice ayuda a la igualdad sobre `palabra_normalizada`. Las coincidencias parciales con `LIKE '%texto%'` siguen disponibles y este índice no acelera ese filtro. En MariaDB 10.4 el backend consulta explícitamente `palabra_normalizada`: escribir solamente `LOWER(TRIM(palabra))` en el WHERE no garantiza que el optimizador use el índice de la columna generada.

La prioridad utiliza las palabras de búsqueda después de retirar términos como «quiero» o «sacar». Si no hay alias exacto, se conserva el orden alfabético; todos los filtros de coincidencia parcial siguen vigentes. `DISTINCT` evita duplicados si un trámite tiene varios alias que se normalizan al mismo valor.

## Migración y comprobación

Para actualizar una instalación anterior, aplica 008 antes de 009; si 008 ya está aplicada, ejecuta solo la normalización pendiente. Desde la raíz del proyecto:

```powershell
& 'C:\xampp\php\php.exe' scripts/migrate-catalog-views.php --check
& 'C:\xampp\php\php.exe' scripts/migrate-catalog-views.php --apply
& 'C:\xampp\php\php.exe' scripts/migrate-normalization.php --check
& 'C:\xampp\php\php.exe' scripts/migrate-normalization.php --apply
& 'C:\xampp\php\php.exe' tests/catalog-views.php
```

En `migrate-catalog-views.php`, `--check` devuelve 0 si los objetos coinciden, 2 si hay cambios pendientes y 1 ante errores o definiciones incompatibles. Para comparar una vista existente con la definición normalizada por el servidor, crea una vista de comprobación con nombre aleatorio y la elimina al terminar; necesita permisos CREATE VIEW, SHOW VIEW y DROP. No modifica registros. Una interrupción abrupta del proceso puede dejar una vista `diri_check_*`, que debe revisarse antes de retirarla.

`--apply` crea solamente objetos ausentes, comprueba los existentes y registra `008_vistas_busqueda`. Repetirlo no duplica vistas ni índices ni reemplaza objetos incompatibles. El DDL hace COMMIT implícito: si falla un paso, no se promete revertir los anteriores. Se puede repetir tras resolver el error; un par columna/índice incompleto se rechaza para su revisión. No se debe reimportar `diri.sql` sobre una instalación existente. Tras aplicar 009, el comprobador 008 reconoce la estructura nueva y no intenta restaurar la columna eliminada. El comprobador de normalización no necesita crear vistas temporales.

Comprobado en MariaDB 10.4.32. Antes de aplicar en esta instalación se guardó un respaldo privado de los alias originales y su definición en `storage/backups/`. La migración 009 también guarda la estructura y los registros de todas las tablas antes del DDL. Véase [normalización en 3FN](normalizacion-3fn.md). Las pruebas agregan filas dentro de una transacción y las revierten; pueden avanzar los contadores AUTO_INCREMENT.
