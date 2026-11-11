<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;
class AttentionService
{
    /** Intervalo descriptivo conservador, no una predicción oficial ni estadística. */
    public static function estimate(array $samples): ?array
    {
        $minutes=array_values(array_filter(array_column($samples,'tiempo_espera'),fn($v)=>$v!==null));
        if(count($minutes)<3) {
            return null;
        }
        sort($minutes,SORT_NUMERIC);
        $count=count($minutes);
        // Se conservan extremos con pocas muestras y se redondea a cinco minutos.
        $low=max(0,(int)(floor((float)$minutes[0]/5)*5)-5);
        $high=(int)(ceil((float)$minutes[$count-1]/5)*5)+5;
        $latest=max(array_column($samples,'momento'));
        $demands=array_count_values(array_filter(array_column($samples,'nivel_demanda')));
        arsort($demands);
        $people=array_values(array_filter(array_column($samples,'personas_esperando'),fn($v)=>$v!==null));
        sort($people,SORT_NUMERIC);
        return ['tiempo_minimo'=>$low,'tiempo_maximo'=>$high,'tiempo_estimado'=>null,
        'personas_esperando'=>$people===[]?null:(int)$people[(int)floor((count($people)-1)/2)],
        'nivel_demanda'=>$demands===[]?'desconocida':(string)array_key_first($demands),
        'fuente'=>'ESTIMACION','fuente_id'=>null,'confianza'=>$count>=5?'media':'baja','cantidad_observaciones'=>$count,
        'ultima_actualizacion'=>$latest,'valido_hasta'=>gmdate('Y-m-d H:i:s',strtotime($latest.' UTC')+3600)];
    }
    public function current(int $branchId,?DateTimeImmutable $now=null): ?array
    {
        $now=($now??new DateTimeImmutable('now',new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('UTC'));
        $pdo=Database::connection();
        $q=$pdo->prepare('SELECT sucursal_id,personas_esperando,ficha_actual,ficha_atendida,tiempo_estimado,tiempo_minimo,tiempo_maximo,nivel_demanda,fuente,fuente_id,confianza,cantidad_observaciones,ultima_actualizacion,valido_hasta FROM estado_atencion WHERE sucursal_id=? AND ultima_actualizacion<=? AND valido_hasta>?');
        $q->execute([$branchId,$now->format('Y-m-d H:i:s'),$now->format('Y-m-d H:i:s')]);
        $stored=$q->fetch();
        if($stored&&in_array($stored['fuente'],['OFICIAL_API','ADMINISTRADOR'],true)) {
            return $stored;
        }
        // Solo la observación más reciente de cada persona en la última hora.
        // Revisar no crea una fecha nueva: se conserva el momento observado.
        $q=$pdo->prepare("SELECT h.tiempo_espera,h.personas_esperando,r.nivel_demanda,CONCAT(h.fecha,' ',h.hora) AS momento FROM historico_atencion h JOIN reportes_atencion r ON r.id=h.reporte_id AND r.sucursal_id=h.sucursal_id WHERE h.sucursal_id=? AND h.fuente='DIRI_USUARIOS' AND r.estado_revision='aceptado' AND CONCAT(h.fecha,' ',h.hora)>=? AND CONCAT(h.fecha,' ',h.hora)<=? AND NOT EXISTS(SELECT 1 FROM reportes_atencion newer WHERE newer.usuario_id=r.usuario_id AND newer.sucursal_id=r.sucursal_id AND newer.estado_revision='aceptado' AND (newer.fecha_registro>r.fecha_registro OR (newer.fecha_registro=r.fecha_registro AND newer.id>r.id))) ORDER BY h.id DESC LIMIT 100");
        $q->execute([$branchId,$now->modify('-1 hour')->format('Y-m-d H:i:s'),$now->format('Y-m-d H:i:s')]);
        $estimate=self::estimate($q->fetchAll());
        if($estimate===null) {
            return null;
        }
        $estimate['sucursal_id']=$branchId;
        return $estimate;
    }
    public function cache(int $branchId): bool
    {
        $state=$this->current($branchId);
        if($state===null||$state['fuente']!=='ESTIMACION') {
            return false;
        }
        $pdo=Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('SELECT id FROM sucursales WHERE id=? FOR UPDATE')->execute([$branchId]);
            $q=$pdo->prepare("SELECT id FROM estado_atencion WHERE sucursal_id=? AND fuente IN ('OFICIAL_API','ADMINISTRADOR') AND valido_hasta>UTC_TIMESTAMP() FOR UPDATE");
            $q->execute([$branchId]);
            if($q->fetch()) {
                $pdo->commit();
                return false;
            }
            $columns=['personas_esperando','tiempo_estimado','tiempo_minimo','tiempo_maximo','nivel_demanda','fuente','fuente_id','confianza','cantidad_observaciones','ultima_actualizacion','valido_hasta'];
            $pdo->prepare('INSERT INTO estado_atencion (sucursal_id,'.implode(',',$columns).') VALUES ('.implode(',',array_fill(0,count($columns)+1,'?')).') ON DUPLICATE KEY UPDATE '.implode(',',array_map(fn($column)=>$column.'=VALUES('.$column.')',$columns)).',ficha_actual=NULL,ficha_atendida=NULL')->execute(array_merge([$branchId],array_map(fn($column)=>$state[$column],$columns)));
            $pdo->commit();
            return true;
        }
        catch(\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
