<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__,2).'/app/core/bootstrap.php';
use App\Core\Database;
// Recuperación exclusiva de fixtures de extended-flow tras un fallo de limpieza.
$pdo=Database::connection();
$rows=$pdo->query("SELECT id,email FROM usuarios WHERE email LIKE 'extended-%@example.invalid'")->fetchAll();$suffixes=[];
foreach($rows as $row){if(preg_match('/\Aextended-(?:admin|user)-([a-f0-9]{20})@example\.invalid\z/',$row['email'],$m)){$suffixes[$m[1]]=true;}}
foreach(array_keys($suffixes) as $suffix){
    $pdo->beginTransaction();
    try{
        $query=$pdo->prepare('SELECT id FROM tramites WHERE slug IN (?,?)');$query->execute(['a-'.$suffix,'b-'.$suffix]);$tramites=$query->fetchAll(PDO::FETCH_COLUMN);
        $query=$pdo->prepare('SELECT id FROM rutas WHERE slug=?');$query->execute(['ruta-'.$suffix]);$routes=$query->fetchAll(PDO::FETCH_COLUMN);
        foreach($routes as $id){foreach(['ruta_dependencias','ruta_tramites'] as $table){$pdo->prepare("DELETE FROM $table WHERE ruta_id=?")->execute([$id]);}$pdo->prepare('DELETE FROM rutas WHERE id=?')->execute([$id]);}
        foreach($tramites as $id){
            foreach(['paso_costos','paso_documentos','paso_dependencias'] as $table){$pdo->prepare("DELETE r FROM $table r JOIN pasos_tramite p ON p.id=r.paso_id WHERE p.tramite_id=?")->execute([$id]);}
            foreach(['pasos_tramite','costos','tramite_sucursales','tramite_fuentes'] as $table){$pdo->prepare("DELETE FROM $table WHERE tramite_id=?")->execute([$id]);}
            $words=$pdo->prepare('SELECT palabra FROM palabras_clave_tramite WHERE tramite_id=?');$words->execute([$id]);
            $pdo->prepare('DELETE FROM palabras_clave_tramite WHERE tramite_id=?')->execute([$id]);
            foreach($words->fetchAll(PDO::FETCH_COLUMN) as $word){$pdo->prepare('DELETE FROM palabras_clave WHERE palabra=? AND NOT EXISTS(SELECT 1 FROM palabras_clave_tramite WHERE palabra=?)')->execute([$word,$word]);}
            $pdo->prepare('DELETE FROM tramites WHERE id=?')->execute([$id]);
        }
        $query=$pdo->prepare('SELECT id FROM sucursales WHERE nombre=?');$query->execute(['Sucursal A '.$suffix]);
        foreach($query->fetchAll(PDO::FETCH_COLUMN) as $id){$pdo->prepare('DELETE FROM horarios WHERE sucursal_id=?')->execute([$id]);$pdo->prepare('DELETE FROM sucursales WHERE id=?')->execute([$id]);}
        $pdo->prepare('DELETE FROM instituciones WHERE nombre=?')->execute(['Institucion de prueba '.$suffix]);
        $pdo->prepare('DELETE FROM fuentes WHERE nombre IN (?,?)')->execute(['Fuente de prueba '.$suffix,'Fuente revisada '.$suffix]);
        foreach(['admin','user'] as $label){$email='extended-'.$label.'-'.$suffix.'@example.invalid';$q=$pdo->prepare('SELECT id FROM usuarios WHERE email=?');$q->execute([$email]);$id=$q->fetchColumn();if($id){$pdo->prepare('DELETE FROM auditoria_administrativa WHERE usuario_id=?')->execute([$id]);$pdo->prepare('DELETE FROM usuarios WHERE id=? AND email=?')->execute([$id,$email]);}}
        $pdo->commit();echo "Fixture recuperado.\n";
    }catch(Throwable $e){$pdo->rollBack();throw $e;}
}
