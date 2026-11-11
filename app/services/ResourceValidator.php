<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Models\Resource;
class ResourceValidator
{
    public static function validate(Resource $resource, array $input, ?array $before): array
    {
        $data=$errors=[];
        foreach ($resource->definition['fields'] as $field=>$meta) {
            $raw=$input[$field]??'';
            if (!is_string($raw) || !mb_check_encoding($raw,'UTF-8')) {
                $errors[$field]='Valor inválido: '.$field;
                $raw='';
            }
            $value=trim($raw);
            $type=$meta['type'];
            if ($value==='') {
                $data[$field]=null;
                if ($meta['required']??false) {
                    $errors[$field]='Completa '.$field;
                }
                continue;
            }
            $valid=true;
            switch ($type) {
                case 'enum': $valid=array_key_exists($value,$meta['values']);
                break;
                case 'ref':
                $valid=TramiteValidator::id($value)!==null;
                if ($valid) {
                    $query=Database::connection()->prepare('SELECT COUNT(*) FROM `'.$meta['table'].'` WHERE id=?');
                    $query->execute([$value]);
                    $valid=(int)$query->fetchColumn()===1;
                }
                break;
                case 'int': $valid=preg_match('/\A[0-9]+\z/',$value)===1 && (float)$value>=($meta['min']??0) && (float)$value<=($meta['max']??4294967295);
                break;
                case 'decimal': $valid=preg_match('/\A-?[0-9]+(?:\.[0-9]{1,'.($meta['scale']??2).'})?\z/',$value)===1 && (float)$value>=($meta['min']??0) && (float)$value<=($meta['max']??9999999999.99);
                break;
                case 'date':
                $parsed=preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/',$value)?\DateTimeImmutable::createFromFormat('!Y-m-d',$value):false;
                $valid=$parsed!==false && $parsed->format('Y-m-d')===$value && (($meta['future']??false)||$value<=date('Y-m-d'));
                break;
                case 'time': $valid=preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?\z/',$value)===1;
                if($valid && strlen($value)===5) {
                    $value.=':00';
                }
                break;
                case 'url': $valid=strlen($value)<=2048 && filter_var($value,FILTER_VALIDATE_URL)!==false && in_array(parse_url($value,PHP_URL_SCHEME),['https','http'],true) && parse_url($value,PHP_URL_USER)===null && parse_url($value,PHP_URL_PASS)===null;
                break;
                case 'email': $value=mb_strtolower($value);
                $valid=strlen($value)<=254&&filter_var($value,FILTER_VALIDATE_EMAIL)!==false;
                break;
                default: $valid=mb_strlen($value)<=($meta['max']??200) && !preg_match($type==='textarea'?'/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u':'/[\x00-\x1F\x7F]/u',$value);
            }
            if (!$valid) {
                $errors[$field]='Revisa el formato o la selección de '.$field;
            }
            $data[$field]=$value;
        }
        if ($before!==null) {
            if(in_array($resource->table,['pasos_tramite','costos'],true)&&(string)$data['tramite_id']!==(string)$before['tramite_id']) {
                $errors['tramite_id']='El trámite original no se puede cambiar. Crea un registro para el otro trámite.';
            }
            foreach($resource->definition['fields'] as $field=>$meta) {
                if(($meta['immutable']??false)&&(string)$data[$field]!==(string)$before[$field]) {
                    $errors[$field]='Este identificador no puede cambiar.';
                }
            }
            foreach ($resource->definition['key'] as $key) {
                if (array_key_exists($key,$data) && (string)$data[$key] !== (string)$before[$key]) {
                    $errors[$key]='La relación original no se puede cambiar. Crea otro registro.';
                }
            }
        }
        if($before===null&&($resource->definition['create']??true)===false) {
            $errors['crear']='Este recurso solo admite editar registros existentes.';
        }
        if($resource->table==='usuarios'&&$errors===[]) {
            $q=Database::connection()->prepare('SELECT estado FROM planes WHERE id=?');
            $q->execute([$data['plan_id']]);
            if((int)$q->fetchColumn()!==1) {
                $errors['plan_id']='Asigna un plan activo.';
            }
        }
        if($resource->table==='planes'&&($before['nombre']??'')==='free'&&$data['estado']!=='1') {
            $errors['estado']='El plan Free debe permanecer activo para el registro.';
        }
        if($resource->table==='integraciones_api') {
            if(parse_url((string)$data['url_base'],PHP_URL_SCHEME)!=='https'||parse_url((string)$data['url_base'],PHP_URL_QUERY)!==null||parse_url((string)$data['url_base'],PHP_URL_FRAGMENT)!==null) {
                $errors['url_base']='Usa una URL base HTTPS sin parámetros, fragmentos ni credenciales.';
            }
            if($data['credencial_referencia']!==null&&!preg_match('/\A[A-Z][A-Z0-9_]{2,99}\z/',(string)$data['credencial_referencia'])) {
                $errors['credencial_referencia']='Guarda solo el nombre de una variable de entorno, nunca el token.';
            }
            if($data['requiere_token']==='1'&&$data['credencial_referencia']===null) {
                $errors['credencial_referencia']='Indica la referencia privada de configuración.';
            }
        }
        if ($resource->table!=='fuentes'&&($data['fecha_verificacion']??null)!==null && ($data['fuente_id']??null)===null) {
            $errors['fuente_id']='Asocia una fuente antes de registrar una revisión.';
        }
        if($resource->table==='fuentes'&&$data['tipo']==='oficial'&&empty($data['url'])) {
            $errors['url']='Registra una URL que permita consultar la fuente oficial.';
        }
        if($resource->table==='costos') {
            if($data['estado_verificacion']!=='pendiente'&&empty($data['fuente_id'])) {
                $errors['fuente_id']='Asocia una fuente para clasificar el costo.';
            }
            if($data['monto']!==null&&empty($data['fuente_id'])) {
                $errors['monto']='Incluso un monto cero necesita una fuente.';
            }
            if($data['estado_verificacion']==='verificado') {
                $query=Database::connection()->prepare('SELECT tipo FROM fuentes WHERE id=?');
                $query->execute([$data['fuente_id']]);
                if($query->fetchColumn()!=='oficial'||empty($data['fecha_verificacion'])||$data['monto']===null) {
                    $errors['verificacion']='El costo verificado exige monto, fuente oficial y fecha de revisión.';
                }
            }
        }
        if ($resource->table==='sucursales' && (($data['latitud']===null)!==($data['longitud']===null))) {
            $errors['coordenadas']='Completa ambas coordenadas o deja ambas vacías.';
        }
        if (in_array($resource->table,['horarios','excepciones_horario'],true)) {
            $closed=($data['cerrada']??'0')==='1';
            if ($closed && (($data['hora_apertura']??null)!==null || ($data['hora_cierre']??null)!==null)) {
                $errors['horas']='Un cierre no tiene horas de atención.';
            }
            if (!$closed && (empty($data['hora_apertura']) || empty($data['hora_cierre']) || $data['hora_apertura']>=$data['hora_cierre'])) {
                $errors['horas']='La apertura debe ser anterior al cierre en el mismo día.';
            }
            if ($resource->table==='horarios' && $errors===[]) {
                $query=Database::connection()->prepare('SELECT COUNT(*) FROM horarios WHERE sucursal_id=? AND dia_semana=? AND hora_apertura<? AND hora_cierre>? AND id<>?');
                $query->execute([$data['sucursal_id'],$data['dia_semana'],$data['hora_cierre'],$data['hora_apertura'],$before['id']??0]);
                if ((int)$query->fetchColumn()>0) {
                    $errors['horas']='El turno se superpone a otro horario.';
                }
            }
        }
        if(isset($data['slug'])&&!preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',(string)$data['slug'])) {
            $errors['slug']='Usa letras minúsculas, números y guiones.';
        }
        if($errors===[]) {
            $errors=self::relations($resource,$data,$before);
        }
        return [$data,$errors];
    }
    private static function relations(Resource $resource,array $data,?array $before): array
    {
        $pdo=Database::connection();
        $table=$resource->table;
        $errors=[];
        if(in_array($table,['paso_documentos','paso_dependencias','paso_costos'],true)) {
            $query=$pdo->prepare('SELECT COUNT(*) FROM pasos_tramite WHERE tramite_id=? AND id=?');
            $query->execute([$data['tramite_id'],$data['paso_id']]);
            if((int)$query->fetchColumn()!==1) {
                $errors['paso_id']='El paso no pertenece al trámite.';
            }
            $other=$table==='paso_documentos'?['tramite_documentos','documento_id','documento_id']:($table==='paso_costos'?['costos','id','costo_id']:['pasos_tramite','id','paso_previo_id']);
            $query=$pdo->prepare('SELECT COUNT(*) FROM '.$other[0].' WHERE tramite_id=? AND '.$other[1].'=?');
            $query->execute([$data['tramite_id'],$data[$other[2]]]);
            if((int)$query->fetchColumn()!==1) {
                $errors['relacion']='La relación debe pertenecer al mismo trámite.';
            }
            if($table==='paso_dependencias'&&$errors===[]) {
                $query=$pdo->prepare('SELECT id FROM pasos_tramite WHERE tramite_id=? ORDER BY numero_paso');
                $query->execute([$data['tramite_id']]);
                $nodes=$query->fetchAll();
                $query=$pdo->prepare('SELECT d.paso_id,d.paso_previo_id FROM paso_dependencias d JOIN pasos_tramite p ON p.id=d.paso_id WHERE p.tramite_id=?');
                $query->execute([$data['tramite_id']]);
                $edges=$query->fetchAll(\PDO::FETCH_NUM);
                $edges[]=[$data['paso_id'],$data['paso_previo_id']];
                try {
                    RoutePlanner::order($nodes,$edges);
                }
                catch(\DomainException $e) {
                    $errors['dependencia']=$e->getMessage();
                }
            }
        }
        if($table==='ruta_dependencias') {
            $query=$pdo->prepare('SELECT tramite_id FROM ruta_tramites WHERE ruta_id=? ORDER BY numero_orden');
            $query->execute([$data['ruta_id']]);
            $nodes=$query->fetchAll();
            $query=$pdo->prepare('SELECT tramite_id,tramite_previo_id FROM ruta_dependencias WHERE ruta_id=?');
            $query->execute([$data['ruta_id']]);
            $edges=$query->fetchAll(\PDO::FETCH_NUM);
            $edges[]=[$data['tramite_id'],$data['tramite_previo_id']];
            try {
                RoutePlanner::order($nodes,$edges,'tramite_id');
            }
            catch(\DomainException $e) {
                $errors['dependencia']=$e->getMessage();
            }
        }
        if($table==='rutas'&&$data['estado']==='publicada') {
            $route=$before===null?null:(new RoutePlanner())->route((int)$before['id'],false);
            if($route===null||$route['tramites']===[]||array_filter($route['tramites'],fn($row)=>$row['estado']!=='publicado')||empty($data['fuente_id'])) {
                $errors['estado']='Agrega trámites publicados y una fuente antes de publicar la ruta.';
            }
        }
        return $errors;
    }
}
