<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
class LibraryService
{
    public function read(int $userId): array
    {
        PlanAccess::requirePremium($userId);
        $pdo=Database::connection();
        $q=$pdo->prepare('SELECT consulta,fecha FROM historial_busquedas WHERE usuario_id=? ORDER BY id DESC LIMIT 100');
        $q->execute([$userId]);
        $history=$q->fetchAll();
        $q=$pdo->prepare("SELECT t.nombre,t.slug FROM favoritos f JOIN tramites t ON t.id=f.tramite_id JOIN categorias c ON c.id=t.categoria_id WHERE f.usuario_id=? AND t.estado='publicado' AND c.estado=1 ORDER BY f.fecha_creacion DESC,t.id DESC LIMIT 100");
        $q->execute([$userId]);
        $favorites=$q->fetchAll();
        $q=$pdo->prepare('SELECT r.id,r.nombre FROM rutas_guardadas g JOIN rutas r ON r.id=g.ruta_id WHERE g.usuario_id=? ORDER BY g.fecha_creacion DESC,r.id DESC LIMIT 100');
        $q->execute([$userId]);
        $routes=array_values(array_filter($q->fetchAll(),fn($row)=>(new RoutePlanner())->route((int)$row['id'])!==null));
        return ['history'=>$history,'favorites'=>$favorites,'routes'=>$routes];
    }
    public function remember(int $userId,string $query): void
    {
        if(!PlanAccess::premium($userId)||trim($query)==='') {
            return;
        }
        if(!mb_check_encoding($query,'UTF-8')||mb_strlen($query)>500) {
            return;
        }
        $pdo=Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('SELECT id FROM usuarios WHERE id=? FOR UPDATE')->execute([$userId]);
            if(!PlanAccess::premium($userId)) {
                $pdo->commit();
                return;
            }
            $q=$pdo->prepare('SELECT consulta,fecha FROM historial_busquedas WHERE usuario_id=? ORDER BY id DESC LIMIT 1');
            $q->execute([$userId]);
            $last=$q->fetch();
            if(!$last||$last['consulta']!==$query||strtotime($last['fecha'].' UTC')<time()-60) {
                $pdo->prepare('INSERT INTO historial_busquedas (usuario_id,consulta) VALUES (?,?)')->execute([$userId,$query]);
            }
            $q=$pdo->prepare('SELECT id FROM historial_busquedas WHERE usuario_id=? ORDER BY id DESC LIMIT 1 OFFSET 99');
            $q->execute([$userId]);
            $cut=$q->fetchColumn();
            if($cut) {
                $pdo->prepare('DELETE FROM historial_busquedas WHERE usuario_id=? AND id<?')->execute([$userId,$cut]);
            }
            $pdo->commit();
        }
        catch(\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    public function clear(int $userId): void
    {
        PlanAccess::requirePremium($userId);
        Database::connection()->prepare('DELETE FROM historial_busquedas WHERE usuario_id=?')->execute([$userId]);
    }
}
