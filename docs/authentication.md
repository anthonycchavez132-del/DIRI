# Autenticación — fase 4

## Funciones disponibles

| Método | Ruta | Acceso | Resultado |
|---|---|---|---|
| GET | `/registro` | Visitante | Formulario de cuenta |
| POST | `/registro` | Visitante + CSRF | Crea usuario/free y redirige al login |
| GET | `/login` | Visitante | Formulario de acceso |
| POST | `/login` | Visitante + CSRF | Inicia sesión y redirige al dashboard |
| GET | `/perfil` | Usuario activo | Datos de cuenta, plan y rol |
| POST | `/logout` | Usuario activo + CSRF | Destruye sesión y vuelve al login |

Las rutas se añaden al prefijo de instalación: con XAMPP, `/login` corresponde a `http://localhost/DIRI/public/login`.

El perfil es de solo lectura. Desde la fase 5 está disponible el [dashboard protegido](dashboard.md) en `/dashboard`, destino tras iniciar sesión. Un usuario autenticado que abra `/login` o `/registro` también es redirigido allí. No hay recuperación de contraseña, verificación de correo, edición de perfil, cambio de plan ni administración de usuarios en esta fase. No existe una contraseña administrativa predeterminada.

## Organización del código

```text
Router → CSRF / AuthMiddleware → AuthController
                                  ↓
                          AuthValidator / AuthService
                                  ↓
                                 User
                                  ↓
                                 PDO
```

- `Session` configura cookies, caducidad, renovación y destrucción.
- `Csrf` genera tokens aleatorios y compara con `hash_equals()`.
- `AuthMiddleware` exige un usuario activo para las rutas protegidas.
- `CsrfMiddleware` exige el token de sesión antes de ejecutar las acciones POST.
- `AuthValidator` normaliza correo y valida nombre, apellido y contraseñas.
- `AuthService` verifica hashes e inicia o cierra sesión.
- `User` contiene las consultas preparadas. El rol `usuario` y el plan `free` se seleccionan en el servidor por nombre.
- `LoginLimiter` limita las verificaciones de contraseña por pareja correo/IP, independientemente de la cookie.

No se cambió el esquema SQL de la fase 3. Los registros utilizan las tablas y relaciones existentes. El nombre del rol y el plan se consultan desde la base, no se deducen de los identificadores enviados por el navegador.

## Validaciones y respuestas

Nombre y apellido admiten letras Unicode, espacios, apóstrofos, puntos y guiones, con un máximo de 100 caracteres. El correo se recorta, se convierte a minúsculas y se valida con `FILTER_VALIDATE_EMAIL` (máximo 254 caracteres).

Las contraseñas no se recortan ni se normalizan. Se exigen al menos ocho caracteres UTF-8 y un máximo de 72 bytes para evitar truncamiento de bcrypt en PHP 8.0. Se rechazan bytes nulos. Solo se guarda el resultado de `password_hash()` y se comprueba con `password_verify()`. Al iniciar sesión se actualiza el hash si `password_needs_rehash()` lo requiere. Nunca se devuelven contraseñas o hashes en el perfil ni en los formularios de error.

- `303`: redirección después de registro, login o logout, o hacia el login si falta sesión.
- `401`: credenciales incorrectas, cuenta inexistente o cuenta inactiva/bloqueada, con el mismo mensaje.
- `405`: método incorrecto, con cabecera `Allow`.
- `419`: token ausente, incorrecto o de una sesión anterior.
- `422`: validación de campos o registro duplicado.
- `429`: cinco verificaciones consumidas por correo/IP en quince minutos. Se envía `Retry-After: 900`.

El límite se guarda bajo `storage/cache/login-<hash>.json`, con bloqueo de archivo para actualizar el contador. La clave combina correo normalizado e IP directa de la conexión; no se confía en cabeceras de proxy. El contador se reinicia después de un login correcto y al vencer su ventana. Esta primera versión no establece un límite global por IP ni una política distribuida entre servidores.

## Sesiones

- Cookie `DIRISESSID`, de sesión, `HttpOnly`, `SameSite=Lax` y limitada al prefijo público de DIRI.
- `Secure` cuando Apache indica HTTPS o el puerto del servidor es 443. En HTTP local no se activa para permitir probar XAMPP.
- Modo estricto, sin identificadores de sesión en las URL.
- Identificador y CSRF renovados al autenticar.
- Caducidad tras 30 minutos de inactividad o 12 horas de duración total.
- La sesión guarda el identificador del usuario; los datos públicos, rol, plan y estado activo se vuelven a consultar en la base en cada solicitud autenticada.
- Desactivar o bloquear una cuenta revoca el acceso en su siguiente solicitud.
- Logout borra el estado de sesión y expira la cookie. GET no puede cerrar sesión.

El registro de errores web usa clase y código de excepción; no imprime trazas con argumentos que puedan contener credenciales. Las páginas dinámicas envían `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` y `Referrer-Policy: same-origin`.

## Probar manualmente

1. Inicia Apache y MySQL en XAMPP.
2. Abre `http://localhost/DIRI/public/registro` y crea una cuenta con tu correo y una contraseña propia.
3. Comprueba que se muestra el login después del registro.
4. Inicia sesión: verás tu dashboard. Abre «Mi perfil» para consultar nombre, correo, rol Usuario y plan Free.
5. Pulsa «Cerrar sesión». Abrir `/perfil` después debe redirigir al login.

Para comprobar que las validaciones también se aplican en el servidor, utiliza las pruebas HTTP. Alterar el HTML de un formulario no permite seleccionar el rol o el plan.

## Pruebas automatizadas

Desde la raíz, inicia un servidor de desarrollo en una terminal:

```powershell
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8087 -t public
```

Con MariaDB iniciado y `diri` instalada, ejecuta en otra terminal:

```powershell
& 'C:\xampp\php\php.exe' tests/auth-flow.php
& 'C:\xampp\php\php.exe' tests/session-security.php
```

También puedes ejecutar la prueba HTTP contra el Apache local:

```powershell
& 'C:\xampp\php\php.exe' tests/auth-flow.php http://127.0.0.1/DIRI/public
```

La prueba HTTP solo admite destinos locales. Registra una cuenta con identificador aleatorio y dominio reservado `.invalid`, comprueba el ciclo completo, y elimina exclusivamente su cuenta y contadores al terminar, incluso si falla. Necesita la misma base configurada que usa la aplicación. Puede avanzar contadores `AUTO_INCREMENT`. Úsala en desarrollo.

La prueba de sesiones utiliza una carpeta temporal independiente y no abre sesiones reales. Comprueba modo estricto, tokens, renovación, caducidad y destrucción, y limpia sus archivos al terminar.

El flujo se comprobó por HTTP local. No había navegador conectado para una revisión visual de los formularios. El CSS incluye adaptaciones para móvil; su presentación visual sigue pendiente de comprobar.
