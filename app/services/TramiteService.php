<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Tramite;
use App\Models\Auditoria;
use DomainException;
use Throwable;

class TramiteService
{
    /** Huella de los campos editables: detecta cambios incluso dentro del mismo segundo. */
    public static function version(array $row): string
    {
        $values = [];
        foreach (['nombre','slug','descripcion','categoria_id','estado','dificultad','tiempo_estimado','fuente_id','estado_verificacion','fecha_verificacion','fecha_actualizacion','revision'] as $key) {
            $values[] = (string) ($row[$key] ?? '');
        }
        return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
    }

    private function transaction(callable $operation): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $id = $operation();
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function create(array $data, int $actorId): int
    {
        return $this->transaction(function () use ($data, $actorId): int {
            EvidenceLock::source($data);
            [$validated,$errors]=TramiteValidator::validate(array_map(fn($value)=>$value===null?'':(string)$value,$data));
            if($errors!==[]){throw new \InvalidArgumentException(implode(' ',$errors));}
            $id = (new Tramite())->create($validated);
            (new Auditoria())->record($actorId, $id, 'crear_tramite');
            return $id;
        });
    }

    public function change(int $id, array $data, string $version, int $actorId, bool $deactivate = false): int
    {
        return $this->transaction(function () use ($id, $data, $version, $actorId, $deactivate): int {
            if(!$deactivate){EvidenceLock::source($data);}
            $model = new Tramite();
            $current = $model->find($id, true);
            if ($current === null) {
                throw new DomainException('Trámite no encontrado.', 404);
            }
            if (!hash_equals(self::version($current), $version)) {
                throw new DomainException('El trámite cambió desde que abriste el formulario.', 409);
            }
            if ($deactivate) {
                if ($current['estado'] === 'inactivo') {
                    return $id;
                }
                $model->deactivate($id);
            } else {
                // Se comprueba dentro del cerrojo del trámite, por si cambiaron
                // los requisitos o fuentes después de abrir el formulario.
                [$validated,$errors]=TramiteValidator::validate(array_map(fn($value)=>$value===null?'':(string)$value,$data),$id);
                if($errors!==[]){throw new \InvalidArgumentException(implode(' ',$errors));}
                $model->update($id, $validated);
            }
            (new Auditoria())->record($actorId, $id, $deactivate ? 'desactivar_tramite' : 'actualizar_tramite');
            return $id;
        });
    }
}
