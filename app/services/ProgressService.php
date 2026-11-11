<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use DomainException;
use Throwable;
class ProgressService
{
    public function read(int $userId,int $tramiteId): array
    {
        PlanAccess::requirePremium($userId);
        $pdo=Database::connection();
        $q=$pdo->prepare('SELECT * FROM progreso_tramites WHERE usuario_id=? AND tramite_id=?');
        $q->execute([$userId,$tramiteId]);
        $progress=$q->fetch()?:null;
        $documents=$steps=[];
        if($progress!==null) {
            foreach(['documentos'=>'documento_id','pasos'=>'paso_id'] as $table=>$column) {
                $q=$pdo->prepare("SELECT $column,completado FROM progreso_$table WHERE progreso_tramite_id=?");
                $q->execute([$progress['id']]);
                $values=array_column($q->fetchAll(),'completado',$column);
                if($table==='documentos'){$documents=$values;}else{$steps=$values;}
            }
        }
        $q=$pdo->prepare('SELECT COUNT(*) FROM favoritos WHERE usuario_id=? AND tramite_id=?');
        $q->execute([$userId,$tramiteId]);
        return ['progress'=>$progress,'documents'=>$documents,'steps'=>$steps,'favorite'=>(int)$q->fetchColumn()===1];
    }
    public function change(int $userId,int $tramiteId,string $action,string $value,?int $itemId=null): void
    {
        $pdo=Database::connection();
        $pdo->beginTransaction();
        try {
            // Serializa acciones de una cuenta y comprueba nuevamente su plan.
            $pdo->prepare('SELECT id FROM usuarios WHERE id=? FOR UPDATE')->execute([$userId]);
            PlanAccess::requirePremium($userId);
            $q=$pdo->prepare("SELECT t.id FROM tramites t JOIN categorias c ON c.id=t.categoria_id WHERE t.id=? AND t.estado='publicado' AND c.estado=1 FOR UPDATE");
            $q->execute([$tramiteId]);
            if(!$q->fetch()) {
                throw new DomainException('Trámite no disponible.',404);
            }
            if($action==='favorito') {
                if(!in_array($value,['0','1'],true)) {
                    throw new DomainException('Valor inválido.',422);
                }
                if($value==='1') {
                    $pdo->prepare('INSERT INTO favoritos (usuario_id,tramite_id) VALUES (?,?) ON DUPLICATE KEY UPDATE usuario_id=VALUES(usuario_id)')->execute([$userId,$tramiteId]);
                }
                else {
                    $pdo->prepare('DELETE FROM favoritos WHERE usuario_id=? AND tramite_id=?')->execute([$userId,$tramiteId]);
                }
            }
            elseif($action==='iniciar') {
                $pdo->prepare('INSERT INTO progreso_tramites (usuario_id,tramite_id) VALUES (?,?) ON DUPLICATE KEY UPDATE usuario_id=VALUES(usuario_id)')->execute([$userId,$tramiteId]);
            }
            else {
                $q=$pdo->prepare('SELECT id FROM progreso_tramites WHERE usuario_id=? AND tramite_id=? FOR UPDATE');
                $q->execute([$userId,$tramiteId]);
                $progressId=$q->fetchColumn();
                if(!$progressId) {
                    throw new DomainException('Inicia primero tu seguimiento.',422);
                }
                if(in_array($action,['documento','paso'],true)) {
                    if(!in_array($value,['0','1'],true)||$itemId===null) {
                        throw new DomainException('Elemento inválido.',422);
                    }
                    $table=$action==='documento'?'tramite_documentos':'pasos_tramite';
                    $column=$action==='documento'?'documento_id':'id';
                    $q=$pdo->prepare("SELECT COUNT(*) FROM $table WHERE tramite_id=? AND $column=?");
                    $q->execute([$tramiteId,$itemId]);
                    if((int)$q->fetchColumn()!==1) {
                        throw new DomainException('Elemento ajeno al trámite.',404);
                    }
                    if($action==='paso') {
                        $sql=$value==='1'?'SELECT COUNT(*) FROM paso_dependencias d LEFT JOIN progreso_pasos p ON p.paso_id=d.paso_previo_id AND p.progreso_tramite_id=? WHERE d.paso_id=? AND COALESCE(p.completado,0)=0':'SELECT COUNT(*) FROM paso_dependencias d JOIN progreso_pasos p ON p.paso_id=d.paso_id AND p.progreso_tramite_id=? WHERE d.paso_previo_id=? AND p.completado=1';
                        $q=$pdo->prepare($sql);
                        $q->execute([$progressId,$itemId]);
                        if((int)$q->fetchColumn()>0) {
                            throw new DomainException('Revisa primero los pasos dependientes.',409);
                        }
                    }
                    $table=$action==='documento'?'progreso_documentos':'progreso_pasos';
                    $column=$action==='documento'?'documento_id':'paso_id';
                    $pdo->prepare("INSERT INTO $table (progreso_tramite_id,$column,completado) VALUES (?,?,?) ON DUPLICATE KEY UPDATE completado=VALUES(completado)")->execute([$progressId,$itemId,$value]);
                    $pdo->prepare("UPDATE progreso_tramites SET estado=CASE WHEN estado='completado' THEN 'en_curso' ELSE estado END,fecha_finalizacion=NULL,fecha_actualizacion=CURRENT_TIMESTAMP WHERE id=?")->execute([$progressId]);
                }
                elseif($action==='estado') {
                    if(!in_array($value,['en_preparacion','en_curso','pausado','completado'],true)) {
                        throw new DomainException('Estado inválido.',422);
                    }
                    if($value==='completado') {
                        $q=$pdo->prepare('SELECT COUNT(*) FROM tramite_documentos d LEFT JOIN progreso_documentos p ON p.documento_id=d.documento_id AND p.progreso_tramite_id=? WHERE d.tramite_id=? AND d.obligatorio=1 AND COALESCE(p.completado,0)=0');
                        $q->execute([$progressId,$tramiteId]);
                        $missing=(int)$q->fetchColumn();
                        $q=$pdo->prepare('SELECT COUNT(*) FROM pasos_tramite d LEFT JOIN progreso_pasos p ON p.paso_id=d.id AND p.progreso_tramite_id=? WHERE d.tramite_id=? AND COALESCE(p.completado,0)=0');
                        $q->execute([$progressId,$tramiteId]);
                        if($missing+(int)$q->fetchColumn()>0) {
                            throw new DomainException('Completa los documentos obligatorios y los pasos antes de terminar.',422);
                        }
                    }
                    $pdo->prepare("UPDATE progreso_tramites SET estado=?,fecha_finalizacion=CASE WHEN ?='completado' THEN CURRENT_TIMESTAMP ELSE NULL END WHERE id=?")->execute([$value,$value,$progressId]);
                }
                else {
                    throw new DomainException('Acción inválida.',422);
                }
            }
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
