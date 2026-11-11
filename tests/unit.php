<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/core/bootstrap.php';

// Convierte avisos de PHP en fallos visibles durante las pruebas.
error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$assertSame = static function ($expected, $actual): void {
    if ($expected !== $actual) {
        throw new RuntimeException(
            'Esperado: ' . var_export($expected, true) . PHP_EOL
            . 'Obtenido: ' . var_export($actual, true)
        );
    }
};

$passed = 0;
$failed = 0;
$files = glob(__DIR__ . '/unit/*Test.php');
sort($files);

try {
    if ($files === []) {
        throw new RuntimeException('No se encontraron pruebas unitarias.');
    }
    foreach ($files as $file) {
        $tests = require $file;
        echo PHP_EOL . basename($file) . PHP_EOL;
        foreach ($tests as $name => $test) {
            try {
                $test($assertSame);
                $passed++;
                echo 'PASS: ' . $name . PHP_EOL;
            } catch (Throwable $exception) {
                $failed++;
                echo 'FAIL: ' . $name . PHP_EOL . $exception->getMessage() . PHP_EOL;
            }
        }
    }
} catch (Throwable $exception) {
    $failed++;
    echo 'FAIL: ' . $exception->getMessage() . PHP_EOL;
} finally {
    restore_error_handler();
}

echo PHP_EOL . "Resultado: $passed aprobadas, $failed fallidas." . PHP_EOL;
exit($failed === 0 ? 0 : 1);
