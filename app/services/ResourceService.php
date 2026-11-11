<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Models\Resource;
use App\Models\Auditoria;
use DomainException;
use Throwable;
class ResourceService
{
    /** Quitar relaciones erróneas sin eliminar documentos ni progreso personal. */
    public function remove(Resource $resource,string $key,string $version,int $actor): void
    {
        if(!$resource->canRemove()){throw new DomainException('Relación no eliminable.',403);}
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $query=$pdo->prepare("SELECT u.id FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.id=? AND u.estado='activo' AND r.nombre='administrador'");
            $query->execute([$actor]);if(!$query->fetch()){throw new DomainException('Acceso denegado.',403);}
            $row=$resource->find($key,true);
            if($row===null){throw new DomainException('Relación no encontrada.',404);}
            if(!hash_equals(ContenidoService::version($row),$version)){throw new DomainException('La relación cambió.',409);}
            if(isset($row['ruta_id'])){
                $pdo->prepare('SELECT id FROM rutas WHERE id=? FOR UPDATE')->execute([$row['ruta_id']]);
                $pdo->prepare("UPDATE rutas SET estado=CASE WHEN estado='publicada' THEN 'borrador' ELSE estado END WHERE id=?")->execute([$row['ruta_id']]);
            }elseif(isset($row['tramite_id'])){
                $pdo->prepare('SELECT id FROM tramites WHERE id=? FOR UPDATE')->execute([$row['tramite_id']]);
                $pdo->prepare("UPDATE tramites SET estado=CASE WHEN estado='publicado' THEN 'borrador' ELSE estado END,estado_verificacion='pendiente',fecha_verificacion=NULL,revision=revision+1 WHERE id=?")->execute([$row['tramite_id']]);
            }
            $resource->remove($row);
            (new Auditoria())->record($actor,(int)$row[$resource->definition['key'][0]],'quitar_relacion',$resource->table);
            $pdo->commit();
        }catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}throw $e;}
    }

    public function save(Resource $resource, array $data, string $key, string $version, int $actor): void
    {
        $pdo=Database::connection();
        $pdo->beginTransaction();
        try {
            // El rol actúa como cerrojo común para no desactivar a los dos
            // últimos administradores en solicitudes concurrentes.
            if($resource->table==='usuarios') {
                $pdo->query("SELECT id FROM roles WHERE nombre='administrador' FOR UPDATE")->fetch();
            }
            $authorization=$pdo->prepare("SELECT u.id FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.id=? AND u.estado='activo' AND r.nombre='administrador'");
            $authorization->execute([$actor]);
            if(!$authorization->fetch()) {
                throw new DomainException('Acceso denegado.',403);
            }
            EvidenceLock::source($data);
            $before=$key===''?null:$resource->find($key,true);
            if ($key!=='' && $before===null) {
                throw new DomainException('Registro no encontrado.',404);
            }
            if ($before!==null && !hash_equals(ContenidoService::version($before),$version)) {
                throw new DomainException('El registro cambió.',409);
            }
            $parents=[];
            if($resource->table==='fuentes'&&$before!==null) {
                $query=$pdo->prepare('SELECT id FROM tramites WHERE fuente_id=? UNION SELECT tramite_id FROM requisitos WHERE fuente_id=? UNION SELECT tramite_id FROM costos WHERE fuente_id=? UNION SELECT tramite_id FROM pasos_tramite WHERE fuente_id=? UNION SELECT td.tramite_id FROM tramite_documentos td JOIN documentos d ON d.id=td.documento_id WHERE td.fuente_id=? OR d.fuente_id=? UNION SELECT tramite_id FROM tramite_fuentes WHERE fuente_id=? UNION SELECT tramite_id FROM tramite_sucursales WHERE fuente_id=?');
                $query->execute(array_fill(0,8,$before['id']));
                $parents=array_map('intval',$query->fetchAll(\PDO::FETCH_COLUMN));
            }
            foreach ([$before,$data] as $row) {
                if (!empty($row['tramite_id'])&&!in_array($resource->table,['ruta_tramites','ruta_dependencias'],true)) {
                    $parents[]=(int)$row['tramite_id'];
                }
            }
            $parents=array_values(array_unique($parents));
            sort($parents);
            foreach($parents as $id) {
                $pdo->prepare('SELECT id FROM tramites WHERE id=? FOR UPDATE')->execute([$id]);
            }
            if(isset($data['ruta_id'])) {
                $pdo->prepare('SELECT id FROM rutas WHERE id=? FOR UPDATE')->execute([$data['ruta_id']]);
            }
            if(isset($data['sucursal_id'])) {
                $pdo->prepare('SELECT id FROM sucursales WHERE id=? FOR UPDATE')->execute([$data['sucursal_id']]);
            }
            [$data,$errors]=ResourceValidator::validate($resource,$data,$before);
            if ($errors!==[]) {
                throw new \InvalidArgumentException(implode(' ',$errors));
            }
            if($before!==null) {
                $changed=false;
                foreach($data as $field=>$value) {
                    if((string)($before[$field]??'')!==(string)($value??'')) {
                        $changed=true;
                        break;
                    }
                }
                if(!$changed) {
                    $pdo->commit();
                    return;
                }
            }
            if($resource->table==='usuarios'&&$before!==null) {
                $adminRole=(int)$pdo->query("SELECT id FROM roles WHERE nombre='administrador'")->fetchColumn();
                if((int)$before['rol_id']===$adminRole&&$before['estado']==='activo'&&((int)$data['rol_id']!==$adminRole||$data['estado']!=='activo')) {
                    $q=$pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id=? AND estado='activo' AND id<>?");
                    $q->execute([$adminRole,$before['id']]);
                    if((int)$q->fetchColumn()===0) {
                        throw new \InvalidArgumentException('Debe permanecer al menos un administrador activo.');
                    }
                }
            }
            $id=$resource->save($data,$before);
            if($resource->table==='fuentes'&&$before!==null) {
                foreach(['requisitos','costos'] as $table) {
                    $pdo->prepare("UPDATE $table SET estado_verificacion='pendiente',fecha_verificacion=NULL WHERE fuente_id=?")->execute([$before['id']]);
                }
                $pdo->prepare("UPDATE rutas SET estado=CASE WHEN estado='publicada' THEN 'borrador' ELSE estado END WHERE fuente_id=?")->execute([$before['id']]);
            }
            if(isset($data['ruta_id'])) {
                $pdo->prepare("UPDATE rutas SET estado=CASE WHEN estado='publicada' THEN 'borrador' ELSE estado END WHERE id=?")->execute([$data['ruta_id']]);
            }
            foreach($parents as $parent) {
                $pdo->prepare("UPDATE tramites SET estado=CASE WHEN estado='publicado' THEN 'borrador' ELSE estado END,estado_verificacion='pendiente',fecha_verificacion=NULL,revision=revision+1 WHERE id=?")->execute([$parent]);
            }
            (new Auditoria())->record($actor,$id,$before===null?'crear_recurso':'editar_recurso',$resource->table);
            $pdo->commit();
        }
        catch(Throwable $exception) {
            if($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
