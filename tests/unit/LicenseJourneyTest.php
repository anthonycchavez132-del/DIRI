<?php

declare(strict_types=1);

use App\Services\LicenseJourneyService;

return [
    'Rechaza una categoria fuera del alcance de primera licencia' => static function ($assertSame): void {
        $result = (new LicenseJourneyService())->validate(['categoria' => 'C'], 'primera');
        $assertSame(['categoria'], array_keys($result['errors']));
    },
    'Acepta declarar un certificado medico disponible con su fecha' => static function ($assertSame): void {
        $result = (new LicenseJourneyService())->validate([
            'tengo' => ['medico' => '1'], 'vencimientos' => ['medico' => '2028-02-29'],
        ], 'primera');
        $assertSame([], $result['errors']);
        $assertSame(['medico' => '1'], $result['data']['tengo']);
        $assertSame(['medico' => '2028-02-29'], $result['data']['vencimientos']);
    },
];
