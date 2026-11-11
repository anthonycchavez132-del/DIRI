<?php
use App\Models\Resource;
use App\Services\ResourceValidator;
$cost=resourceSave('costos',['tramite_id'=>(string)$tramiteA,'concepto'=>'Costo desconocido '.$suffix,'moneda'=>'BOB','estado_verificacion'=>'pendiente']);
ensure($cost['monto']===null,'Fase 10: monto desconocido permanece NULL.');
[, $errors]=ResourceValidator::validate(new Resource('costos'),array_merge(array_map(fn($v)=>$v===null?'':(string)$v,$cost),['monto'=>'-1']),$cost);
ensure(isset($errors['monto']),'Fase 10: costos negativos rechazados.');
[, $errors]=ResourceValidator::validate(new Resource('costos'),array_merge(array_map(fn($v)=>$v===null?'':(string)$v,$cost),['monto'=>'0','fuente_id'=>(string)$source,'estado_verificacion'=>'verificado','fecha_verificacion'=>date('Y-m-d')]),$cost);
ensure(isset($errors['verificacion']),'Fase 10: fuente interna no acredita costo oficial.');
$ownSource=resourceSave('fuentes',['nombre'=>'Fuente de prueba '.$suffix,'tipo'=>'referencial','url'=>'https://example.invalid/test']);
$cost=resourceSave('costos',array_merge($cost,['fuente_id'=>(string)$ownSource['id'],'estado_verificacion'=>'referencial']),$cost);
resourceSave('paso_costos',['tramite_id'=>(string)$tramiteA,'paso_id'=>(string)$stepA['id'],'costo_id'=>(string)$cost['id']]);
$pdo->prepare("UPDATE tramites SET estado='publicado',estado_verificacion='referencial',fuente_id=?,descripcion='Fixture' WHERE id=?")->execute([$ownSource['id'],$tramiteA]);
$ownSource=resourceSave('fuentes',array_merge($ownSource,['nombre'=>'Fuente revisada '.$suffix]),$ownSource);
$query=$pdo->prepare('SELECT estado FROM tramites WHERE id=?');$query->execute([$tramiteA]);
ensure($query->fetchColumn()==='borrador','Fase 10: editar fuente exige revisar el trámite.');
ensure($admin->request($base,'/admin/gestion?recurso=costos&nuevo=1')[0]===200,'Fase 10: formulario de costos disponible.');
