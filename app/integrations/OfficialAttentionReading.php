<?php

declare(strict_types=1);
namespace App\Integrations;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
/** Traducción explícita del formato del proveedor al dominio de DIRI. */
class OfficialAttentionReading
{
    private array $values;
    public function __construct(int $branchId,int $sourceId,DateTimeImmutable $observed,array $measurements)
    {
        if($branchId<1||$sourceId<1) {
            throw new InvalidArgumentException('Identificadores inválidos.');
        }
        $data=['sucursal_id'=>$branchId,'fuente_id'=>$sourceId,'ultima_actualizacion'=>$observed->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')];
        $limits=['personas_esperando'=>1000000,'ficha_actual'=>4294967295,'ficha_atendida'=>4294967295,'tiempo_estimado'=>1440,'tiempo_minimo'=>1440,'tiempo_maximo'=>1440];
        foreach(array_keys($measurements) as $key) {
            if(!array_key_exists($key,$limits)&&$key!=='nivel_demanda') {
                throw new InvalidArgumentException('Campo no permitido en lectura.');
            }
        }
        foreach($limits as $field=>$limit) {
            $value=$measurements[$field]??null;
            if($value!==null&&(!is_int($value)||$value<0||$value>$limit)) {
                throw new InvalidArgumentException('Medición inválida.');
            }
            $data[$field]=$value;
        }
        if(($data['tiempo_minimo']===null)!==($data['tiempo_maximo']===null)||($data['tiempo_minimo']!==null&&$data['tiempo_minimo']>$data['tiempo_maximo'])) {
            throw new InvalidArgumentException('Intervalo inválido.');
        }
        if(($data['ficha_actual']===null)!==($data['ficha_atendida']===null)||($data['ficha_actual']!==null&&$data['ficha_actual']<$data['ficha_atendida'])) {
            throw new InvalidArgumentException('Fichas inválidas.');
        }
        if($data['personas_esperando']===null&&$data['tiempo_estimado']===null) {
            throw new InvalidArgumentException('Se requiere una medición de personas o tiempo.');
        }
        $demand=$measurements['nivel_demanda']??'desconocida';
        if(!in_array($demand,['desconocida','baja','media','alta'],true)) {
            throw new InvalidArgumentException('Demanda inválida.');
        }
        $data['nivel_demanda']=$demand;
        $data['valido_hasta']=gmdate('Y-m-d H:i:s',$observed->getTimestamp()+1800);
        $this->values=$data;
    }
    public function values(): array {
        return $this->values;
    }
}
