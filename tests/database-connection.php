<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/core/bootstrap.php';

// Pruebas de integración de solo lectura: no requieren la base diri.
function check(bool $condition, string $description): void
{
    if (!$condition) {
        throw new RuntimeException($description);
    }
    echo 'PASS: ' . $description . "\n";
}

try {
    $pdo = App\Core\Database::connection(false);
    check($pdo === App\Core\Database::connection(false), 'La conexion se reutiliza.');
    check($pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION, 'Los errores SQL generan excepciones.');
    check(!$pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES), 'Las consultas preparadas son nativas.');

    $payload = "' OR 1=1; --";
    $unicode = json_decode('"C\u00e9dula, ni\u00f1ez y \ud83c\udde7\ud83c\uddf4"', true, 512, JSON_THROW_ON_ERROR);
    $query = $pdo->prepare('SELECT ? AS entrada, ? AS texto, @@character_set_connection AS charset, DATABASE() AS base_actual');
    $query->execute([$payload, $unicode]);
    $row = $query->fetch();

    check(is_array($row) && $row['entrada'] === $payload, 'El contenido similar a SQL se trata como texto.');
    check($row['texto'] === $unicode && $row['charset'] === 'utf8mb4', 'Tildes y caracteres de cuatro bytes conservan su valor.');
    check($row['base_actual'] === null, 'La prueba no necesita seleccionar una base.');
    $query->closeCursor();

    $multipleStatementsRejected = false;
    try {
        $pdo->prepare('SELECT 1; SELECT 2');
    } catch (PDOException $exception) {
        $multipleStatementsRejected = true;
    }
    check($multipleStatementsRejected, 'Se rechazan varias instrucciones en una consulta.');
    echo "OK: pruebas de integracion completadas sin modificar datos.\n";
    exit(0);
} catch (Throwable $exception) {
    echo "FAIL: no se completaron las pruebas de conexion; revisa el diagnostico de consola.\n";
    exit(1);
}
