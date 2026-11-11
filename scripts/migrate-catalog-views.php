<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/core/bootstrap.php';

$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check', '--apply'], true)) {
    fwrite(STDERR, "Uso: php scripts/migrate-catalog-views.php --check|--apply\n"); exit(1);
}
try {
    $pdo = App\Core\Database::connection();
    if ($pdo->query("SELECT COUNT(*) FROM versiones_esquema WHERE version='009_normalizacion_3fn'")->fetchColumn()) {
        if (App\Services\NormalizationSchema::pending($pdo)!==[]) { throw new RuntimeException('Revisa la migración 009 con migrate-normalization.php.'); }
        $pdo->query('SELECT id,categoria FROM vista_tramites_publicados LIMIT 0');
        $pdo->query('SELECT tramite_id,documento_id FROM vista_documentos_tramite LIMIT 0');
        echo "OK: migración 008 sustituida por la estructura normalizada 009.\n";
        exit(0);
    }
    $sql = preg_replace('/^--.*$/m', '', file_get_contents(BASE_PATH . '/database/migrations/008_vistas_busqueda.sql'));
    $steps = array_values(array_filter(array_map('trim', explode(';', $sql))));
    $column = $pdo->query("SELECT COLUMN_TYPE, GENERATION_EXPRESSION, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='palabras_clave_tramite' AND COLUMN_NAME='palabra_normalizada'")->fetch();
    $indexes = $pdo->query("SHOW INDEX FROM palabras_clave_tramite WHERE Key_name='idx_palabras_normalizada'")->fetchAll();
    if ($column && ($column['COLUMN_TYPE'] !== 'varchar(150)' ||
        str_replace(['`',' ','lower('], ['','','lcase('], strtolower($column['GENERATION_EXPRESSION'])) !== 'lcase(trim(palabra))' ||
        strpos($column['EXTRA'], 'STORED GENERATED') === false)) {
        throw new RuntimeException('La columna palabra_normalizada tiene una definición distinta.');
    }
    if ($indexes && (array_column($indexes, 'Column_name') !== ['palabra_normalizada','tramite_id'] || (int)$indexes[0]['Non_unique'] !== 1)) {
        throw new RuntimeException('El índice idx_palabras_normalizada tiene una definición distinta.');
    }
    $pending = [];
    if (!$column && !$indexes) { $pending[] = $steps[0]; }
    elseif (!$column || !$indexes) { throw new RuntimeException('Columna e índice incompletos; revisar antes de continuar.'); }

    // Compara contra la definición que el propio servidor normaliza, sin reemplazar objetos existentes.
    foreach (['vista_tramites_publicados','vista_documentos_tramite'] as $i => $view) {
        $query = $pdo->prepare('SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $query->execute([$view]); $type = $query->fetchColumn();
        if ($type === false) { $pending[] = $steps[$i+1]; continue; }
        if ($type !== 'VIEW') { throw new RuntimeException("Existe una tabla con el nombre $view."); }
        $probe = 'diri_check_' . bin2hex(random_bytes(8));
        $probeCreated = false;
        try {
            $pdo->exec(str_replace('VIEW '.$view.' AS', 'VIEW '.$probe.' AS', $steps[$i+1]));
            $probeCreated = true;
            $actual = $pdo->query('SHOW CREATE VIEW `'.$view.'`')->fetch()['Create View'];
            $expected = $pdo->query('SHOW CREATE VIEW `'.$probe.'`')->fetch()['Create View'];
            $expected = str_replace('`'.$probe.'`', '`'.$view.'`', $expected);
            // DEFINER depende del usuario instalador; SQL SECURITY INVOKER sí debe coincidir.
            $normalize = fn(string $value): string => preg_replace('/DEFINER=.*? SQL SECURITY/', 'SQL SECURITY', $value);
            if ($normalize($actual) !== $normalize($expected)) { throw new RuntimeException("La vista $view tiene una definición distinta."); }
        } finally { if ($probeCreated) { $pdo->exec('DROP VIEW `'.$probe.'`'); } }
    }
    if ($mode === '--check') {
        echo count($pending) ? 'PENDIENTE: '.count($pending)." cambios. Usa --apply.\n" : "OK: vistas e índice compatibles.\n";
        exit(count($pending) ? 2 : 0);
    }
    foreach ($pending as $step) { $pdo->exec($step); }
    $pdo->exec("INSERT INTO versiones_esquema(version,descripcion) SELECT '008_vistas_busqueda','Vistas del catálogo e índice de alias normalizados' WHERE NOT EXISTS(SELECT 1 FROM versiones_esquema WHERE version='008_vistas_busqueda')");
    echo "OK: vistas e índice disponibles. No se borraron registros.\n";
} catch (Throwable $exception) {
    // No publicar mensajes del driver ni credenciales.
    fwrite(STDERR, ($exception instanceof PDOException ? 'ERROR SQL: revisa permisos y compatibilidad de la base.' : $exception->getMessage()) . "\n"); exit(1);
}
