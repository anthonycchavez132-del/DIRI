<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Documento
{
    public function all(): array
    {
        return Database::connection()->query('SELECT d.*, (SELECT COUNT(*) FROM tramite_documentos td WHERE td.documento_id=d.id) AS usos
            FROM documentos d ORDER BY d.nombre')->fetchAll();
    }

    public function find(int $id, bool $lock = false): ?array
    {
        $query = Database::connection()->prepare('SELECT * FROM documentos WHERE id=?' . ($lock ? ' FOR UPDATE' : ''));
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function save(array $data, ?int $id): int
    {
        $values = array_map(fn ($key) => $data[$key], ['nombre','descripcion','fuente_id','fecha_verificacion']);
        $pdo = Database::connection();
        if ($id === null) {
            $pdo->prepare('INSERT INTO documentos (nombre,descripcion,fuente_id,fecha_verificacion) VALUES (?,?,?,?)')->execute($values);
            return (int) $pdo->lastInsertId();
        }
        $pdo->prepare('UPDATE documentos SET nombre=?,descripcion=?,fuente_id=?,fecha_verificacion=? WHERE id=?')->execute(array_merge($values, [$id]));
        return $id;
    }
}
