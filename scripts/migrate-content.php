<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/core/bootstrap.php';

try {
    $pdo = App\Core\Database::connection();
    $column = $pdo->query("SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tramites' AND COLUMN_NAME = 'revision'")->fetch();
    if ($column === false) {
        $pdo->exec('ALTER TABLE tramites ADD COLUMN revision INT UNSIGNED NOT NULL DEFAULT 1');
    } elseif (!preg_match('/\Aint(?:\([0-9]+\))? unsigned\z/i', $column['COLUMN_TYPE']) || $column['IS_NULLABLE'] !== 'NO' || (string) $column['COLUMN_DEFAULT'] !== '1') {
        throw new RuntimeException('Definición inesperada de revision.');
    }
    $pdo->exec("INSERT INTO versiones_esquema (version, descripcion)
        SELECT '007_revision_contenido', 'Revisión de trámites y protección de cambios de requisitos y documentos'
        WHERE NOT EXISTS (SELECT 1 FROM versiones_esquema WHERE version = '007_revision_contenido')");
    echo "OK: migracion de contenido aplicada o ya disponible. No se borraron datos.\n";
} catch (Throwable $exception) {
    echo "ERROR: no se pudo completar la migracion de contenido. Revisa instalacion y permisos.\n";
    exit(1);
}
