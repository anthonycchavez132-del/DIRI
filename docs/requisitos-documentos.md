# Requisitos y documentos — fase 7

## Disponible

Desde «Trámites» → «Ver» → «Gestionar requisitos y documentos» puedes crear y editar requisitos, asociar documentos existentes y modificar sus condiciones particulares. El catálogo de documentos reutilizables permite crear y editar su información compartida.

Todas las pantallas y escrituras requieren administrador activo. Las peticiones POST requieren CSRF. Los requisitos se consultan con su trámite: un identificador de otro trámite devuelve 404. Los valores se validan en el servidor y se muestran escapados como texto.

Las acciones de esta fase son agregar y editar. Se conservan los registros y sus relaciones; no se implementó eliminación de requisitos, documentos o asociaciones.

## Separación de datos

| Tabla | Información |
|---|---|
| `requisitos` | Nombre, descripción, obligatoriedad, observaciones, fuente, clasificación y fecha de revisión de un requisito de un trámite |
| `documentos` | Nombre único, descripción, fuente y fecha de revisión del documento compartido |
| `tramite_documentos` | Obligatoriedad, cantidad, presentación, observaciones, fuente y revisión específicas del trámite |

Editar las condiciones de un trámite no cambia las de otro. Editar el catálogo actualiza el nombre, descripción y evidencia compartidos, conservando cantidades, presentaciones, obligatoriedad y progreso existentes. El formulario informa cuántos trámites utilizan el documento.

No se copian fuentes o fechas desde el catálogo a las condiciones particulares. La obligatoriedad exige una selección explícita; cantidad, presentación y evidencia desconocidas quedan como `NULL`. No se cargaron requisitos oficiales ni documentos de ejemplo.

## Revisión y publicación

Cuando cambia un requisito o asociación, el trámite afectado queda pendiente de revisión. Si estaba publicado, vuelve a borrador. Los inactivos permanecen inactivos. Cambiar un documento compartido aplica esta regla a todos sus trámites. Guardar los mismos valores no provoca cambios ni auditoría adicional.

Para publicar información referencial se necesitan requisitos revisados con fuente y fuentes para los documentos del catálogo y sus condiciones. Para publicar el conjunto como verificado se exige evidencia oficial y fecha de revisión tanto en los requisitos como en los documentos y sus condiciones, además de la información general del trámite.

Seleccionar una fuente o registrar una fecha no comprueba su contenido automáticamente. El administrador sigue siendo responsable de revisar la evidencia. Si no dispone de respaldo, debe mantener la información pendiente.

## Concurrencia y auditoría

Las ediciones usan huellas del registro abierto para rechazar versiones antiguas con 409. Las escrituras, la marca de revisión de los trámites y la auditoría comparten transacción. Un fallo de auditoría revierte todos esos cambios.

Los documentos se bloquean antes de sus trámites asociados; estos se bloquean en orden de identificador. Los requisitos se bloquean junto con su trámite. Esta organización evita que una asociación nueva quede fuera de una edición compartida concurrente.

El contador `tramites.revision` aumenta cuando se modifica el trámite o su contenido. Se incluye en la huella del formulario general, para impedir que una edición abierta antes de modificar requisitos o documentos publique una versión antigua del conjunto.

## Migración

La migración ya se aplicó en este entorno. Añade únicamente `tramites.revision`, un entero sin signo con valor inicial 1, y registra `007_revision_contenido`. No borra registros ni reinicia tablas.

En una instalación existente de fases anteriores:

```powershell
& 'C:\xampp\php\php.exe' scripts/migrate-content.php
```

El comando inspecciona la columna y su definición; repetirlo no duplica la columna ni la versión. El DDL de MariaDB no es una transacción reversible: si se interrumpe entre añadir la columna y registrar la versión, repetir el comando completa el registro. La alternativa SQL de una sola ejecución está en `database/migrations/007_revision_contenido.sql`.

Para una instalación nueva, `database/diri.sql` ya incluye la columna y las versiones inicial y 007. No vuelvas a importar el instalador sobre una base existente. El esquema mantiene 39 tablas.

## Rutas

| Grupo | Rutas |
|---|---|
| Contenido de trámite | GET `/admin/tramites/contenido?tramite_id=…` |
| Requisitos | GET/POST `/admin/requisitos/crear` y `/admin/requisitos/editar` |
| Catálogo | GET `/admin/documentos` |
| Documentos reutilizables | GET/POST `/admin/documentos/crear` y `/admin/documentos/editar` |
| Condiciones de documentos | GET/POST `/admin/tramites/documentos/asociar` y `/admin/tramites/documentos/editar` |

Requisitos y condiciones incluyen `tramite_id`. Editar un requisito o documento global requiere `id`; editar condiciones requiere `documento_id`. Los formularios incluyen contexto, versión cuando corresponde y CSRF. El tipo de operación se determina por la ruta.

## Archivos

Creados:

- `app/controllers/AdminContenidoController.php`
- `app/models/Requisito.php`, `Documento.php`, `TramiteDocumento.php`
- `app/services/ContenidoValidator.php`, `ContenidoService.php`
- `app/views/admin/contenido/index.php`, `catalog.php`, `form.php`
- `database/migrations/007_revision_contenido.sql`
- `scripts/migrate-content.php`
- `tests/contenido-flow.php`
- `docs/requisitos-documentos.md`

Modificados: instalador SQL, modelo/auditoría de trámites, servicio y validador de trámites, controlador administrativo de trámites, helpers de fecha, rutas, vista de detalle, error 409, CSS y documentación relacionada.

## Probar

Con Apache y MySQL iniciados, entra como administrador, consulta un trámite y abre «Gestionar requisitos y documentos». Crea primero los tipos de documento en el catálogo y después asócialos al trámite. Al editar un documento compartido, revisa sus trámites antes de republicarlos.

Pruebas automatizadas con un servidor local en el puerto 8087:

```powershell
# Terminal 1:
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8087 -t public

# Terminal 2:
& 'C:\xampp\php\php.exe' tests/contenido-flow.php
& 'C:\xampp\php\php.exe' tests/admin-tramites-flow.php
& 'C:\xampp\php\php.exe' tests/dashboard-flow.php
& 'C:\xampp\php\php.exe' tests/auth-flow.php
```

Puedes agregar `http://127.0.0.1/DIRI/public` como argumento para probar contra Apache. Servidor HTTP y configuración de la prueba deben usar la misma base local de desarrollo.

El test de contenido usa dos cuentas, dos trámites, un documento y una fuente oficial **simulada** con dominio reservado `.invalid`. Comprueba permisos, CSRF, validación, versiones, fuentes, reutilización, independencia de condiciones, conservación del progreso, revisión de publicaciones y rollback. Sus fixtures y contadores se eliminan al terminar. Los contadores `AUTO_INCREMENT` pueden avanzar. No ejecutarlo contra producción.

El checklist editable para usuarios pertenece a la fase 12. Próxima fase, después de confirmación: instituciones y sucursales.

## Verificación realizada

- 67 archivos PHP sin errores de sintaxis.
- Flujo HTTP de contenido y regresiones de trámites, dashboard, autenticación e integridad del esquema aprobados.
- Migración repetible comprobada en la instalación existente e instalador SQL comprobado en una base temporal nueva, eliminada al terminar.
- Sin cuentas, trámites, requisitos, documentos, asociaciones ni fuentes de prueba conservados.
- Revisión visual pendiente: no había navegador conectado. Las fechas de revisión se muestran como día/mes/año, sin aplicar conversión horaria a una fecha de calendario.
