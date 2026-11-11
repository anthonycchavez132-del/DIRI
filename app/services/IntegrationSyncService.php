<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Integrations\IntegrationRegistry;
use App\Integrations\OfficialAttentionReading;
use DomainException;
use Throwable;
class IntegrationSyncService
{
    private IntegrationRegistry $registry;
    public function __construct(IntegrationRegistry $registry) {
        $this->registry=$registry;
    }
    public function sync(int $integrationId): int
    {
        $pdo=Database::connection();
        $q=$pdo->prepare('SELECT * FROM integraciones_api WHERE id=?');
        $q->execute([$integrationId]);
        $integration=$q->fetch();
        if(!$integration||$integration['estado']!=='activa') {
            throw new DomainException('Integración no activa.',409);
        }
        $provider=$this->registry->provider($integrationId);
        $token=null;
        if((int)$integration['requiere_token']===1) {
            $reference=$integration['credencial_referencia']??'';
            if(!preg_match('/\A[A-Z][A-Z0-9_]{2,99}\z/',$reference)||!is_string($token=getenv($reference))||$token==='') {
                throw new DomainException('Credencial privada no configurada.',409);
            }
        }
        $pdo->prepare('INSERT INTO integracion_ejecuciones (integracion_id) VALUES (?)')->execute([$integrationId]);
        $execution=(int)$pdo->lastInsertId();
        try {
            // El adaptador opera fuera de la transacción. No se incluye cliente
            // HTTP: cada institución requerirá autorización y contrato propios.
            $readings=$provider->fetch($integration,$token);
            unset($token);
            if(count($readings)>500) {
                throw new DomainException('Lote demasiado grande.',422);
            }
            $pdo->beginTransaction();
            $q=$pdo->prepare('SELECT * FROM integraciones_api WHERE id=? FOR UPDATE');
            $q->execute([$integrationId]);
            $current=$q->fetch();
            if(!$current||$current['estado']!=='activa'||!hash_equals(ContenidoService::version($integration),ContenidoService::version($current))) {
                throw new DomainException('La configuración cambió durante la consulta.',409);
            }
            $seen=[];
            $now=time();
            foreach($readings as $reading) {
                if(!$reading instanceof OfficialAttentionReading) {
                    throw new DomainException('Formato de lectura inválido.',422);
                }
                $data=$reading->values();
                $observed=strtotime($data['ultima_actualizacion'].' UTC');
                if($observed>$now||$observed<$now-1800||isset($seen[$data['sucursal_id']])) {
                    throw new DomainException('Lectura duplicada, futura o caducada.',422);
                }
                $seen[$data['sucursal_id']]=true;
                $q=$pdo->prepare("SELECT id FROM sucursales WHERE id=? AND institucion_id=? AND estado='activa' FOR UPDATE");
                $q->execute([$data['sucursal_id'],$integration['institucion_id']]);
                if(!$q->fetch()) {
                    throw new DomainException('Sucursal ajena a la integración.',422);
                }
                $q=$pdo->prepare("SELECT id FROM fuentes WHERE id=? AND tipo='oficial' AND url IS NOT NULL AND fecha_verificacion IS NOT NULL");
                $q->execute([$data['fuente_id']]);
                if(!$q->fetch()) {
                    throw new DomainException('Lectura sin fuente oficial revisada.',422);
                }
                $q=$pdo->prepare('SELECT ultima_actualizacion FROM estado_atencion WHERE sucursal_id=? FOR UPDATE');
                $q->execute([$data['sucursal_id']]);
                $last=$q->fetchColumn();
                if($last&&$last>$data['ultima_actualizacion']) {
                    throw new DomainException('Existe una observación más reciente.',409);
                }
                $columns=array_keys($data);
                $pdo->prepare("INSERT INTO estado_atencion (".implode(',',$columns).",fuente,confianza,cantidad_observaciones) VALUES (".implode(',',array_fill(0,count($columns),'?')).",'OFICIAL_API',NULL,1) ON DUPLICATE KEY UPDATE ".implode(',',array_map(fn($key)=>$key.'=VALUES('.$key.')',$columns)).",fuente='OFICIAL_API',confianza=NULL,cantidad_observaciones=1")->execute(array_values($data));
                $date=substr($data['ultima_actualizacion'],0,10);
                $hour=substr($data['ultima_actualizacion'],11);
                $q=$pdo->prepare("SELECT id FROM historico_atencion WHERE sucursal_id=? AND fecha=? AND hora=? AND fuente='OFICIAL_API' AND fuente_id=?");
                $q->execute([$data['sucursal_id'],$date,$hour,$data['fuente_id']]);
                if(!$q->fetch()) {
                    $pdo->prepare("INSERT INTO historico_atencion (sucursal_id,fecha,hora,personas_esperando,tiempo_espera,fuente,fuente_id,confianza) VALUES (?,?,?,?,?,'OFICIAL_API',?,'baja')")->execute([$data['sucursal_id'],$date,$hour,$data['personas_esperando'],$data['tiempo_estimado'],$data['fuente_id']]);
                }
            }
            $pdo->prepare("UPDATE integracion_ejecuciones SET fecha_fin=UTC_TIMESTAMP(),resultado='exito',resumen=? WHERE id=?")->execute(['Lecturas válidas: '.count($readings),$execution]);
            $pdo->prepare("UPDATE integraciones_api SET ultima_consulta=UTC_TIMESTAMP(),ultima_respuesta='Consulta validada.' WHERE id=?")->execute([$integrationId]);
            $pdo->commit();
            return count($readings);
        }
        catch(Throwable $e) {
            if($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Nunca guardar el mensaje del proveedor: puede contener un token.
            $pdo->prepare("UPDATE integracion_ejecuciones SET fecha_fin=UTC_TIMESTAMP(),resultado='error',resumen='Consulta o validación fallida.' WHERE id=?")->execute([$execution]);
            throw new DomainException('La consulta o validación de la integración falló.',422);
        }
    }
}
