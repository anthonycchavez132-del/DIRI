# Administración de trámites — fase 6

Esta fase permite listar, consultar, crear, editar y desactivar trámites. Solo las cuentas activas con rol `administrador` pueden acceder. No es todavía el panel administrativo completo de la fase 16.

## Habilitar el primer administrador

1. Registra tu cuenta desde `/registro`, si todavía no la tienes.
2. Desde la raíz del proyecto, ejecuta el siguiente comando **con el correo de tu propia cuenta**:

```powershell
& 'C:\xampp\php\php.exe' scripts/enable-admin.php 'tu-correo@ejemplo.com'
```

El comando solo funciona en consola y solo habilita el primer administrador activo. No crea usuarios, no cambia la contraseña ni el plan, no eleva una segunda cuenta y no tiene ruta web. Repetirlo para una cuenta que ya es administradora no modifica nada. Una cuenta inexistente o inactiva se rechaza.

Después inicia sesión y abre `http://localhost/DIRI/public/admin/tramites`. Si ya habías iniciado sesión, una nueva solicitud reflejará el rol actualizado. El enlace «Trámites» aparecerá en la navegación.

Durante el desarrollo no se habilitó automáticamente ninguna cuenta real: las pruebas utilizaron cuentas temporales que después se eliminaron.

## Rutas

| Método | Ruta | Acción |
|---|---|---|
| GET | `/admin/tramites` | Listado paginado y filtro por estado |
| GET | `/admin/tramites/ver?id=…` | Consulta completa de los campos generales |
| GET | `/admin/tramites/crear` | Formulario nuevo |
| POST | `/admin/tramites/crear` | Creación |
| GET | `/admin/tramites/editar?id=…` | Formulario con datos actuales |
| POST | `/admin/tramites/editar` | Actualización |
| GET | `/admin/tramites/desactivar?id=…` | Confirmación, sin cambiar datos |
| POST | `/admin/tramites/desactivar` | Desactivación |

Cada ruta exige sesión y rol; las escrituras exigen además CSRF. Los visitantes son redirigidos al login y los usuarios normales reciben 403. Las comprobaciones se aplican en el servidor, aunque alguien modifique el formulario o escriba directamente una dirección.

Los identificadores viajan en parámetros para conservar el enrutador sencillo. Se validan como enteros positivos dentro del rango de la clave de la tabla. Un identificador, página o filtro inválido devuelve 404. Las páginas muestran hasta 15 registros ordenados por actualización e identificador descendentes.

## Formulario y publicación

- Nombre: obligatorio, máximo 200 caracteres, sin saltos de línea ni caracteres de control.
- Slug: único, máximo 220 caracteres, letras ASCII minúsculas, números y guiones. Si está vacío se genera desde el nombre. No se cambia automáticamente un slug ya cumplimentado al modificar el nombre.
- Descripción: texto plano opcional en borrador, máximo 5000 caracteres. Es obligatoria para publicar.
- Categoría: una categoría activa de la base de datos.
- Estado: borrador, publicado o inactivo. Los nuevos formularios empiezan como borrador.
- Dificultad y tiempo estimado: opcionales; un dato desconocido queda como `NULL`.
- Fuente: seleccionada de las fuentes existentes. Su administración completa corresponde a la fase 10.
- Clasificación: pendiente, referencial o verificado con fuente oficial.
- Fecha de verificación: fecha válida, sin valores futuros. Es obligatoria para verificado.

No se publica información pendiente. Para publicar información referencial hacen falta descripción y fuente. Para marcar como verificado hacen falta fuente de tipo oficial y fecha de revisión. Las restricciones SQL de la fase 3 se mantienen como otra capa de integridad.

Seleccionar una fuente no verifica automáticamente su contenido: el administrador debe revisar la información antes de indicar su verificación. El sistema no consulta ni inventa requisitos, precios o procedimientos. Si no hay fuente adecuada, conserva el trámite como borrador pendiente.

Los valores recibidos se validan y se insertan con consultas preparadas. El contenido se trata como texto y se escapa al mostrarlo; no se permite interpretar HTML guardado en nombre o descripción.

## Conservación, auditoría y concurrencia

Desactivar cambia `estado` a `inactivo`. No borra el trámite, sus documentos, favoritos o seguimiento. El dashboard deja de mostrarlo porque filtra los trámites publicados. Para rehabilitarlo, un administrador puede editar su estado cumpliendo las mismas reglas de publicación.

Cada creación, actualización y desactivación efectiva se guarda junto con una fila de `auditoria_administrativa`, indicando actor, entidad, identificador y acción. Ambos cambios comparten transacción: si la auditoría falla, el cambio del trámite se revierte. No se copian contenidos privados, contraseñas o tokens a la auditoría. El comando de instalación inicial no registra un actor ficticio de la aplicación; la auditoría descrita corresponde a las acciones web autenticadas.

Los formularios incluyen una huella de la versión abierta. Al guardar se bloquea y consulta el registro con `FOR UPDATE`, se compara la huella y se escribe dentro de la transacción. Si otro administrador lo cambió, se devuelve 409 sin sobrescribirlo. La huella incluye los campos editables, por lo que no depende únicamente de una fecha con precisión de segundos. La comprobación también se aplica a desactivar.

Después de guardar se redirige al listado con un aviso, evitando repetir el envío al refrescar. Un duplicado de slug o un dato inválido devuelve 422 y conserva los valores introducidos.

## Arquitectura y archivos

```text
Ruta → AuthMiddleware → AdminMiddleware → CSRF (para POST)
     → AdminTramiteController → TramiteValidator / TramiteService
     → Tramite + Auditoria → PDO
```

Creados:

- `app/controllers/AdminTramiteController.php`
- `app/middleware/AdminMiddleware.php`
- `app/models/Tramite.php`, `Categoria.php`, `Fuente.php`, `Auditoria.php`
- `app/services/TramiteValidator.php`, `TramiteService.php`
- `app/views/admin/tramites/index.php`, `form.php`, `show.php`, `deactivate.php`
- `app/views/errors/403.php`, `409.php`
- `scripts/enable-admin.php`
- `tests/admin-tramites-flow.php`
- `docs/admin-tramites.md`

Modificados: `routes/web.php`, `app/views/layouts/main.php`, `public/css/app.css`, `README.md`, `docs/dashboard.md` y `database/README.md`.

El esquema continúa con 39 tablas. No se modificaron los borradores iniciales ni las credenciales de conexión.

## Pruebas

Con MariaDB activo y un servidor de desarrollo en el puerto 8087:

```powershell
# En una terminal:
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8087 -t public

# En otra terminal:
& 'C:\xampp\php\php.exe' tests/admin-tramites-flow.php
& 'C:\xampp\php\php.exe' tests/dashboard-flow.php
& 'C:\xampp\php\php.exe' tests/auth-flow.php
```

Para usar Apache local, agrega `http://127.0.0.1/DIRI/public` como argumento a cada prueba HTTP. La configuración de base debe corresponder al servidor que estás probando. Ejecuta las pruebas únicamente en desarrollo.

La prueba administrativa crea dos cuentas, un trámite y una fuente **explícitamente simulada** con dominio `.invalid`. Comprueba permisos, configuración inicial, paginación, CSRF, validaciones, duplicados, conservación de campos, clasificación y publicación, escape HTML, versiones desactualizadas, auditoría atómica y desactivación con relaciones. Elimina exclusivamente sus fixtures y contadores al terminar. Puede dejar saltos normales en los contadores `AUTO_INCREMENT`.

El flujo funcional se comprobó por HTTP. No hay navegador conectado para la revisión visual; las vistas incluyen estilos responsive, tabla con desplazamiento horizontal y etiquetas accesibles para su revisión posterior.

Desde la fase 7 puedes [gestionar requisitos y documentos](requisitos-documentos.md) desde el detalle del trámite. El contador de revisión también protege el formulario general frente a cambios de ese contenido.
