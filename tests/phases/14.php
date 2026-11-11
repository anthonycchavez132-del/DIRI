<?php
use App\Services\AttentionService;
$now=gmdate('Y-m-d H:i:s');$samples=[];
ensure((new AttentionService())->current((int)$branch['id'])===null,'Fase 14: un reporte no produce precisión falsa.');
foreach([31,36,42] as $minutes){$samples[]=['tiempo_espera'=>$minutes,'personas_esperando'=>20,'nivel_demanda'=>'media','momento'=>$now];}
$estimate=AttentionService::estimate($samples);
ensure($estimate['tiempo_minimo']===25&&$estimate['tiempo_maximo']===50&&$estimate['confianza']==='baja','Fase 14: intervalo redondeado y confianza conservadora.');
// Dos cuentas adicionales independientes para probar deduplicación y vigencia.
foreach([36,42] as $index=>$minutes){
    $email='extended-extra-'.$index.'-'.$suffix.'@example.invalid';$id=(new \App\Models\User())->create('Prueba','Estimación',$email,password_hash($password,PASSWORD_DEFAULT));$userIds[]=$id;remember('usuarios',['id'=>$id]);
    $reportIdExtra=$reports->submit($id,array_merge($reportData,['tiempo_espera'=>$minutes]));remember('reportes_atencion',['id'=>$reportIdExtra]);
    $q=$pdo->prepare('SELECT * FROM reportes_atencion WHERE id=?');$q->execute([$reportIdExtra]);$reports->moderate($userIds[0],$reportIdExtra,'aceptado',\App\Services\ContenidoService::version($q->fetch()));remember('historico_atencion',['reporte_id'=>$reportIdExtra]);
}
$attentionService=new AttentionService();$current=$attentionService->current((int)$branch['id']);
ensure($current!==null&&$current['fuente']==='ESTIMACION'&&$current['cantidad_observaciones']===3,'Fase 14: estimación de tres personas distintas, con procedencia.');
ensure($attentionService->cache((int)$branch['id']),'Fase 14: estado estimado almacenado.');remember('estado_atencion',['sucursal_id'=>$branch['id']]);
ensure($attentionService->current((int)$branch['id'],new DateTimeImmutable('+2 hours',new DateTimeZone('UTC')))===null,'Fase 14: datos caducados no se muestran como actuales.');
