<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
ensure($ordinary->request($base,'/admin/gestion?recurso=instituciones')[0]===403,'Fase 8: acceso administrativo protegido.');
ensure($admin->request($base,'/admin/gestion?recurso=usuarios_inventados')[0]===404,'Fase 8: no se consultan tablas arbitrarias.');
$institution=resourceSave('instituciones',['nombre'=>'Institucion de prueba '.$suffix,'estado'=>'1','fuente_id'=>(string)$source]);
$branch=resourceSave('sucursales',['institucion_id'=>(string)$institution['id'],'nombre'=>'Sucursal A '.$suffix,'estado'=>'activa','ciudad'=>'Ciudad de prueba','latitud'=>'-16.5','longitud'=>'-68.1','fuente_id'=>(string)$source]);
$hours=resourceSave('horarios',['sucursal_id'=>(string)$branch['id'],'dia_semana'=>'1','hora_apertura'=>'08:00','hora_cierre'=>'12:00','fuente_id'=>(string)$source]);
[$invalid,$errors]=\App\Services\ResourceValidator::validate(new \App\Models\Resource('horarios'),['sucursal_id'=>(string)$branch['id'],'dia_semana'=>'1','hora_apertura'=>'11:00','hora_cierre'=>'13:00'],null);
ensure($errors!==[],'Fase 8: turnos superpuestos rechazados.');
[$invalid,$errors]=\App\Services\ResourceValidator::validate(new \App\Models\Resource('sucursales'),['institucion_id'=>(string)$institution['id'],'nombre'=>'Invalida','estado'=>'activa','latitud'=>'-16'],null);
ensure($errors!==[],'Fase 8: coordenadas incompletas rechazadas.');
[, $html]=$admin->request($base,'/admin/gestion?recurso=instituciones&nuevo=1');
$csrfStatus=$admin->request($base,'/admin/gestion/guardar',['recurso'=>'instituciones','nombre'=>'No CSRF','estado'=>'1'])[0];
ensure($csrfStatus===419,'Fase 8: CSRF exigido (HTTP '.$csrfStatus.').');
$resource=new \App\Models\Resource('instituciones');$old=$institution;
$institution=resourceSave('instituciones',array_merge($institution,['descripcion'=>'Editada']),$institution);
$conflict=false;try{(new \App\Services\ResourceService())->save($resource,array_merge($old,['descripcion'=>'Vieja']),$resource->key($old),\App\Services\ContenidoService::version($old),$userIds[0]);}catch(\DomainException $e){$conflict=$e->getCode()===409;}
ensure($conflict,'Fase 8: versiones antiguas no sobrescriben cambios.');
ensure($admin->request($base,'/admin/gestion?recurso=sucursales')[0]===200,'Fase 8: listado y formularios responden correctamente.');
