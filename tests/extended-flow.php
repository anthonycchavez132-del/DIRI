<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/core/bootstrap.php';
require __DIR__.'/support/HttpTest.php';
use App\Core\Database;
use App\Models\Resource;
use App\Models\User;
use App\Services\ResourceValidator;
use App\Services\ResourceService;
use App\Services\ContenidoService;

$base=rtrim($argv[1]??'http://127.0.0.1:8087','/');$parts=parse_url($base);
if($parts===false||!in_array($parts['host']??'', ['127.0.0.1','localhost','[::1]'],true)||!in_array($parts['scheme']??'', ['http','https'],true)||isset($parts['user'])||isset($parts['pass'])||isset($parts['query'])||isset($parts['fragment'])){echo "Local URL required\n";exit(2);}
$pdo=Database::connection();$suffix=bin2hex(random_bytes(10));$password='Temporal-'.bin2hex(random_bytes(16));
$journal=[];$userIds=[];$emails=[];$admin=new TestBrowser();$ordinary=new TestBrowser();$failure=null;
function remember(string $table,array $where): void {global $journal;$journal[]=[$table,$where];}
function insertFixture(string $table,array $values): int {
    global $pdo;$sql='INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($values)).'`) VALUES ('.implode(',',array_fill(0,count($values),'?')).')';
    $pdo->prepare($sql)->execute(array_values($values));$id=(int)$pdo->lastInsertId();remember($table,['id'=>$id]);return $id;
}
function resourceSave(string $name,array $values,?array $before=null,?int $actor=null): array {
    global $userIds;$values=array_map(fn($value)=>is_int($value)||is_float($value)?(string)$value:$value,$values);$resource=new Resource($name);[$data,$errors]=ResourceValidator::validate($resource,$values,$before);
    ensure($errors===[],'Valid resource fixture: '.$name,false);
    (new ResourceService())->save($resource,$data,$before===null?'':$resource->key($before),$before===null?'':ContenidoService::version($before),$actor??$userIds[0]);
    if($before!==null){return $resource->find($resource->key($before));}
    if($resource->definition['key']===['id']){$row=$resource->rows()[0];remember($name,['id'=>$row['id']]);return $row;}
    $where=[];foreach($resource->definition['key'] as $key){$where[$key]=$data[$key];}remember($name,$where);return $resource->find($resource->key($data));
}
function authenticateExtended(TestBrowser $browser,string $email): void {
    global $base,$password;[, $html]=$browser->request($base,'/login');ensure($browser->request($base,'/login',['_token'=>token($html),'email'=>$email,'password'=>$password])[0]===303,'Fixture login',false);
}
try{
    foreach(['admin','user'] as $label){$email='extended-'.$label.'-'.$suffix.'@example.invalid';$emails[]=$email;$id=(new User())->create('Prueba '.$label,'Temporal',$email,password_hash($password,PASSWORD_DEFAULT));$userIds[]=$id;remember('usuarios',['id'=>$id]);}
    $pdo->prepare("UPDATE usuarios SET rol_id=(SELECT id FROM roles WHERE nombre='administrador') WHERE id=?")->execute([$userIds[0]]);
    $source=(int)$pdo->query("SELECT id FROM fuentes WHERE tipo='DIRI' ORDER BY id LIMIT 1")->fetchColumn();
    $category=(int)$pdo->query('SELECT id FROM categorias ORDER BY id LIMIT 1')->fetchColumn();
    authenticateExtended($admin,$emails[0]);authenticateExtended($ordinary,$emails[1]);
    foreach(glob(__DIR__.'/phases/*.php') as $phase){require $phase;}
}catch(Throwable $exception){$failure=$exception;}
finally{
    $pdo->beginTransaction();
    foreach($userIds as $id){
        foreach(['auditoria_administrativa','historial_busquedas','rutas_guardadas','favoritos'] as $table){$pdo->prepare('DELETE FROM `'.$table.'` WHERE usuario_id=?')->execute([$id]);}
        $pdo->prepare('DELETE FROM progreso_tramites WHERE usuario_id=?')->execute([$id]);
    }
    // El orden del esquema respeta las claves foráneas, incluso cuando una
    // edición asocia una fila antigua con otra creada después.
    preg_match_all('/CREATE TABLE ([a-z_]+) /',file_get_contents(BASE_PATH.'/database/diri.sql'),$tables);
    $order=array_flip(array_reverse($tables[1]));$cleanup=$journal;
    usort($cleanup,fn($a,$b)=>$order[$a[0]]<=>$order[$b[0]]);
    foreach($cleanup as [$table,$where]){
        $sql='DELETE FROM `'.$table.'` WHERE '.implode(' AND ',array_map(fn($key)=>'`'.$key.'`=?',array_keys($where)));$pdo->prepare($sql)->execute(array_values($where));
    }
    $pdo->commit();
    foreach([$admin->ip,$ordinary->ip] as $ip){foreach($emails as $email){$path=BASE_PATH.'/storage/cache/login-'.hash('sha256',$email.'|'.$ip).'.json';if(is_file($path)){unlink($path);}}}
}
if($failure!==null){echo 'FAIL: '.($failure instanceof PDOException?'SQL code '.($failure->errorInfo[1]??'?'):$failure->getMessage())."\n";exit(1);}
echo "OK: extended phase checks; temporary fixtures removed.\n";
