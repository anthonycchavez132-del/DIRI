# Avance por fases

La instrucción «continua todo lo que puedas» autoriza avanzar sin confirmaciones entre fases, conservando comprobaciones separadas.

## Fase 8 — Instituciones y sucursales

Creado: `config/resources.php`, modelo `Resource`, `ResourceValidator`, `ResourceService`, controladores `AdminHomeController` y `AdminResourceController`, vistas `admin/resources`, `tests/extended-flow.php` y `tests/phases/08.php`.

Modificado: rutas, navegación administrativa y helpers. Sin cambios de esquema.

Desde `/admin`, gestionar instituciones, sucursales, turnos, excepciones y disponibilidad por trámite. Los datos desconocidos permanecen vacíos. Se valida el par de coordenadas, horas y superposición de turnos. Escrituras con CSRF, permisos, control de versiones y auditoría en una transacción.

Comprobado: `php tests/extended-flow.php`: acceso denegado a usuarios comunes, tabla arbitraria rechazada, CSRF, conflicto de versiones, turnos y coordenadas inválidos. Las pruebas eliminan sus datos temporales. Falta revisión visual en navegador.

## Fase 9 — Pasos y rutas

Creado: `RoutePlanner` y `tests/phases/09.php`. Modificado: registro de recursos, validador y servicio administrativo. Los pasos, documentos por paso, dependencias, rutas y miembros se administran desde `/admin`.

El orden topológico respeta las dependencias y utiliza el número editorial para desempatar. Se rechazan ciclos y relaciones ajenas al trámite. Una ruta pública exige miembros publicados; sus cambios la devuelven a borrador. No hay borrados de contenido ni datos oficiales añadidos.

Comprobado: `php tests/extended-flow.php` aprueba fases 8–9, incluidos ciclo, orden y aislamiento de trámites. Siguiente: costos y fuentes.

## Fase 10 — Costos y fuentes

Modificado: registro de recursos, validadores de recursos y publicación, servicio administrativo y limpieza de fixtures. Creado: `tests/phases/10.php` y herramienta CLI de recuperación exclusiva de fixtures `tests/support/cleanup-extended.php`.

En `/admin`: costos, moneda, lugar y medio de pago, fuente, clasificación, documentos y costos por paso. `NULL` distingue lo desconocido de un costo cero. Verificado exige fuente oficial, monto y fecha; editar una fuente devuelve el contenido relacionado a revisión. No se consulta ninguna URL externa.

Comprobado: fases 8–10 aprobadas, incluidos montos negativos, desconocidos, fuente no oficial e invalidación de publicación. Datos temporales eliminados. Siguiente: buscador público.

## Fase 11 — Buscador y ficha pública

Creado: `SearchProvider`, `DatabaseSearch`, `CatalogService`, `CatalogController`, vistas `catalog`, `tests/phases/11.php`. Modificado: rutas, portada, buscador del dashboard y recursos de etiquetas/alias.

Prueba `/buscar?q=quiero+sacar+mi+carnet`; los alias se administran en «Palabras clave y alias». La ficha `/tramite?slug=...` presenta requisitos, documentos, pasos ordenados, costos, pagos, instituciones, horarios y fuentes. Solo contenido publicado de categorías activas. La portada muestra «Trámites disponibles»; no inventa estadísticas de popularidad.

Comprobado: fases 8–11 aprobadas; alias natural, borrador 404, consulta inválida 422, SQL como texto y HTML escapado. Las 20 semillas originales continúan en borrador: el catálogo público puede estar vacío hasta revisar y publicar contenido real. Siguiente: checklist y progreso.

## Fase 12 — Checklist y progreso

Creado: `PlanAccess`, `PremiumMiddleware`, `ProgressService`, `PersonalController`, vistas `personal`, `tests/phases/12.php`. Modificado: rutas y acceso desde ficha pública. La comprobación de plan se introduce ahora para proteger estas escrituras; se completará el panel de asignación en las fases 16–17.

Abre «Mi checklist y favoritos» desde un trámite publicado con una cuenta Premium. Se puede iniciar seguimiento, marcar documentos/pasos, guardar favoritos y cambiar estado. Solo se termina cuando están los documentos obligatorios y pasos; dependencias previas respetadas. El estado es personal, no una aprobación institucional.

Comprobado: fases 8–12 aprobadas; aislamiento de cuentas, dependencias, finalización, CSRF y revocación de plan en la siguiente petición. No se cambió el plan de ninguna cuenta real. Siguiente: reportes ciudadanos.

## Fase 13 — Reportes ciudadanos

Creado: `ReportService`, `ReportController`, vistas `reports`, `tests/phases/13.php`. Modificado: rutas, menú y enlace de moderación en administración.

Con sesión Free o Premium: `/reportar`. Se pide sucursal, demanda y al menos una medición de personas/minutos; fecha y hora las pone el servidor. Máximo uno por sucursal cada 15 minutos y diez por día. En `/admin/reportes`, aceptar o rechazar una vez. Aceptar incorpora histórico con fuente `DIRI_USUARIOS` y confianza baja, nunca oficial.

Comprobado: fases 8–13 aprobadas; validación, límites, CSRF, permisos, histórico con procedencia e imposibilidad de doble revisión. Siguiente: intervalos de espera.

## Fase 14 — Estimación de espera

Creado: `AttentionService`, componente `catalog/attention.php`, comando `scripts/refresh-attention.php`, `tests/phases/14.php`. Modificado: catálogo y ficha pública.

La ficha muestra intervalos con procedencia, confianza, actualización y caducidad. Usa la última observación aceptada de cada persona en la última hora; requiere tres tiempos de personas distintas. Redondea extremos y agrega margen de cinco minutos. Confianza baja con 3–4 muestras y media desde 5; nunca atribuye confianza alta a esta heurística ciudadana. Es un intervalo descriptivo de esperas observadas, no un pronóstico garantizado ni un modelo de velocidad de cola.

Una fuente oficial o administrativa vigente tiene prioridad. El comando CLI permite almacenar estimaciones, sin llamadas externas. Comprobado: fases 8–14, muestras insuficientes, intervalo, tres personas distintas, almacenamiento y caducidad. Siguiente: recomendación de sucursales.

## Fase 15 — Comparación de sucursales y rutas

Creado: `RecommendationService`, `RecommendationController`, vistas `personal/branches`, `routes`, `route`, `tests/phases/15.php`. Modificado: rutas y enlaces de ficha.

Premium: `/comparar-sucursales?slug=...`, `/rutas`, `/ruta?id=...`. Solo se recomienda una sucursal disponible, activa, abierta según sus horarios/excepciones y con espera vigente. Se compara el límite superior de espera y desempata la distancia opcional. Sin datos suficientes no hay recomendación. Coordenadas usadas solo en la consulta; distancia en línea recta, sin inventar tráfico o velocidad de transporte.

Las rutas respetan dependencias y sugieren sucursal por trámite. Se pueden guardar; no es una optimización global del recorrido geográfico. Comprobado: fases 8–15, plan, disponibilidad, cierre excepcional, distancia y coordenadas inválidas. Siguiente: administración de usuarios, planes y operaciones.

## Fase 16 — Panel administrativo

Creado: `AdminAttentionService`, `AdminOperationsController`, vistas `admin/attention`, `audit`, `tests/phases/16.php`. Modificado: recursos de categorías/usuarios/planes, validación, permisos de servicio, navegación y rutas.

En `/admin`, todos los módulos anteriores, categorías, asignación de rol/plan, bloqueo de cuentas, disponibilidad de planes, observación de atención e historial de auditoría. Los formularios de usuarios excluyen contraseñas y hashes. No crean cuentas sin contraseña. El plan Free permanece activo; los identificadores de planes no se renombran. Se conserva al menos un administrador activo mediante un cerrojo compartido al editar usuarios.

Atención manual: fuente obligatoria, histórico y estado con procedencia `ADMINISTRADOR`, confianza baja y caducidad de 30 minutos. Comprobado: fases 8–16, autorización también en servicio, asignación sobre cuenta temporal, exclusión de hashes, formulario de creación rechazado y procedencia administrativa. No se modificó ninguna cuenta real. Siguiente: completar biblioteca e historial Premium.

## Fase 17 — Free y Premium

Creado: `LibraryService`, vista `personal/library`, `tests/phases/17.php`. Modificado: catálogo, controlador personal, dashboard, perfil, rutas y prueba anterior de dashboard.

Premium: favoritos, progreso/checklist, historial, biblioteca, guardar rutas y comparar sucursales. Free conserva consulta pública y reportes. Las restricciones existen en middleware y servicios, sin confiar en campos del navegador. Desactivar Premium o cambiar de plan revoca acceso en la siguiente solicitud; los datos personales se conservan. No hay pagos ni cambio de plan por autoservicio.

Historial: últimas 100 consultas de la propia cuenta Premium, sin repetición inmediata; se puede borrar con POST y CSRF. Las consultas Free no se guardan. `/mi-biblioteca` reúne historial, favoritos y rutas disponibles. Recordatorios automáticos quedan para una etapa futura.

Comprobado: fases 8–17, aislamiento, deduplicación, revocación, dashboard Free sin datos Premium y ficha pública accesible. Siguiente: responsive.

## Fase 18 — Responsive y accesibilidad

Creado: `public/css/modules.css`, `tests/phases/18.php`. Modificado: CSS compartido, JavaScript, layout y panel administrativo.

Formularios y tarjetas flexibles, navegación que se ajusta en varias líneas, controles de 44 px, foco visible, estilos de movimiento reducido, filas de checklist apiladas y campos de coordenadas adaptables. El buscador activo ya tiene cursor y estilo de botón habilitado. Filtro administrativo opcional en JavaScript; todos los módulos siguen visibles sin JavaScript.

Comprobado: fases 8–18, viewport, CSS servido, enlaces y filtro presentes. Revisión visual pendiente: `computer-use` devolvió `apps: []` y `browsers: []`. No se afirma validación visual a tamaños concretos. Siguiente: API REST de consulta.

## Fase 19 — API pública de consulta

Creado: `ApiResponse`, `ApiController`, `routes/api.php`, `docs/api.md`, `tests/phases/19.php`. Modificado: router y entrada pública.

Consultar `/api/v1/tramites?q=...`, `/api/v1/tramite?slug=...`, `/api/v1/atencion?sucursal_id=...`. Reutiliza servicios; devuelve JSON versionado y errores JSON 404/405/422/500. No publica reportantes, cuentas, configuraciones privadas ni borradores. La API no escribe historial.

Comprobado: fases 8–19, formato, Content-Type, borradores, métodos, rutas inexistentes, tipos inválidos y procedencia de atención. Autenticación móvil y escrituras API quedan para otra versión. Siguiente: contratos de integración oficial.

## Fase 20 — Integraciones oficiales preparadas

Creado: contratos `app/integrations`, `IntegrationSyncService`, `config/integrations.example.php`, `scripts/sync-integrations.php`, `docs/integraciones.md`, `tests/phases/20.php`. Modificado: registro/validación de metadatos de integración, vista administrativa, `.gitignore` y documentación.

Sin proveedores reales ni llamadas de red. Solo un adaptador registrado localmente puede consultar una integración activa. Las credenciales se resuelven desde referencias de entorno; no se guardan tokens en MariaDB. Los lotes se validan por institución/sucursal, fuente, fecha, campos y tamaño antes de guardarse atómicamente. Los errores no conservan mensajes privados de proveedores. El simulador se limita a pruebas temporales.

Comprobado: fases 8–20, adaptador ausente, proveedor simulado, procedencia, lectura futura, reversión de lote inválido y sanitización de errores. Revisadas además conexión, esquema, sesiones, autenticación, dashboard, trámites y requisitos/documentos: todas las regresiones pasan.

## Revisión final y límites

Se reforzó la validación de publicación dentro de la transacción y el bloqueo de fuentes al asociarlas. Los formularios sin cambios no generan nueva auditoría ni fuerzan revisión. Se impide trasladar pasos/costos a otro trámite y se validan los enlaces externos al mostrarlos.

Las relaciones auxiliares (dependencias, documentos/costos de un paso, miembros de ruta, etiquetas, alias y fuentes complementarias) se pueden quitar desde su formulario de edición, con confirmación explícita, CSRF, versión y auditoría. Se devuelve el trámite/ruta a revisión; las relaciones todavía usadas por dependencias se conservan hasta quitar esas dependencias. No se eliminan usuarios, trámites, documentos ni progreso desde estos formularios. El dashboard enlaza directamente a la checklist y excluye categorías inactivas.

`tests/fresh-install.php` importó el SQL completo en una base temporal: 39 tablas y 20 borradores; verificó que el último administrador no puede bloquearse ni perder su rol. La base temporal se eliminó. `tests/phases/99-regressions.php` prueba además formularios HTTP, rutas guardadas, no-op y eliminación confirmada de relaciones. El código nuevo de servicios/controladores se separó en líneas legibles, comprobando que el formateo conservaba exactamente los tokens PHP.

Verificación final: 134 archivos PHP sin errores de sintaxis; 87 comprobaciones de la batería ampliada aprobadas también contra Apache en `/DIRI/public`. Se corrigió una diferencia de Apache con el código CSRF 419: requiere frase de estado explícita para evitar un 500. Portada, búsqueda y API responden 200; `config/app.php` y `tests/extended-flow.php` quedan bloqueados con 403 por Apache. La instalación conserva una cuenta existente y 20 borradores; no conserva instituciones, costos, reportes ni integraciones de prueba.

Pendiente: revisión visual real en escritorio/móvil; verificación y carga de contenido oficial por administración; datos suficientes para estimaciones reales; adaptadores autorizados cuando existan contratos; pagos, recordatorios y autenticación móvil en etapas futuras. El orden de rutas y las recomendaciones de sucursal no constituyen optimización geográfica global.

## Catálogo oficial — 21 de octubre de 2026

Se completó la revisión y publicación de las veinte entradas iniciales, usando La Paz para los trámites municipales. Se consultaron 21 fuentes institucionales; once fichas quedaron verificadas y nueve referenciales, incluidas cuatro guías generales. Las discrepancias de tarifas y los requisitos que dependen del caso permanecen visibles. La fecha de consulta no se presenta como fecha de publicación de la norma.

La carga conserva un respaldo privado del contenido previo, registra la publicación en auditoría y detecta una segunda ejecución para no duplicar datos. El manifiesto revisable y el detalle de fuentes, alcance y limitaciones están en [el informe del catálogo](catalogo-oficial-20261021.md). El buscador acepta expresiones habituales como «quiero sacar mi carnet» y «quiero abrir una tienda». Las fichas muestran las fuentes de requisitos, documentos, pasos y costos; los documentos condicionales se distinguen de los opcionales.

Validación posterior a la carga: 138 archivos PHP sin errores de sintaxis, 87 comprobaciones de la batería ampliada, pruebas de autenticación, dashboard, administración y contenido aprobadas. Las 33 comprobaciones específicas del catálogo confirmaron las veinte fichas por HTTP, búsquedas, clasificación, condiciones y API pública. Las cuentas y datos temporales de las pruebas se retiraron. La revisión visual en navegador y las integraciones reales siguen pendientes.

## Rutas sencillas gratuitas — Beta

Se habilitaron `/guias` y `/guia?slug=...` para visitantes y cuentas Free, con enlaces desde navegación, búsqueda y fichas. Doce entradas tienen secuencia disponible, incluidas primera licencia y renovación; ocho conservan su ficha mientras se completa la secuencia. La primera licencia detalla evaluación médica, autoescuela autorizada, antecedentes, pago y emisión en SEGIP, con directorios oficiales y fuente consultada nuevamente. El orden de preparación es sugerido y no convierte requisitos independientes en dependencias obligatorias.

Las marcas de avance son temporales por pestaña y no alteran cuentas ni progreso Premium. El contenido funciona sin JavaScript. Los pasos publicados desde administración tienen prioridad sobre los resúmenes beta; cambiar la fecha de revisión impide reutilizar automáticamente el resumen anterior. Véase [alcance de la beta](rutas-sencillas-beta.md).

Comprobado: rutas gratuitas por HTTP, secuencia de licencia, renovación condicional, fuentes, enlaces, parámetros inválidos y conservación de permisos avanzados; catálogo oficial y 87 regresiones ampliadas aprobados. Los 144 archivos PHP pasan sintaxis. La interacción visual de las marcas en un navegador sigue pendiente; el motor JavaScript de pruebas no pudo iniciarse en este entorno.

### Ampliación a las veinte entradas

Se añadieron las ocho guías que faltaban: preparación de primera cédula, REJAP, alta de actividad y pasaporte; orientación para apertura de negocio, certificados SERECI, vehículos y servicios municipales de La Paz. Las guías generales ofrecen enlaces a otras guías según el caso. Se distinguen preparación, orientación y secuencia publicada; el estado original de cada ficha aparece en la guía. Las tarifas desconocidas y los requisitos incompletos conservan sus advertencias.

Cada paso puede citar una fuente complementaria de la ficha, con su propia institución y fecha. Si alguna fuente requerida falta o cambia su fecha, se retira el resumen beta completo hasta revisarlo. Los destinos relacionados se limitan a trámites publicados con categoría activa. El buscador permite abrir directamente la ruta desde cada resultado y el listado muestra veinte guías disponibles sin sección vacía de pendientes.

Comprobado por HTTP: veinte guías, destinos internos, límites de pasaporte, requisitos de cédula por edad, fuentes de SEPREC/SIN y ausencia de secuencias parciales. Pasaron también las 33 comprobaciones del catálogo, las 87 regresiones ampliadas y la revisión de sintaxis de 145 archivos PHP. No se alteraron los datos oficiales ni las cuentas. La revisión visual de las marcas en navegador sigue pendiente.

## Ruta personalizada de licencia — Beta

Se habilitó `/mi-ruta` para visitantes y cuentas Free, con formularios funcionales sin JavaScript. Primera licencia y renovación tienen selecciones separadas de categoría, documentos vigentes y zona preferida; en primera emisión se comprueba el rango de edad. La ruta omite obtener documentos declarados disponibles, vuelve a incluir los vencidos según la fecha indicada, diferencia certificado médico de registro en SEGIP y conserva la presentación final. La renovación incorpora habilidades solo según su condición o pide confirmarla cuando se desconoce.

Se revisaron los directorios oficiales de SEGIP y se incorporaron 13 centros médicos y 12 autoescuelas del municipio de La Paz en un archivo de datos con fuente, fecha y huellas de descarga. Para la siguiente gestión se prioriza coincidencia de zona, con orden alfabético para empates y aviso cuando no hay coincidencias. No se inventan distancias, disponibilidad, precios ni tiempos de espera.

Las elecciones se conservan solo en sesión y por modalidad/cuenta, con botón para borrarlas. No se solicitan archivos ni se validan certificados mediante una API. CSRF, tipos, modalidades y versiones se comprueban en servidor. Cambios en reglas, fuentes, contenido o directorio obligan a revisar las elecciones; un formulario anterior recibe 409. Las cuentas y los datos oficiales no se modifican. Véase [alcance y decisiones](personalizacion-licencia-beta.md).

Comprobado: 39 pruebas de personalización, 56 de guías, 33 del catálogo y 87 regresiones ampliadas aprobadas; 150 archivos PHP sin errores de sintaxis. Probados por HTTP el ejemplo médico→autoescuela, las opciones por zona, el aislamiento de sesiones/modalidades y el reinicio protegido. La revisión visual del navegador sigue pendiente.

## Limpieza de componentes ajenos al producto — 22 de octubre de 2026

Se retiraron los componentes añadidos exclusivamente para una demostración académica: dos tablas auxiliares y sus veinte casos ficticios, tres vistas SQL, cuatro triggers, una función, un procedimiento, servicios, instaladores, semillas, pruebas y documentación específica. Sus marcadores y auditoría auxiliar también se retiraron. Las pantallas y rutas correspondientes ya estaban eliminadas.

La base vuelve a las 39 tablas del modelo original de DIRI. Se verificaron las dependencias y se guardó un respaldo privado antes del retiro. Las huellas y cantidades de datos de las 39 tablas originales, descontando los marcadores y auditoría retirados, coinciden antes y después; se conservaron catálogo, usuarios y funcionalidades reales.

Tras esa limpieza, las consultas JOIN, subconsultas, índices convencionales, validaciones y baja lógica permanecieron en el código del producto. En ese momento no se utilizaban vistas SQL ni índices funcionales sobre expresiones. La incorporación posterior de vistas e índices propios del catálogo se describe a continuación.

## Vistas e índice del catálogo — 22 de octubre de 2026

Se incorporaron `vista_tramites_publicados` y `vista_documentos_tramite`, utilizadas por el buscador, las fichas y el modelo de documentos. El buscador prioriza alias exactos mediante `idx_palabras_normalizada`, un índice sobre la columna generada `LOWER(TRIM(palabra))`; conserva las coincidencias parciales. No se agregaron tablas ni pantallas académicas. La migración 008 y el esquema inicial contienen las mismas definiciones. Véanse [consultas y uso de EXPLAIN](vistas-indices.md).

Comprobación posterior a esta migración: sintaxis PHP, integridad del esquema, administración, regresiones ampliadas, catálogo, guías y personalización aprobadas. Las once comprobaciones específicas de vistas e índice y la instalación desde cero también pasaron. En esta etapa se conservaron las 39 tablas, veinte fichas publicadas y los 74 alias originales; no quedaron vistas temporales de comprobación. EXPLAIN de la subconsulta real para «carnet» eligió `idx_palabras_normalizada`, acceso `ref` y una fila estimada.

## Normalización hasta 3FN — 22 de octubre de 2026

La migración 009 elimina `tramite_id` de las cinco relaciones donde dependía de una parte de la clave. Se obtiene por JOIN desde el paso o seguimiento. `palabras_clave` separa el catálogo y su columna calculada de las asociaciones de trámites; se retira la clasificación constante de integraciones. El diseño actual tiene 40 tablas, dos vistas y diecisiete triggers que mantienen las reglas de pertenencia y el catálogo. Véase [análisis de claves y dependencias de las 40 tablas](normalizacion-3fn.md).

Se adaptaron lecturas y escrituras de pasos, documentos, dependencias, progreso y búsqueda. Los formularios mantienen el contexto sin almacenarlo dos veces. Comprobado: 393 verificaciones en diez suites aprobadas y 154 archivos PHP sin errores de sintaxis. También se probó la migración desde una copia de la estructura anterior con datos en las cinco relaciones, su repetición y la instalación desde cero. Los registros originales de las 38 tablas de negocio coinciden con el respaldo; se conservan veinte fichas publicadas y 74 asociaciones de alias. `versiones_esquema` registra 009. EXPLAIN continúa eligiendo `idx_palabras_normalizada` con acceso `ref` sobre el catálogo compartido.
