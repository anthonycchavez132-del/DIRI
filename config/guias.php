<?php

declare(strict_types=1);

// Orden editorial beta, separado del lote oficial inmutable. No impone dependencias
// entre certificados que la fuente enumera como requisitos independientes.
$source = 'https://www.segip.gob.bo/licencia-para-conducir/';
$medical = ['label' => 'Centros de salud autorizados', 'url' => 'https://www.segip.gob.bo/centros-de-salud-autorizados/'];
$schools = ['label' => 'Autoescuelas autorizadas', 'url' => 'https://www.segip.gob.bo/autoescuelas-autorizadas/'];
$records = ['label' => 'Estado Digital · CUDAP', 'url' => 'https://estadodigital.mingobierno.gob.bo/cudap/home'];

return [
    'licencia-de-conducir' => [
        'source_url' => $source,
        'reviewed_on' => '2026-10-21',
        'intro' => 'Primera licencia para adultos. Revisa primero la edad y la categoría en la ficha: P/M desde 18 años y A/T desde 21. Este es un orden sugerido para preparar los requisitos; puedes coordinar la escuela y los antecedentes según su disponibilidad.',
        'steps' => [
            ['title' => 'Obtén tu certificado médico',
             'description' => 'Elige un centro de salud acreditado por SEGIP y realiza la evaluación médica. Consulta el directorio para La Paz antes de acudir.',
             'place' => 'Centro de salud acreditado por SEGIP',
             'result' => 'Certificado de evaluación médica registrado.',
             'note' => 'SEGIP indica que los centros registran los certificados al final del día: acude a SEGIP desde el día siguiente a la evaluación. Si necesitas valoración especializada, el centro te indicará cómo continuar.',
             'links' => [$medical]],
            ['title' => 'Contacta una autoescuela autorizada y aprueba la evaluación',
             'description' => 'Busca una autoescuela autorizada para tu departamento. Consulta requisitos, disponibilidad, capacitación necesaria y costo; debes aprobar las habilidades de conducción para obtener el certificado.',
             'place' => 'Autoescuela autorizada por SEGIP',
             'result' => 'Certificado original de aprobación de habilidades de conducción.',
             'note' => 'El costo y el plazo de la escuela se confirman con el centro elegido.',
             'links' => [$schools]],
            ['title' => 'Obtén los antecedentes para conducir',
             'description' => 'Gestiona los antecedentes de Tránsito, FELCC y FELCN por la Policía Boliviana (Organismo Operativo de Tránsito) o mediante Estado Digital CUDAP, según la modalidad disponible.',
             'place' => 'Policía Boliviana / Estado Digital CUDAP',
             'result' => 'Antecedentes de Tránsito, FELCC y FELCN requeridos para primera licencia.',
             'note' => 'El certificado judicial REJAP es un documento distinto y no sustituye estos antecedentes.',
             'links' => [$records]],
            ['title' => 'Realiza el pago de tu categoría',
             'description' => 'Confirma la categoría que solicitarás y paga por los canales indicados por SEGIP. Conserva el comprobante. En la ficha puedes consultar las tarifas y los pagos separados.',
             'place' => 'Banco Unión o canal digital indicado por SEGIP',
             'result' => 'Comprobante del pago correspondiente a tu categoría.',
             'note' => 'El pago de emisión no incluye la autoescuela ni los demás certificados.'],
            ['title' => 'Acude a SEGIP para tramitar la licencia',
             'description' => 'Con tu cédula vigente, certificado médico registrado, certificado original de habilidades, antecedentes y comprobante de pago, acude a la oficina de licencias de SEGIP. Confirma su atención antes de salir.',
             'place' => 'Oficina de licencias de SEGIP / SEGELIC',
             'result' => 'Presentación de requisitos para la emisión de tu licencia.',
             'note' => 'Tránsito participa en los antecedentes; la emisión de la licencia se realiza en SEGIP. No se garantiza completar todas las gestiones en un día.',
             'links' => [['label' => 'Oficinas de SEGIP', 'url' => 'https://www.segip.gob.bo/oficina/']]],
        ],
    ],
    'renovacion-de-licencia' => [
        'source_url' => $source,
        'reviewed_on' => '2026-10-21',
        'intro' => 'Guía de renovación. La autoescuela solo se incluye en el caso indicado por SEGIP; revisa la condición antes de pagar una evaluación adicional.',
        'steps' => [
            ['title' => 'Realiza la evaluación médica', 'description' => 'Acude a un centro acreditado por SEGIP. Los certificados se registran al final del día; presenta tu solicitud en SEGIP desde el día siguiente.', 'place' => 'Centro de salud acreditado', 'result' => 'Certificado médico registrado.', 'links' => [$medical]],
            ['title' => 'Revisa si necesitas evaluación de conducción', 'description' => 'Si no renovaste tu licencia en cinco años y un día, SEGIP exige el certificado de habilidades de una autoescuela autorizada. Si no corresponde a tu caso, continúa con los antecedentes.', 'place' => 'Autoescuela autorizada, solo cuando corresponda', 'result' => 'Certificado de habilidades únicamente si corresponde.', 'links' => [$schools]],
            ['title' => 'Gestiona los antecedentes de Tránsito', 'description' => 'Solicita el certificado de antecedentes de Tránsito por la Policía Boliviana o Estado Digital CUDAP según modalidad disponible.', 'place' => 'Policía Boliviana / Estado Digital CUDAP', 'result' => 'Antecedentes de Tránsito para renovación.', 'links' => [$records]],
            ['title' => 'Paga y presenta tu solicitud en SEGIP', 'description' => 'Realiza el pago de la categoría correspondiente por un canal indicado por SEGIP. Acude con tu cédula vigente, evaluación médica y los certificados aplicables. Revisa documentos y tarifas en la ficha.', 'place' => 'Canal de pago y oficina de licencias de SEGIP', 'result' => 'Solicitud de renovación con sus requisitos.', 'links' => [['label' => 'Oficinas de SEGIP', 'url' => 'https://www.segip.gob.bo/oficina/']]],
        ],
    ],
] + (require __DIR__ . '/guias-adicionales.php');
