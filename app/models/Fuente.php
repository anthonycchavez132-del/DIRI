<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Fuente
{
    public function all(): array
    {
        return Database::connection()->query('SELECT id, nombre, tipo FROM fuentes ORDER BY nombre')->fetchAll();
    }
}
