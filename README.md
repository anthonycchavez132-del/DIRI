# DIRI

**Tu guía para realizar trámites en Bolivia.** Aplicación web que reúne requisitos, documentos, pasos, costos y fuentes para ayudar a las personas a preparar sus gestiones. Los procedimientos municipales del catálogo toman como referencia La Paz.

Proyecto académico desarrollado con PHP, arquitectura MVC y acceso a MariaDB mediante PDO.

## Funcionalidades

- Búsqueda de trámites por nombre, palabras clave y expresiones habituales.
- Fichas con requisitos, documentos, pasos, costos y fuentes.
- Veinte guías sencillas beta de acceso público.
- Personalización gratuita de primera licencia de conducir y renovación, indicando documentos disponibles y vencimientos conocidos.
- Registro, inicio de sesión, perfil y espacio personal.
- Organización Premium con favoritos, checklist persistente, biblioteca, historial y rutas guardadas.
- Administración de trámites, instituciones, sucursales, horarios, documentos, fuentes, usuarios y planes.
- Validación de formularios, protección CSRF, permisos por rol, desactivación lógica y auditoría.
- Reportes de atención moderados y comparación de sucursales cuando existen datos suficientes y vigentes.
- API JSON pública de consulta.

El catálogo incluye un lote revisado el **21 de octubre de 2026**, con 11 fichas verificadas y 9 referenciales. Cada ficha identifica sus fuentes y su estado de verificación. Esa revisión corresponde a la fecha indicada.

## Tecnologías y requisitos

| Componente | Tecnología |
| --- | --- |
| Backend | PHP 8.0 o superior, MVC |
| Base de datos | MariaDB 10.4, PDO MySQL |
| Interfaz | HTML, CSS y JavaScript |
| Servidor local | Apache con XAMPP o servidor integrado de PHP |
| Datos | UTF-8, InnoDB y esquema normalizado en 3FN |

La instalación se comprobó con MariaDB 10.4.32 de XAMPP. PHP necesita las extensiones `pdo_mysql` y `mbstring`. Apache requiere `mod_rewrite` y `AllowOverride All` para las rutas.

## Instalación local con XAMPP

### 1. Clonar el repositorio

Desde PowerShell en Windows, con Git instalado:

```powershell
Set-Location C:\xampp\htdocs
git clone https://github.com/anthonycchavez132-del/DIRI.git
Set-Location DIRI
```

### 2. Configurar la conexión

```powershell
Copy-Item config/database.example.php config/database.php
```

Edita `config/database.php` con el host, puerto, usuario y contraseña de tu MariaDB. Conserva `diri` como nombre de base para utilizar el SQL incluido. Adapta los valores del ejemplo a tu entorno.

### 3. Instalar la base de datos

Inicia MySQL/MariaDB en XAMPP. En una instalación nueva, ejecuta el siguiente comando con una cuenta que tenga permisos para crear la base y sus objetos. Introduce la contraseña cuando se solicite.

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' --default-character-set=utf8mb4 --abort-source-on-error -h 127.0.0.1 -u root -p -e "SOURCE C:/xampp/htdocs/DIRI/database/diri.sql;"
```

También puedes importar `database/diri.sql` desde phpMyAdmin. El archivo crea la base `diri`, sus tablas, vistas, triggers y datos iniciales. Debe ejecutarse sobre una instalación nueva. Si ya tienes una base DIRI, consulta las migraciones y conserva un respaldo antes de actualizarla.

El esquema contiene **40 tablas, 2 vistas SQL y 17 triggers**. Los datos iniciales incluyen roles, planes, categorías y 20 trámites en borrador. La carga del catálogo publicado se realiza en el paso 6.

### 4. Abrir la aplicación

Inicia Apache y abre `http://localhost/DIRI/`. La entrada redirige a `http://localhost/DIRI/public/`. Para usar un VirtualHost, configura `public/` como `DocumentRoot`.

Como alternativa para desarrollo, mantén MariaDB iniciado y ejecuta desde la raíz:

```powershell
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8080 -t public
```

Después abre `http://127.0.0.1:8080/`.

### 5. Crear el primer administrador

Registra una cuenta desde `/registro` y ejecuta el comando con el correo de esa cuenta:

```powershell
& 'C:\xampp\php\php.exe' scripts/enable-admin.php 'tu-correo@ejemplo.com'
```

El comando habilita al primer administrador activo. Utiliza la contraseña que elegiste al registrarte. Las cuentas registradas reciben inicialmente rol Usuario y plan Free. El panel administrativo está en `/admin`.

### 6. Cargar el catálogo revisado

Con una cuenta administradora activa, comprueba y aplica el lote incluido:

```powershell
& 'C:\xampp\php\php.exe' scripts/import-official-catalog.php --check
& 'C:\xampp\php\php.exe' scripts/import-official-catalog.php --apply
& 'C:\xampp\php\php.exe' tests/official-catalog.php
```

`--check` simula la carga dentro de una transacción que revierte y puede avanzar los contadores de identificadores. `--apply` guarda un respaldo privado del contenido previo y publica el lote. La importación comprueba cambios existentes para conservar las ediciones. Consulta el informe del catálogo antes de utilizar sus datos.

## Estructura del proyecto

```text
DIRI/
├── app/
│   ├── controllers/   Controladores de solicitudes
│   ├── core/          Arranque, router, sesiones y conexión PDO
│   ├── helpers/       Funciones compartidas
│   ├── integrations/  Contratos de integración
│   ├── middleware/    Autorización y protección CSRF
│   ├── models/        Modelos y acceso a datos
│   ├── services/      Reglas de negocio y validaciones
│   └── views/         Plantillas de la aplicación
├── config/            Configuración y archivos de ejemplo
├── database/          Esquema SQL, migraciones y vistas
├── docs/              Documentación funcional y técnica
├── public/            Entrada web, CSS y JavaScript
├── routes/            Rutas web y API
├── scripts/           Comandos de administración local
├── storage/           Caché, registros y respaldos locales
└── tests/             Comprobaciones y pruebas de integración
```

Una solicitud entra por `public/index.php`, carga el arranque y las rutas, y ejecuta el controlador y los servicios correspondientes.

## Rutas principales

| Ruta | Función |
| --- | --- |
| `/` | Inicio y buscador |
| `/buscar` | Resultados de búsqueda |
| `/tramite?slug=...` | Ficha de un trámite |
| `/guias` | Guías sencillas beta |
| `/mi-ruta` | Preparación personalizada de licencia |
| `/registro` y `/login` | Registro e inicio de sesión |
| `/dashboard` | Espacio personal |
| `/mi-biblioteca` | Biblioteca personal Premium |
| `/admin` | Panel administrativo |
| `/api/v1/tramites?q=...` | Consulta JSON del catálogo |

Con Apache en subcarpeta, añade estas rutas después de `http://localhost/DIRI/public`.

## Comprobaciones

Desde la raíz, con la base configurada e iniciada:

```powershell
& 'C:\xampp\php\php.exe' scripts/check-database.php
& 'C:\xampp\php\php.exe' tests/database-connection.php
& 'C:\xampp\php\php.exe' tests/session-security.php
```

Para las pruebas HTTP, inicia un servidor en otra terminal:

```powershell
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8087 -t public
```

Ejecuta las pruebas desde una segunda terminal:

```powershell
& 'C:\xampp\php\php.exe' tests/auth-flow.php
& 'C:\xampp\php\php.exe' tests/extended-flow.php
```

Las pruebas de integración utilizan datos temporales y pueden avanzar contadores de identificadores. Ejecútalas en desarrollo. Las comprobaciones detalladas están en `tests/` y en la documentación.

## Documentación

- [Avances y alcance de los módulos](docs/avance.md)
- [Base de datos e instalación](database/README.md)
- [Normalización en tercera forma normal](docs/normalizacion-3fn.md)
- [Vistas SQL e índices](docs/vistas-indices.md)
- [Guía de demostración en MariaDB](docs/guia-demostracion-mariadb.md)
- [Autenticación y sesiones](docs/authentication.md)
- [Administración de trámites](docs/admin-tramites.md)
- [Requisitos y documentos](docs/requisitos-documentos.md)
- [Revisión del catálogo](docs/catalogo-oficial-20261021.md)
- [Guías sencillas beta](docs/rutas-sencillas-beta.md)
- [Personalización de licencia](docs/personalizacion-licencia-beta.md)
- [API pública](docs/api.md)
- [Integraciones](docs/integraciones.md)

## Alcance actual

La personalización beta utiliza documentos y vencimientos declarados por la persona. Las estimaciones de atención dependen de reportes suficientes y vigentes. Las rutas siguen dependencias entre pasos y la comparación de sucursales depende de los datos disponibles.

Quedan pendientes las integraciones reales con instituciones, pagos, recordatorios automáticos y autenticación móvil. La revisión visual completa en escritorio y móvil también figura como pendiente en el registro de avances.

## Equipo

- Anthony Chavez Coronado
- Sebastian Peñarrieta Peña
- Abel Daniel Osco Aquino

## Trabajo en equipo

Para proponer cambios, crea una rama desde `main` y abre un pull request para revisión.

```powershell
git switch main
git pull --ff-only origin main
git switch -c mejora/descripcion-del-cambio
```

Las credenciales de cada instalación se configuran en los archivos locales excluidos mediante `.gitignore`.
