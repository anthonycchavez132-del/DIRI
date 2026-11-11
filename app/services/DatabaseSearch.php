<?php

declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use InvalidArgumentException;
class DatabaseSearch implements SearchProvider
{
    public function search(string $query, int $page=1): array
    {
        $query=trim($query);
        if(!mb_check_encoding($query,'UTF-8')||mb_strlen($query)>500||preg_match('/[\x00-\x1F\x7F]/u',$query)||$page<1||$page>100000) {
            throw new InvalidArgumentException('Escribe una consulta de hasta 500 caracteres.');
        }
        $tokens=preg_split('/[^\p{L}\p{N}]+/u',mb_strtolower($query),-1,PREG_SPLIT_NO_EMPTY);
        $stop=['quiero','necesito','sacar','obtener','realizar','tramite','trámite','mi','mis','el','la','los','las','un','una','de','del','para','en','y','como','cómo'];
        $tokens=array_values(array_unique(array_diff($tokens,$stop)));
        // La vista aplica publicación y categoría activa en todas las búsquedas.
        $where=['1=1'];
        $params=[];
        foreach(array_slice($tokens,0,12) as $token) {
            // ! escapa comodines: %, _ y ! se buscan como texto.
            $like='%'.strtr($token,['!'=>'!!','%'=>'!%','_'=>'!_']).'%';
            $where[]="(t.nombre LIKE ? ESCAPE '!' OR t.descripcion LIKE ? ESCAPE '!' OR t.categoria LIKE ? ESCAPE '!' OR EXISTS(SELECT 1 FROM palabras_clave_tramite p WHERE p.tramite_id=t.id AND p.palabra LIKE ? ESCAPE '!') OR EXISTS(SELECT 1 FROM tramite_etiquetas te JOIN etiquetas e ON e.id=te.etiqueta_id WHERE te.tramite_id=t.id AND e.nombre LIKE ? ESCAPE '!'))";
            array_push($params,$like,$like,$like,$like,$like);
        }
        $from=' FROM vista_tramites_publicados t';
        $filter=' WHERE '.implode(' AND ',$where);
        $pdo=Database::connection();
        $count=$pdo->prepare('SELECT COUNT(*)'.$from.$filter);
        $count->execute($params);
        $total=(int)$count->fetchColumn();
        $phrase=implode(' ',array_slice($tokens,0,12));
        $order='t.nombre,t.id';
        if ($phrase!=='' && mb_strlen($phrase)<=150) {
            // En MariaDB 10.4 se consulta la columna generada por su nombre para usar su índice.
            // DISTINCT conserva un resultado por trámite aunque tenga varios alias equivalentes.
            $from.=' LEFT JOIN (SELECT DISTINCT p.tramite_id FROM palabras_clave c JOIN palabras_clave_tramite p ON p.palabra=c.palabra WHERE c.palabra_normalizada=LOWER(TRIM(?))) exactas ON exactas.tramite_id=t.id';
            array_unshift($params,$phrase);
            $order='(exactas.tramite_id IS NULL),'.$order;
        }
        $statement=$pdo->prepare('SELECT t.id,t.nombre,t.slug,t.descripcion,t.estado_verificacion,t.categoria'.$from.$filter.' ORDER BY '.$order.' LIMIT 12 OFFSET ?');
        foreach($params as $index=>$value) {
            $statement->bindValue($index+1,$value);
        }
        $statement->bindValue(count($params)+1,($page-1)*12,\PDO::PARAM_INT);
        $statement->execute();
        return ['query'=>$query,'page'=>$page,'total'=>$total,'items'=>$statement->fetchAll()];
    }
}
