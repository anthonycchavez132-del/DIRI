<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {http_response_code(404); exit;}
require dirname(__DIR__) . '/app/core/bootstrap.php';

if (count($argv)!==2 || !in_array($argv[1],['--check','--apply'],true)) {
    echo "Uso: php scripts/import-official-catalog.php --check|--apply\n";
    exit(2);
}
try {
    $query=App\Core\Database::connection()->query("SELECT u.id FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.estado='activo' AND r.nombre='administrador' ORDER BY u.id LIMIT 1");
    $actor=$query->fetchColumn();
    if (!$actor) {throw new RuntimeException('Se requiere una cuenta administradora activa para la auditoría.');}
    $importer=new App\Services\OfficialCatalogImporter();
    $manifest=$importer->load(BASE_PATH.'/database/catalogo/bolivia-la-paz-20261021.json');
    $result=$importer->run($manifest,(int)$actor,$argv[1]==='--apply');
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),PHP_EOL;
} catch (Throwable $exception) {
    $message=$exception instanceof PDOException ? 'Error SQL: carga revertida, sin cambios parciales.' : $exception->getMessage();
    fwrite(STDERR,"ERROR: $message\n");
    exit(1);
}
