<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    public function findForLogin(string $email): ?array
    {
        $query = Database::connection()->prepare('SELECT id, password, estado FROM usuarios WHERE email = ? LIMIT 1');
        $query->execute([$email]);
        return $query->fetch() ?: null;
    }

    public function findActive(int $id): ?array
    {
        $query = Database::connection()->prepare("SELECT u.id, u.nombre, u.apellido, u.email, r.nombre AS rol, p.nombre AS plan
            FROM usuarios u JOIN roles r ON r.id = u.rol_id JOIN planes p ON p.id = u.plan_id
            WHERE u.id = ? AND u.estado = 'activo'");
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function create(string $name, string $surname, string $email, string $hash): int
    {
        $pdo = Database::connection();
        // El cliente no puede elegir rol ni plan, aunque agregue campos al formulario.
        $query = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, email, password, rol_id, plan_id)
            SELECT ?, ?, ?, ?, r.id, p.id FROM roles r CROSS JOIN planes p
            WHERE r.nombre = 'usuario' AND p.nombre = 'free' AND p.estado = 1");
        $query->execute([$name, $surname, $email, $hash]);
        if ($query->rowCount() !== 1) {
            throw new \RuntimeException('No están disponibles el rol o el plan inicial.');
        }
        return (int) $pdo->lastInsertId();
    }

    public function updateHash(int $id, string $hash): void
    {
        Database::connection()->prepare('UPDATE usuarios SET password = ? WHERE id = ?')->execute([$hash, $id]);
    }
}
