<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__, 2));

// Compartido por la entrada web y los comandos de consola.
spl_autoload_register(function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }

    $parts = explode('\\', substr($class, 4));
    $parts[0] = strtolower($parts[0]);
    $file = BASE_PATH . '/app/' . implode('/', $parts) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

$config = require BASE_PATH . '/config/app.php';
date_default_timezone_set($config['timezone']);
require BASE_PATH . '/app/helpers/functions.php';
