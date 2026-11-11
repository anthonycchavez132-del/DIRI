<?php
use App\Models\Resource;
use App\Services\ContenidoService;
// Recorre el formulario HTTP real, además de las pruebas de servicios.
$institutionResource=new Resource('instituciones');$before=$institutionResource->find($institutionResource->key($institution));
[, $form]=$admin->request($base,'/admin/gestion?'.http_build_query(['recurso'=>'instituciones','key'=>$institutionResource->key($before)]));
$post=array_map(fn($value)=>$value===null?'':(string)$value,$before);
$post+=['_token'=>token($form),'recurso'=>'instituciones','key'=>$institutionResource->key($before),'version'=>ContenidoService::version($before)];$post['descripcion']='Editada mediante HTTP';
ensure($admin->request($base,'/admin/gestion/guardar',$post)[0]===303,'Regresión: formulario administrativo completo guarda con CSRF.');
$before=$institutionResource->find($institutionResource->key($institution));
$auditCount=(int)$pdo->query('SELECT COUNT(*) FROM auditoria_administrativa')->fetchColumn();resourceSave('instituciones',$before,$before);
ensure((int)$pdo->query('SELECT COUNT(*) FROM auditoria_administrativa')->fetchColumn()===$auditCount,'Regresión: guardar sin cambios no genera auditoría.');
$routeResource=new Resource('rutas');$route=$routeResource->find($routeResource->key($route));
$pdo->prepare("UPDATE tramites SET estado='publicado',fuente_id=?,estado_verificacion='referencial',descripcion='Fixture' WHERE id=?")->execute([$source,$tramiteB]);
$route=resourceSave('rutas',array_merge($route,['estado'=>'publicada']),$route);
ensure($admin->request($base,'/ruta?id='.$route['id'])[0]===200,'Regresión: ruta Premium publicada disponible.');
[, $form]=$admin->request($base,'/ruta?id='.$route['id']);
ensure($admin->request($base,'/ruta/guardar',['_token'=>token($form),'ruta_id'=>(string)$route['id'],'valor'=>'1'])[0]===303,'Regresión: guardar ruta mediante POST.');
ensure(count((new \App\Services\LibraryService())->read($userIds[0])['routes'])===1,'Regresión: ruta guardada pertenece a la cuenta.');
ensure(external_url('javascript:alert(1)')===null&&external_url('https://example.invalid/source')!==null,'Regresión: enlaces importados rechazan esquemas ejecutables.');
$relation=new Resource('ruta_dependencias');$relationRow=$relation->find($relation->key(['ruta_id'=>$route['id'],'tramite_id'=>$tramiteB,'tramite_previo_id'=>$tramiteA]));
[, $form]=$admin->request($base,'/admin/gestion?'.http_build_query(['recurso'=>'ruta_dependencias','key'=>$relation->key($relationRow)]));
$removal=['_token'=>token($form),'recurso'=>'ruta_dependencias','key'=>$relation->key($relationRow),'version'=>ContenidoService::version($relationRow)];
ensure($admin->request($base,'/admin/gestion/quitar',$removal)[0]===422,'Regresión: quitar relaciones exige confirmación explícita.');
ensure($admin->request($base,'/admin/gestion/quitar',$removal+['confirmar'=>'1'])[0]===303,'Regresión: dependencia errónea puede quitarse y auditarse.');
ensure($relation->find($relation->key($relationRow))===null&&(new \App\Services\RoutePlanner())->route((int)$route['id'])===null,'Regresión: quitar dependencia exige revisar la ruta.');
