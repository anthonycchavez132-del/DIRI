<?php
[$status,$json]=$ordinary->request($base,'/api/v1/tramites?q=carnet-'.$suffix);$payload=json_decode($json,true,512,JSON_THROW_ON_ERROR);
ensure($status===200&&$payload['version']==='1'&&$payload['data']['total']===1,'Fase 19: búsqueda JSON reutiliza catálogo publicado.');
ensure($ordinary->headerContains('application/json'),'Fase 19: Content-Type JSON.');
[$status,$json]=$ordinary->request($base,'/api/v1/tramite?slug=b-'.$suffix);
ensure($status===404&&json_decode($json,true)['error']['code']==='not_found','Fase 19: borrador 404 en JSON.');
[$status,$json]=$ordinary->request($base,'/api/v1/tramites',['q'=>'test']);
ensure($status===405&&json_decode($json,true)['error']['code']==='method_not_allowed','Fase 19: métodos de escritura rechazados en JSON.');
[$status,$json]=$ordinary->request($base,'/api/v1/no-existe');ensure($status===404&&json_decode($json,true)['data']===null,'Fase 19: ruta desconocida devuelve JSON.');
[$status,$json]=$ordinary->request($base,'/api/v1/tramites?q[]=x');ensure($status===422&&json_decode($json,true)['error']['code']==='invalid_query','Fase 19: validación JSON.');
[$status,$json]=$ordinary->request($base,'/api/v1/atencion?sucursal_id='.$branch['id']);ensure($status===200&&json_decode($json,true)['data']['attention']['fuente']==='ADMINISTRADOR','Fase 19: atención pública conserva procedencia.');
