<?php
use App\Models\Resource;
use App\Services\ResourceValidator;
$tramiteA=insertFixture('tramites',['nombre'=>'Trámite A '.$suffix,'slug'=>'a-'.$suffix,'categoria_id'=>$category]);
$tramiteB=insertFixture('tramites',['nombre'=>'Trámite B '.$suffix,'slug'=>'b-'.$suffix,'categoria_id'=>$category]);
$stepA=resourceSave('pasos_tramite',['tramite_id'=>(string)$tramiteA,'numero_paso'=>'2','titulo'=>'Primero por dependencia']);
$stepB=resourceSave('pasos_tramite',['tramite_id'=>(string)$tramiteA,'numero_paso'=>'1','titulo'=>'Después por dependencia']);
resourceSave('paso_dependencias',['tramite_id'=>(string)$tramiteA,'paso_id'=>(string)$stepB['id'],'paso_previo_id'=>(string)$stepA['id']]);
$ordered=(new \App\Services\RoutePlanner())->steps($tramiteA);
ensure((int)$ordered[0]['id']===(int)$stepA['id'],'Fase 9: las dependencias preceden al orden editorial.');
[, $errors]=ResourceValidator::validate(new Resource('paso_dependencias'),['tramite_id'=>(string)$tramiteA,'paso_id'=>(string)$stepA['id'],'paso_previo_id'=>(string)$stepB['id']],null);
ensure(isset($errors['dependencia']),'Fase 9: ciclos rechazados.');
[, $errors]=ResourceValidator::validate(new Resource('paso_dependencias'),['tramite_id'=>(string)$tramiteB,'paso_id'=>(string)$stepA['id'],'paso_previo_id'=>(string)$stepB['id']],null);
ensure(isset($errors['paso_id']),'Fase 9: pasos de otro trámite rechazados.');
$route=resourceSave('rutas',['nombre'=>'Ruta '.$suffix,'slug'=>'ruta-'.$suffix,'estado'=>'borrador','fuente_id'=>(string)$source]);
resourceSave('ruta_tramites',['ruta_id'=>(string)$route['id'],'tramite_id'=>(string)$tramiteA,'numero_orden'=>'1']);
resourceSave('ruta_tramites',['ruta_id'=>(string)$route['id'],'tramite_id'=>(string)$tramiteB,'numero_orden'=>'2']);
resourceSave('ruta_dependencias',['ruta_id'=>(string)$route['id'],'tramite_id'=>(string)$tramiteB,'tramite_previo_id'=>(string)$tramiteA]);
ensure((new \App\Services\RoutePlanner())->route((int)$route['id'])===null,'Fase 9: rutas en borrador no son públicas.');
