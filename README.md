# DIRI

Aplicación web para preparar trámites en La Paz. La persona elige un trámite, indica su ubicación y marca los documentos vigentes que ya tiene. DIRI incorpora la obtención de los requisitos pendientes al recorrido y organiza la visita final.

Esta es la versión reconstruida del proyecto, desarrollada con PHP estricto y MVC propio. Las versiones anteriores y sus contribuciones permanecen en el historial de Git.

## Tecnologías

- PHP 8.0 o superior, con PDO MySQL, cURL y mbstring.
- MySQL/MariaDB y Apache mediante XAMPP.
- HTML, JavaScript y CSS propio, sin frameworks.
- OpenStreetMap y el servicio peatonal OSRM de FOSSGIS para el mapa y las conexiones a pie.
- Enlaces de Google Maps para abrir las paradas en el orden planificado.

## Instalación en XAMPP

1. Iniciar Apache y MySQL.
2. Clonar el repositorio en una carpeta llamada **DIRI2**:

   ```bash
   git clone https://github.com/anthonycchavez132-del/DIRI.git C:/xampp/htdocs/DIRI2
   ```

3. Importar `diri2.sql` desde phpMyAdmin en una instalación nueva, con la base `diri2` vacía. El archivo contiene la estructura, los triggers de auditoría y el catálogo; no contiene cuentas personales ni las auditorías de la instalación original.
4. Revisar `modelos/Conexion.php`: la configuración local usa MySQL en `127.0.0.1:3306`, base `diri2`, usuario `root` y contraseña vacía. Adaptarla si XAMPP utiliza otras credenciales.
5. Abrir **http://localhost/DIRI2/**. Si Apache usa otro puerto, incluirlo en la dirección.
6. Crear una cuenta desde el formulario de registro. Las cuentas nuevas tienen rol `usuario`.

Para habilitar un administrador en tu propia instalación, después de registrar su cuenta, ejecutar en phpMyAdmin sustituyendo el correo:

```sql
UPDATE usuarios
SET rol = 'administrador'
WHERE correo = 'correo-de-tu-cuenta';
```

El nombre local `DIRI2` corresponde a la ruta de las cookies de sesión configurada en `index.php`. El repositorio público conserva el nombre DIRI.

## Funcionalidad actual

- Registro e inicio de sesión, contraseñas almacenadas con `password_hash` y comprobadas con `password_verify`.
- Roles de usuario y administrador, protección CSRF y validaciones del servidor.
- Administración de usuarios con confirmación y eliminación lógica; se bloquea el acceso de las cuentas inactivas.
- Catálogo con diez trámites básicos y fuentes consultadas.
- Gestión administrativa de trámites, documentos, establecimientos, gestiones y horarios; desactivación lógica de registros.
- Selección de documentos disponibles para omitir pasos de obtención.
- Planificación de visitas con dependencias, esperas entre días y horarios configurados.
- La salida se establece automáticamente con la fecha y hora actuales de La Paz al calcular el recorrido.
- Comparación de recorridos: menor distancia y tiempo estimado como desempate. La búsqueda tiene un límite de tiempo y avisa si no completó toda la comparación.
- Mapa con paradas numeradas y enlaces a Google Maps por tramos y visitas.
- Interfaz adaptable a celulares y hojas CSS separadas por responsabilidad.
- Navegación interna con botones para volver a los listados, ir al inicio y continuar los procesos mediante sus formularios. Las acciones de guardar y eliminar no se repiten al utilizar estos enlaces de regreso.

Los casos especiales se indican en la descripción de cada trámite. Los lugares sin horario publicado requieren confirmación; los tiempos de atención no registrados hacen que la finalización sea una estimación mínima. El mapa y el cálculo peatonal requieren conexión a Internet. Google Maps recalcula los caminos al abrir sus enlaces.

## Organización MVC

```text
DIRI2/
  index.php          Entrada y selección de controladores
  controladores/     Recepción de solicitudes, validaciones y respuestas
  modelos/           Conexión, consultas y reglas del recorrido
  vistas/            Pantallas HTML y su presentación
  publico/           Estilos generales y por módulo
  diri2.sql          Estructura y catálogo para una instalación nueva
```

La base contiene diez tablas: `usuarios`, `tramites`, `documentos`, `tramite_documentos`, `gestiones`, `gestion_dependencias`, `establecimientos`, `gestion_establecimientos`, `horarios` y `auditorias`.

## Validaciones y seguridad

- **Validaciones:** `controladores/UsuarioController.php`, método `registro()`: nombres, correo, longitud de contraseña, confirmación y correo duplicado. El servidor rechaza entradas inválidas aunque se evite la validación HTML.
- **Datos personales:** nombres y apellidos admiten letras, espacios, guiones y apóstrofes; el apellido materno es opcional. Se rechazan tipos de entrada incorrectos y textos con codificación inválida.
- **Inicio de sesión:** correo válido, contraseña obligatoria, verificación del hash y cuenta activa. Las credenciales incorrectas y las cuentas inactivas reciben un mensaje general. Al iniciar sesión se renuevan el identificador de sesión y el token CSRF.
- **Eliminación de usuarios:** el administrador selecciona una cuenta y confirma la acción. El servidor exige POST, CSRF válido, confirmación explícita e identificador válido. Vuelve a comprobar el permiso del responsable dentro de una transacción, impide la eliminación de la propia cuenta y conserva al menos un administrador activo. La cuenta queda inactiva y el trigger registra el cambio.
- **Sesiones existentes:** cada solicitud protegida consulta el estado y el rol actuales en la base de datos; una cuenta desactivada pierde acceso aunque conserve una sesión anterior.
- **Otros formularios:** los controladores validan identificadores, longitudes, estados y permisos. También se comprueban las coordenadas y fuentes de los establecimientos, los solapamientos de horarios y las dependencias entre gestiones.
- **Tipos estrictos:** `declare(strict_types=1)` en los archivos PHP. `modelos/Usuario.php`, método `crear()`, declara parámetros `string`, un apellido opcional `?string` y retorno `int`.
- **Estructuras de datos:** `modelos/Recorrido.php` usa arreglos asociativos para documentos y gestiones, matrices de distancias y duraciones, y relaciones de dependencias que representan un grafo dirigido. `explorar()` recorre alternativas de forma recursiva respetando sus requisitos previos.
- **Persistencia:** `modelos/Usuario.php` utiliza consultas preparadas PDO para insertar y consultar usuarios.
- **Auditorías:** los triggers de las tablas de negocio registran cambios en `auditorias`. Las contraseñas no se incluyen en el contenido de esas auditorías.

## Integrantes

- Anthony Chavez Coronado.
- Sebastian Peñarrieta Peña.
- Abel Daniel Osco Aquino.
