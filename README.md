# DIRI - Guía Web de Trámites en Bolivia

## Descripción del Proyecto

DIRI es una aplicación web que ayuda a las personas a preparar sus trámites en Bolivia, tomando como referencia los procedimientos de La Paz.

El sistema reúne en un solo lugar los requisitos, documentos, pasos, costos, sucursales y fuentes de cada trámite. Además, permite personalizar la preparación de la licencia de conducir, llevar un checklist de avance, reportar tiempos de espera en sucursales y consultar el catálogo mediante una API pública.

## Características Principales

- **Autenticación y Control de Accesos:** registro e inicio de sesión con roles diferenciados (Usuario y Administrador), planes Free y Premium, contraseñas encriptadas, protección CSRF y límite de intentos de acceso.
- **Buscador de Trámites:** búsqueda por nombre, palabras clave y expresiones habituales (por ejemplo, "quiero sacar mi carnet").
- **Ficha de Trámite:** requisitos, documentos, pasos ordenados, costos en bolivianos (Bs.), instituciones, horarios y fuentes, indicando si la información es verificada o referencial.
- **Guías Sencillas:** veinte guías de acceso público para los trámites más comunes.
- **Personalización de Licencia de Conducir:** ruta de preparación para primera licencia o renovación según los documentos que tiene la persona y sus fechas de vencimiento.
- **Checklist y Biblioteca Premium:** favoritos, avance por documentos y pasos, historial de consultas y rutas guardadas.
- **Reportes de Atención:** los usuarios reportan tiempos de espera en sucursales; el administrador los modera y el sistema muestra intervalos estimados de espera.
- **Comparación de Sucursales:** recomendación de sucursal según horarios, disponibilidad y tiempos de espera vigentes.
- **Panel Administrativo:** gestión de trámites, instituciones, sucursales, horarios, documentos, fuentes, usuarios y planes, con historial de auditoría.
- **API Pública:** consulta del catálogo en formato JSON (`/api/v1/tramites?q=...`).

## Tecnologías utilizadas

- **Backend:** PHP 8.x con arquitectura MVC propia (sin framework)
- **Base de datos:** MariaDB / MySQL vía XAMPP, acceso con PDO
- **Interfaz de usuario:** HTML5, CSS3, JavaScript
- **Servidor local:** Apache / XAMPP

## Plan de Desarrollo por Fases

| Fase | Módulo / Enfoque |
| --- | --- |
| Fases 1 a 7 | Base de datos, autenticación y sesiones, dashboard, administración de trámites, requisitos y documentos |
| Fase 8 | Instituciones y sucursales |
| Fase 9 | Pasos y rutas de trámites |
| Fase 10 | Costos y fuentes |
| Fase 11 | Buscador y ficha pública |
| Fase 12 | Checklist y progreso |
| Fase 13 | Reportes ciudadanos |
| Fase 14 | Estimación de tiempos de espera |
| Fase 15 | Comparación de sucursales y rutas |
| Fase 16 | Panel administrativo completo |
| Fase 17 | Planes Free y Premium |
| Fase 18 | Diseño responsive y accesibilidad |
| Fase 19 | API pública de consulta |
| Fase 20 | Integraciones oficiales preparadas |

El detalle de cada fase está en `docs/avance.md`.

## Instalación

1. Clonar el repositorio en `C:\xampp\htdocs`:
   ```
   git clone https://github.com/anthonycchavez132-del/DIRI.git
   ```
2. Copiar `config/database.example.php` como `config/database.php` y colocar los datos de conexión.
3. Iniciar Apache y MySQL en XAMPP e importar `database/diri.sql` desde phpMyAdmin.
4. Abrir `http://localhost/DIRI/` en el navegador.

Los pasos completos (administrador inicial y carga del catálogo) están en `database/README.md`.

## Pruebas Unitarias

Las pruebas están en `tests/unit/` y se ejecutan desde la raíz del proyecto:

```
php tests/unit.php
```

La suite contiene **21 pruebas unitarias**. Cada prueba muestra `PASS` o `FAIL` y al final se muestra el total de pruebas aprobadas y fallidas. No necesitan base de datos.

| Archivo | Casos | Qué comprueba |
| --- | --- | --- |
| `AuthValidatorTest.php` | 4 | Registro válido, correo inválido, contraseña corta y confirmación |
| `RoutePlannerTest.php` | 3 | Orden de pasos, ciclos y dependencias ajenas a la ruta |
| `LicenseJourneyTest.php` | 2 | Certificado médico declarado y categorías de primera licencia |
| `CatalogHelpersTest.php` | 3 | Escape de HTML, enlaces e identificadores inválidos |
| `DocumentValidityTest.php` | 9 | Vencimientos, documentos disponibles, fechas y zona horaria de Bolivia |

## Estructura del Repositorio

```
DIRI/
├── app/
│   ├── controllers/   # Controladores de cada módulo
│   ├── core/          # Router, sesiones y conexión a la base de datos
│   ├── helpers/       # Funciones de uso general
│   ├── middleware/    # Control de acceso y protección CSRF
│   ├── models/        # Modelos y consultas a la base de datos
│   ├── services/      # Reglas de negocio y validaciones
│   └── views/         # Pantallas del sistema
├── config/            # Configuración del sistema
├── database/          # Script SQL, migraciones y vistas
├── docs/              # Documentación de cada módulo
├── public/            # Punto de entrada, estilos CSS y scripts JS
├── routes/            # Rutas web y de la API
├── scripts/           # Comandos de administración
├── tests/             # Pruebas unitarias y de integración
└── README.md          # Documentación del repositorio
```

## Integrantes

| Nombre | 
| --- | 
| Anthony Chavez Coronado 
| Sebastian Alejandro Peñarrieta Peña 
| Abel Daniel Osco Aquino
