<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Tramite;
use App\Models\Requisito;
use App\Models\Documento;
use App\Models\TramiteDocumento;
use App\Models\Auditoria;
use DomainException;
use Throwable;

class ContenidoService
{
    public static function version(array $row): string
    {
        ksort($row);
        return hash('sha256', json_encode(array_map(fn ($value) => (string) ($value ?? ''), $row), JSON_THROW_ON_ERROR));
    }

    private function check(?array $row, string $version): array
    {
        if ($row === null) {
            throw new DomainException('Registro no encontrado.', 404);
        }
        if (!hash_equals(self::version($row), $version)) {
            throw new DomainException('El contenido cambió.', 409);
        }
        return $row;
    }

    private function changed(array $row, array $data): bool
    {
        foreach ($data as $key => $value) {
            if ((string) ($row[$key] ?? '') !== (string) ($value ?? '')) {
                return true;
            }
        }
        return false;
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

    private function parent(int $id): void
    {
        if ((new Tramite())->find($id, true) === null) {
            throw new DomainException('Trámite no encontrado.', 404);
        }
    }

    private function requireReview(int $id, int $actor): void
    {
        Database::connection()->prepare("UPDATE tramites SET estado=CASE WHEN estado='publicado' THEN 'borrador' ELSE estado END,
            estado_verificacion='pendiente',fecha_verificacion=NULL,revision=revision+1,fecha_actualizacion=UTC_TIMESTAMP() WHERE id=?")->execute([$id]);
        (new Auditoria())->record($actor, $id, 'revisar_contenido', 'tramites');
    }

    public function requisito(int $tramiteId, ?int $id, array $data, string $version, int $actor): int
    {
        return $this->transaction(function () use ($tramiteId, $id, $data, $version, $actor): int {
            EvidenceLock::source($data);
            $this->parent($tramiteId);
            $model = new Requisito();
            if ($id !== null && !$this->changed($this->check($model->find($id, $tramiteId, true), $version), $data)) {
                return $id;
            }
            $savedId = $model->save($data, $tramiteId, $id);
            $this->requireReview($tramiteId, $actor);
            (new Auditoria())->record($actor, $savedId, $id === null ? 'crear_requisito' : 'editar_requisito', 'requisitos', 'Trámite #' . $tramiteId);
            return $savedId;
        });
    }

    public function documento(?int $id, array $data, string $version, int $actor): int
    {
        return $this->transaction(function () use ($id, $data, $version, $actor): int {
            EvidenceLock::source($data);
            $model = new Documento();
            if ($id !== null) {
                if (!$this->changed($this->check($model->find($id, true), $version), $data)) {
                    return $id;
                }
                // Asociar también bloquea primero el documento, evitando cambios a mitad de esta operación.
                $parents = (new TramiteDocumento())->parents($id);
                foreach ($parents as $parent) {
                    $this->parent($parent);
                }
            } else {
                $parents = [];
            }
            $savedId = $model->save($data, $id);
            foreach ($parents as $parent) {
                $this->requireReview($parent, $actor);
            }
            (new Auditoria())->record($actor, $savedId, $id === null ? 'crear_documento' : 'editar_documento', 'documentos');
            return $savedId;
        });
    }

    public function asociacion(int $tramiteId, int $documentId, bool $editing, array $data, string $version, int $actor): int
    {
        return $this->transaction(function () use ($tramiteId, $documentId, $editing, $data, $version, $actor): int {
            EvidenceLock::source($data);
            if ((new Documento())->find($documentId, true) === null) {
                throw new DomainException('Documento no encontrado.', 404);
            }
            $this->parent($tramiteId);
            $model = new TramiteDocumento();
            if ($editing && !$this->changed($this->check($model->find($tramiteId, $documentId, true), $version), $data)) {
                return $tramiteId;
            }
            $model->save($data, $tramiteId, $documentId, $editing);
            $this->requireReview($tramiteId, $actor);
            (new Auditoria())->record($actor, $tramiteId, $editing ? 'editar_condiciones_documento' : 'asociar_documento', 'tramite_documentos', 'Documento #' . $documentId);
            return $tramiteId;
        });
    }
}
