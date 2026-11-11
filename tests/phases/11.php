<?php
use App\Services\DatabaseSearch;
resourceSave('palabras_clave_tramite',['tramite_id'=>(string)$tramiteA,'palabra'=>'carnet-'.$suffix]);
remember('palabras_clave',['palabra'=>'carnet-'.$suffix]);
$query=$pdo->prepare("UPDATE tramites SET estado='publicado',estado_verificacion='referencial',fuente_id=?,descripcion=? WHERE id=?");$query->execute([$source,'Contenido de prueba '.$suffix,$tramiteA]);
$search=(new DatabaseSearch())->search('quiero sacar mi carnet-'.$suffix);
ensure(in_array($tramiteA,array_map('intval',array_column($search['items'],'id')),true),'Fase 11: consulta natural encuentra un alias de la base.');
$query=$pdo->prepare('SELECT slug FROM tramites WHERE id=?');$query->execute([$tramiteA]);$slugA=$query->fetchColumn();
[$status,$html]=$ordinary->request($base,'/tramite?slug='.$slugA);
ensure($status===200&&str_contains($html,'Costo referencial')&&str_contains($html,'Sin monto confirmado'),'Fase 11: ficha pública con costos desconocidos explícitos.');
ensure($ordinary->request($base,'/tramite?slug=b-'.$suffix)[0]===404,'Fase 11: borradores fuera de la ficha pública.');
ensure($ordinary->request($base,'/buscar?q[]=x')[0]===422,'Fase 11: consulta con tipo inválido rechazada.');
ensure((new DatabaseSearch())->search("' OR 1=1 -- ".$suffix)['total']===0,'Fase 11: consulta SQL se trata como texto.');
$pdo->prepare('UPDATE tramites SET descripcion=? WHERE id=?')->execute(['<script>alert(1)</script> '.$suffix,$tramiteA]);
[, $html]=$ordinary->request($base,'/tramite?slug='.$slugA);
ensure(!str_contains($html,'<script>alert(1)</script>')&&str_contains($html,'&lt;script&gt;'),'Fase 11: HTML de contenido escapado.');
ensure($ordinary->request($base,'/buscar?q=')[0]===200,'Fase 11: catálogo sin consulta disponible.');
