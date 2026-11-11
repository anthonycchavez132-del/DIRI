<?php
use App\Services\LibraryService;
$libraryService=new LibraryService();$libraryService->remember($userIds[1],'Consulta privada '.$suffix);$libraryService->remember($userIds[1],'Consulta privada '.$suffix);
ensure(count($libraryService->read($userIds[1])['history'])===1,'Fase 17: historial propio sin repetición inmediata.');
ensure($libraryService->read($userIds[0])['history']===[],'Fase 17: historial aislado por cuenta.');
ensure($ordinary->request($base,'/mi-biblioteca')[0]===200,'Fase 17: biblioteca Premium disponible.');
$pdo->prepare("UPDATE usuarios SET plan_id=(SELECT id FROM planes WHERE nombre='free') WHERE id=?")->execute([$userIds[1]]);
ensure($ordinary->request($base,'/mi-biblioteca')[0]===403,'Fase 17: biblioteca protegida al bajar de plan.');
$dashboard=(new \App\Models\Dashboard())->forUser($userIds[1]);
ensure($dashboard['counts']['favoritos']===0&&$dashboard['recent']===[],'Fase 17: dashboard no expone organización Premium a Free.');
ensure($ordinary->request($base,'/tramite?slug='.$slugA)[0]===200,'Fase 17: Free conserva ficha pública.');
$libraryService->remember($userIds[1],'No guardar '.$suffix);
$q=$pdo->prepare('SELECT COUNT(*) FROM historial_busquedas WHERE usuario_id=?');$q->execute([$userIds[1]]);ensure((int)$q->fetchColumn()===1,'Fase 17: consultas Free no se guardan como historial Premium.');
