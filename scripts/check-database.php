<?php

declare(strict_types=1);

// Este diagnóstico no se publica como una ruta de la aplicación.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/core/bootstrap.php';

$arguments = array_slice($argv, 1);
if ($arguments !== [] && $arguments !== ['--server']) {
    echo "Uso: php scripts/check-database.php [--server]\n";
    exit(2);
}

$serverOnly = $arguments === ['--server'];

try {
    $pdo = App\Core\Database::connection(!$serverOnly);
    // Prueba de lectura con una consulta preparada real, sin crear tablas ni datos.
    $statement = $pdo->prepare('SELECT ? AS prueba, @@character_set_connection AS charset, DATABASE() AS base_actual');
    $statement->execute(['DIRI']);
    $result = $statement->fetch();

    if ($result['prueba'] !== 'DIRI' || $result['charset'] !== 'utf8mb4') {
        throw new RuntimeException('La conexión no superó la comprobación de consulta y codificación.');
    }

    echo "OK: conexión PDO y consulta preparada con utf8mb4.\n";
    echo $serverOnly
        ? "Modo servidor: no se seleccionó ni creó ninguna base de datos.\n"
        : "Base de datos configurada seleccionada correctamente.\n";
    exit(0);
} catch (RuntimeException $exception) {
    // Solo Database genera los mensajes de configuración/conexión que mostramos.
    // Una excepción PDO de consulta puede contener detalles: se reemplaza por texto genérico.
    $message = $exception instanceof PDOException
        ? 'La consulta de comprobación no pudo completarse.'
        : $exception->getMessage();
    echo 'ERROR: ' . $message . "\n";
    exit(1);
}
