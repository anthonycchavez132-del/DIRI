<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/core/bootstrap.php';
require __DIR__.'/support/HttpTest.php';

use App\Core\Database;
use App\Services\DatabaseSearch;
use App\Services\CatalogService;
use App\Models\TramiteDocumento;

$pdo=Database::connection();
$failure=null;
try {
    // Fixtures dentro de una transacción: rollback también ante errores.
    $pdo->beginTransaction();
    $suffix=bin2hex(random_bytes(8)); $word='alias'.$suffix;
    $pdo->prepare('INSERT INTO categorias(nombre,slug,estado) VALUES(?,?,1)')->execute(['Categoría '.$suffix,'cat-'.$suffix]);
    $category=(int)$pdo->lastInsertId();
    $insert=$pdo->prepare("INSERT INTO tramites(nombre,slug,descripcion,categoria_id,estado) VALUES(?,?,?,?,'publicado')");
    $insert->execute(['AAA '.$word,'parcial-'.$suffix,'Coincidencia en el nombre',$category]);
    $partial=(int)$pdo->lastInsertId();
    $insert->execute(['ZZZ '.$suffix,'exacto-'.$suffix,'Coincidencia en un alias',$category]);
    $exact=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO palabras_clave_tramite(tramite_id,palabra) VALUES(?,?)')->execute([$exact,'  '.strtoupper($word).'  ']);
    $alias=$pdo->query("SELECT p.palabra,c.palabra_normalizada FROM palabras_clave_tramite p JOIN palabras_clave c ON c.palabra=p.palabra WHERE p.tramite_id=$exact")->fetch();
    ensure($alias['palabra_normalizada']===$word && $alias['palabra']==='  '.strtoupper($word).'  ','Columna calculada normaliza sin cambiar el alias original.');
    $search=new DatabaseSearch();
    $result=$search->search($word);
    ensure($result['total']===2 && (int)$result['items'][0]['id']===$exact,'Alias exacto tiene prioridad sobre coincidencia parcial.');
    ensure($search->search(substr($word,0,-1))['total']===2,'La búsqueda parcial mantiene sus resultados.');
    ensure($search->search("' OR 1=1 --")['items']===$search->search('or 1')['items'],'La consulta usa parámetros y trata la inyección como texto.');
    $plan=$pdo->prepare('EXPLAIN SELECT palabra FROM palabras_clave WHERE palabra_normalizada=LOWER(TRIM(?))');
    $plan->execute([$word]); $explain=$plan->fetch();
    ensure($explain['key']==='idx_palabras_normalizada' && $explain['type']==='ref','EXPLAIN confirma búsqueda ref mediante idx_palabras_normalizada.');
    $pdo->prepare('UPDATE palabras_clave_tramite SET palabra=? WHERE tramite_id=?')->execute(['Cambio'.$suffix,$exact]);
    ensure($search->search($word)['total']===1,'Actualizar alias actualiza también la columna y el índice.');
    $pdo->prepare('INSERT INTO documentos(nombre,descripcion) VALUES(?,?)')->execute(['Documento '.$suffix,'Descripción del documento']);
    $document=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO tramite_documentos(tramite_id,documento_id,obligatorio,cantidad,presentacion) VALUES(?,?,1,1,?)')->execute([$exact,$document,'original']);
    $documents=(new TramiteDocumento())->forTramite($exact);
    ensure(count($documents)===1 && (int)$documents[0]['documento_id']===$document,'Modelo obtiene los documentos usando la vista con JOIN.');
    $pdo->prepare('UPDATE documentos SET nombre=? WHERE id=?')->execute(['Renombrado '.$suffix,$document]);
    ensure((new TramiteDocumento())->forTramite($exact)[0]['nombre']==='Renombrado '.$suffix,'La vista refleja cambios sin copiar registros.');
    $pdo->exec("UPDATE tramites SET estado='borrador' WHERE id=$exact");
    ensure((new CatalogService())->detail('exacto-'.$suffix)===null,'La vista pública excluye borradores.');
    ensure(count((new TramiteDocumento())->forTramite($exact))===1,'La administración conserva los documentos de borradores.');
    $pdo->exec("UPDATE categorias SET estado=0 WHERE id=$category");
    ensure($search->search($word)['total']===0,'Una categoría inactiva excluye sus trámites públicos.');
} catch (Throwable $e) { $failure=$e; }
finally { if ($pdo->inTransaction()) { $pdo->rollBack(); } }
if ($failure) { fwrite(STDERR,'FAIL: '.$failure->getMessage()."\n"); exit(1); }
echo "OK: fixtures revertidos, vistas e índice comprobados.\n";
