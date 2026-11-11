# Dashboard — fase 5

## Disponible

`GET /dashboard` abre «Mi espacio» para usuarios activos con sesión. Es el destino después de iniciar sesión y también al entrar en `/login` o `/registro` estando autenticado. El perfil de solo lectura continúa en `/perfil`.

El panel muestra nombre, plan actual, resumen de pendientes/favoritos/completados, trámites recientes, favoritos y progreso. Si no hay actividad, muestra ceros y mensajes de estado vacío. No utiliza datos de demostración.

El buscador está deshabilitado e identificado como próximo. No se implementa búsqueda ni se añaden acciones para guardar favoritos o cambiar el progreso. Esas acciones corresponden a las fases 11, 12 y posteriores. Las reglas de prestaciones Free/Premium corresponden a la fase 17; esta fase solo muestra el plan almacenado.

## Origen de los datos

| Sección | Consulta |
|---|---|
| Nombre y plan | Usuario activo y relaciones con roles/planes mediante `AuthService` |
| Pendientes | Seguimientos de la cuenta cuyo estado no sea completado |
| Favoritos | Favoritos de la cuenta |
| Completados | Seguimientos de la cuenta en estado completado |
| Trámites recientes | Últimos cinco seguimientos, ordenados por fecha de actualización e identificador descendentes |
| Lista de favoritos | Últimos cinco favoritos por fecha de creación e identificador de trámite descendentes |
| Mi progreso | Últimos cinco seguimientos pendientes, incluidos pausados |

Todos los conteos y listas excluyen trámites que no estén publicados. Los totales abarcan todos los registros visibles de la cuenta, aunque cada lista esté limitada a cinco.

«Recientes» representa actualizaciones de seguimiento, no visitas ni búsquedas. Consultar el panel no escribe en el historial y no crea ni modifica seguimientos. No se calcula un porcentaje ni se consulta la checklist, que todavía no tiene interfaz de edición.

La fecha de actualización se interpreta como UTC y se convierte a `America/La_Paz` antes de mostrarla. Los títulos, categorías, nombre y plan se escapan como HTML.

## Arquitectura

```text
GET /dashboard
  → AuthMiddleware
  → DashboardController
  → Dashboard::forUser(id de la sesión)
  → app/views/dashboard/index.php
```

La identidad viene de la sesión validada. Parámetros como `usuario_id` o `user_id` en la URL no cambian la cuenta consultada. Las consultas utilizan PDO y parámetros preparados. Una cuenta bloqueada o desactivada pierde acceso en su siguiente solicitud.

No se modificó la base de datos ni se añadieron semillas. Los veinte títulos iniciales siguen en borrador. Por eso una cuenta nueva no muestra trámites ni favoritos hasta que existan datos publicados y actividad propia.

## Archivos de esta fase

Creados:

- `app/controllers/DashboardController.php`
- `app/models/Dashboard.php`
- `app/views/dashboard/index.php`
- `tests/dashboard-flow.php`
- `tests/support/HttpTest.php` (ayudantes extraídos del test de autenticación)
- `docs/dashboard.md`

Modificados:

- `app/controllers/AuthController.php`: destino del login y página activa de perfil.
- `app/helpers/functions.php`: formato de fecha local.
- `routes/web.php`: ruta protegida.
- `app/views/layouts/main.php`: navegación de cuenta y página activa.
- `app/views/auth/login.php` y `profile.php`: textos y acceso al dashboard.
- `public/css/app.css`: estilos del panel y adaptación de navegación móvil.
- `tests/auth-flow.php`: ayudantes compartidos y nueva redirección esperada.
- `README.md` y `docs/authentication.md`: estado actual e instrucciones.

## Pruebas

Con MariaDB iniciado y `diri` instalada, inicia un servidor en una terminal:

```powershell
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8087 -t public
```

En otra terminal:

```powershell
& 'C:\xampp\php\php.exe' tests/dashboard-flow.php
& 'C:\xampp\php\php.exe' tests/auth-flow.php
```

Para probar contra Apache de XAMPP, agrega `http://127.0.0.1/DIRI/public` como argumento a cada comando. El destino y la configuración de base deben corresponder a esta instalación local de desarrollo.

El test del dashboard crea dos cuentas y seis trámites temporales, además de favoritos y seguimientos. Comprueba acceso, estados vacíos, aislamiento, filtros, conteos, orden, escape HTML, plan dinámico, fechas y revocación de cuentas bloqueadas. Elimina exclusivamente sus fixtures y contadores al terminar, incluso si falla. Los contadores `AUTO_INCREMENT` pueden avanzar. No se guardan requisitos, precios ni procedimientos oficiales.

Se aprobaron las 17 comprobaciones del dashboard y la regresión completa de autenticación. Los 40 archivos PHP pasan la revisión de sintaxis. No había navegador conectado para revisar visualmente el resultado; el CSS tiene reglas para escritorio, tablet y móvil, pendientes de confirmar visualmente.

## Prueba manual

1. Inicia Apache y MySQL en XAMPP.
2. Abre `http://localhost/DIRI/public/login` e inicia sesión con tu cuenta.
3. Comprueba el saludo, plan y estados vacíos en `/dashboard`.
4. Abre «Mi perfil», vuelve a «Mi espacio» y cierra sesión.
5. Intenta abrir `/dashboard`: debe redirigirte al login.

Desde la fase 6 está disponible el [CRUD administrativo de trámites](admin-tramites.md). Las acciones de favoritos y seguimiento continúan pendientes de sus fases correspondientes.
