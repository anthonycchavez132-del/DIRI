<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
class RecommendationService
{
    public static function distance(float $lat1,float $lon1,float $lat2,float $lon2): float
    {
        $dLat=deg2rad($lat2-$lat1);
        $dLon=deg2rad($lon2-$lon1);
        $a=sin($dLat/2)**2+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)**2;
        return 6371*2*atan2(sqrt(max(0,min(1,$a))),sqrt(max(0,1-$a)));
    }
    public function opening(int $branchId,DateTimeImmutable $now): string
    {
        $local=$now->setTimezone(new DateTimeZone('America/La_Paz'));
        $pdo=Database::connection();
        $q=$pdo->prepare('SELECT cerrada,hora_apertura,hora_cierre FROM excepciones_horario WHERE sucursal_id=? AND fecha=?');
        $q->execute([$branchId,$local->format('Y-m-d')]);
        $exception=$q->fetch();
        if($exception) {
            return (int)$exception['cerrada']===1?'cerrada':($local->format('H:i:s')>=$exception['hora_apertura']&&$local->format('H:i:s')<$exception['hora_cierre']?'abierta':'cerrada');
        }
        $q=$pdo->prepare('SELECT dia_semana,hora_apertura,hora_cierre FROM horarios WHERE sucursal_id=?');
        $q->execute([$branchId]);
        $hours=$q->fetchAll();
        if($hours===[]) {
            return 'desconocido';
        }
        foreach($hours as $shift) {
            if((int)$shift['dia_semana']===(int)$local->format('N')&&$local->format('H:i:s')>=$shift['hora_apertura']&&$local->format('H:i:s')<$shift['hora_cierre']) {
                return 'abierta';
            }
        }
        return 'cerrada';
    }
    public function compare(int $userId,int $tramiteId,?float $latitude=null,?float $longitude=null,?DateTimeImmutable $now=null): array
    {
        PlanAccess::requirePremium($userId);
        if(($latitude===null)!==($longitude===null)||($latitude!==null&&(!is_finite($latitude)||!is_finite($longitude)||abs($latitude)>90||abs($longitude)>180))) {
            throw new DomainException('Coordenadas inválidas.',422);
        }
        $now=$now??new DateTimeImmutable('now',new DateTimeZone('UTC'));
        $pdo=Database::connection();
        $q=$pdo->prepare("SELECT id FROM tramites WHERE id=? AND estado='publicado'");
        $q->execute([$tramiteId]);
        if(!$q->fetch()) {
            throw new DomainException('Trámite no disponible.',404);
        }
        $q=$pdo->prepare("SELECT s.id,s.nombre,s.direccion,s.ciudad,s.latitud,s.longitud,s.fuente_id,s.fecha_verificacion,i.nombre AS institucion FROM tramite_sucursales ts JOIN sucursales s ON s.id=ts.sucursal_id JOIN instituciones i ON i.id=s.institucion_id WHERE ts.tramite_id=? AND ts.disponible=1 AND s.estado='activa' AND i.estado=1 ORDER BY s.nombre,s.id");
        $q->execute([$tramiteId]);
        $rows=$q->fetchAll();
        foreach($rows as &$row) {
            $row['horario']=$this->opening((int)$row['id'],$now);
            $row['atencion']=(new AttentionService())->current((int)$row['id'],$now);
            $row['distancia_km']=$latitude!==null&&$row['latitud']!==null?round(self::distance($latitude,$longitude,(float)$row['latitud'],(float)$row['longitud']),1):null;
            $row['espera_comparable']=$row['atencion']['tiempo_maximo']??$row['atencion']['tiempo_estimado']??null;
            $row['elegible']=$row['horario']==='abierta'&&$row['espera_comparable']!==null;
        }
        unset($row);
        usort($rows,function($a,$b) {
            return ((int)$b['elegible']<=>(int)$a['elegible'])?:((float)($a['espera_comparable']??INF)<=>(float)($b['espera_comparable']??INF))?:(($a['distancia_km']??INF)<=>($b['distancia_km']??INF))?:($a['id']<=>$b['id']);
        }
        );
        $eligible=array_values(array_filter($rows,fn($row)=>$row['elegible']));
        $chosen=$eligible[0]['id']??null;
        $reason=$chosen===null?'No hay datos recientes suficientes de horario y espera para recomendar una sucursal.':(count($eligible)===1?'Es la única sucursal con horario abierto y espera reciente registrada.':'Presenta el menor límite superior de espera entre las sucursales abiertas con información reciente; la distancia desempata.');
        return ['items'=>$rows,'recommended_id'=>$chosen,'reason'=>$reason,'evaluated_at'=>$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')];
    }
}
