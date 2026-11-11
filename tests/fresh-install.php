<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/core/bootstrap.php';
use App\Core\Database;
use App\Models\Resource;
use App\Models\User;
use App\Services\ResourceValidator;
use App\Services\ResourceService;
use App\Services\ContenidoService;

// Todo ocurre en una base nueva con nombre aleatorio, nunca en la base DIRI.
$testDatabase='diri_test_'.bin2hex(random_bytes(12));$created=false;$failure=null;
$property=new ReflectionProperty(Database::class,'connections');$property->setAccessible(true);$previous=$property->getValue();
$pdo=Database::connection(false);
try{
    if(!preg_match('/\Adiri_test_[a-f0-9]{24}\z/',$testDatabase)){throw new RuntimeException('Nombre de prueba inválido.');}
    $sql=file_get_contents(BASE_PATH.'/database/diri.sql');
    $sql=str_replace(['CREATE DATABASE diri ','USE diri;'],['CREATE DATABASE `'.$testDatabase.'` ','USE `'.$testDatabase.'`;'],$sql);
    foreach(App\Services\SqlScript::statements($sql) as $statement){
        $pdo->exec($statement);if(strpos($statement,'CREATE DATABASE')!==false){$created=true;}
    }
    if($pdo->query('SELECT DATABASE()')->fetchColumn()!==$testDatabase){throw new RuntimeException('Base de prueba no seleccionada.');}
    $property->setValue(null,['database'=>$pdo]);
    $count=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'")->fetchColumn();
    if($count!==40||(int)$pdo->query("SELECT COUNT(*) FROM tramites WHERE estado='borrador' AND estado_verificacion='pendiente'")->fetchColumn()!==20){throw new RuntimeException('Esquema inicial incompleto.');}
    echo "PASS: instalación nueva, 40 tablas y 20 borradores.\n";
    if ((int)$pdo->query("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('vista_tramites_publicados','vista_documentos_tramite')")->fetchColumn()!==2 ||
        (int)$pdo->query("SELECT COUNT(*) FROM vista_tramites_publicados")->fetchColumn()!==0 ||
        count($pdo->query("SHOW INDEX FROM palabras_clave WHERE Key_name='idx_palabras_normalizada'")->fetchAll())!==2) {
        throw new RuntimeException('Vistas o índice inicial incompletos.');
    }
    echo "PASS: vistas reales e índice de alias instalados desde cero.\n";
    if (App\Services\NormalizationSchema::pending($pdo)!==[]) { throw new RuntimeException('Normalización inicial incompleta.'); }
    echo "PASS: normalización 009 y triggers consistentes con la instalación inicial.\n";
    $adminId=(new User())->create('Administrador','De prueba','fresh-admin@example.invalid',password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT));
    $pdo->prepare("UPDATE usuarios SET rol_id=(SELECT id FROM roles WHERE nombre='administrador') WHERE id=?")->execute([$adminId]);
    $resource=new Resource('usuarios');$before=$resource->find($resource->key(['id'=>$adminId]));$input=array_map(fn($value)=>(string)$value,$before);$input['estado']='bloqueado';
    [$data,$errors]=ResourceValidator::validate($resource,$input,$before);if($errors!==[]){throw new RuntimeException('Fixture de administrador inválido.');}
    $refused=false;try{(new ResourceService())->save($resource,$data,$resource->key($before),ContenidoService::version($before),$adminId);}catch(InvalidArgumentException $e){$refused=true;}
    if(!$refused||$pdo->query("SELECT estado FROM usuarios WHERE id=$adminId")->fetchColumn()!=='activo'){throw new RuntimeException('Se permitió bloquear al último administrador.');}
    echo "PASS: último administrador protegido y transacción revertida.\n";
    $input['estado']='activo';$input['rol_id']=(string)$pdo->query("SELECT id FROM roles WHERE nombre='usuario'")->fetchColumn();
    [$data,$errors]=ResourceValidator::validate($resource,$input,$before);
    $refused=false;try{(new ResourceService())->save($resource,$data,$resource->key($before),ContenidoService::version($before),$adminId);}catch(InvalidArgumentException $e){$refused=true;}
    if(!$refused){throw new RuntimeException('Se permitió degradar al último administrador.');}
    echo "PASS: último administrador no puede perder su rol.\n";
}catch(Throwable $e){$failure=$e;}
finally{
    if($pdo->inTransaction()){$pdo->rollBack();}$property->setValue(null,$previous);
    if($created){
        if(!preg_match('/\Adiri_test_[a-f0-9]{24}\z/',$testDatabase)){throw new RuntimeException('Destino de limpieza inválido.');}
        $pdo->exec('USE information_schema');$pdo->exec('DROP DATABASE `'.$testDatabase.'`');
    }
}
if($failure!==null){echo "FAIL: instalación o protección de administrador.\n";exit(1);}
echo "OK: base temporal eliminada, sin modificar la instalación real.\n";
