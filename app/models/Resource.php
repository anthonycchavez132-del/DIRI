<?php

declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
use DomainException;
class Resource
{
    public function canRemove(): bool
    {
        return in_array($this->table,['paso_dependencias','ruta_dependencias','paso_documentos','paso_costos','ruta_tramites','tramite_etiquetas','palabras_clave_tramite','tramite_fuentes'],true);
    }

    public function remove(array $row): void
    {
        if(!$this->canRemove()){throw new DomainException('Este contenido se conserva; utiliza su estado para desactivarlo.',403);}
        $keys=$this->definition['key'];
        $where=implode(' AND ',array_map(fn($field)=>'`'.$field.'`=?',$keys));
        Database::connection()->prepare('DELETE FROM `'.$this->table.'` WHERE '.$where)->execute(array_map(fn($key)=>$row[$key],$keys));
    }

    public string $table;
    public array $definition;
    public function __construct(string $name)
    {
        $resources = require BASE_PATH . '/config/resources.php';
        if (!isset($resources[$name])) {
            throw new DomainException('Recurso no encontrado.',404);
        }
        $this->table = $name;
        $this->definition = $resources[$name];
    }
    private function columns(): string
    {
        return implode(',', array_map(function ($key) {
            if ($key==='tramite_id' && $this->derivedTramite()) {
                return '(SELECT p.tramite_id FROM pasos_tramite p WHERE p.id=`'.$this->table.'`.paso_id) AS tramite_id';
            }
            return '`'.$key.'`';
        },array_unique(array_merge($this->definition['key'],array_keys($this->definition['fields'])))));
    }
    private function derivedTramite(): bool
    {
        return in_array($this->table,['paso_documentos','paso_dependencias','paso_costos'],true);
    }
    public function rows(int $page = 1): array
    {
        $query = Database::connection()->prepare('SELECT '.$this->columns().' FROM `'.$this->table.'` ORDER BY `'.$this->definition['key'][0].'` DESC LIMIT 30 OFFSET ?');
        $query->bindValue(1,($page-1)*30,\PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }
    public function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM `'.$this->table.'`')->fetchColumn();
    }
    public function key(array $row): string
    {
        return base64_encode(json_encode(array_map(fn($key)=>(string)$row[$key],$this->definition['key']),JSON_THROW_ON_ERROR));
    }
    public function find(string $key, bool $lock = false): ?array
    {
        if (strlen($key)>4096) {
            return null;
        }
        $decoded = base64_decode($key,true);
        $values = $decoded === false ? null : json_decode($decoded,true);
        if (!is_array($values) || !array_is_list_compat($values) || count($values)!==count($this->definition['key'])) {
            return null;
        }
        foreach ($values as $value) {
            if (!is_string($value) || strlen($value)>1000) {
                return null;
            }
        }
        $where = implode(' AND ',array_map(fn($field)=>'`'.$field.'`=?',$this->definition['key']));
        $query = Database::connection()->prepare('SELECT '.$this->columns().' FROM `'.$this->table.'` WHERE '.$where.($lock?' FOR UPDATE':''));
        $query->execute($values);
        return $query->fetch() ?: null;
    }
    public function save(array $data, ?array $before): int
    {
        $pdo = Database::connection();
        $fields = array_keys($this->definition['fields']);
        // El formulario conserva el contexto del trámite, pero se obtiene del paso y no se almacena dos veces.
        if ($this->derivedTramite()) { $fields=array_values(array_diff($fields,['tramite_id'])); }
        $values = array_map(fn($key)=>$data[$key],$fields);
        if ($before === null) {
            $sql='INSERT INTO `'.$this->table.'` (`'.implode('`,`',$fields).'`) VALUES ('.implode(',',array_fill(0,count($fields),'?')).')';
        }
        else {
            $sql='UPDATE `'.$this->table.'` SET '.implode(',',array_map(fn($key)=>'`'.$key.'`=?',$fields)).' WHERE '.implode(' AND ',array_map(fn($key)=>'`'.$key.'`=?',$this->definition['key']));
            foreach ($this->definition['key'] as $key) {
                $values[]=$before[$key];
            }
        }
        $pdo->prepare($sql)->execute($values);
        return $before === null ? (int)($pdo->lastInsertId()?:($data[$this->definition['key'][0]]??0)) : (int)($before[$this->definition['key'][0]] ?? 0);
    }
    public function options(): array
    {
        $options=[];
        foreach ($this->definition['fields'] as $field=>$meta) {
            if ($meta['type']==='ref') {
                // Table y label provienen solo de la configuración interna.
                $options[$field]=Database::connection()->query('SELECT id,`'.$meta['label'].'` AS label FROM `'.$meta['table'].'` ORDER BY `'.$meta['label'].'`')->fetchAll();
            }
        }
        return $options;
    }
}
