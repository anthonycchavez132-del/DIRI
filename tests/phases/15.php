<?php
use App\Services\RecommendationService;
ensure($ordinary->request($base,'/comparar-sucursales?slug='.$slugA)[0]===403,'Fase 15: comparación protegida por plan.');
resourceSave('tramite_sucursales',['tramite_id'=>(string)$tramiteA,'sucursal_id'=>(string)$branch['id'],'disponible'=>'1','fuente_id'=>(string)$source]);
// La edición exige revisión; solo esta fila temporal se publica para probar.
$pdo->prepare("UPDATE tramites SET estado='publicado' WHERE id=?")->execute([$tramiteA]);
$localNow=new DateTimeImmutable('now',new DateTimeZone('America/La_Paz'));
$exception=resourceSave('excepciones_horario',['sucursal_id'=>(string)$branch['id'],'fecha'=>$localNow->format('Y-m-d'),'cerrada'=>'0','hora_apertura'=>'00:00','hora_cierre'=>'23:59:59','fuente_id'=>(string)$source]);
$comparison=(new RecommendationService())->compare($userIds[0],$tramiteA,-16.5,-68.1);
ensure((int)$comparison['recommended_id']===(int)$branch['id']&&$comparison['items'][0]['distancia_km']===0.0,'Fase 15: recomienda disponible, abierta y con espera reciente.');
$exception=resourceSave('excepciones_horario',array_merge($exception,['cerrada'=>'1','hora_apertura'=>'','hora_cierre'=>'']),$exception);
$comparison=(new RecommendationService())->compare($userIds[0],$tramiteA);
ensure($comparison['recommended_id']===null,'Fase 15: un cierre excepcional impide recomendar.');
ensure(abs(RecommendationService::distance(0,0,0,1)-111.195)<0.1,'Fase 15: cálculo de distancia geográfica comprobado.');
ensure($admin->request($base,'/comparar-sucursales?slug='.$slugA.'&latitud[]=1')[0]===422,'Fase 15: coordenadas con tipo inválido rechazadas.');
