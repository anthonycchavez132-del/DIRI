<?php

declare(strict_types=1);

use App\Services\TramiteValidator;

return [
    'Rechaza enlaces ejecutables y enlaces con credenciales' => static function ($assertSame): void {
        $assertSame(null, external_url('javascript:alert(1)'));
        $assertSame(null, external_url('https://usuario:clave@example.com/'));
        $assertSame(null, external_url(null));
    },
    'Rechaza identificadores negativos decimales y fuera de rango' => static function ($assertSame): void {
        foreach (['0', '-1', '1.5', '01', '4294967296', '1 OR 1=1', '', ['1']] as $value) {
            $assertSame(null, TramiteValidator::id($value));
        }
    },
    'Escapa etiquetas y comillas antes de mostrar contenido' => static function ($assertSame): void {
        $assertSame('&lt;script&gt;&quot;x&quot;&lt;/script&gt;', e('<script>"x"</script>'));
    },
];
