<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/core/bootstrap.php';
if(count($argv)!==2||($id=App\Services\TramiteValidator::id($argv[1]))===null){echo "Uso: php scripts/sync-integrations.php ID\nNo hay proveedores de instituciones incluidos.\n";exit(2);}
try{$count=(new App\Services\IntegrationSyncService(App\Integrations\IntegrationRegistry::configured()))->sync($id);echo "Lecturas válidas almacenadas: $count.\n";}
catch(DomainException $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
catch(Throwable $e){fwrite(STDERR,"No se pudo completar la sincronización.\n");exit(1);}
