<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/core/bootstrap.php';
require __DIR__.'/support/HttpTest.php';
use App\Core\Database;
use App\Services\NormalizationSchema;

function refuses(PDO $pdo,string $sql,array $values,string $message): void {
    try { $pdo->prepare($sql)->execute($values); }
    catch (PDOException $e) {
        if (in_array((int)($e->errorInfo[1]??0),[1451,1452,1644],true)) { ensure(true,$message); return; }
        throw $e;
    }
    throw new RuntimeException($message);
}
$pdo=Database::connection();$failure=null;
try {
    ensure(NormalizationSchema::pending($pdo)===[],'Estructura y triggers de normalización completos.');
    foreach (NormalizationSchema::TABLES as $table) {
        $q=$pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME='tramite_id'");$q->execute([$table]);
        ensure((int)$q->fetchColumn()===0,'Sin trámite redundante: '.$table);
    }
    $pdo->beginTransaction();$suffix=bin2hex(random_bytes(8));
    $cat=(int)$pdo->query('SELECT id FROM categorias ORDER BY id LIMIT 1')->fetchColumn();
    $q=$pdo->prepare('INSERT INTO tramites(nombre,slug,categoria_id) VALUES(?,?,?)');
    $q->execute(['Prueba 3FN','nf-a-'.$suffix,$cat]);$a=(int)$pdo->lastInsertId();
    $q->execute(['Prueba 3FN','nf-b-'.$suffix,$cat]);$b=(int)$pdo->lastInsertId();
    $q=$pdo->prepare('INSERT INTO pasos_tramite(tramite_id,numero_paso,titulo) VALUES(?,?,?)');
    $q->execute([$a,1,'Primero']);$s1=(int)$pdo->lastInsertId();$q->execute([$a,2,'Segundo']);$s2=(int)$pdo->lastInsertId();$q->execute([$b,1,'Otro']);$other=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO documentos(nombre) VALUES(?)')->execute(['Documento NF '.$suffix]);$doc=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO tramite_documentos(tramite_id,documento_id) VALUES(?,?)')->execute([$a,$doc]);
    $pdo->prepare('INSERT INTO costos(tramite_id,concepto) VALUES(?,?)')->execute([$a,'Concepto']);$cost=(int)$pdo->lastInsertId();
    $user=(int)$pdo->query('SELECT id FROM usuarios ORDER BY id LIMIT 1')->fetchColumn();
    if (!$user) { throw new RuntimeException('Esta prueba requiere al menos una cuenta existente.'); }
    $pdo->prepare('INSERT INTO progreso_tramites(usuario_id,tramite_id) VALUES(?,?)')->execute([$user,$a]);$progress=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO paso_documentos(paso_id,documento_id) VALUES(?,?)')->execute([$s1,$doc]);
    $pdo->prepare('INSERT INTO paso_costos(paso_id,costo_id) VALUES(?,?)')->execute([$s1,$cost]);
    $pdo->prepare('INSERT INTO paso_dependencias(paso_id,paso_previo_id) VALUES(?,?)')->execute([$s2,$s1]);
    $pdo->prepare('INSERT INTO progreso_documentos(progreso_tramite_id,documento_id,completado) VALUES(?,?,1)')->execute([$progress,$doc]);
    $pdo->prepare('INSERT INTO progreso_pasos(progreso_tramite_id,paso_id,completado) VALUES(?,?,1)')->execute([$progress,$s1]);
    ensure(true,'Las cinco relaciones aceptan registros válidos sin repetir el trámite.');
    refuses($pdo,'UPDATE paso_documentos SET paso_id=? WHERE paso_id=?',[$other,$s1],'UPDATE no mezcla documentos de otro trámite.');
    refuses($pdo,'UPDATE paso_costos SET paso_id=? WHERE paso_id=?',[$other,$s1],'UPDATE no mezcla costos de otro trámite.');
    refuses($pdo,'UPDATE paso_dependencias SET paso_previo_id=? WHERE paso_id=?',[$other,$s2],'UPDATE no mezcla dependencias de otro trámite.');
    refuses($pdo,'UPDATE progreso_pasos SET paso_id=? WHERE progreso_tramite_id=?',[$other,$progress],'UPDATE no mezcla pasos de otro seguimiento.');
    refuses($pdo,'UPDATE pasos_tramite SET tramite_id=? WHERE id=?',[$b,$s1],'No se mueve un paso que tiene relaciones.');
    refuses($pdo,'UPDATE costos SET tramite_id=? WHERE id=?',[$b,$cost],'No se mueve un costo asociado a un paso.');
    refuses($pdo,'UPDATE progreso_tramites SET tramite_id=? WHERE id=?',[$b,$progress],'No se mueve un seguimiento con progreso guardado.');
    refuses($pdo,'DELETE FROM tramite_documentos WHERE tramite_id=? AND documento_id=?',[$a,$doc],'No se quita un documento usado en pasos o seguimiento.');
    refuses($pdo,'UPDATE tramite_documentos SET tramite_id=? WHERE tramite_id=? AND documento_id=?',[$b,$a,$doc],'No se cambia la asociación documental mientras esté en uso.');
    $word='compartida'.$suffix;
    $q=$pdo->prepare('INSERT INTO palabras_clave_tramite(tramite_id,palabra) VALUES(?,?)');$q->execute([$a,$word]);$q->execute([$b,$word]);
    $q=$pdo->prepare('SELECT COUNT(*) FROM palabras_clave WHERE palabra=?');$q->execute([$word]);
    ensure((int)$q->fetchColumn()===1,'Dos trámites comparten una sola palabra en el catálogo.');
    $q=$pdo->prepare('SELECT COUNT(*) FROM palabras_clave_tramite WHERE palabra=?');$q->execute([$word]);
    ensure((int)$q->fetchColumn()===2,'La relación mantiene ambas asociaciones de búsqueda.');
} catch (Throwable $e) { $failure=$e; }
finally { if ($pdo->inTransaction()) { $pdo->rollBack(); } }
if ($failure) { fwrite(STDERR,'FAIL: '.$failure->getMessage()."\n"); exit(1); }
echo "OK: normalización e integridad comprobadas; fixtures revertidos.\n";
