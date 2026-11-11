<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Dashboard
{
    /** Panel de solo lectura, siempre limitado al usuario de la sesión. */
    public function forUser(int $userId): array
    {
        if(!\App\Services\PlanAccess::premium($userId)){return ['counts'=>['favoritos'=>0,'pendientes'=>0,'completados'=>0],'recent'=>[],'favorites'=>[],'progress'=>[]];}
        $pdo = Database::connection();
        $counts = $pdo->prepare("SELECT
            (SELECT COUNT(*) FROM favoritos f JOIN tramites t ON t.id = f.tramite_id JOIN categorias c ON c.id=t.categoria_id
                WHERE f.usuario_id = ? AND t.estado = 'publicado' AND c.estado=1) AS favoritos,
            (SELECT COUNT(*) FROM progreso_tramites p JOIN tramites t ON t.id = p.tramite_id JOIN categorias c ON c.id=t.categoria_id
                WHERE p.usuario_id = ? AND t.estado = 'publicado' AND c.estado=1 AND p.estado <> 'completado') AS pendientes,
            (SELECT COUNT(*) FROM progreso_tramites p JOIN tramites t ON t.id = p.tramite_id JOIN categorias c ON c.id=t.categoria_id
                WHERE p.usuario_id = ? AND t.estado = 'publicado' AND c.estado=1 AND p.estado = 'completado') AS completados");
        $counts->execute([$userId, $userId, $userId]);

        $recent = $pdo->prepare("SELECT t.nombre,t.slug, c.nombre AS categoria, p.estado, p.fecha_actualizacion
            FROM progreso_tramites p JOIN tramites t ON t.id = p.tramite_id
            JOIN categorias c ON c.id = t.categoria_id
            WHERE p.usuario_id = ? AND t.estado = 'publicado' AND c.estado=1
            ORDER BY p.fecha_actualizacion DESC, p.id DESC LIMIT 5");
        $recent->execute([$userId]);

        $favorites = $pdo->prepare("SELECT t.nombre,t.slug, c.nombre AS categoria
            FROM favoritos f JOIN tramites t ON t.id = f.tramite_id
            JOIN categorias c ON c.id = t.categoria_id
            WHERE f.usuario_id = ? AND t.estado = 'publicado' AND c.estado=1
            ORDER BY f.fecha_creacion DESC, t.id DESC LIMIT 5");
        $favorites->execute([$userId]);

        $progress = $pdo->prepare("SELECT t.nombre,t.slug, p.estado, p.fecha_actualizacion
            FROM progreso_tramites p JOIN tramites t ON t.id = p.tramite_id
            JOIN categorias c ON c.id=t.categoria_id
            WHERE p.usuario_id = ? AND t.estado = 'publicado' AND c.estado=1 AND p.estado <> 'completado'
            ORDER BY p.fecha_actualizacion DESC, p.id DESC LIMIT 5");
        $progress->execute([$userId]);

        return [
            'counts' => array_map('intval', $counts->fetch()),
            'recent' => $recent->fetchAll(),
            'favorites' => $favorites->fetchAll(),
            'progress' => $progress->fetchAll(),
        ];
    }
}
