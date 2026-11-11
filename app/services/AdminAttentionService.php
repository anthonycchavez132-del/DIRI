<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Models\Auditoria;
use DomainException;
use Throwable;
class AdminAttentionService
{
    public function record(int $actor,array $input): void
    {
        [$data,$errors]=ReportService::validate($input);
        $source=TramiteValidator::id($input['fuente_id']??null);
        if($source===null) {
            $errors['fuente']='Selecciona la fuente del dato administrativo.';
        }
        if($errors!==[]) {
            throw new DomainException(implode(' ',$errors),422);
        }
        $pdo=Database::connection();
        $pdo->beginTransaction();
        try {
            $q=$pdo->prepare("SELECT u.id FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.id=? AND u.estado='activo' AND r.nombre='administrador'");
            $q->execute([$actor]);
            if(!$q->fetch()) {
                throw new DomainException('Acceso denegado.',403);
            }
            $q=$pdo->prepare('SELECT id FROM sucursales WHERE id=? FOR UPDATE');
            $q->execute([$data['sucursal_id']]);
            if(!$q->fetch()) {
                throw new DomainException('Sucursal no encontrada.',404);
            }
            $q=$pdo->prepare('SELECT id FROM fuentes WHERE id=?');
            $q->execute([$source]);
            if(!$q->fetch()) {
                throw new DomainException('Fuente no encontrada.',422);
            }
            $pdo->prepare("INSERT INTO historico_atencion (sucursal_id,fecha,hora,personas_esperando,tiempo_espera,fuente,fuente_id,confianza) VALUES (?,UTC_DATE(),UTC_TIME(),?,?,'ADMINISTRADOR',?,'baja')")->execute([$data['sucursal_id'],$data['personas_esperando'],$data['tiempo_espera'],$source]);
            $pdo->prepare("INSERT INTO estado_atencion (sucursal_id,personas_esperando,tiempo_estimado,nivel_demanda,fuente,fuente_id,confianza,cantidad_observaciones,ultima_actualizacion,valido_hasta) VALUES (?,?,?,?,'ADMINISTRADOR',?,'baja',1,UTC_TIMESTAMP(),UTC_TIMESTAMP()+INTERVAL 30 MINUTE) ON DUPLICATE KEY UPDATE personas_esperando=VALUES(personas_esperando),tiempo_estimado=VALUES(tiempo_estimado),nivel_demanda=VALUES(nivel_demanda),fuente=VALUES(fuente),fuente_id=VALUES(fuente_id),confianza='baja',cantidad_observaciones=1,ultima_actualizacion=VALUES(ultima_actualizacion),valido_hasta=VALUES(valido_hasta),tiempo_minimo=NULL,tiempo_maximo=NULL,ficha_actual=NULL,ficha_atendida=NULL")->execute([$data['sucursal_id'],$data['personas_esperando'],$data['tiempo_espera'],$data['nivel_demanda'],$source]);
            (new Auditoria())->record($actor,$data['sucursal_id'],'actualizar_atencion','sucursales');
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
