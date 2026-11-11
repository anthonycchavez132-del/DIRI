<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Models\Auditoria;
use DomainException;
use Throwable;
class ReportService
{
    public static function validate(array $input): array
    {
        $data=['sucursal_id'=>TramiteValidator::id($input['sucursal_id']??null)];
        $errors=[];
        if($data['sucursal_id']===null) {
            $errors['sucursal']='Selecciona una sucursal.';
        }
        foreach(['personas_esperando'=>10000,'tiempo_espera'=>1440] as $field=>$max) {
            $value=$input[$field]??'';
            if(!is_string($value)||($value!==''&&(!preg_match('/\A[0-9]{1,5}\z/',$value)||(int)$value>$max))) {
                $errors[$field]='Revisa el número de '.$field;
                $value='';
            }
            $data[$field]=$value===''?null:(int)$value;
        }
        if($data['personas_esperando']===null&&$data['tiempo_espera']===null) {
            $errors['medicion']='Indica al menos personas esperando o minutos de espera.';
        }
        $demand=$input['nivel_demanda']??null;
        if(!is_string($demand)||!in_array($demand,['baja','media','alta'],true)) {
            $errors['demanda']='Selecciona una demanda válida.';
            $demand='media';
        }
        $data['nivel_demanda']=$demand;
        $notes=$input['observaciones']??'';
        if(!is_string($notes)||!mb_check_encoding($notes,'UTF-8')||mb_strlen($notes)>1000||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',$notes)) {
            $errors['observaciones']='Usa hasta 1000 caracteres de texto.';
            $notes='';
        }
        $data['observaciones']=trim($notes)===''?null:trim($notes);
        return [$data,$errors];
    }
    public function submit(int $userId,array $data): int
    {
        // Una segunda validación protege también a futuros consumidores del servicio.
        [$data,$errors]=self::validate(array_map(fn($v)=>$v===null?'':(string)$v,$data));
        if($errors!==[]) {
            throw new DomainException(implode(' ',$errors),422);
        }
        $pdo=Database::connection();
        $pdo->beginTransaction();
        try {
            $q=$pdo->prepare("SELECT id FROM usuarios WHERE id=? AND estado='activo' FOR UPDATE");
            $q->execute([$userId]);
            if(!$q->fetch()) {
                throw new DomainException('Cuenta no disponible.',403);
            }
            $q=$pdo->prepare("SELECT s.id FROM sucursales s JOIN instituciones i ON i.id=s.institucion_id WHERE s.id=? AND s.estado='activa' AND i.estado=1");
            $q->execute([$data['sucursal_id']]);
            if(!$q->fetch()) {
                throw new DomainException('Sucursal no disponible.',404);
            }
            $q=$pdo->prepare('SELECT COUNT(*) FROM reportes_atencion WHERE usuario_id=? AND sucursal_id=? AND fecha_registro>UTC_TIMESTAMP()-INTERVAL 15 MINUTE');
            $q->execute([$userId,$data['sucursal_id']]);
            $recent=(int)$q->fetchColumn();
            $q=$pdo->prepare('SELECT COUNT(*) FROM reportes_atencion WHERE usuario_id=? AND fecha_registro>UTC_TIMESTAMP()-INTERVAL 1 DAY');
            $q->execute([$userId]);
            if($recent>0||(int)$q->fetchColumn()>=10) {
                throw new DomainException('Espera antes de enviar otro reporte. Máximo uno por sucursal cada 15 minutos y diez al día.',429);
            }
            $pdo->prepare('INSERT INTO reportes_atencion (usuario_id,sucursal_id,fecha,hora,personas_esperando,tiempo_espera,nivel_demanda,observaciones) VALUES (?,?,UTC_DATE(),UTC_TIME(),?,?,?,?)')->execute([$userId,$data['sucursal_id'],$data['personas_esperando'],$data['tiempo_espera'],$data['nivel_demanda'],$data['observaciones']]);
            $id=(int)$pdo->lastInsertId();
            $pdo->commit();
            return $id;
        }
        catch(Throwable $e) {
            if($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
    public function moderate(int $actor,int $id,string $decision,string $version): void
    {
        if(!in_array($decision,['aceptado','rechazado'],true)) {
            throw new DomainException('Decisión inválida.',422);
        }
        $pdo=Database::connection();
        $pdo->beginTransaction();
        try {
            $q=$pdo->prepare("SELECT u.id FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.id=? AND u.estado='activo' AND r.nombre='administrador'");
            $q->execute([$actor]);
            if(!$q->fetch()) {
                throw new DomainException('Acceso denegado.',403);
            }
            $q=$pdo->prepare('SELECT * FROM reportes_atencion WHERE id=? FOR UPDATE');
            $q->execute([$id]);
            $row=$q->fetch();
            if(!$row) {
                throw new DomainException('Reporte no encontrado.',404);
            }
            if($row['estado_revision']!=='pendiente'||!hash_equals(ContenidoService::version($row),$version)) {
                throw new DomainException('El reporte ya cambió o fue revisado.',409);
            }
            $pdo->prepare('UPDATE reportes_atencion SET estado_revision=?,revisado_por=?,fecha_revision=UTC_TIMESTAMP() WHERE id=?')->execute([$decision,$actor,$id]);
            if($decision==='aceptado') {
                $pdo->prepare("INSERT INTO historico_atencion (sucursal_id,fecha,hora,personas_esperando,tiempo_espera,fuente,reporte_id,confianza) VALUES (?,?,?,?,?,'DIRI_USUARIOS',?,'baja')")->execute([$row['sucursal_id'],$row['fecha'],$row['hora'],$row['personas_esperando'],$row['tiempo_espera'],$id]);
            }
            (new Auditoria())->record($actor,$id,'reporte_'.$decision,'reportes_atencion');
            $pdo->commit();
        }
        catch(Throwable $e) {
            if($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
