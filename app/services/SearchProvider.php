<?php

declare(strict_types=1);
namespace App\Services;
/** Una implementación futura puede interpretar la consulta sin acoplar las vistas. */
interface SearchProvider
{
    public function search(string $query, int $page=1): array;
}
