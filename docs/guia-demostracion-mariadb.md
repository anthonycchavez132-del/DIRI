# Guía sencilla para demostrar DIRI

Usa tres ventanas: terminal de MariaDB, editor con PHP y navegador con DIRI. La evidencia de SQL, vistas, índices y tablas se muestra en terminal y código; los formularios y botones se demuestran en la aplicación.

## 1. Entrar a MariaDB

Inicia MySQL y Apache en XAMPP. En PowerShell:

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u root -p
```

Usa tu usuario de MariaDB si es distinto de `root`. Es la contraseña de MariaDB, no la del administrador de DIRI. Si tu root local no tiene contraseña, presiona Enter cuando la solicite. Al ver `MariaDB ...>`, escribe los siguientes comandos SQL, terminando cada uno con punto y coma.

```sql
USE diri;
SELECT DATABASE() AS base_actual;
```

## 2. Mostrar tablas, vistas y registros

```sql
SHOW FULL TABLES;

SELECT COUNT(*) AS cantidad_tablas
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE';

SELECT id, nombre, estado FROM tramites ORDER BY id LIMIT 5;
SELECT id, nombre FROM documentos ORDER BY id LIMIT 5;
SELECT COUNT(*) AS cantidad_tramites FROM tramites;
```

La estructura actual tiene 40 tablas y dos vistas. Al preparar esta guía había veinte trámites. `BASE TABLE` evita contar las vistas como tablas físicas.

Para mostrar la conexión de la aplicación abre `app/core/Database.php`, método `connection`: configura PDO y devuelve la conexión. Abre `app/models/Tramite.php`, método `page`: usa esa conexión, prepara la consulta y la ejecuta. Entra a Administración → Trámites para activar esa lectura. No hace falta mostrar contraseñas ni el contenido privado de `config/database.php`.

## 3. Consulta con JOIN

```sql
SELECT t.nombre AS tramite, c.nombre AS categoria
FROM tramites t
JOIN categorias c ON c.id=t.categoria_id
LIMIT 5;
```

Di: «El JOIN reúne el trámite con el nombre de su categoría usando la clave foránea». La versión utilizada por el listado administrativo está en `Tramite::page`, con filtros, paginación y parámetros adicionales.

## 4. Subconsulta real del backend

```sql
SELECT d.nombre,
       (SELECT COUNT(*)
        FROM tramite_documentos td
        WHERE td.documento_id=d.id) AS usos
FROM documentos d
ORDER BY d.nombre;
```

Di: «Para cada documento, la consulta interna cuenta cuántos trámites lo utilizan». Abre `app/models/Documento.php`, método `all`, donde está esta misma lógica. Entra a Administración → Documentos reutilizables: `AdminContenidoController::catalog` llama a ese método. Muestra `prepare/execute` en otros métodos del mismo modelo para explicar las consultas parametrizadas; `all` usa `query` porque su SQL no contiene entradas del usuario.

## 5. Vistas SQL

```sql
SHOW FULL TABLES WHERE Table_type='VIEW';
SELECT * FROM vista_tramites_publicados LIMIT 5;
SELECT * FROM vista_documentos_tramite LIMIT 5;
SHOW CREATE VIEW vista_tramites_publicados;
```

Si la definición es difícil de leer en terminal, sustituye el último punto y coma por `\G` para mostrarla verticalmente. Di: «Una vista guarda una consulta sobre tablas existentes; no copia sus registros». La primera une trámites y categorías y filtra la publicación. La segunda reúne documentos y condiciones por trámite.

Muestra su uso en `CatalogService::detail`, `DatabaseSearch::search` y `TramiteDocumento::forTramite`.

## 6. Índice sobre una expresión y EXPLAIN

```sql
SHOW CREATE TABLE palabras_clave;
SHOW INDEX FROM palabras_clave;

EXPLAIN
SELECT palabra FROM palabras_clave
WHERE palabra_normalizada='carnet';

EXPLAIN
SELECT palabra FROM palabras_clave
IGNORE INDEX (idx_palabras_normalizada)
WHERE palabra_normalizada='carnet';
```

Di: «MariaDB calcula `LOWER(TRIM(palabra))` en una columna generada y esa columna tiene un índice. `key` muestra el índice elegido, `type` el tipo de acceso y `rows` la cantidad estimada de filas a revisar».

En la base local comprobada, la primera consulta eligió `idx_palabras_normalizada` con acceso `ref`; al ignorarlo, el plan mostró `ALL` y `key=NULL`. No se elimina el índice para comparar. Las decisiones dependen de los datos y del optimizador; `rows` no es tiempo de ejecución ni una cantidad exacta.

Abre `DatabaseSearch::search`: la subconsulta `exactas` consulta el catálogo normalizado y lo une con sus asociaciones a trámites. El índice ayuda a coincidencias exactas, no al filtro parcial `LIKE '%texto%'`.

## 7. Normalización hasta 3FN

```sql
DESCRIBE usuarios;
DESCRIBE tramite_documentos;
DESCRIBE paso_documentos;
SHOW CREATE TABLE tramite_documentos;
```

Di: «En usuarios guardo `rol_id` y `plan_id`, mientras sus datos están en los catálogos. Los documentos están separados del trámite y la tabla puente guarda las condiciones de cada pareja. En `paso_documentos` no repito `tramite_id`: ya se obtiene del paso».

1FN: sin grupos de columnas repetidas ni listas de IDs. 2FN: cada atributo depende de la clave completa. 3FN: sin dependencias transitivas entre atributos no clave según las reglas del modelo. Consulta `docs/normalizacion-3fn.md` para el análisis completo; las FK por sí solas no demuestran 3FN.

## 8. Demostrar veinte tablas programadas

La mitad de 40 es 20. No basta con enseñar sus nombres: abre la configuración de recursos y sus operaciones PHP.

| Tablas | Dónde mostrar el código |
| --- | --- |
| `tramites`, `requisitos`, `documentos`, `tramite_documentos` (4) | Modelos `Tramite`, `Requisito`, `Documento`, `TramiteDocumento`; `AdminContenidoController`, `ContenidoService` |
| `categorias`, `fuentes`, `instituciones`, `sucursales`, `horarios`, `excepciones_horario`, `tramite_sucursales`, `pasos_tramite`, `paso_documentos`, `paso_dependencias`, `costos`, `paso_costos`, `rutas`, `ruta_tramites`, `ruta_dependencias`, `usuarios` (16) | Entradas de `config/resources.php`; métodos `rows`, `find`, `save` de `app/models/Resource.php`; `AdminResourceController` y `ResourceService` |

Son veinte tablas distintas. La implementación genérica utiliza una lista permitida de recursos; no acepta tablas arbitrarias. Demuestra una lectura y una escritura de un recurso para explicar cómo se ejecuta el mismo mecanismo con cada configuración. Usuarios permite lectura, edición y cambio de estado; la creación administrativa está deshabilitada. No presentes todos los recursos como CRUD completo si sus operaciones están restringidas.

## 9. Formularios, funcionamiento y eliminación lógica

Estas comprobaciones se hacen con los botones de DIRI y se respaldan mostrando el código y la BD:

1. Entra como administrador. Abre Crear trámite e intenta enviarlo con un nombre requerido vacío. Muestra `TramiteValidator::validate` para demostrar validación en servidor además del navegador.
2. Crea un registro propio para la prueba, en borrador, con un slug que no exista; por ejemplo `practica-diri`. Consulta ese slug en MariaDB para comprobar que se guardó mediante el formulario.
3. Edita su nombre desde DIRI y repite el SELECT. Luego usa Desactivar y confirma.

```sql
SELECT id, nombre, slug, estado
FROM tramites
WHERE slug='practica-diri';
```

Después de desactivar, la fila debe seguir existiendo y `estado` ser `inactivo`. Abre `Tramite::deactivate`: ejecuta UPDATE, no DELETE. En el panel puedes mostrar el registro con el filtro de inactivos. Si usaste otro slug, sustitúyelo en el SELECT. No desactives una ficha oficial para esta prueba.

4. En gestión de costos intenta un monto negativo y muestra `ResourceValidator::validate`, que rechaza valores fuera de rango. En SQL, `SHOW CREATE TABLE costos` muestra además su CHECK de monto no negativo. Si el navegador bloquea el envío, explica esa capa y señala también la validación PHP.
5. Busca «carnet», abre una ficha y su ruta sencilla. En la ruta personalizada de licencia, marca que ya tienes certificado médico vigente y verifica que la orientación omita obtenerlo nuevamente cuando corresponda. Esa personalización beta usa sesión; no afirmes que comprueba el certificado con una API oficial ni que crea registros persistentes por cada selección.

Para terminar escribe `exit;` en MariaDB. No necesitas reimportar el esquema, crear más tablas ni ejecutar migraciones durante la exposición.
