<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Auditoria
{
    public function record(int $actorId, int $entityId, string $action, string $entity = 'tramites', ?string $summary = null): void
    {
        Database::connection()->prepare('INSERT INTO auditoria_administrativa (usuario_id, entidad, entidad_id, accion, resumen)
            VALUES (?, ?, ?, ?, ?)')->execute([$actorId, $entity, $entityId, $action, $summary]);
    }
}
