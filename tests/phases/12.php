<?php
use App\Services\ProgressService;
ensure($ordinary->request($base,'/mi-checklist?slug='.$slugA)[0]===403,'Fase 12: herramientas personales exigen Premium.');
$pdo->prepare("UPDATE usuarios SET plan_id=(SELECT id FROM planes WHERE nombre='premium') WHERE id=?")->execute([$userIds[1]]);
$documentId=insertFixture('documentos',['nombre'=>'Documento temporal '.$suffix]);
$pdo->prepare('INSERT INTO tramite_documentos (tramite_id,documento_id,obligatorio) VALUES (?,?,1)')->execute([$tramiteA,$documentId]);remember('tramite_documentos',['tramite_id'=>$tramiteA,'documento_id'=>$documentId]);
$progress=new ProgressService();$progress->change($userIds[1],$tramiteA,'iniciar','');
$rejected=false;try{$progress->change($userIds[1],$tramiteA,'estado','completado');}catch(DomainException $e){$rejected=$e->getCode()===422;}
ensure($rejected,'Fase 12: no finaliza con documentos o pasos pendientes.');
$rejected=false;try{$progress->change($userIds[1],$tramiteA,'paso','1',(int)$stepB['id']);}catch(DomainException $e){$rejected=$e->getCode()===409;}
ensure($rejected,'Fase 12: checklist respeta dependencias.');
$progress->change($userIds[1],$tramiteA,'documento','1',$documentId);
$progress->change($userIds[1],$tramiteA,'paso','1',(int)$stepA['id']);$progress->change($userIds[1],$tramiteA,'paso','1',(int)$stepB['id']);
$progress->change($userIds[1],$tramiteA,'estado','completado');$progress->change($userIds[1],$tramiteA,'favorito','1');
ensure($progress->read($userIds[1],$tramiteA)['progress']['estado']==='completado','Fase 12: progreso completo guardado.');
$pdo->prepare("UPDATE usuarios SET plan_id=(SELECT id FROM planes WHERE nombre='premium') WHERE id=?")->execute([$userIds[0]]);
ensure($progress->read($userIds[0],$tramiteA)['progress']===null,'Fase 12: progreso aislado por usuario.');
ensure($ordinary->request($base,'/mi-checklist?slug='.$slugA)[0]===200,'Fase 12: checklist Premium disponible.');
ensure($ordinary->request($base,'/mi-checklist/guardar',['tramite_id'=>(string)$tramiteA,'accion'=>'favorito','valor'=>'0'])[0]===419,'Fase 12: progreso exige CSRF.');
$pdo->prepare("UPDATE usuarios SET plan_id=(SELECT id FROM planes WHERE nombre='free') WHERE id=?")->execute([$userIds[1]]);
ensure($ordinary->request($base,'/mi-checklist?slug='.$slugA)[0]===403,'Fase 12: retirar Premium revoca acceso en la siguiente solicitud.');
