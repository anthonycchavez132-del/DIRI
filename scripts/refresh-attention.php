<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/core/bootstrap.php';
if(count($argv)!==1){echo "Uso: php scripts/refresh-attention.php\n";exit(2);}
try{
    $service=new App\Services\AttentionService();$count=0;
    foreach(App\Core\Database::connection()->query("SELECT id FROM sucursales WHERE estado='activa'")->fetchAll(PDO::FETCH_COLUMN) as $id){if($service->cache((int)$id)){$count++;}}
    echo "Estimaciones actualizadas: $count. Sin llamadas externas.\n";
}catch(Throwable $e){fwrite(STDERR,"No se pudo actualizar el estado de atención.\n");exit(1);}
