<?php

declare(strict_types=1);

use App\Services\LicenseJourneyService;

// Sustituye solo la consulta del catálogo: estas pruebas no necesitan MySQL.
$service = new class extends LicenseJourneyService {
    public function context(string $mode): array
    {
        return [];
    }
};

$profile = $service->defaults('primera');
$profile['edad'] = '21mas';
$profile['medico_registro'] = 'si';
$profile['tengo'] = array_fill_keys(array_keys(LicenseJourneyService::DOCUMENTS), '1');
$today = new DateTimeImmutable('2026-11-11 12:00:00', new DateTimeZone('America/La_Paz'));

return [
    'Un documento vencido vuelve a aparecer como pendiente' => static function ($assertSame) use ($service, $profile, $today): void {
        $profile['vencimientos']['ci'] = '2026-11-10';
        $plan = $service->plan($profile, $today);
        $assertSame(['ci', 'segip'], array_column($plan['actions'], 'key'));
        $assertSame(false, in_array(LicenseJourneyService::DOCUMENTS['ci'], $plan['covered'], true));
        $assertSame(1, count($plan['warnings']));
        $assertSame(false, $plan['ready']);
    },
    'Un documento que vence hoy sigue disponible durante ese dia' => static function ($assertSame) use ($service, $profile, $today): void {
        $profile['vencimientos']['ci'] = '2026-11-11';
        $plan = $service->plan($profile, $today);
        $assertSame(['segip'], array_column($plan['actions'], 'key'));
        $assertSame([], $plan['warnings']);
        $assertSame(true, $plan['ready']);
    },
    'Un documento con vencimiento futuro no debe repetirse' => static function ($assertSame) use ($service, $profile, $today): void {
        $profile['vencimientos']['ci'] = '2026-11-12';
        $plan = $service->plan($profile, $today);
        $assertSame(true, in_array(LicenseJourneyService::DOCUMENTS['ci'], $plan['covered'], true));
        $assertSame([], $plan['warnings']);
        $assertSame(true, $plan['ready']);
    },
    'La fecha de vencimiento no reemplaza la declaracion de tener el documento' => static function ($assertSame) use ($service, $profile, $today): void {
        unset($profile['tengo']['ci']);
        $profile['vencimientos']['ci'] = '2026-11-12';
        $plan = $service->plan($profile, $today);
        $assertSame(['ci', 'segip'], array_column($plan['actions'], 'key'));
        $assertSame(false, $plan['ready']);
    },
    'Conserva documentos declarados disponibles cuando no se indica fecha' => static function ($assertSame) use ($service, $profile, $today): void {
        $plan = $service->plan($profile, $today);
        $assertSame(5, count($plan['covered']));
        $assertSame([], $plan['warnings']);
        $assertSame(true, $plan['ready']);
    },
    'Evalua el vencimiento usando el dia de Bolivia aunque reciba hora UTC' => static function ($assertSame) use ($service, $profile): void {
        $profile['vencimientos']['ci'] = '2026-11-10';
        // 02:00 UTC del 11 equivale a las 22:00 del 10 en Bolivia.
        $plan = $service->plan($profile, new DateTimeImmutable('2026-11-11T02:00:00+00:00'));
        $assertSame([], $plan['warnings']);
        $assertSame(true, $plan['ready']);
    },
    'Varios documentos vencidos generan pendientes y advertencias separados' => static function ($assertSame) use ($service, $profile, $today): void {
        $profile['vencimientos'] = ['ci' => '2026-11-09', 'antecedentes' => '2026-11-10'];
        $plan = $service->plan($profile, $today);
        $assertSame(['ci', 'antecedentes', 'segip'], array_column($plan['actions'], 'key'));
        $assertSame(2, count($plan['warnings']));
        $assertSame(3, count($plan['covered']));
    },
    'Rechaza fechas inexistentes y formatos distintos de anio mes dia' => static function ($assertSame) use ($service): void {
        foreach (['2026-02-30', '11/11/2026', '2026-1-01', 'texto'] as $date) {
            $result = $service->validate(['vencimientos' => ['ci' => $date]], 'primera');
            $assertSame(['vencimientos'], array_keys($result['errors']));
            $assertSame([], $result['data']['vencimientos']);
        }
    },
    'Acepta el 29 de febrero de un anio bisiesto' => static function ($assertSame) use ($service): void {
        $result = $service->validate(['vencimientos' => ['ci' => '2028-02-29']], 'primera');
        $assertSame([], $result['errors']);
        $assertSame(['ci' => '2028-02-29'], $result['data']['vencimientos']);
    },
];
