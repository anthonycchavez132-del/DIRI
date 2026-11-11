<?php
use App\Services\ReportService;
use App\Services\ContenidoService;
ensure($ordinary->request($base,'/admin/reportes')[0]===403,'Fase 13: moderación solo administrativa.');
[, $errors]=ReportService::validate(['sucursal_id'=>(string)$branch['id'],'nivel_demanda'=>'baja']);
ensure($errors!==[],'Fase 13: un reporte requiere alguna medición.');
$reports=new ReportService();
[$reportData,$errors]=ReportService::validate(['sucursal_id'=>(string)$branch['id'],'nivel_demanda'=>'media','personas_esperando'=>'20','tiempo_espera'=>'35']);
$reportId=$reports->submit($userIds[1],$reportData);remember('reportes_atencion',['id'=>$reportId]);
$limited=false;try{$reports->submit($userIds[1],$reportData);}catch(DomainException $e){$limited=$e->getCode()===429;}
ensure($limited,'Fase 13: límite de frecuencia aplicado.');
$q=$pdo->prepare('SELECT * FROM reportes_atencion WHERE id=?');$q->execute([$reportId]);$report=$q->fetch();
$reports->moderate($userIds[0],$reportId,'aceptado',ContenidoService::version($report));remember('historico_atencion',['reporte_id'=>$reportId]);
$q=$pdo->prepare('SELECT fuente FROM historico_atencion WHERE reporte_id=?');$q->execute([$reportId]);
ensure($q->fetchColumn()==='DIRI_USUARIOS','Fase 13: moderación conserva procedencia ciudadana.');
$conflict=false;try{$reports->moderate($userIds[0],$reportId,'rechazado',ContenidoService::version($report));}catch(DomainException $e){$conflict=$e->getCode()===409;}
ensure($conflict,'Fase 13: no se modera dos veces ni duplica el histórico.');
ensure($ordinary->request($base,'/reportar')[0]===200,'Fase 13: reportes disponibles para Free.');
ensure($ordinary->request($base,'/reportar',['sucursal_id'=>(string)$branch['id']])[0]===419,'Fase 13: reportes exigen CSRF.');
