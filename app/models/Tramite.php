<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Tramite
{
    public function page(int $page, string $state): array
    {
        $pdo = Database::connection();
        $where = $state === '' ? '' : ' WHERE t.estado = ?';
        $parameters = $state === '' ? [] : [$state];
        $count = $pdo->prepare('SELECT COUNT(*) FROM tramites t' . $where);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $query = $pdo->prepare('SELECT t.id, t.nombre, t.slug, t.estado, t.estado_verificacion, t.fecha_actualizacion, c.nombre AS categoria
            FROM tramites t JOIN categorias c ON c.id = t.categoria_id' . $where . '
            ORDER BY t.fecha_actualizacion DESC, t.id DESC LIMIT ? OFFSET ?');
        $index = 1;
        foreach ($parameters as $parameter) {
            $query->bindValue($index++, $parameter, PDO::PARAM_STR);
        }
        $query->bindValue($index++, 15, PDO::PARAM_INT);
        $query->bindValue($index, ($page - 1) * 15, PDO::PARAM_INT);
        $query->execute();
        return ['items' => $query->fetchAll(), 'total' => $total, 'pages' => max(1, (int) ceil($total / 15))];
    }

    public function find(int $id, bool $lock = false): ?array
    {
        $query = Database::connection()->prepare('SELECT * FROM tramites WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''));
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    private function values(array $data): array
    {
        return array_map(fn ($key) => $data[$key], [
            'nombre', 'slug', 'descripcion', 'categoria_id', 'estado', 'dificultad',
            'tiempo_estimado', 'fuente_id', 'estado_verificacion', 'fecha_verificacion',
        ]);
    }

    public function create(array $data): int
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO tramites (nombre,slug,descripcion,categoria_id,estado,dificultad,tiempo_estimado,fuente_id,estado_verificacion,fecha_verificacion)
            VALUES (?,?,?,?,?,?,?,?,?,?)')->execute($this->values($data));
        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        Database::connection()->prepare('UPDATE tramites SET nombre=?,slug=?,descripcion=?,categoria_id=?,estado=?,dificultad=?,tiempo_estimado=?,fuente_id=?,estado_verificacion=?,fecha_verificacion=?,revision=revision+1 WHERE id=?')
            ->execute(array_merge($this->values($data), [$id]));
    }

    public function deactivate(int $id): void
    {
        Database::connection()->prepare("UPDATE tramites SET estado = 'inactivo', revision=revision+1 WHERE id = ?")->execute([$id]);
    }
}
