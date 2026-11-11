<?php

declare(strict_types=1);

use App\Services\LicenseJourneyService;

return [
    'La ruta inicia con categoria particular y sin documentos declarados' => static function ($assertSame): void {
        $data = (new LicenseJourneyService())->defaults('primera');
        $assertSame('primera', $data['modalidad']);
        $assertSame('P', $data['categoria']);
        $assertSame([], $data['tengo']);
        $assertSame([], $data['vencimientos']);
    },
    'Acepta declarar un certificado medico disponible con su fecha' => static function ($assertSame): void {
        $result = (new LicenseJourneyService())->validate([
            'tengo' => ['medico' => '1'], 'vencimientos' => ['medico' => '2028-02-29'],
        ], 'primera');
        $assertSame([], $result['errors']);
        $assertSame(['medico' => '1'], $result['data']['tengo']);
        $assertSame(['medico' => '2028-02-29'], $result['data']['vencimientos']);
    },
    'Rechaza una categoria fuera del alcance de primera licencia' => static function ($assertSame): void {
        $result = (new LicenseJourneyService())->validate(['categoria' => 'C'], 'primera');
        $assertSame(['categoria'], array_keys($result['errors']));
    },
    'Acepta categoria C en renovacion' => static function ($assertSame): void {
        $result = (new LicenseJourneyService())->validate(['categoria' => 'C'], 'renovacion');
        $assertSame([], $result['errors']);
        $assertSame('C', $result['data']['categoria']);
    },
    'Rechaza documentos desconocidos' => static function ($assertSame): void {
        $result = (new LicenseJourneyService())->validate(['tengo' => ['inventado' => '1']], 'primera');
        $assertSame(['tengo'], array_keys($result['errors']));
    },
    'Rechaza fechas de calendario inexistentes' => static function ($assertSame): void {
        $result = (new LicenseJourneyService())->validate(['vencimientos' => ['medico' => '2027-02-29']], 'primera');
        $assertSame(['vencimientos'], array_keys($result['errors']));
    },
    'El comprobante de pago no admite fecha de vencimiento' => static function ($assertSame): void {
        $result = (new LicenseJourneyService())->validate(['vencimientos' => ['pago' => '2028-01-01']], 'primera');
        $assertSame(['vencimientos'], array_keys($result['errors']));
    },
    'Rechaza listas de documentos enviadas como texto' => static function ($assertSame): void {
        $result = (new LicenseJourneyService())->validate(['tengo' => 'medico'], 'primera');
        $assertSame(['tengo'], array_keys($result['errors']));
    },
    'Rechaza marcas de documentos distintas del valor permitido' => static function ($assertSame): void {
        $result = (new LicenseJourneyService())->validate(['tengo' => ['medico' => 'si']], 'primera');
        $assertSame(['tengo'], array_keys($result['errors']));
    },
];
