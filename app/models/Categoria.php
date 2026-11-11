<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Categoria
{
    public function all(): array
    {
        return Database::connection()->query('SELECT id, nombre, estado FROM categorias ORDER BY nombre')->fetchAll();
    }

    public function active(): array
    {
        return Database::connection()->query('SELECT id, nombre FROM categorias WHERE estado = 1 ORDER BY nombre')->fetchAll();
    }
}
