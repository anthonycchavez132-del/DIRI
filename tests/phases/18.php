<?php
[$status,$html]=$ordinary->request($base,'/buscar?q=');
ensure($status===200&&str_contains($html,'name="viewport"')&&str_contains($html,'css/modules.css'),'Fase 18: viewport y estilos adaptables presentes.');
[$status,$html]=$admin->request($base,'/admin');
ensure($status===200&&str_contains($html,'data-module-query')&&str_contains($html,'Usuarios, roles y asignación'),'Fase 18: navegación administrativa con filtro progresivo.');
ensure($ordinary->request($base,'/css/modules.css')[0]===200,'Fase 18: hoja de estilos servida.');
