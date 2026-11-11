# Rutas sencillas · Beta

Disponible gratis para visitantes y cuentas Free en `/guias` y `/guia?slug=licencia-de-conducir`. No requiere iniciar sesión. Las fichas con secuencia disponible muestran un acceso destacado; la búsqueda y el menú enlazan al listado.

Primera licencia y renovación incluyen también [personalización por documentos disponibles y zona](personalizacion-licencia-beta.md), con cálculo en servidor en `/mi-ruta`. Las casillas temporales de las guías generales conservan su funcionamiento anterior.

Las veinte entradas publicadas tienen guía beta: diez reutilizan los pasos publicados del catálogo, dos organizan la primera licencia y su renovación, cuatro ayudan a preparar cédula, REJAP, alta de actividad y pasaporte, y cuatro orientan para elegir una gestión específica de negocios, vehículos, SERECI o servicios municipales. Las guías de preparación y orientación no se presentan como secuencias completas verificadas. Se conserva el estado original de cada ficha: once verificadas y nueve referenciales.

## Licencia por primera vez

Secuencia editorial sugerida: evaluación médica, autoescuela autorizada y aprobación de habilidades, antecedentes de Tránsito/FELCC/FELCN, pago de categoría y presentación en SEGIP. Los certificados pueden coordinarse según disponibilidad; la guía no impone que la evaluación médica sea requisito previo para contactar una escuela. SEGIP indica que el certificado médico se registra al final del día y que la persona debe acudir desde el día siguiente. Tránsito participa en los antecedentes; SEGIP realiza la emisión de la licencia.

Se consultó nuevamente el 21/10/2026 la [ficha oficial de SEGIP](https://www.segip.gob.bo/licencia-para-conducir/), que enlaza los directorios de centros médicos y autoescuelas autorizadas, Estado Digital CUDAP y oficinas. La guía enlaza esas páginas; no recomienda empresas privadas concretas ni promete citas, aprobaciones o duración total. La autoescuela exige aprobación de habilidades, no solo pago de un certificado. En renovación se conserva la condición de cinco años y un día para la evaluación de conducción.

## Contenido y progreso

`config/guias.php` contiene los resúmenes editoriales de licencia e incluye los ocho adicionales de `config/guias-adicionales.php`, separados del manifiesto oficial ya aplicado. `SimpleGuideService` da prioridad a los pasos publicados desde administración. Solo utiliza el resumen beta si coinciden la fuente principal y la fecha de consulta esperadas, y si todas sus fuentes por paso siguen presentes con la fecha revisada; una nueva revisión exige revisar también ese resumen. Una fuente ausente impide mostrar una secuencia parcial. No cambia requisitos, tarifas, estado de publicación, auditoría ni datos personales.

Las guías generales enlazan otras guías de DIRI según el caso; solo se incluyen destinos publicados con categoría activa. Cada paso cita la institución que respalda su contenido: por ejemplo, el registro comercial de una apertura cita SEPREC y la inscripción tributaria cita SIN. Los pasos municipales y vehiculares se presentan como alternativas condicionales, no obligaciones acumuladas. En pasaporte se mantiene el importe sin confirmar y se pide validar requisitos por modalidad con Migración.

Para las ocho ampliaciones se volvieron a consultar el 21/10/2026 la [cédula en SEGIP](https://www.segip.gob.bo/cedula-de-identidad/), [modificación de actividad en SIN](https://siatinfo.impuestos.gob.bo/index.php/modificaciones-en-el-rnc/actividad-economica), [actividades del RNC](https://siatinfo.impuestos.gob.bo/index.php/actividades-economicas), [certificaciones SERECI](https://web.oep.org.bo/registro-civico/servicios-registro-civil/certificaciones/), [orientación ministerial de pasaporte](https://web.mingobierno.gob.bo/?tramite=estado-digital-2-flujo-migratorio), [directorio del GAMLP](https://lapaz.bo/tramites-y-servicios-gamlp/) y [vehículos](https://lapaz.bo/vehiculos/). REJAP reutiliza el instructivo y reglamento ya revisados en el [informe del catálogo](catalogo-oficial-20261021.md). Se conservan las limitaciones documentadas de esas fuentes.

Cada paso muestra lugar, indicaciones y fuente con fecha; los resúmenes nuevos también explican el resultado esperado y enlazan recursos oficiales. La beta no calcula tráfico, visitas óptimas o esperas de oficinas. La comparación de sucursales y las rutas avanzadas guardadas conservan sus permisos anteriores.

Con JavaScript se pueden marcar pasos y reiniciarlos. Solo se guardan índices de pasos en `sessionStorage` de esa pestaña, asociados a una huella del contenido. No se escribe en la cuenta, ni se marca un trámite oficial como aprobado. Si el navegador impide el almacenamiento, se informa que las marcas se reiniciarán al recargar. Sin JavaScript todos los pasos y enlaces siguen siendo legibles.

## Comprobación

`php tests/simple-guides.php` comprueba acceso anónimo a las veinte guías, secuencia de licencia, renovación condicional, directorios, evidencia por institución, destinos enlazados, límites de pasaporte, documentos por edad, entradas inválidas, prioridad de administración, protección ante fuentes ausentes y ausencia de escrituras en rutas guardadas. Usa la instalación local de Apache por defecto. No crea cuentas ni modifica contenido.
