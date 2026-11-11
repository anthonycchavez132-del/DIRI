# Personalización de licencia · Beta gratuita

Disponible en `/mi-ruta?modalidad=primera` y `/mi-ruta?modalidad=renovacion`, con acceso desde las fichas y las guías. Funciona sin cuenta, con cuentas Free y sin JavaScript. Esta etapa personaliza esas dos modalidades de licencia; las demás guías conservan su secuencia general.

## Documentos y ruta

La persona selecciona categoría, rango de edad para primera emisión, condición de renovación y zona preferida del municipio de La Paz. Puede marcar cédula, certificado médico, habilidades, antecedentes y comprobante de pago de la categoría seleccionada. Los antecedentes requeridos se explican por modalidad. La condición de cinco años y un día solo se usa en renovación; si no se conoce y falta el certificado de habilidades, se pide confirmar antes de pagar una evaluación.

Cada marca es una declaración de vigencia del usuario; no se valida con una API ni se solicita subir certificados o datos de identidad. La fecha de vencimiento es opcional: si se indica una fecha anterior al día actual de Bolivia, ese documento vuelve a quedar pendiente. No se infiere su duración ni se renueva automáticamente la declaración.

Ejemplo: con cédula y certificado médico vigentes, la primera licencia continúa en autoescuela. Si falta confirmar el registro del certificado médico, se añade una comprobación antes de la presentación final; no se recomienda otra evaluación por ese motivo. La presentación final permanece en la ruta aunque todos los documentos estén preparados. No se marca la licencia como emitida ni un trámite de la cuenta como completado.

La edad desconocida, los menores de 18 años y la primera categoría A/T antes de 21 años detienen esa ruta y orientan a revisar la modalidad. Los ascensos, casos especiales y autorizaciones para menores quedan fuera de esta personalización. En renovación la categoría indicada es la que se renueva, no una solicitud de ascenso.

## Opciones por zona

Se consultaron el 21/10/2026 los directorios de [centros de salud autorizados](https://www.segip.gob.bo/centros-de-salud-autorizados/) y [autoescuelas autorizadas](https://www.segip.gob.bo/autoescuelas-autorizadas/), enlazados desde la [ficha de licencia de SEGIP](https://www.segip.gob.bo/licencia-para-conducir/). El archivo revisable `database/catalogo/proveedores-licencia-lapaz-20261021.json` conserva 13 centros médicos y 12 autoescuelas cuyo municipio está identificado como NUESTRA SEÑORA DE LA PAZ, sus direcciones literales, fuente, fecha y huellas SHA-256 de las descargas.

Las zonas se normalizan a partir de las direcciones del directorio; no se geocodifican. Las opciones correspondientes al siguiente paso se ordenan primero por coincidencia con la zona elegida y luego por nombre. Sin coincidencias se avisa y se muestran otras opciones de la ciudad. Sin zona se muestran alfabéticamente. No se presentan como las más cercanas, baratas o rápidas: no hay coordenadas, precios, horarios ni disponibilidad confirmados en esta carga. Se pide confirmar habilitación para la categoría y atención antes de acudir.

El criterio de esta beta es evitar repetir la obtención de documentos y priorizar opciones de la zona declarada. No calcula una ruta vial óptima ni asegura tiempos de espera. Las comparaciones avanzadas existentes conservan sus permisos.

## Sesión y actualización

Las selecciones se guardan solo en la sesión del navegador, separadas por visitante o cuenta y por modalidad. No se escriben en MariaDB ni se trasladan automáticamente entre primera licencia y renovación. Hay un botón para borrarlas. Las fechas se vuelven a evaluar al consultar el resultado; un documento puede pasar a pendiente al vencer.

La versión del formulario vincula reglas, contenido, documentos, costos y directorio. Un formulario antiguo recibe 409; una sesión con otra versión vuelve al formulario. Si las fichas dejan de estar publicadas, cambia la fecha revisada, faltan fuentes o hay pasos administrativos que sustituyan la secuencia beta, se suspende el cálculo hasta revisar las reglas. Las guías generales siguen sujetas a sus propios controles de publicación.

Todas las operaciones POST usan CSRF, validan tipos y opciones permitidas y redirigen al resultado para evitar reenvíos al recargar. La pantalla conserva las elecciones válidas cuando hay errores. El resultado distingue documentos declarados disponibles, gestiones pendientes y próximo paso, con enlace a requisitos, tarifas y evidencia.

## Verificación

`php tests/license-journey.php` comprueba omisión de obtención, vencimientos, registro médico, edad/categoría, modalidades, condición desconocida, directorio, prioridades por zona y falta de coincidencias. Por HTTP comprueba casillas, CSRF, formularios obsoletos, cálculo, aislamiento entre sesiones/modalidades y reinicio. No crea cuentas ni cambia datos del catálogo. La interacción visual requiere revisión en un navegador conectado.
