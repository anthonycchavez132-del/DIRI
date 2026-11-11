<?php

declare(strict_types=1);

return [
    'name' => 'DIRI',
    'timezone' => 'America/La_Paz',
    // En Apache, SCRIPT_NAME incluye la subcarpeta donde está instalado DIRI.
    'base_path' => rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/.'),
];
