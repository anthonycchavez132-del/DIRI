<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use DomainException;
class PlanAccess
{
    public static function premium(int $userId): bool
    {
        $query=Database::connection()->prepare("SELECT COUNT(*) FROM usuarios u JOIN planes p ON p.id=u.plan_id WHERE u.id=? AND u.estado='activo' AND p.nombre='premium' AND p.estado=1");
        $query->execute([$userId]);
        return (int)$query->fetchColumn()===1;
    }
    public static function requirePremium(int $userId): void
    {
        if(!self::premium($userId)) {
            throw new DomainException('Esta herramienta requiere un plan Premium activo.',403);
        }
    }
}
