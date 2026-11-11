<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Requisito
{
    public function forTramite(int $tramiteId): array
    {
        $query = Database::connection()->prepare('SELECT * FROM requisitos WHERE tramite_id=? ORDER BY id');
        $query->execute([$tramiteId]);
        return $query->fetchAll();
    }

    public function find(int $id, int $tramiteId, bool $lock = false): ?array
    {
        $query = Database::connection()->prepare('SELECT * FROM requisitos WHERE id=? AND tramite_id=?' . ($lock ? ' FOR UPDATE' : ''));
        $query->execute([$id,$tramiteId]);
        return $query->fetch() ?: null;
    }

    public function save(array $data, int $tramiteId, ?int $id): int
    {
        $values = array_map(fn ($key) => $data[$key], ['nombre','descripcion','obligatorio','observaciones','fuente_id','estado_verificacion','fecha_verificacion']);
        $pdo = Database::connection();
        if ($id === null) {
            $pdo->prepare('INSERT INTO requisitos (nombre,descripcion,obligatorio,observaciones,fuente_id,estado_verificacion,fecha_verificacion,tramite_id) VALUES (?,?,?,?,?,?,?,?)')
                ->execute(array_merge($values, [$tramiteId]));
            return (int) $pdo->lastInsertId();
        }
        $pdo->prepare('UPDATE requisitos SET nombre=?,descripcion=?,obligatorio=?,observaciones=?,fuente_id=?,estado_verificacion=?,fecha_verificacion=? WHERE id=? AND tramite_id=?')
            ->execute(array_merge($values, [$id,$tramiteId]));
        return $id;
    }
}
