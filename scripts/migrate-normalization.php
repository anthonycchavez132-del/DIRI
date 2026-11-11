<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/core/bootstrap.php';
use App\Core\Database;
use App\Services\NormalizationSchema;

$mode=$argv[1]??'--check';
if (!in_array($mode,['--check','--apply'],true)) { echo "Uso: --check|--apply\n"; exit(1); }
try {
    $pdo=Database::connection(); $pending=NormalizationSchema::pending($pdo);
    if ($mode==='--check') { echo count($pending)?'PENDIENTE: '.count($pending)." pasos de normalización.\n":"OK: estructura normalizada comprobada.\n"; exit($pending?2:0); }
    if ($pending) {
        // Respaldo privado previo al DDL, que no admite rollback transaccional.
        $backup=['fecha'=>gmdate('c'),'tablas'=>[]];
        $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $pdo->beginTransaction();
        try {
            foreach ($pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN) as $table) {
                if (!preg_match('/\A[a-z_]+\z/',$table)) { throw new RuntimeException('Nombre de tabla inesperado.'); }
                $backup['tablas'][$table]=['estructura'=>$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch()['Create Table'],'filas'=>$pdo->query('SELECT * FROM `'.$table.'`')->fetchAll()];
            }
            $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
        $path=BASE_PATH.'/storage/backups/normalizacion-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
        if (!is_dir(dirname($path)) && !mkdir(dirname($path),0700,true)) { throw new RuntimeException('No se pudo crear la carpeta de respaldo.'); }
        if (file_put_contents($path,json_encode($backup,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX)===false) { throw new RuntimeException('No se pudo guardar el respaldo.'); }
        echo "Respaldo privado guardado antes de modificar la estructura.\n";
    }
    NormalizationSchema::apply($pdo);
    echo "OK: normalización 009 aplicada y comprobada.\n";
} catch (Throwable $e) {
    fwrite(STDERR,($e instanceof PDOException?'ERROR SQL: revisa compatibilidad y permisos. Código '.($e->errorInfo[1]??'?'):$e->getMessage())."\n"); exit(1);
}
