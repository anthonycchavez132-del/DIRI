<?php

declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/core/bootstrap.php';
require __DIR__.'/support/HttpTest.php';

use App\Core\Database;
use App\Services\CatalogService;
use App\Services\DatabaseSearch;
use App\Services\OfficialCatalogImporter;

$base=rtrim($argv[1]??'http://127.0.0.1/DIRI/public','/');$parts=parse_url($base);
if($parts===false||!in_array($parts['host']??'', ['127.0.0.1','localhost','[::1]'],true)||!in_array($parts['scheme']??'', ['http','https'],true)||isset($parts['user'])||isset($parts['pass'])||isset($parts['query'])||isset($parts['fragment'])){exit(2);}
$failure=null;
try{
    $manifest=(new OfficialCatalogImporter())->load(BASE_PATH.'/database/catalogo/bolivia-la-paz-20261021.json');
    $pdo=Database::connection();$catalog=new CatalogService();$browser=new TestBrowser();$verified=0;$referential=0;
    foreach($manifest['procedures'] as $expected){
        $item=$catalog->detail($expected['slug']);
        ensure($item!==null,'Ficha pública: '.$expected['slug']);
        ensure($item['fecha_verificacion']===$manifest['reviewed_on'],'Fecha de consulta conservada.',false);
        $sourceTypes=array_column($item['fuentes'],'tipo','id');
        ensure(($sourceTypes[$item['fuente_id']]??null)==='oficial','La fuente principal es oficial.',false);
        ensure($item['estado_verificacion']===$expected['estado_verificacion'],'Se conserva la clasificación individual.',false);
        if($item['estado_verificacion']==='verificado'){$verified++;}else{$referential++;}
        [$status,$html]=$browser->request($base,'/tramite?slug='.$expected['slug']);
        ensure($status===200&&str_contains($html,'Fuente:')&&str_contains($html,'21/10/2026'),'Ficha HTTP con evidencia.',false);
    }
    ensure($verified===11&&$referential===9,'Once fichas verificadas y nueve referenciales; no se verifican todas por defecto.');
    foreach([
        'quiero sacar mi carnet'=>'cedula-de-identidad',
        'quiero renovar mi licencia'=>'renovacion-de-licencia',
        'quiero abrir una tienda'=>'apertura-de-negocio',
        'necesito mi certificado de nacimiento'=>'certificado-de-nacimiento',
        'registrar empresa'=>'registro-de-empresa',
        'alcaldia la paz'=>'tramites-municipales',
    ] as $query=>$slug){
        $found=(new DatabaseSearch())->search($query);
        ensure(in_array($slug,array_column($found['items'],'slug'),true),'Búsqueda natural: '.$query);
    }
    $passport=$catalog->detail('pasaporte');
    ensure($passport['costos'][0]['monto']===null,'Pasaporte conserva importe desconocido ante discrepancia de fuentes.');
    $birth=$catalog->detail('certificado-de-nacimiento');
    ensure($birth['costos'][0]['monto']===null,'Duplicado de nacimiento no convierte tarifas contradictorias en un importe confirmado.');
    $license=$catalog->detail('licencia-de-funcionamiento');
    ensure($license['costos'][0]['monto']==='0.00'&&str_contains($license['costos'][0]['observaciones'],'patentes'),'La gratuidad municipal se limita a la emisión de la modalidad consultada.');
    $id=$catalog->detail('cedula-de-identidad');$conditional=array_values(array_filter($id['documentos'],fn($row)=>str_starts_with((string)$row['observaciones'],'Condicional:')));
    ensure($conditional!==[]&&str_contains(obligation_label($conditional[0]),'Según tu caso'),'Los documentos condicionales no se rotulan como opcionales.');
    $q=$pdo->prepare('SELECT descripcion FROM versiones_esquema WHERE version=?');$q->execute([$manifest['batch']]);
    ensure($q->fetchColumn()==='Catálogo oficial La Paz; SHA256 '.hash('sha256',json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)),'Lote aplicado coincide con el manifiesto revisado.');
    [$status,$json]=$browser->request($base,'/api/v1/tramites?q=carnet');$payload=json_decode($json,true,512,JSON_THROW_ON_ERROR);
    ensure($status===200&&$payload['data']['total']>=3,'El catálogo cargado está disponible también en la API pública.');
}catch(Throwable $e){$failure=$e;}
if($failure!==null){echo 'FAIL: '.($failure instanceof PDOException?'Consulta SQL fallida.':$failure->getMessage()).PHP_EOL;exit(1);}
echo "OK: catálogo oficial consultado y pruebas de solo lectura aprobadas.\n";
