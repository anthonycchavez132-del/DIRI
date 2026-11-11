<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use DomainException;
/** Orden topológico: primero los previos; el orden editorial desempata. */
class RoutePlanner
{
    public static function order(array $nodes, array $edges, string $idColumn='id'): array
    {
        $remaining=[];
        $result=[];
        $done=[];
        foreach($nodes as $node) {
            $remaining[(int)$node[$idColumn]]=$node;
        }
        foreach($edges as [$node,$previous]) {
            if(!isset($remaining[(int)$node],$remaining[(int)$previous])) {
                throw new DomainException('Dependencia fuera de la ruta.',422);
            }
        }
        while($remaining!==[]) {
            $advanced=false;
            foreach($remaining as $id=>$node) {
                foreach($edges as [$target,$previous]) {
                    if((int)$target===$id&&!isset($done[(int)$previous])) {
                        continue 2;
                    }
                }
                $result[]=$node;
                $done[$id]=true;
                unset($remaining[$id]);
                $advanced=true;
                break;
            }
            if(!$advanced) {
                throw new DomainException('Las dependencias forman un ciclo.',422);
            }
        }
        return $result;
    }
    public function steps(int $tramiteId): array
    {
        $pdo=Database::connection();
        $query=$pdo->prepare('SELECT p.*,i.nombre AS institucion FROM pasos_tramite p LEFT JOIN instituciones i ON i.id=p.institucion_id WHERE p.tramite_id=? ORDER BY numero_paso,id');
        $query->execute([$tramiteId]);
        $nodes=$query->fetchAll();
        $query=$pdo->prepare('SELECT d.paso_id,d.paso_previo_id FROM paso_dependencias d JOIN pasos_tramite p ON p.id=d.paso_id WHERE p.tramite_id=?');
        $query->execute([$tramiteId]);
        return self::order($nodes,$query->fetchAll(\PDO::FETCH_NUM));
    }
    public function route(int $id, bool $public=true): ?array
    {
        $pdo=Database::connection();
        $query=$pdo->prepare('SELECT * FROM rutas WHERE id=?');
        $query->execute([$id]);
        $route=$query->fetch();
        if(!$route||($public&&$route['estado']!=='publicada')) {
            return null;
        }
        $query=$pdo->prepare('SELECT t.id,t.nombre,t.slug,t.estado,c.estado AS categoria_activa,rt.numero_orden,rt.observaciones FROM ruta_tramites rt JOIN tramites t ON t.id=rt.tramite_id JOIN categorias c ON c.id=t.categoria_id WHERE rt.ruta_id=? ORDER BY rt.numero_orden,t.id');
        $query->execute([$id]);
        $nodes=$query->fetchAll();
        if($public&&($nodes===[]||array_filter($nodes,fn($row)=>$row['estado']!=='publicado'||(int)$row['categoria_activa']!==1))) {
            return null;
        }
        $query=$pdo->prepare('SELECT tramite_id,tramite_previo_id FROM ruta_dependencias WHERE ruta_id=?');
        $query->execute([$id]);
        $route['tramites']=self::order($nodes,$query->fetchAll(\PDO::FETCH_NUM));
        return $route;
    }
}
