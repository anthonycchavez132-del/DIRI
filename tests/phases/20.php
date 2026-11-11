<?php
use App\Integrations\OfficialAttentionProvider;
use App\Integrations\OfficialAttentionReading;
use App\Integrations\IntegrationRegistry;
use App\Services\IntegrationSyncService;
$integration=resourceSave('integraciones_api',['institucion_id'=>(string)$institution['id'],'nombre'=>'Simulación exclusiva de prueba '.$suffix,'url_base'=>'https://example.invalid/authorized-test','requiere_token'=>'0','estado'=>'activa']);
$refused=false;try{(new IntegrationSyncService(new IntegrationRegistry()))->sync((int)$integration['id']);}catch(DomainException $e){$refused=$e->getCode()===409;}
ensure($refused,'Fase 20: una fila activa no habilita un adaptador inexistente.');
$officialTestSource=resourceSave('fuentes',['nombre'=>'Fuente ficticia solo para pruebas '.$suffix,'tipo'=>'oficial','url'=>'https://example.invalid/test-source','fecha_verificacion'=>date('Y-m-d')]);
$reading=new OfficialAttentionReading((int)$branch['id'],(int)$officialTestSource['id'],new DateTimeImmutable('now',new DateTimeZone('UTC')),['personas_esperando'=>3,'tiempo_estimado'=>8,'nivel_demanda'=>'baja']);
$provider=new class([$reading]) implements OfficialAttentionProvider {
    public array $readings;public bool $fail=false;
    public function __construct(array $readings){$this->readings=$readings;}
    public function fetch(array $integration,?string $token): array {if($this->fail){throw new RuntimeException('TEST_SECRET_MUST_NOT_LEAK');}return $this->readings;}
};
$sync=new IntegrationSyncService(new IntegrationRegistry([(int)$integration['id']=>$provider]));
remember('integracion_ejecuciones',['integracion_id'=>$integration['id']]);
ensure($sync->sync((int)$integration['id'])===1,'Fase 20: contrato probado con proveedor simulado, sin red.');
$q=$pdo->prepare('SELECT fuente,tiempo_estimado FROM estado_atencion WHERE sucursal_id=?');$q->execute([$branch['id']]);$officialState=$q->fetch();
ensure($officialState['fuente']==='OFICIAL_API'&&(int)$officialState['tiempo_estimado']===8,'Fase 20: lectura validada conserva procedencia de contrato oficial.');
$provider->readings=[new OfficialAttentionReading((int)$branch['id'],(int)$officialTestSource['id'],new DateTimeImmutable('+1 day',new DateTimeZone('UTC')),['personas_esperando'=>1,'tiempo_estimado'=>1])];
$invalid=false;try{$sync->sync((int)$integration['id']);}catch(DomainException $e){$invalid=$e->getCode()===422;}
$q=$pdo->prepare('SELECT tiempo_estimado FROM estado_atencion WHERE sucursal_id=?');$q->execute([$branch['id']]);
ensure($invalid&&(int)$q->fetchColumn()===8,'Fase 20: lote inválido no sobrescribe estado válido.');
$provider->fail=true;$sanitized=false;try{$sync->sync((int)$integration['id']);}catch(DomainException $e){$sanitized=!str_contains($e->getMessage(),'TEST_SECRET');}
$q=$pdo->prepare('SELECT resumen FROM integracion_ejecuciones WHERE integracion_id=?');$q->execute([$integration['id']]);
ensure($sanitized&&!str_contains(json_encode($q->fetchAll()),'TEST_SECRET'),'Fase 20: errores de proveedor sanitizados sin credenciales.');
$invalid=false;try{new OfficialAttentionReading((int)$branch['id'],(int)$officialTestSource['id'],new DateTimeImmutable(),['personas_esperando'=>-1]);}catch(InvalidArgumentException $e){$invalid=true;}
ensure($invalid,'Fase 20: contrato rechaza mediciones negativas.');
