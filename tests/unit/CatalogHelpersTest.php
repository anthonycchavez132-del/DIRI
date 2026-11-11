<?php

declare(strict_types=1);

use App\Services\TramiteValidator;

return [
    'Escapa etiquetas y comillas antes de mostrar contenido' => static function ($assertSame): void {
        $assertSame('&lt;script&gt;&quot;x&quot;&lt;/script&gt;', e('<script>"x"</script>'));
    },
    'Permite enlaces HTTPS a fuentes oficiales' => static function ($assertSame): void {
        $assertSame('https://www.segip.gob.bo/', external_url('https://www.segip.gob.bo/'));
    },
    'Rechaza enlaces ejecutables y enlaces con credenciales' => static function ($assertSame): void {
        $assertSame(null, external_url('javascript:alert(1)'));
        $assertSame(null, external_url('https://usuario:clave@example.com/'));
        $assertSame(null, external_url(null));
    },
    'Distingue documentos obligatorios de documentos condicionales' => static function ($assertSame): void {
        $assertSame('Obligatorio', obligation_label(['obligatorio' => '1']));
        $assertSame('Según tu caso: revisa la condición', obligation_label([
            'obligatorio' => '0', 'observaciones' => 'Condicional: solo para renovación.',
        ]));
        $assertSame('Opcional', obligation_label(['obligatorio' => '0']));
    },
    'Muestra las marcas UTC en hora de Bolivia' => static function ($assertSame): void {
        $assertSame('31/12/2025 22:00', local_datetime('2026-01-01 02:00:00'));
    },
    'Conserva el dia de una fecha editorial' => static function ($assertSame): void {
        $assertSame('21/10/2026', verification_date('2026-10-21'));
        $assertSame('Pendiente', verification_date(null));
    },
    'Acepta los limites de identificadores de tramites' => static function ($assertSame): void {
        $assertSame(1, TramiteValidator::id('1'));
        $assertSame(4294967295, TramiteValidator::id('4294967295'));
    },
    'Rechaza identificadores negativos decimales y fuera de rango' => static function ($assertSame): void {
        foreach (['0', '-1', '1.5', '01', '4294967296', '1 OR 1=1', '', ['1']] as $value) {
            $assertSame(null, TramiteValidator::id($value));
        }
    },
];
