<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;

class LicenseJourneyService
{
    public const DOCUMENTS = ['ci' => 'Cédula de identidad vigente', 'medico' => 'Certificado médico vigente de centro acreditado',
        'habilidades' => 'Certificado original vigente de aprobación de habilidades de conducción',
        'antecedentes' => 'Antecedentes vigentes que corresponden a mi modalidad',
        'pago' => 'Comprobante de pago de la categoría que estoy seleccionando'];
    public const CATEGORIES = ['P', 'A', 'M', 'T', 'B', 'C'];

    public function defaults(string $mode): array
    {
        return ['modalidad' => $mode, 'categoria' => 'P', 'edad' => 'no_se', 'zona' => 'todas',
            'medico_registro' => 'no_se', 'renovacion_larga' => 'no_se', 'tengo' => [], 'vencimientos' => []];
    }

    public function validate(array $input, string $mode): array
    {
        $data = $this->defaults($mode);
        $errors = [];
        $choices = ['categoria' => $mode === 'primera' ? ['P', 'A', 'M', 'T'] : self::CATEGORIES,
            'edad' => ['no_se', 'menor18', '18a20', '21mas'], 'zona' => array_keys(LicenseProviderService::ZONES),
            'medico_registro' => ['si', 'pendiente', 'no_se'], 'renovacion_larga' => ['si', 'no', 'no_se']];
        foreach ($choices as $key => $allowed) {
            $value = $input[$key] ?? $data[$key];
            if (!is_string($value) || !in_array($value, $allowed, true)) {
                $errors[$key] = 'Selecciona una opción válida para ' . str_replace('_', ' ', $key) . '.';
            } else {
                $data[$key] = $value;
            }
        }
        foreach (['tengo', 'vencimientos'] as $key) {
            $values = $input[$key] ?? [];
            if (!is_array($values) || count($values) > count(self::DOCUMENTS)) {
                $errors[$key] = 'Revisa la selección de documentos.';
                continue;
            }
            foreach ($values as $document => $value) {
                if (!is_string($document) || !isset(self::DOCUMENTS[$document]) || !is_string($value)) {
                    $errors[$key] = 'Documento o valor no válido.';
                    continue;
                }
                if ($key === 'tengo') {
                    if ($value !== '1') { $errors[$key] = 'Marca únicamente los documentos que ya tienes vigentes.'; }
                    else { $data[$key][$document] = '1'; }
                } elseif ($value !== '') {
                    $date = preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $value) === 1
                        ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;
                    if ($document === 'pago' || !$date || $date->format('Y-m-d') !== $value) {
                        $errors[$key] = 'Revisa las fechas de vencimiento.';
                    } else {
                        $data[$key][$document] = $value;
                    }
                }
            }
        }
        return ['data' => $data, 'errors' => $errors];
    }

    public function context(string $mode): array
    {
        if (!in_array($mode, ['primera', 'renovacion'], true)) { throw new DomainException('Modalidad no válida.', 422); }
        $slug = $mode === 'primera' ? 'licencia-de-conducir' : 'renovacion-de-licencia';
        $item = (new CatalogService())->detail($slug);
        if ($item === null) { throw new DomainException('Esta modalidad no está disponible.', 404); }
        $guide = (new SimpleGuideService())->forItem($item);
        if ($guide['steps'] === [] || $item['pasos'] !== []
            || substr((string)$item['fecha_verificacion'], 0, 10) !== '2026-10-21') {
            throw new DomainException('La ficha cambió y necesitamos revisar sus reglas de personalización. Consulta la guía publicada.', 409);
        }
        $providers = new LicenseProviderService();
        return ['item' => $item, 'guide' => $guide,
            'version' => hash('sha256', json_encode(['license-journey-v1', $guide['fingerprint'], $item['descripcion'],
                $item['estado_verificacion'], $item['documentos'], $item['costos'], $providers->data()], JSON_THROW_ON_ERROR))];
    }

    public function plan(array $profile, ?DateTimeImmutable $now = null): array
    {
        $mode = $profile['modalidad'] ?? '';
        if (!is_string($mode) || !in_array($mode, ['primera', 'renovacion'], true)) {
            throw new DomainException('Modalidad no válida.', 422);
        }
        $validated = $this->validate($profile, $mode);
        if ($validated['errors'] !== []) { throw new DomainException('Revisa los datos de tu ruta.', 422); }
        $profile = $validated['data'];
        $context = $this->context($mode);
        $today = ($now ?? new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('America/La_Paz'))->format('Y-m-d');
        $available = [];
        $warnings = [];
        foreach (self::DOCUMENTS as $key => $label) {
            $available[$key] = isset($profile['tengo'][$key]);
            if ($available[$key] && isset($profile['vencimientos'][$key]) && $profile['vencimientos'][$key] < $today) {
                $available[$key] = false;
                $warnings[] = $label . ': la fecha indicada está vencida; vuelve a aparecer como pendiente.';
            }
        }
        $blocked = null;
        if ($mode === 'primera' && $profile['edad'] === 'no_se') {
            $blocked = 'Indica tu rango de edad para comprobar qué categoría cubre esta guía.';
        } elseif ($mode === 'primera' && $profile['edad'] === 'menor18') {
            $blocked = 'Esta beta cubre primera licencia de adultos. Consulta con SEGIP la autorización para menores y sus requisitos específicos.';
        } elseif ($mode === 'primera' && $profile['edad'] === '18a20' && in_array($profile['categoria'], ['A', 'T'], true)) {
            $blocked = 'La ficha exige 21 años cumplidos para primera licencia A o T. Revisa tu categoría o consulta tu caso en SEGIP.';
        }
        $actions = [];
        $covered = [];
        $optional = [];
        $add = static function(string $key, string $title, string $description, string $place, array $links = [], ?string $providerType = null) use (&$actions): void {
            $actions[] = ['key' => $key, 'title' => $title, 'description' => $description, 'place' => $place,
                'links' => $links, 'provider_type' => $providerType];
        };
        if ($blocked === null) {
            if (!$available['ci']) {
                $add('ci', 'Prepara tu cédula vigente', 'Confirma si necesitas primera emisión, renovación o reposición de cédula antes de presentar la solicitud de licencia.', 'SEGIP',
                    [['label' => 'Requisitos de cédula en SEGIP', 'url' => 'https://www.segip.gob.bo/cedula-de-identidad/']]);
            } else { $covered[] = self::DOCUMENTS['ci']; }
            if (!$available['medico']) {
                $add('medico', 'Obtén el certificado médico vigente', 'Elige un centro acreditado. Los certificados se registran al final del día; SEGIP indica acudir desde el día siguiente a la evaluación.', 'Centro de salud acreditado por SEGIP', [], 'medico');
            } else { $covered[] = self::DOCUMENTS['medico']; }
            $skillsNeeded = $mode === 'primera' || $profile['renovacion_larga'] === 'si';
            if ($skillsNeeded && !$available['habilidades']) {
                $add('habilidades', 'Contacta una autoescuela autorizada', 'Consulta disponibilidad, requisitos y costo de capacitación o evaluación. Necesitas aprobar habilidades de conducción para obtener el certificado original.', 'Autoescuela autorizada por SEGIP', [], 'escuela');
            } elseif ($skillsNeeded && $available['habilidades']) {
                $covered[] = self::DOCUMENTS['habilidades'];
            } elseif ($mode === 'renovacion' && $profile['renovacion_larga'] === 'no_se' && !$available['habilidades']) {
                $add('confirmar_habilidades', 'Confirma si necesitas evaluación de conducción', 'SEGIP exige habilidades si no renovaste en cinco años y un día. Confirma si se cumple esa condición antes de pagar una evaluación; luego actualiza tu respuesta.', 'SEGIP');
            } elseif ($mode === 'renovacion' && $profile['renovacion_larga'] === 'no') {
                $optional[] = 'Evaluación de conducción: no se incluye porque indicaste que no se cumple la condición de cinco años y un día sin renovar.';
            } elseif ($available['habilidades']) { $covered[] = self::DOCUMENTS['habilidades']; }
            if (!$available['antecedentes']) {
                $description = $mode === 'primera' ? 'Gestiona antecedentes de Tránsito, FELCC y FELCN.' : 'Gestiona los antecedentes de Tránsito para renovación.';
                $add('antecedentes', 'Obtén los antecedentes correspondientes', $description . ' Puedes consultar la modalidad de Policía Boliviana o Estado Digital CUDAP. REJAP es un certificado diferente.', 'Policía Boliviana / Estado Digital CUDAP',
                    [['label' => 'Estado Digital · CUDAP', 'url' => 'https://estadodigital.mingobierno.gob.bo/cudap/home']]);
            } else { $covered[] = self::DOCUMENTS['antecedentes']; }
            if (!$available['pago']) {
                $add('pago', 'Paga la emisión de la categoría ' . $profile['categoria'], 'Confirma la tarifa de tu categoría y utiliza el canal indicado por SEGIP. El pago de emisión es separado de los certificados y la autoescuela.', 'Banco Unión o canal digital de SEGIP');
            } else { $covered[] = self::DOCUMENTS['pago']; }
            if ($available['medico'] && $profile['medico_registro'] !== 'si') {
                $add('registro_medico', 'Confirma el registro del certificado médico', 'Ya indicaste que tienes certificado vigente: no repitas la evaluación por este motivo. Confirma con el centro que esté registrado en SEGIP antes de tu visita final. Si se emitió hoy, SEGIP indica acudir desde el día siguiente.', 'Centro emisor / SEGIP');
            }
            $add('segip', 'Presenta tu solicitud de licencia en SEGIP', 'Acude con los documentos vigentes y el comprobante de la categoría ' . $profile['categoria'] . '. Si la evaluación médica se realizó hoy, SEGIP indica acudir desde el día siguiente. La emisión depende de su revisión; conserva los documentos ya obtenidos para presentarlos cuando corresponda.', 'Oficina de licencias SEGIP / SEGELIC',
                [['label' => 'Oficinas de SEGIP', 'url' => 'https://www.segip.gob.bo/oficina/']]);
        }
        $providerType = $actions[0]['provider_type'] ?? null;
        $providers = $providerType !== null ? (new LicenseProviderService())->options($providerType, $profile['zona']) : [];
        return ['context' => $context, 'profile' => $profile, 'actions' => $actions, 'next' => $actions[0] ?? null,
            'covered' => $covered, 'warnings' => $warnings, 'optional' => $optional, 'blocked' => $blocked,
            'providers' => $providers, 'ready' => count($actions) === 1 && $actions[0]['key'] === 'segip'];
    }
}
