<?php

declare(strict_types=1);

// Resúmenes editoriales de preparación y selección de trámites. No cambian la
// verificación del catálogo ni convierten gestiones condicionales en obligatorias.
$ci = 'https://www.segip.gob.bo/cedula-de-identidad/';
$sin = 'https://siatinfo.impuestos.gob.bo/index.php/modificaciones-en-el-rnc/actividad-economica';
$activity = 'https://siatinfo.impuestos.gob.bo/index.php/actividades-economicas';
$nit = 'https://siatinfo.impuestos.gob.bo/index.php/requisitos-para-la-inscripcion/requisitos-para-obtener-el-nit-para-personas';
$seprec = 'https://www.seprec.gob.bo/index.php/tramite1/';
$municipal = 'https://lapaz.bo/tramites-y-servicios-gamlp/';
$licence = 'https://lapaz.bo/solicitud-de-licencia-de-funcionamiento-para-actividades-en-general-comercio-y-servicio-sin-inspeccion/';
$vehicles = 'https://lapaz.bo/vehiculos/';
$transfer = 'https://lapaz.bo/registro-de-impuesto-municipal-a-las-transferencias-onerosas-de-vehiculos-automotores-terrestres/';
$sereci = 'https://web.oep.org.bo/registro-civico/servicios-registro-civil/certificaciones/';
$civil = 'https://web.oep.org.bo/registro-civico/servicios-registro-civil/';
$passport = 'https://web.mingobierno.gob.bo/?tramite=estado-digital-2-flujo-migratorio';
$rejap = 'https://magistratura.organojudicial.gob.bo/wp-content/uploads/2024/12/INSTRUCTIVO-REJAP-NAL-CM-No-15-2024.pdf';
$rejapRules = 'https://magistratura.organojudicial.gob.bo/wp-content/uploads/2024/12/ACUERDO-N%C2%B0-038-2019.pdf';

return [
    'cedula-de-identidad' => [
        'source_url' => $ci, 'reviewed_on' => '2026-10-21', 'kind' => 'preparacion',
        'intro' => 'Primera cédula física para bolivianos. Organiza los documentos según la edad. Naturalización y casos con observaciones requieren revisar su modalidad específica.',
        'steps' => [
            ['title' => 'Revisa si la solicitud es de un menor o de un adulto',
             'description' => 'Para menores se exige su presencia y acompañamiento con identificación vigente del adulto responsable. Para mayores, revisa la resolución administrativa del SERECI y la certificación de partida única cuando corresponda.',
             'place' => 'Consulta de requisitos de SEGIP', 'result' => 'Lista de documentos aplicables a tu caso.',
             'note' => 'Si un tutor acompaña al menor en ausencia de sus padres, SEGIP exige resolución judicial que acredite su condición.'],
            ['title' => 'Prepara el certificado de nacimiento y los respaldos que correspondan',
             'description' => 'Reúne el certificado de nacimiento original emitido por SERECI. Si eres mayor de edad, prepara la resolución administrativa original o copia legalizada, y la certificación de partida única si corresponde.',
             'place' => 'SERECI, si te falta algún respaldo', 'result' => 'Documentación de primera emisión preparada.',
             'related' => ['certificado-de-nacimiento', 'certificados-sereci'],
             'note' => 'Si aún no existe la inscripción de nacimiento, confirma primero el registro aplicable; el duplicado de un certificado no crea una partida nueva.'],
            ['title' => 'Confirma el pago y acude a SEGIP',
             'description' => 'Revisa la tarifa y la posibilidad de gratuidad que figuran en la ficha. Utiliza el canal de pago indicado por SEGIP y acude con los documentos correspondientes. Si la solicitud es de un menor, debe estar presente con su acompañante.',
             'place' => 'Canal de pago y oficina de SEGIP', 'result' => 'Presentación de la solicitud de primera cédula.',
             'links' => [['label' => 'Oficinas de SEGIP', 'url' => 'https://www.segip.gob.bo/oficina/']]],
        ],
    ],
    'certificado-de-antecedentes' => [
        'source_url' => $rejap, 'reviewed_on' => '2026-10-21', 'kind' => 'preparacion',
        'intro' => 'Preparación de una solicitud de antecedentes judiciales REJAP. Confirma la tarifa y el punto de atención vigente antes de pagar. Los antecedentes policiales y CENVI son certificados distintos.',
        'steps' => [
            ['title' => 'Confirma qué certificado te están solicitando',
             'description' => 'Comprueba con la entidad receptora si necesitas REJAP, CENVI o antecedentes policiales. Esta guía corresponde a REJAP; no sirve como sustituto de Tránsito, FELCC o FELCN.',
             'place' => 'Entidad que solicita el certificado', 'result' => 'Tipo de certificado identificado.'],
            ['title' => 'Prepara identificación y documentos de tu caso',
             'description' => 'Prepara identificación vigente en original y copia. El instructivo contempla documentos adicionales para representación legal y solicitudes por convocatoria laboral; revisa las condiciones de la ficha antes de acudir.',
             'place' => 'Preparación de documentos para REJAP', 'result' => 'Identificación y respaldos aplicables reunidos.',
             'note' => 'El poder y la convocatoria no se exigen a todos los solicitantes.'],
            ['title' => 'Consulta el pago y presenta la solicitud',
             'description' => 'Confirma con REJAP la tarifa vigente y el canal de pago. Presenta los documentos, revisa el formulario de solicitud y firma o coloca tu huella conforme al procedimiento de atención.',
             'place' => 'Punto de atención REJAP', 'result' => 'Solicitud revisada y presentada.',
             'source_url' => $rejapRules,
             'note' => 'La tarifa normativa de la ficha sigue siendo referencial. Confirma la forma y el momento de entrega en la oficina; no se garantiza emisión inmediata.'],
        ],
    ],
    'registro-de-actividad-economica' => [
        'source_url' => $sin, 'reviewed_on' => '2026-10-21', 'kind' => 'preparacion',
        'intro' => 'Alta de actividad en el RNC para persona natural o empresa unipersonal que ya tiene NIT. Es una modificación tributaria; la licencia municipal se gestiona por separado.',
        'steps' => [
            ['title' => 'Comprueba que ya tienes NIT y define la actividad',
             'description' => 'Si todavía no tienes NIT, revisa primero la inscripción que corresponda a tu tipo de contribuyente. Si ya lo tienes, identifica la nueva actividad y si será primaria o complementaria.',
             'place' => 'Registro Nacional de Contribuyentes del SIN', 'result' => 'Tipo de modificación identificado.',
             'source_url' => $activity, 'related' => ['nit'],
             'note' => 'La ficha de NIT enlazada cubre persona natural. Para empresa unipersonal consulta al SIN sus requisitos específicos.'],
            ['title' => 'Revisa el código y las autorizaciones de la actividad',
             'description' => 'Consulta la actividad en el clasificador del SIN. Si es regulada, verifica qué autorización del ente competente debes registrar para su aprobación y habilitación.',
             'place' => 'Clasificador del RNC y ente regulador, si corresponde', 'result' => 'Actividad y respaldos sectoriales identificados.',
             'source_url' => $activity,
             'note' => 'La exigencia de una autorización depende de la actividad elegida.'],
            ['title' => 'Solicita el alta y actualiza los porcentajes',
             'description' => 'Realiza la modificación mediante el RNC según la modalidad habilitada. Para persona natural o empresa unipersonal la ficha pide identificación del sujeto o responsable. Actualiza también los porcentajes de significatividad de todas las actividades vigentes.',
             'place' => 'Sistema del RNC / atención del SIN', 'result' => 'Alta y porcentajes presentados para su actualización.',
             'note' => 'Si cambias la actividad primaria o perteneces a un régimen especial, consulta el efecto tributario antes de confirmar.'],
        ],
    ],
    'pasaporte' => [
        'source_url' => $passport, 'reviewed_on' => '2026-10-21', 'kind' => 'preparacion',
        'intro' => 'Orientación inicial de pasaporte. La información disponible no permite confirmar el arancel total ni una lista exhaustiva de documentos. Confirma tu modalidad directamente con Migración antes de pagar o reservar un viaje.',
        'steps' => [
            ['title' => 'Confirma la modalidad con Migración',
             'description' => 'Indica si solicitas primera emisión, renovación, reposición o pasaporte para un menor. Pide los requisitos vigentes, la forma de atención y si necesitas cita para tu caso.',
             'place' => 'Atención oficial de Migración', 'result' => 'Requisitos y atención confirmados para tu modalidad.',
             'links' => [['label' => 'Ficha del Ministerio de Gobierno', 'url' => $passport]],
             'note' => 'Esta guía no afirma que la emisión pueda completarse íntegramente en línea.'],
            ['title' => 'Prepara la documentación confirmada',
             'description' => 'La ficha ministerial menciona cédula vigente y formato de solicitud. Añade los demás documentos que Migración indique para tu modalidad; no consideres estos dos documentos como lista completa.',
             'place' => 'Preparación de documentos', 'result' => 'Documentación requerida para tu caso reunida.'],
            ['title' => 'Confirma el importe antes de pagar y continúa la solicitud',
             'description' => 'Solicita el arancel total y el canal oficial de pago. Conserva el comprobante y presenta la solicitud por el procedimiento que confirme Migración.',
             'place' => 'Canal y oficina indicados por Migración', 'result' => 'Solicitud presentada según las instrucciones confirmadas.',
             'note' => 'Se detectaron importes distintos en fuentes oficiales. DIRI mantiene el monto sin confirmar y no promete un plazo de entrega.'],
        ],
    ],
    'apertura-de-negocio' => [
        'source_url' => $licence, 'reviewed_on' => '2026-10-21', 'kind' => 'orientacion',
        'intro' => 'Mapa de preparación para abrir un negocio en La Paz. Las gestiones dependen de la actividad, la forma de operación y el local. Este orden ayuda a organizarte; no es una secuencia obligatoria para todos los negocios.',
        'steps' => [
            ['title' => 'Define qué negocio abrirás y revisa su modalidad municipal',
             'description' => 'Describe la actividad y el lugar donde funcionará. Revisa qué licencia municipal corresponde. La ficha disponible en DIRI cubre comercio y servicios sin inspección para persona natural; otras modalidades requieren consultar al GAMLP.',
             'place' => 'GAMLP · actividades económicas', 'result' => 'Actividad, local y modalidad identificados.',
             'related' => ['licencia-de-funcionamiento'],
             'links' => [['label' => 'Modalidad municipal de comercio y servicios', 'url' => $licence]]],
            ['title' => 'Revisa si corresponde inscripción en SEPREC',
             'description' => 'Si operarás como comerciante individual o empresa unipersonal y corresponde el Registro de Comercio, consulta su inscripción. Si eliges otra forma de organización, revisa su trámite específico.',
             'place' => 'SEPREC, cuando corresponda', 'result' => 'Registro comercial aplicable identificado.',
             'source_url' => $seprec, 'related' => ['registro-de-empresa'],
             'note' => 'No se exige inscribir a todas las personas como empresa unipersonal.'],
            ['title' => 'Confirma tu inscripción tributaria y actividad',
             'description' => 'Consulta al SIN el tipo de contribuyente y la inscripción o actualización que corresponde. La guía de NIT de DIRI cubre persona natural; la empresa unipersonal tiene requisitos propios.',
             'place' => 'Servicio de Impuestos Nacionales', 'result' => 'Gestión tributaria y actividad a registrar identificadas.',
             'source_url' => $nit, 'related' => ['nit', 'registro-de-actividad-economica'],
             'note' => 'La lista municipal no reemplaza las obligaciones del SIN.'],
            ['title' => 'Tramita la licencia y confirma permisos de tu actividad',
             'description' => 'Con la modalidad definida, sigue su procedimiento municipal. Si tu actividad requiere inspección o autorización sectorial, consulta el procedimiento correspondiente antes de iniciar operaciones.',
             'place' => 'GAMLP y entidad sectorial que corresponda', 'result' => 'Ruta específica de habilitación identificada.',
             'related' => ['licencia-de-funcionamiento'],
             'note' => 'La gratuidad de emisión de la licencia consultada no elimina patentes ni otras obligaciones. Esta guía no reúne todos los requisitos sectoriales o laborales.'],
        ],
    ],
    'certificados-sereci' => [
        'source_url' => $sereci, 'reviewed_on' => '2026-10-21', 'kind' => 'orientacion',
        'intro' => 'Elige el certificado exacto que exige la entidad receptora. Un duplicado de nacimiento, matrimonio o defunción es distinto de una certificación de estado civil, filiación o partida única.',
        'steps' => [
            ['title' => 'Pregunta el nombre exacto del certificado solicitado',
             'description' => 'Confirma si necesitas un duplicado o una certificación específica. El catálogo SERECI distingue estado civil, partida única, filiación y existencia o inexistencia de partidas.',
             'place' => 'Entidad que solicita el documento y catálogo SERECI', 'result' => 'Documento exacto identificado.',
             'links' => [['label' => 'Tipos de certificaciones SERECI', 'url' => $sereci]]],
            ['title' => 'Abre la ficha del documento que corresponde',
             'description' => 'Para duplicados de nacimiento, matrimonio o defunción, consulta su guía específica. Para otras certificaciones utiliza el catálogo oficial y el formulario correspondiente.',
             'place' => 'Ficha específica / catálogo del SERECI', 'result' => 'Requisitos de la modalidad correcta localizados.',
             'source_url' => $civil,
             'related' => ['certificado-de-nacimiento', 'certificado-de-matrimonio', 'certificado-de-defuncion'],
             'note' => 'Si todavía no existe la partida, consulta primero su registro. Solicitar un duplicado no inscribe un nacimiento, matrimonio o defunción.'],
            ['title' => 'Confirma oficina, requisitos y tarifa de esa modalidad',
             'description' => 'Reúne los documentos indicados para el certificado elegido y confirma el punto de atención y el importe antes de pagar. Sigue el procedimiento de esa ficha para presentar la solicitud.',
             'place' => 'Punto de atención que indique SERECI', 'result' => 'Solicitud preparada según el documento elegido.',
             'note' => 'No hay una tarifa ni una lista de requisitos común para todas las certificaciones.'],
        ],
    ],
    'tramites-relacionados-con-vehiculos' => [
        'source_url' => $vehicles, 'reviewed_on' => '2026-10-21', 'kind' => 'orientacion',
        'intro' => 'Selecciona la gestión de tu vehículo en el municipio de La Paz. Transferencia, duplicado de placa, duplicado de propiedad y baja tributaria tienen procedimientos diferentes.',
        'steps' => [
            ['title' => 'Identifica qué necesitas cambiar o solicitar',
             'description' => 'Revisa el catálogo vehicular del GAMLP y elige la gestión exacta. No uses los documentos de una transferencia para asumir los requisitos de un duplicado o de una baja.',
             'place' => 'Catálogo de trámites vehiculares del GAMLP', 'result' => 'Trámite vehicular identificado.',
             'links' => [['label' => 'Catálogo oficial de vehículos', 'url' => $vehicles]]],
            ['title' => 'Si compraste o vendiste un vehículo, revisa la transferencia',
             'description' => 'Para una transferencia onerosa normal en La Paz, abre la guía específica y revisa sus etapas de inicio y continuación. Si no es tu caso, continúa con la ficha de tu gestión en el catálogo municipal.',
             'place' => 'GAMLP · procedimiento específico', 'result' => 'Ruta correspondiente a la gestión elegida.',
             'source_url' => $transfer, 'related' => ['transferencia-de-vehiculo'],
             'note' => 'La transferencia es una opción según tu caso, no un paso obligatorio para todos los vehículos.'],
            ['title' => 'Prepara solo los documentos y pagos de esa ficha',
             'description' => 'Comprueba los requisitos, las entidades que intervienen y la atención de tu trámite antes de acudir. Confirma cualquier liquidación o pago con la entidad que corresponda.',
             'place' => 'Entidad y oficina indicadas en la ficha elegida', 'result' => 'Documentación y siguiente visita identificadas.',
             'note' => 'No hay un impuesto ni un plazo único para todos los trámites vehiculares.'],
        ],
    ],
    'tramites-municipales' => [
        'source_url' => $municipal, 'reviewed_on' => '2026-10-21', 'kind' => 'orientacion',
        'intro' => 'Orientación para elegir un trámite del municipio de La Paz. El directorio municipal reúne servicios con requisitos, oficinas y pagos distintos.',
        'steps' => [
            ['title' => 'Busca el servicio municipal que necesitas',
             'description' => 'Describe tu necesidad y consulta el directorio del GAMLP. Antes de reunir documentos, identifica el nombre y la modalidad del trámite.',
             'place' => 'Directorio de trámites y servicios del GAMLP', 'result' => 'Servicio municipal identificado.',
             'links' => [['label' => 'Directorio oficial del GAMLP', 'url' => $municipal]]],
            ['title' => 'Si es un negocio, revisa la licencia de funcionamiento',
             'description' => 'Para comercio o servicios de persona natural sin inspección, revisa la ficha disponible. Si tu actividad pertenece a otra modalidad, consulta al GAMLP el procedimiento aplicable.',
             'place' => 'GAMLP · actividades económicas', 'result' => 'Modalidad municipal de negocio identificada, si corresponde.',
             'source_url' => $licence, 'related' => ['licencia-de-funcionamiento', 'apertura-de-negocio'],
             'note' => 'Este paso solo corresponde si tu necesidad es habilitar una actividad económica.'],
            ['title' => 'Si es un vehículo, elige la gestión vehicular',
             'description' => 'Abre el catálogo vehicular para elegir transferencia, duplicado u otra gestión. Para servicios distintos de negocios o vehículos, sigue la ficha concreta del directorio municipal.',
             'place' => 'GAMLP · área correspondiente al servicio', 'result' => 'Guía específica y oficina a consultar identificadas.',
             'source_url' => $vehicles, 'related' => ['tramites-relacionados-con-vehiculos'],
             'note' => 'Los pasos de negocios y vehículos son alternativas según tu necesidad, no obligaciones acumuladas.'],
            ['title' => 'Confirma requisitos y atención antes de salir',
             'description' => 'Consulta los documentos, costos, canales y oficina que indica la ficha elegida. El directorio municipal publica el 155 para orientación general y seguimiento; verifica el área que corresponde.',
             'place' => 'Canal de atención del GAMLP para tu trámite', 'result' => 'Próxima gestión y atención confirmadas.',
             'note' => 'No se aplica una misma lista de documentos ni un mismo horario a todos los servicios municipales.'],
        ],
    ],
];
