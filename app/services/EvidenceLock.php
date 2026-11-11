<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
class EvidenceLock
{
    /** Evita asociar una fuente mientras su revisión está cambiando. */
    public static function source(array $data): void
    {
        if(!empty($data['fuente_id'])) {
            Database::connection()->prepare('SELECT id FROM fuentes WHERE id=? FOR UPDATE')->execute([$data['fuente_id']]);
        }
    }
}
