<?php
use App\Models\Resource;
use App\Services\ContenidoService;
use App\Services\ResourceService;
ensure($ordinary->request($base,'/admin/gestion?recurso=usuarios')[0]===403,'Fase 16: gestión de usuarios solo para administradores.');
$userResource=new Resource('usuarios');$ownUser=$userResource->find($userResource->key(['id'=>$userIds[1]]));
ensure(!array_key_exists('password',$ownUser),'Fase 16: el recurso administrativo excluye hashes.');
$ownUser=resourceSave('usuarios',array_merge($ownUser,['plan_id'=>(string)$pdo->query("SELECT id FROM planes WHERE nombre='premium'")->fetchColumn()]),$ownUser);
ensure($ordinary->request($base,'/mi-checklist?slug='.$slugA)[0]===200,'Fase 16: plan asignado desde servicio administrativo.');
ensure($admin->request($base,'/admin/gestion?recurso=usuarios&nuevo=1')[0]===404,'Fase 16: no se crean usuarios sin contraseña mediante formularios genéricos.');
$unauthorized=false;try{(new ResourceService())->save($userResource,array_map(fn($v)=>(string)$v,$ownUser),$userResource->key($ownUser),ContenidoService::version($ownUser),$userIds[1]);}catch(DomainException $e){$unauthorized=$e->getCode()===403;}
ensure($unauthorized,'Fase 16: el servicio también comprueba el rol.');
(new \App\Services\AdminAttentionService())->record($userIds[0],['sucursal_id'=>(string)$branch['id'],'fuente_id'=>(string)$source,'personas_esperando'=>'5','tiempo_espera'=>'12','nivel_demanda'=>'baja']);remember('historico_atencion',['sucursal_id'=>$branch['id']]);
$current=(new \App\Services\AttentionService())->current((int)$branch['id']);
ensure($current['fuente']==='ADMINISTRADOR'&&(int)$current['tiempo_estimado']===12,'Fase 16: observación administrativa identificada sin fingir API.');
ensure($admin->request($base,'/admin/auditoria')[0]===200,'Fase 16: auditoría accesible.');
